<?php

namespace App\Http\Controllers\Store;

use App\Bundle;
use App\Carer;
use App\Centre;
use App\Family;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppendBundleRequest;
use App\Http\Requests\StorePickupBundleRequest;
use App\Http\Requests\StoreTransitionBundleRequest;
use App\Registration;
use App\Services\TransitionProcessor\TransitionProcessor;
use App\Trader;
use App\Voucher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use RuntimeException;
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
            'pool_size' => $registration->centre?->getPoolSize() ?? 0,
        ]);
    }

    /**
     * Append a single voucher, range of vouchers, or a quantity drawn from the pool
     * to the current bundle.
     */
    public function addVouchersToCurrentBundle(
        StoreAppendBundleRequest $request,
        Registration $registration,
    ): RedirectResponse {
        $managerRoute = $this->managerRoute($registration);

        if ($request->filled('voucher-quantity')) {
            try {
                $voucherCodes = $registration->centre->claimFromPool(
                    (int)$request->input('voucher-quantity'),
                )
                    ->pluck('code')
                    ->all();
            } catch (Throwable $e) {
                return $this->redirectAfterRequest(
                    ['pool' => $e->getMessage()],
                    $managerRoute,
                    $managerRoute,
                );
            }
        } else {
            $voucherCodes = Voucher::generateCodeRange(
                $request->input('start'),
                $request->input('end'),
            );
        }

        $errors = (count($voucherCodes) <= config('arc.bundle_max_voucher_append'))
            ? $registration->currentBundle()->addVouchers($voucherCodes)
            : ['append' => count($voucherCodes)];

        return $this->redirectAfterRequest($errors, $managerRoute, $managerRoute);
    }

    /**
     * Disburse the current bundle if collection details are present.
     */
    public function pickup(StorePickupBundleRequest $request, Registration $registration): RedirectResponse
    {
        $managerRoute = $this->managerRoute($registration);
        $bundle = $registration->currentBundle();

        $errors = $this->attemptDisbursal(
            $bundle,
            // Should be here, due to form request validation
            $request->only(['collected_at', 'collected_by', 'collected_on'])
        );

        return $this->redirectAfterRequest($errors, route('store.registration.index'), $managerRoute, $bundle);
    }

    /**
     * Disburse the current bundle and trigger a collection transition.
     */
    public function collectBundle(StoreTransitionBundleRequest $request, Registration $registration): RedirectResponse
    {
        $managerRoute = $this->managerRoute($registration);
        $bundle = $registration->currentBundle();
        $errors = [];

        try {
            // As this is an important change, we need to rollback, rather than plough on.
            DB::transaction(function () use ($request, $bundle, &$errors) {
                $errors = $this->attemptDisbursal(
                    $bundle,
                    // Should be here, due to form request validation
                    $request->only(['collected_at', 'collected_by', 'collected_on'])
                );

                if (!empty($errors)) {
                    throw new RuntimeException('disbursal errors');
                }

                $trader = Trader::findOrFail($request->input('trader_id'));
                $processor = new TransitionProcessor($trader, 'collect');
                $response = $processor->handle($bundle->vouchers());

                if ($response->hasFailures()) {
                    $errors['transition'] = $response->getFailureCodes();
                    throw new RuntimeException('transition errors');
                }
            });
        } catch (Throwable $e) {
            Log::error(sprintf(
                'Rollback Bad transaction for %s@%s by user %s because of: %e',
                self::class,
                __FUNCTION__,
                Auth::id() ?? 'unauthenticated',
                $e->getMessage()
            ));
        }

        return $this->redirectAfterRequest($errors, route('store.registration.index'), $managerRoute, $bundle);
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
     * Guard against an empty bundle then delegate to disburseBundle.
     * Returns an error map on failure, or an empty array on success.
     */
    private function attemptDisbursal(Bundle $bundle, array $inputs): array
    {
        if ($bundle->vouchers->isEmpty()) {
            return ['empty' => true];
        }

        return $this->disburseBundle($bundle, $inputs) ? [] : ['transaction' => true];
    }

    /**
     * Attempt to mark a bundle as disbursed.
     */
    private function disburseBundle(Bundle $bundle, array $inputs): bool
    {
        try {
            $bundle->disbursed_at = Carbon::createFromFormat('Y-m-d', $inputs['collected_on'])
                ->startOfDay();

            $bundle->collectingCarer()->associate(Carer::findOrFail($inputs['collected_by']));
            $bundle->disbursingCentre()->associate(Centre::findOrFail($inputs['collected_at']));
            $bundle->disbursingUser()->associate(Auth::user());
            $bundle->save();
        } catch (Throwable $e) {
            Log::error(sprintf(
                'Bad transaction for %s@%s by service user %s',
                self::class,
                __FUNCTION__,
                Auth::id() ?? 'unauthenticated',
            ));
            Log::error($e->getTraceAsString());
            return false;
        }
        return true;
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

        if (!empty($relevant)) {
            $messages[] = new HtmlString(
                "These vouchers are currently allocated to a different $familyAlias. "
                . "Click on the voucher number to view the other $familyAlias's record: "
                . implode(', ', $relevant),
            );
        }

        if (!empty($inaccessible)) {
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
