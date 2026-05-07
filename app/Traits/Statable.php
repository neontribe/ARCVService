<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
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
     * @throws SMException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
        return $this->history()->latest('id')->first();
    }

    /**
     * Getter/Setter for the new state by transition
     */
    public function state(string $transition = null): bool|string
    {
        return (is_null($transition))
            ? $this->getStateMachine()->getState()
            : $this->applyTransition($transition);
    }

    /**
     * Do the transition
     */
    public function applyTransition(string $transition): bool
    {
        return $this->getStateMachine()->apply($transition);
    }

    /**
     * Checks if a transition is "allowed" by the FSM graph
     * @throws SMException
     */
    public function transitionAllowed(string $transition): bool
    {
        return $this->getStateMachine()->can($transition);
    }

    /**
     * Gets a collection of Models representing the history.
     */
    public function history(): HasMany
    {
        return $this->hasMany(self::HISTORY_MODEL);
    }

    /**
     * Creates a transitionDef object
     */
    public static function createTransitionDef(string $fromState, string $transitionName): ?object
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
}
