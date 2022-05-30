<?php

namespace App\Services\ViewComponents;

use Illuminate\View\View;
use Log;

class LinkButton
{

    protected $variable;

    /**
     * Create a new profile composer.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Bind data to the view.
     *
     * @param View $view
     * @return void
     */
    public function compose(View $view)
    {
        log::info(json_encode($view->getData()));
    }
}