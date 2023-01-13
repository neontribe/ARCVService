<?php
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 25/04/17
 * Time: 12:42
 */

namespace App\Traits;

use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SM\Factory\FactoryInterface;
use SM\SMException;
use SM\StateMachine\StateMachine;
use SM\StateMachine\StateMachineInterface;

/**
 * Class Statable
 * @package App\Traits
 */
trait Statable
{
    /**
     * @var StateMachine $stateMachine
     */
    protected $stateMachine;

    /**
     * gets the FSM associated with the Stateable model.
     *
     * @return StateMachine|StateMachineInterface
     */
    public function getStateMachine()
    {
        if (!$this->stateMachine) {
            $this->stateMachine = app(FactoryInterface::class)->get($this, self::SM_CONFIG);
        }
        return $this->stateMachine;
    }

    /**
     * gets the prior state associated with the Stateable model.
     *
     * @return self::HISTORY_MODEL
     */
    public function getPriorState()
    {
        return $this->history()->get("*")->last();
    }

    /**
     * Getter/Setter for the new state by transition
     *
     * @param string $transition
     * @return string State
     */
    public function state($transition = null)
    {
        if ($transition) {
            return $this->applyTransition($transition);
        } else {
            return $this->getStateMachine()->getState();
        }
    }

    /**
     * @param $transition
     * @return bool
     * @throws SMException
     */
    public function applyTransition($transition)
    {
        $sm = $this->getStateMachine();
        // get the current state and set it to "from"
        $from = $sm->getState();
        // try to transition - might throw an SMException and abort
        $transitioned = $sm->apply($transition);

        if ($transitioned) {
            // if it worked save all the stuff
            $to = $sm->getState();
            $this->postTransition($from, $to, $transition);
        }

        // return
        return $transition;
    }

    /**
     * Checks if a transition is "allowed" by the FSM graph
     *
     * @param string $transition
     * @return bool
     */
    public function transitionAllowed($transition)
    {
        return $this->getStateMachine()->can($transition);
    }

    /**
     * Gets a collection of Models representing the history.
     *
     * @return HasMany
     */
    public function history()
    {
        return $this->hasMany(self::HISTORY_MODEL);
    }

    /**
     * Creates a transitionDef object
     *
     * @param $fromState
     * @param $transitionName
     * @return object
     */
    public static function createTransitionDef($fromState, $transitionName)
    {
        // Set a transition details, because we can't pull the protected StateMachine config.
        $transition = config('state-machine.' . self::SM_CONFIG . '.transitions.' . $transitionName) ?? null;

        if ($transition && in_array($fromState, $transition["from"])) {
            $transitionDef['to'] = $transition['to'];
            $transitionDef['name'] = $transitionName;
            $transitionDef['from'] = $fromState;
            // Send it back to the user
            return (object) $transitionDef;
        }
        return null;
    }


    /**
     * @param string $from
     * @param string $to
     * @param string $transition
     * @return void
     * @throws Exception
     */
    public function postTransition($from, $to, $transition)
    {
        $sm = $this->getStateMachine();
        $model = $sm->getObject();

        // actually saves the model
        if ($transition === "confirm") {
            if (!$model->update(['currentstate' => $to])) {
                throw new Exception("failed to update a voucher");
            }
        } else {
            $model->save();
        }

        // We need to collect the user_type (basically the classname)
        // as we now have several with conflicting ids.
        // Will permit accurate tidying up late.
        $user_type = class_basename(auth()->user());
        $this->history()->create([
            "transition" => $transition,
            "from" => $from, // what the state was before.
            "to" => $to, // what it is now
            "user_id" => auth()->id(), // the user ID
            "user_type" => $user_type, // the type of user (we now have many)
            "source" => "",
        ]);

    }
}
