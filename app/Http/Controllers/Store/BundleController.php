<?php

namespace App\Http\Controllers\Store;

use App\Bundle;
use App\Carer;
use App\Centre;
use App\Family;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppendBundleRequest;
use App\Http\Requests\StoreUpdateBundleRequest;
use App\Registration;
use App\Voucher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

class BundleController extends Controller
{
    /**
     * Return the voucher-manager page for a given registration.
     */
    public function create(Registration $registration): View
    {
        $user = Auth::user();
        $bundle = $registration->currentBundle()->vouchers;
        $valuation = $registration->getValuation();
        $carers = $registration->family->carers->all();

        $lastCollectedBundle = $registration->bundles()
            ->whereNotNull('disbursed_at')
            ->whereDate('disbursed_at', '<=', Carbon::today()->toDateString())
            ->orderByDesc('disbursed_at')
            ->first();

        $lastCollection = $lastCollectedBundle?->disbursed_at?->format('l jS \of F Y');

        return view('store.manage_vouchers', [
            'user_name' => $user->name,
            'centre_name' => $user->centre?->name,
            'registration' => $registration,
            'lastCollection' => $lastCollection,
            'children' => $registration->family->children,
            'centre' => $user->centre,
            'carers' => $carers,
            'pri_carer' => array_shift($carers),
            'vouchers' => $bundle->sortBy('code'),
            'vouchers_amount' => $bundle->count(),
            'entitlement' => $valuation->getEntitlement(),
            'noticeReasons' => $valuation->getNoticeReasons(),
            'programme' => $user->centre->sponsor->programme,
        ]);
    }

    /**
     * Append a single voucher or range of vouchers to the current bundle.
     */
    public function addVouchersToCurrentBundle(
        StoreAppendBundleRequest $request,
        Registration $registration,
    ): RedirectResponse {
        $voucherCodes = Voucher::generateCodeRange(
            $request->input('start'),
            $request->input('end'),
        );

        $managerRoute = $this->managerRoute($registration);

        $errors = count($voucherCodes) <= config('arc.bundle_max_voucher_append')
            ? $registration->currentBundle()->addVouchers($voucherCodes)
            : ['append' => count($voucherCodes)];

        return $this->redirectAfterRequest($errors, $managerRoute, $managerRoute);
    }

    /**
     * Update (sync) vouchers on the current bundle, and optionally disburse it.
     */
    public function update(StoreUpdateBundleRequest $request, Registration $registration): RedirectResponse
    {
        $managerRoute = $this->managerRoute($registration);
        $successRoute = $managerRoute;
        $errors = [];

        /** @var Bundle $bundle */
        $bundle = $registration->currentBundle();

        // --- Sync voucher codes if supplied ---
        if ($request->exists('vouchers')) {
            $rawCodes = array_filter(
                $request->input('vouchers', []),
                static function (mixed $v): bool {
                    return !empty($v);
                },
            );

            $voucherCodes = $rawCodes !== []
                ? Voucher::cleanCodes(array_values($rawCodes))
                : [];

            $errors = array_merge_recursive($errors, $bundle->syncVouchers($voucherCodes));
        }

        // --- Disburse if collection details are present ---
        if ($request->filled(['collected_at', 'collected_by', 'collected_on'])) {
            if ($bundle->vouchers->isEmpty()) {
                $errors['empty'] = true;
            } else {
                $errors = array_merge_recursive(
                    $errors,
                    $this->disburseBundle($bundle, $request->only(['collected_at', 'collected_by', 'collected_on'])),
                );

                if (empty($errors)) {
                    $successRoute = route('store.registration.index');
                }
            }
        }

        return $this->redirectAfterRequest($errors, $successRoute, $managerRoute, $bundle);
    }

    /**
     * Remove all vouchers from the current bundle.
     */
    public function removeAllVouchersFromCurrentBundle(Registration $registration): RedirectResponse
    {
        $bundle = $registration->currentBundle();
        $errors = $bundle->alterVouchers($bundle->vouchers()->get());
        $route = $this->managerRoute($registration);

        return $this->redirectAfterRequest($errors, $route, $route);
    }

    /**
     * Remove a single voucher from the current bundle.
     */
    public function removeVoucherFromCurrentBundle(
        Registration $registration,
        Voucher $voucher,
    ): RedirectResponse {
        $bundle = $registration->currentBundle();
        $route = $this->managerRoute($registration);

        $errors = $voucher->bundle_id === $bundle->id
            ? $bundle->alterVouchers(collect([$voucher]))
            : ['foreign' => [$voucher->code]];

        return $this->redirectAfterRequest($errors, $route, $route);
    }

    /**
     * Attempt to mark a bundle as disbursed.
     */
    private function disburseBundle(Bundle $bundle, array $inputs): array
    {
        try {
            $bundle->disbursed_at = Carbon::createFromFormat('Y-m-d', $inputs['collected_on'])
                ->startOfDay();

            $bundle->collectingCarer()->associate(Carer::findOrFail($inputs['collected_by']));
            $bundle->disbursingCentre()->associate(Centre::findOrFail($inputs['collected_at']));
            $bundle->disbursingUser()->associate(Auth::user());
            $bundle->save();

            return [];
        } catch (Throwable $e) {
            Log::error(sprintf(
                'Bad transaction for %s@%s by service user %s',
                self::class,
                __FUNCTION__,
                Auth::id() ?? 'unauthenticated',
            ));
            Log::error($e->getTraceAsString());

            return ['transaction' => true];
        }
    }

    /**
     * Named route to the voucher-manager for a registration.
     */
    private function managerRoute(Registration $registration): string
    {
        return route('store.registration.voucher-manager', ['registration' => $registration->id]);
    }

    /**
     * Build flash messages from an error map and redirect accordingly.
     */
    public function redirectAfterRequest(
        array $errors,
        string $successRoute,
        string $failRoute,
        ?Bundle $bundle = null,
    ): RedirectResponse {
        if (!empty($errors)) {
            return redirect($failRoute)
                ->withInput()
                ->with('error_messages', $this->buildErrorMessages($errors));
        }

        $message = $bundle instanceof Bundle
            ? $this->buildSuccessMessage($bundle)
            : 'Vouchers updated';

        return redirect($successRoute)->with('message', $message);
    }

    /**
     * Map the error array to human-readable message strings / HtmlString objects.
     */
    private function buildErrorMessages(array $errors): array
    {
        $messages = [];

        foreach ($errors as $type => $values) {
            $codeStrings = implode(', ', (array)$values);
            $messages[] = match ($type) {
                'transaction' => 'Database transaction problem',
                'empty' => 'Action denied on empty bundle',
                'append' => 'Failed adding more than ' . config('arc.bundle_max_voucher_append') . ' vouchers',
                'transition' => 'Voucher state change problem with: ' . $codeStrings,
                'codes' => 'These codes are invalid: ' . $codeStrings,
                'disbursed' => 'These vouchers have been given out: ' . $codeStrings,
                'used' => 'These vouchers have already been used: ' . $codeStrings,
                'foreign' => 'These vouchers do not belong to this bundle: ' . $codeStrings,
                'bundled' => $this->buildBundledMessages((array)$values),
                default => 'There was an unknown error',
            };
        }

        return Arr::flatten($messages);
    }

    /**
     * Build one or two messages for vouchers already bundled elsewhere,
     * partitioned by whether the current user can access the other registration.
     */
    private function buildBundledMessages(array $vouchers): array
    {
        $user = Auth::user();
        $programme = $user->centre->sponsor->programme;
        $familyAlias = Family::getAlias($programme);
        $relevant = [];
        $inaccessible = [];

        foreach ($vouchers as $voucher) {
            $registration = $voucher->bundle->registration;

            if ($user->isRelevantCentre($registration->centre)) {
                $relevant[] = '<a href="' . e($this->managerRoute($registration)) . '">' . e($voucher->code) . '</a>';
            } else {
                $inaccessible[] = $voucher->code;
            }
        }

        $messages = [];

        if ($relevant !== []) {
            $messages[] = new HtmlString(
                "These vouchers are currently allocated to a different $familyAlias. "
                . "Click on the voucher number to view the other $familyAlias's record: "
                . implode(', ', $relevant),
            );
        }

        if ($inaccessible !== []) {
            $messages[] = "These vouchers are allocated to a different $familyAlias in a centre you can't access: "
                . implode(', ', $inaccessible);
        }

        return $messages;
    }

    /**
     * Build the success message shown after a bundle is disbursed.
     */
    private function buildSuccessMessage(Bundle $bundle): string
    {
        $count = $bundle->vouchers->count();
        $fullFamily = $bundle->registration()->withFullFamily()->first();
        $name = $fullFamily->family->pri_carer;

        return sprintf(
            'You have just marked %d %s as collected by %s',
            $count,
            Str::plural('voucher', $count),
            $name,
        );
    }
}
