<?php
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 25/04/17
 * Time: 13:37
 */

namespace App\Listeners;

use SM\Event\TransitionEvent;

class StateHistoryManager
{
    public function testTransition(TransitionEvent $event)
    {
    }

    public function preTransition(TransitionEvent $event)
    {
    }

    /**
     * Fires on postTransition Events to save the model and it's history.
     * The model is carried in the TransitionEvent.
     *
     * @param TransitionEvent $event
     */
    public function postTransition(TransitionEvent $event)
    {
    }
}
