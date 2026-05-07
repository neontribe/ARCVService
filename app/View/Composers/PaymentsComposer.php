<?php

namespace App\View\Composers;

use App\StateToken;
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
        $checkPayments = StateToken::checkIfOutstandingPayments();
        $view->with('hasPayments', $checkPayments);
    }
}
