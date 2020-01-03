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
            'evaluee' => $input['evaluee'] ?? null,
            'notices' => $input['notices'] ?? [],
            'credits' => $input['credits'] ?? [],
            'disqualifiers' => $input['disqualifiers'] ?? [],
        ];
        parent::__construct($expected, $flags, $iterator_class);
    }

    /**
     * Processes notices to make a discrete list
     *
     * @return array
     */
    public function getNoticeReasons()
    {
        $notice_reasons = [];

        // get all notices
        $notices = array_merge($this->flat("notices"), $this->flat('disqualifications'));

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

        // return eligible credits
        $credits =  $this->flat("credits", true);

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
     * Gets the Entitlement total, by counting eligible credits
     *
     * @return integer
     */
    public function getEntitlement()
    {
        // Get only eligible credits from this and relation valuations.
        $credits = $this->flat("credits", true);
        // return summed value
        return array_sum(array_column($credits, 'value'));
    }

    /**
     * Gets the Evaluee's eligibility by checking no disqualifications
     * @return boolean
     */
    public function getEligibility()
    {
        // If we have no disqualifications, we are eligible
        return empty($this->disqualifiers);
    }

    /**
     * Grabs and flattens a specified attribute from Valuations.
     * @param $attribute
     * @param bool $onlyEligible
     * @return mixed|null
     */
    public function flat($attribute, bool $onlyEligible = false)
    {
        // Check this attribute
        if (property_exists($this, $attribute) &&
            // If this valuation isn't eligible
            $this->getEligibility() === $onlyEligible
        ) {
            $flatAttrib = $this[$attribute];

            if (is_array($flatAttrib)) {
                // Merge on it's descendents
                /** @var Valuation $valuation */
                foreach ($this->valuations as $valuation) {
                    $flatAttrib = array_merge($flatAttrib, $valuation->flat($attribute, $onlyEligible));
                }
            }
            return $flatAttrib;
        }
        // Something went wrong
        return [];
    }
}