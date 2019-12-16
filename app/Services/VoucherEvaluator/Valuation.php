<?php

namespace App\Services\VoucherEvaluator;

use ArrayObject;

/**
 * Class Valuation
 * Decorated ArrayObject, tweaked so we can access core array as properties
 * Cheeky way to throw in an Array and then auto-magically get/set members as properties.
 *
 * Holds the results of an Evaluator's walk around the subject and it's related models
 *
 * @package App\Services\VoucherEvaluator
 */
class Valuation extends ArrayObject
{
    /**
     * Valuation constructor
     * @param array $input
     * @param int $flags overridden to change default behaviour from "0"
     * @param string $iterator_class
     */
    public function __construct($input = array(), $flags = parent::ARRAY_AS_PROPS, $iterator_class = "ArrayIterator")
    {
        // Set some expected values;
        $expected = [
            'valuations' => $input['valuations'] ?? [],
            'entitlement' => $input['entitlement'] ?? 0,
            'evaluee' => $input['evaluee'] ?? null,
            'eligibility' => $input['eligibility'] ?? false,
            'notices' => $input['notices'] ?? [],
            'credits' => $input['credits'] ?? [],
        ];
        parent::__construct($expected, $flags, $iterator_class);
    }

    /**
     * Checks if this valuation contains a satisfied evaluations
     *
     * @param string $class
     * @param bool $state
     * @param bool $deep
     * @return bool
     */
    public function hasSatisfiedEvaluation(string $class, bool $state = true, bool $deep = false)
    {
        // make a list of the evaluations
        if (!$deep) {
            // just what this valuation holds
            $evaluations = array_merge($this->notices, $this->credits);
        } else {
            // all the way down the chain of valuations
            $evaluations = array_merge($this->flat('notices'), $this->flat('credits'));
        }

        // resolve to boolean
        return (
            !empty(
                // return those evaluations that are present.
                array_filter(
                    $evaluations,
                    function ($evaluation) use ($class) {
                        // has the classname?
                        return (get_class($evaluation) === $class);
                    }
                )
            )
        );
    }

    /**
     * Processes notices to make a discrete list
     *
     * @return array
     */
    public function getNoticeReasons()
    {
        $notice_reasons = [];

        $notices = $this->flat("notices");

        // get distinct reasons and frequency.
        $reason_count = array_count_values(array_column($notices, 'reason'));

        foreach ($reason_count as $reason => $count) {
            /*
             * Each element used by Blade in the format
             */
            $notice_reasons[] = [
                "entity" => explode('|', $reason)[0],
                "reason" => explode('|', $reason)[1],
                "count" => $count,
            ];
        }
        return $notice_reasons;
    }

    /**
     * Processes the credits to make a discrete list
     *
     * @return array
     */
    public function getCreditReasons()
    {
        $credit_reasons = [];

        $credits = $this->flat("credits");

        // get distinct reasons and frequency.
        $reason_count = array_count_values(array_column($credits, 'reason'));

        foreach ($reason_count as $reason => $count) {
            // Filter the raw credits by reason
            // create an array of the 'vouchers' column for that
            // sum that column.
            $reason_vouchers = array_sum(
                array_column(
                    array_filter(
                        $credits,
                        function ($credit) use ($reason) {
                            return $credit['reason'] == $reason;
                        }
                    ),
                    'value'
                )
            );

            /*
             * Each element used by Blade in the format
             * $voucher_sum for $reason_count $entity $reason
             */
            $credit_reasons[] = [
                "entity" => explode('|', $reason)[0],
                "reason" => explode('|', $reason)[1],
                "count" => $count,
                "reason_vouchers" => $reason_vouchers,
            ];
        }
        return $credit_reasons;
    }

    /**
     * Gets the Entitlement
     * @return integer;
     */
    public function getEntitlement()
    {
        return $this->flat('entitlement');
    }

    /**
     * Grabs and flattens a specified attribute from Valuations.
     * @param $attribute
     * @return mixed
     */
    public function flat($attribute)
    {
        // take this attribute
        if (property_exists($this, $attribute)) {
            $flatAttrib = $this[$attribute];

            if (is_array($flatAttrib)) {
                // Merge on it's descendents
                /** @var Valuation $valuation */
                foreach ($this->valuations as $valuation) {
                    array_merge($flatAttrib, $valuation->flat($attribute));
                }
            } else if (is_integer($flatAttrib)) {
                // Merge on it's descendents
                foreach ($this->valuations as $valuation) {
                    /** @var Valuation $valuation */
                    $flatAttrib += $valuation->flat($attribute);
                }
            }
            return $flatAttrib;
        } else {
            // Something went wrong
            return null;
        }
    }
}