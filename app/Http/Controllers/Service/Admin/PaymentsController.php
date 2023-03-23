<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\StateToken;
use App\Trader;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentsController extends Controller
{
    /**
     * List Payments
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $pendingPaymentData = $this::getPaymentsPast7Days('payment_pending',Carbon::now()->subDays(7));
        $reimbursedPaymentData = $this::getPaymentsPast7Days('reimbursed',Carbon::now()->subDays(7));
        return view('service.payments.index',['pending'=>$pendingPaymentData, 'reimbursed'=>$reimbursedPaymentData]);
    }

    public static function getPaymentsPast7Days($currentState,$fromDate)
    { //TODO first just pending then add past 7 days when working okay?

        $pending = DB::select(DB::raw("select *,
(select count(*) -- this gets a count of all the vouchers in the payment request 
				from voucher_states as vs
                left join vouchers v on vs.voucher_id = v.id
                where v.currentstate = ? and vsstid = vs.state_token_id
                and vs.state_token_id is not null) as total
,
(select count(*) -- this splits the count off all vouchers into the vouchers per voucher area (voucher sponsor as opposed to market sponsor)
				from voucher_states as vs
                left join vouchers v on vs.voucher_id = v.id
                where v.currentstate = ? and vsstid = vs.state_token_id and v.sponsor_id = vsponsid
                and vs.state_token_id is not null) as byarea
 from -- adding state token
    (select * from	-- adding voucher states
		(select * from -- adding market area
			(select v.id as vid, v.trader_id as tid, t.name as tname, t.market_id as tmid, v.currentstate as vstate, v.sponsor_id as vsponsid, s.name as vsponname from vouchers v
			left join traders t on v.trader_id = t.id
			left join sponsors s on v.sponsor_id = s.id
			where v.currentstate = ?
			and v.updated_at >= ?
            -- gets all the vouchers with payment-pending, plus the area the voucher is from via sponsor and then trader who has scanned the voucher
			) as pending
		left join
			(select vs.voucher_id as vsvid, vs.id as vsid, vs.state_token_id as vsstid, vs.user_id, u.name as uname 
				from voucher_states as vs
				left join users u on vs.user_id = u.id
                -- gets the vouchers states for the voucher, as we need this to get both the user that requested payment and the state token to enable pyament
			) as pwithusers
		on pending.vid = pwithusers.vsvid
        ) as pusers
        left join 
		(select m.id as mid, m.sponsor_id as msid, m.name as mname, mspon.name as msponname
		from markets m
        left join sponsors mspon on m.sponsor_id = mspon.id
        -- gets the area for the market, as we need this separately to the area that the voucher was issued, to be able to distinguish SP (special program) vouchers
		) as mspons
        on mspons.mid = pusers.tmid
	) as allp     
		left join
	(select st.id as stid, st.uuid as stuuid from state_tokens st
    -- gets the state token information as we need the uuid to enable payment
	) as sts
on allp.vsstid = sts.stid
where stid is not null;
"), array($currentState,$currentState,$currentState,$fromDate)

        );
        //TODO suspect this needs some error handling
        $lists = collect($pending);

        $payments = $lists->sortBy(function ($list){
            return
                $list->tname . '#' .
                $list->mname . "#" .
                $list->msponname . "#" .
                $list->uname . "#" .
                $list->vsponname . "#" .
                $list->total . "#" .
                $list->byarea;

        });

        $paymentData = [];

        foreach ($payments as $payment) {

            // grab any existing id in the array
            $currentUuid = $paymentData[$payment->stuuid] ?? [];

            // overwrite with things it probably already has...
            if (empty($currentUuid)) {
                $currentUuid["traderName"] = $payment->tname;
                $currentUuid["marketName"] = $payment->mname;
                $currentUuid["area"] = $payment->msponname;
                $currentUuid["requestedBy"] = $payment->uname;
                $currentUuid["vouchersTotal"] = $payment->total;
                $currentUuid["voucherAreas"] = [];
            }

            $currentUuid["voucherAreas"][$payment->vsponname] = $payment->byarea;

            // update paymentData;
            $paymentData[$payment->stuuid] = $currentUuid;
        }

        return $paymentData;

    }

    /** Get a specific payment request by link
     * @param $paymentUuid
     * @return mixed
     */
    public function show($paymentUuid)
    {
        // Initialise
        $vouchers = [];
        $trader = "trader";
        $number_to_pay = 0;

        // Find the StateToken of a given uuid
        $state_token = StateToken::where('uuid', $paymentUuid)->first();
        if ($state_token !== null) {

            // Get the VoucherStates with this StateToken
            $voucher_states = $state_token
                ->voucherStates()
                ->get();

            // Get the voucher codes of states TODO - better
            foreach ($voucher_states as $voucher_state) {
                $vouchers[] = $voucher_state
                    ->voucher()
                    ->first();
            }

            // Count the payable vouchers
            $number_to_pay = collect($vouchers)
                ->where('currentstate', 'payment_pending')
                ->count();

            // Get the trader's name
            if(!empty($vouchers)) {
                $trader = Trader::find($vouchers[0]->trader_id)->name;
            }
        }

        return view('service.payments.paymentRequest', [
            'state_token' => $state_token,
            'vouchers' => $vouchers,
            'trader' => $trader,
            'number_to_pay' => $number_to_pay,
        ]);
    }

    /** Pay a specific payment request by link
     * @param $paymentUuid
     * @return mixed
     */
    public function update($paymentUuid)
    {
        // Initialise
        $vouchers = [];

        // Find the StateToken of a given uuid
        $state_token = StateToken::where('uuid', $paymentUuid)->first();
        if ($state_token !== null) {

            // Get the VoucherStates with this StateToken
            $voucher_states = $state_token
                ->voucherStates()
                ->get();

            // Get the voucher codes of states TODO - better
            foreach ($voucher_states as $voucher_state) {
                $voucher = $voucher_state
                    ->voucher()
                    ->first();

                $vouchers[] = $voucher;
            }

            // Transition the vouchers
            foreach ($vouchers as $v) {
                if ($v->transitionAllowed('payout')) {
                    $v->applyTransition('payout');
                }
            }
        }

        return redirect()->route('admin.payments.index')->with('notification','Vouchers Paid!');

    }
}
