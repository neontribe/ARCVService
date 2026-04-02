<?php

namespace App\View\Composers;

use App\Http\Controllers\Service\Admin\PaymentsController;
use Illuminate\View\View;

class PaymentsComposer
{
    /**
     * Bind data to the view.
     *
     * @param View $view
     * @return void
     */
    public function compose(View $view): void
    {
        $checkPayments = PaymentsController::checkIfOutstandingPayments();
        $view->with('hasPayments', $checkPayments);
    }
}
