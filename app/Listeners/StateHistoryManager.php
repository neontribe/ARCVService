<?php
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 25/04/17
 * Time: 13:37
 */

namespace App\Listeners;

use SM\Event\TransitionEvent;
use Log;

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
        $sm = $event->getStateMachine();
        $model = $sm->getObject();

        Log::info(class_basename(auth()->user()));
        $model->history()->create([
            "transition" => $event->getTransition(),
            "from" => $event->getState(), // what the state was before.
            "to" => $sm->getState(),
            "user_id" => auth()->id(),
            "user_type" => class_basename(auth()->user()),
            "source" => "",
        ]);

        $model->save();
    }
}
