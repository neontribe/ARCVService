<?php

namespace App\Views\Composers;

use App\Http\Controllers\Service\Admin\PaymentsController;
use Carbon\Carbon;
use Illuminate\View\View;

class PaymentsComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {

        $checkPayments = PaymentsController::getPaymentsPast7Days('payment_pending',Carbon::now()->subDays(7));

        $countPayments = count($checkPayments);

        $view->with('hasPayments', $countPayments);
    }
}