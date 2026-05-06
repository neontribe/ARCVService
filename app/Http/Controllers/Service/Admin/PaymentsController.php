<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Services\TransitionProcessor\TransitionProcessor;
use App\StateToken;
use App\Voucher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use JsonException;

class PaymentsController extends Controller
{
    /**
     * Lists the payments paid and pending
     */
    public function index(): Factory|View|Application
    {
        $pending = StateToken::pending()
            ->withinPaymentWindow()
            ->withPaymentRelations()
            ->orderByDesc('created_at')
            ->get();

        $reimbursed = StateToken::reimbursed()
            ->withinPaymentWindow()
            ->withPaymentRelations()
            ->orderByDesc('created_at')
            ->get();

        return view('service.payments.index', [
            'pending' => self::makePaymentDataStructure($pending),
            'reimbursed' => self::makePaymentDataStructure($reimbursed),
        ]);
    }

    /**
     * Constructs the payment data structure for the payments blade.
     * @throws JsonException
     */
    public static function makePaymentDataStructure(Collection $tokens): array
    {
        $results = [];

        foreach ($tokens as $stateToken) {
            $voucherStates = $stateToken->voucherStates;

            $firstTrader = $voucherStates->first()?->voucher?->trader;
            if ($firstTrader === null || empty($firstTrader->name)) {
                Log::warning(sprintf(
                    'Skipping token %s — missing trader on first voucher state',
                    $stateToken->uuid
                ));
                continue;
            }

            // A trader without a market is a data integrity issue.
            // Skip rather than throw — the admin should still see other tokens.
            if ($firstTrader->market === null) {
                Log::error(sprintf(
                    'Skipping token %s — trader %d has no associated market',
                    $stateToken->uuid,
                    $firstTrader->id
                ));
                continue;
            }

            $currentTokenResults = [
                'requestedBy' => $stateToken->user?->name ?? 'System',
                'vouchersTotal' => $voucherStates->count(),
                'traderName' => $firstTrader->name,
                'marketName' => $firstTrader->market->name,
                'area' => $firstTrader->market->sponsor?->name ?? '',
                'voucherAreas' => $voucherStates
                    ->countBy(function ($vs) {
                        return $vs->voucher->sponsor->name;
                    })
                    ->all(),
            ];

            $requiredKeys = ['requestedBy', 'vouchersTotal', 'traderName', 'marketName', 'area', 'voucherAreas'];
            if (
                collect($requiredKeys)->contains(function ($k) use ($currentTokenResults) {
                    return empty($currentTokenResults[$k]);
                })
            ) {
                Log::error(sprintf(
                    'Incomplete payment data for token %s — %s',
                    $stateToken->uuid,
                    json_encode($currentTokenResults, JSON_THROW_ON_ERROR)
                ));
                continue;
            }

            $results[$stateToken->uuid] = $currentTokenResults;
        }

        return $results;
    }

    /**
     * Get a specific payment request by link.
     *
     * Passes state_token = null to the view when the UUID is not found so the
     * view can render an inline error message. A redirect was previously
     * considered but PaymentsPageTest establishes that the correct UX is to
     * stay on the paymentRequest page with an explanatory message — not to
     * bounce the admin to the index.
     */
    public function show(string $paymentUuid): Factory|View
    {
        $stateToken = StateToken::with([
            'voucherStates.voucher.trader',
            'voucherStates.voucher.sponsor',
        ])
            ->where('uuid', $paymentUuid)
            ->first();

        $vouchers = $stateToken?->voucherStates
            ->map(function ($vs) {
                return $vs->voucher;
            })
            ->filter()
            ?? collect();

        return view('service.payments.paymentRequest', [
            'state_token' => $stateToken,
            'vouchers' => $vouchers,
            'trader' => $vouchers->first()?->trader?->name ?? 'Unknown trader',
            'number_to_pay' => $vouchers->where('currentstate', 'payment_pending')->count(),
        ]);
    }

    /**
     * Pay a specific payment request by link.
     *
     * trader: null is intentional — payout is admin-driven and handlePayout
     * preserves the voucher's existing trader_id unchanged. See TransitionProcessor.
     */
    public function update(Request $request, string $paymentUuid): RedirectResponse
    {
        $stateToken = StateToken::where('uuid', $paymentUuid)->firstOrFail();

        $query = Voucher::whereHas(
            'history',
            static function ($q) use ($stateToken) {
                return $q->where('state_token_id', $stateToken->id);
            }
        );

        $processor = new TransitionProcessor(
            trader: null,
            transition: 'payout',
            sendPaymentEmail: false
        );

        $response = $processor->handle($query);

        if (!$response->hasFailures()) {
            $stateToken->admin_user_id = Auth::id();
            $stateToken->save();
            return redirect()->route('admin.payments.index')->with('notification', 'Vouchers Paid!');
        }

        return redirect()->route('admin.payments.index')->withErrors($response->toArray());
    }
}
