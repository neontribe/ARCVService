<?php

namespace App\Console\Commands;

use App\Bundle;
use App\Carer;
use App\Centre;
use App\CentreUser;
use App\Child;
use App\Family;
use App\Note;
use App\Registration;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class RetireCentre extends Command
{
    protected $signature = 'arc:retireCentre
        {centre : The ID of the centre to retire}
        {--force : Execute without confirmation}
        {--remove-registrations : Remove registrations and bundles}
        {--remove-families : Remove families, carers, children and notes; implies --remove-registrations}
    ';

    protected $description = 'Retires a centre: dissociates users, frees vouchers from undisbursed bundles, marks families as left, and soft-deletes the centre.';

    /**
     * @throws Throwable
     */
    public function handle(): int
    {
        $centre = Centre::find($this->argument('centre'));

        if (!$centre) {
            $this->error("Centre {$this->argument('centre')} not found.");
            return 1;
        }

        // Resolve implied options
        $removeRegistrations = $this->option('remove-registrations')
            || $this->option('remove-families');
        $removeFamilies = $this->option('remove-families');

        // Summarise what will happen before asking for confirmation
        $this->warn("Retiring centre: [{$centre->id}] {$centre->name}");
        $this->line(' ● Dissociate centre users; soft-delete those with no remaining centre');
        $this->line(' ● Mark families as left');
        $this->line(' ● Release vouchers from undisbursed bundles');
        if ($removeRegistrations) {
            $this->line(' ● Delete bundles and registrations');
        }
        if ($removeFamilies) {
            $this->line(' ● Delete families, carers, children and notes');
        }
        $this->line(' ● Soft-delete the centre');

        if (!$this->option('force') && !$this->confirm('Proceed?')) {
            $this->info('Aborted.');
            return 0;
        }

        try {
            DB::transaction(function () use ($centre, $removeRegistrations, $removeFamilies) {
                // Resolve affected families before any deletions — getAffectedFamilyIds
                // queries via registrations, which are removed by removeRegistrationsAndBundles.
                // Resolving early ensures both markFamiliesAsLeft and removeFamilies
                // operate on the same consistent set.
                $affectedFamilyIds = $this->getAffectedFamilyIds($centre);

                $this->retireCentreUsers($centre);
                $this->markFamiliesAsLeft($centre, $affectedFamilyIds);
                $this->freeVouchersFromUndisbursedBundles($centre);

                if ($removeRegistrations) {
                    $this->removeRegistrationsAndBundles($centre);
                }

                if ($removeFamilies) {
                    $this->removeFamilies($centre, $affectedFamilyIds);
                }

                $centre->delete();
                $this->info("Centre [{$centre->id}] {$centre->name} retired successfully.");
            });
        } catch (Throwable $e) {
            $this->error('Retirement failed and was rolled back: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Dissociate all centre users from the retiring centre.
     * - Users with no remaining centre association are soft-deleted.
     * - Users with remaining centres have their home centre set to the
     *   first available if the retiring centre was their home.
     */
    protected function retireCentreUsers(Centre $centre): void
    {
        $centre->centreUsers->each(function (CentreUser $centreUser) use ($centre) {
            $remainingCentres = $centreUser->centres()
                ->where('centres.id', '!=', $centre->id)
                ->get();

            // Detach before acting so pivot state is accurate
            $centreUser->centres()->detach($centre->id);

            if ($remainingCentres->isEmpty()) {
                $this->line("  Soft-deleting user: {$centreUser->name} (no remaining centres)");
                $centreUser->delete();
                return;
            }

            // If no remaining centre is flagged as home, promote the first one
            $hasHome = $centreUser->centres()->wherePivot('homeCentre', true)->exists();
            if (!$hasHome) {
                $centreUser->centres()->updateExistingPivot(
                    $remainingCentres->first()->id,
                    ['homeCentre' => true]
                );
                $this->line("  Reassigned home centre for user: {$centreUser->name}");
            }
        });
    }

    /**
     * Mark all families registered at this centre as having left.
     * Families are preserved — only their leaving fields are set.
     * Accepts pre-resolved $familyIds to avoid re-querying after registrations
     * have been deleted.
     */
    protected function markFamiliesAsLeft(Centre $centre, Collection $familyIds): void
    {
        $affected = Family::whereIn('id', $familyIds)
            ->update([
                'leaving_on' => Carbon::now(),
                'leaving_reason' => 'centre_retired',
            ]);

        $this->line("  Marked {$affected} family/families as left.");
    }

    /**
     * Nullify bundle_id on vouchers belonging to undisbursed bundles for this
     * centre's registrations, preserving voucher and voucher_state history.
     */
    protected function freeVouchersFromUndisbursedBundles(Centre $centre): void
    {
        $undisbursedBundleIds = Bundle::whereHas('registration', function ($q) use ($centre) {
            $q->where('centre_id', $centre->id);
        })
            ->whereNull('disbursed_at')
            ->pluck('id');

        $affected = Voucher::whereIn('bundle_id', $undisbursedBundleIds)
            ->update(['bundle_id' => null]);

        $this->line("  Freed {$affected} voucher(s) from undisbursed bundles.");
    }

    /**
     * Delete all bundles then registrations for this centre.
     * Vouchers must be freed from undisbursed bundles before this runs
     * to avoid FK violations; freeVouchersFromUndisbursedBundles() is
     * always called first when this method is reached.
     *
     * Disbursed bundle vouchers have their bundle_id nullified here too,
     * to preserve the full voucher history before bundle rows are removed.
     */
    protected function removeRegistrationsAndBundles(Centre $centre): void
    {
        $registrationIds = Registration::where('centre_id', $centre->id)->pluck('id');

        $bundleIds = Bundle::whereIn('registration_id', $registrationIds)->pluck('id');

        // Nullify remaining voucher bundle links (disbursed bundles)
        Voucher::whereIn('bundle_id', $bundleIds)->update(['bundle_id' => null]);

        Bundle::whereIn('id', $bundleIds)->delete();
        $this->line("  Deleted " . $bundleIds->count() . " bundle(s).");

        Registration::whereIn('id', $registrationIds)->delete();
        $this->line("  Deleted " . $registrationIds->count() . " registration(s).");
    }

    /**
     * Remove families and all their direct dependents for this centre.
     * Intended to be callable independently if needed.
     * Assumes registrations have already been removed.
     *
     * Accepts optional pre-resolved $familyIds — when called from handle() the IDs
     * are resolved before registrations are deleted, so the whereHas query in
     * getAffectedFamilyIds still has data to work with. When called standalone,
     * $familyIds is omitted and resolved here directly.
     */
    public function removeFamilies(Centre $centre, ?Collection $familyIds = null): void
    {
        $familyIds ??= $this->getAffectedFamilyIds($centre);

        $notesDeleted = Note::whereIn('family_id', $familyIds)->delete();
        $this->line("  Deleted {$notesDeleted} note(s).");

        $carerIds = Carer::whereIn('family_id', $familyIds)->pluck('id');

        // blind_indexes is a polymorphic index table with no FK constraint — it will
        // not cascade when carers are deleted, so must be cleaned up explicitly.
        // getMorphClass() respects any morph map defined in the application rather
        // than assuming the fully-qualified class name.
        $blindIndexesDeleted = DB::table('blind_indexes')
            ->where('indexable_type', (new Carer)->getMorphClass())
            ->whereIn('indexable_id', $carerIds)
            ->delete();
        $this->line("  Deleted {$blindIndexesDeleted} blind index(es) for carers.");

        $carersDeleted = Carer::whereIn('id', $carerIds)->delete();
        $this->line("  Deleted {$carersDeleted} carer(s).");

        $childrenDeleted = Child::whereIn('family_id', $familyIds)->delete();
        $this->line("  Deleted {$childrenDeleted} child(ren).");

        $familiesDeleted = Family::whereIn('id', $familyIds)->delete();
        $this->line("  Deleted {$familiesDeleted} family/families.");
    }

    /**
     * Returns IDs of families that should be affected by this centre's retirement.
     *
     * A family is affected if:
     *   - it has a registration at this centre, AND
     *   - it has no registration at any other non-retired centre.
     *
     * This prevents marking or removing families who have an active relationship
     * elsewhere, while correctly capturing families whose only remaining
     * registrations are at centres also being retired.
     */
    protected function getAffectedFamilyIds(Centre $centre): Collection
    {
        return Family::whereHas('registrations', static function ($q) use ($centre) {
            $q->where('centre_id', $centre->id);
        })->whereDoesntHave('registrations', function ($q) use ($centre) {
            $q->where('centre_id', '!=', $centre->id)
                ->whereHas('centre', function ($q) {
                    $q->whereNull('deleted_at');
                });
        })->pluck('id');
    }
}
