<?php
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 25/04/17
 * Time: 12:42
 */

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use SM\Factory\FactoryInterface;
use SM\StateMachine\StateMachine;

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
     * @return StateMachine|\SM\StateMachine\StateMachineInterface
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
        return $this->history()->get(null)->last();
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
     * @param string $transition
     * @return bool
     */
    public function applyTransition($transition)
    {
        return $this->getStateMachine()->apply($transition);
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
     * @return \Illuminate\Database\Eloquent\Collection
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
        if ($transition = config('state-machine.' . self::SM_CONFIG . '.transitions.' . $transitionName)
            ?? null
        ) {
            $transitionDef['to'] = $transition['to'];
            $transitionDef['name'] = $transitionName;
            $transitionDef['from'] = $fromState;

            return (object) $transitionDef;
        }
        return null;
    }
}
