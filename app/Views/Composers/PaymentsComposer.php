<?php

namespace App\Views\Composers;

use App\Http\Controllers\Service\Admin\PaymentsController;
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

        $checkPayments = PaymentsController::checkIfOutstandingPayments();

//        $countPayments = count($checkPayments);

        $view->with('hasPayments', $checkPayments);
    }
}