<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewVoucherRequest;
use App\Sponsor;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VouchersController extends Controller
{
    /**
     * Display a listing of Vouchers.
     *
     * @return Factory|View
     */
    public function index()
    {
        $vouchers = Voucher::orderBy('id', 'desc')->paginate(50);
        return view('service.vouchers.index', compact('vouchers'));
    }

    /**
     * Show the form for creating new Vouchers.
     *
     * @return Factory|View
     */
    public function create()
    {
        // A range of numbers from first to last.
        // Last can be empty. Then only one is created.
        $sponsors = Sponsor::all();
        return view('service.vouchers.create', compact('sponsors'));
    }

    /**
     * Store Voucher range.
     *
     * @param AdminNewVoucherRequest $request
     * @return RedirectResponse
     */
    public function storeBatch(AdminNewVoucherRequest $request)
    {
        $codes = range($request->input('start'), $request->input('end'));

        $sponsor = Sponsor::findOrFail($request->input('sponsor_id'));

        $new_vouchers = [];

        $now_time = Carbon::now();
        foreach ($codes as $c) {
            $v = new Voucher();
            $v->code = $sponsor->shortcode . $c;
            $v->sponsor_id = $sponsor->id;
            $v->currentstate = 'requested';
            $v->created_at = $now_time;
            $v->updated_at = $now_time;
            $new_vouchers[] = $v->attributesToArray();
        }
        // batch insert raw vouchers.
        // Todo : there's NO "this voucher already exists" checking!!
        Voucher::insert($new_vouchers);

        Voucher::where('created_at', '=', $now_time)
            ->where('updated_at', '=', $now_time)
            ->where('currentstate', '=', 'requested')
            ->chunk(1000, function ($vouchers) {
                foreach ($vouchers as $voucher) {
                    $voucher->applyTransition('order');
                    $voucher->applyTransition('print');
                    // printed vouchers should now be redeemable.
                }
            })
        ;

        // batch progress


        $notification_msg = trans('service.messages.vouchers_create_success', [
            'shortcode' => $sponsor->shortcode,
            'start' => $request['start'],
            'end' => $request['end'],
        ]);

        return redirect()
            ->route('admin.vouchers.index')
            ->with('notification', $notification_msg)
            ;
    }
}
