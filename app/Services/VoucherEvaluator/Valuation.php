<?php

namespace App\Services\VoucherEvaluator;

class Valuation
{
    /** @var array $valuation */
    public $valuation = [];

    /**
     * Valuation constructor.
     * @param array $valuation
     */
    public function __construct($valuation = [])
    {
        $this->valuation = $valuation;
    }

    /**
     * Processes notices to make a discrete list
     *
     * @return array
     */
    public function getNoticeReasons()
    {
        $notice_reasons = [];
        $notices = $this->valuation["notices"];

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
        $credits = $this->valuation["credits"];

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
     * Gets the the entitlement, specifically
     * @return int
     */
    public function getEntitlement()
    {
        return $this->valuation['entitlement'];
    }
}