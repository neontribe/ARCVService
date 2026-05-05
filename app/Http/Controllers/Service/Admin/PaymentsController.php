<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Services\TransitionProcessor\TransitionProcessor;
use App\StateToken;
use App\Trader;
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

            $currentTokenResults = [
                'requestedBy' => $stateToken->user?->name ?? 'System',
                'vouchersTotal' => $voucherStates->count(),
                'traderName' => $firstTrader->name,
                'marketName' => $firstTrader->market->name,
                'area' => $firstTrader->market->sponsor->name,
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
     * Get a specific payment request by link
     */
    public function show(string $paymentUuid): Factory|View
    {
        $stateToken = StateToken::with([
            'voucherStates.voucher.trader',
            'voucherStates.voucher.sponsor',
        ])
            ->where('uuid', $paymentUuid)
            ->firstOrFail();

        $vouchers = $stateToken->voucherStates
            ->map(function ($vs) {
                return $vs->voucher;
            })
            ->filter();

        return view('service.payments.paymentRequest', [
            'state_token' => $stateToken,
            'vouchers' => $vouchers,
            'trader' => $vouchers->first()?->trader?->name ?? 'Unknown trader',
            'number_to_pay' => $vouchers->where('currentstate', 'payment_pending')->count(),
        ]);
    }

    /**
     * Pay a specific payment request by link
     */
    public function update(Request $request, string $paymentUuid): RedirectResponse
    {
        $stateToken = StateToken::where('uuid', $paymentUuid)->firstOrFail();

        $query = Voucher::whereHas(
            'voucherStates',
            static function ($q) use ($stateToken) {
                return $q->where('state_token_id', $stateToken->id);
            }
        );

        $processor = new TransitionProcessor(
            // not entirely relevant, we'll not be changing the trader.
            trader: Trader::find($query->first()->trader_id),
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
