<?php

namespace App\Services\TransitionProcessor;

use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;
use InvalidArgumentException;

class TransitionResponse
{
    private MessageBag $buckets;

    /**
     * Voucher IDs confirmed for payment - IDs only.
     */
    private array $vouchersForPayment = [];

    public const BUCKETS = [
        'success_add',
        'success_reject',
        'own_duplicate',
        'other_duplicate',
        'invalid',
        'failed_reject',
        'undelivered',
    ];

    public function __construct()
    {
        $this->buckets = new MessageBag();
    }

    /**
     * Adds a single voucher code to the named bucket.
     * Called per-voucher by TransitionProcessor handlers.
     */
    public function addCode(string $bucket, string $code): void
    {
        if (!in_array($bucket, self::BUCKETS, true)) {
            throw new InvalidArgumentException("Unknown response bucket: [$bucket]");
        }
        $this->buckets->add($bucket, $code);
    }

    /**
     * Batch-adds invalid codes. Called by callers that hold user-submitted
     * code strings (VoucherController, ProcessTransitionJob) after handle() returns.
     * Callers that build their own query (CLI commands, scoped jobs) never call this.
     */
    public function addInvalid(array $codes): void
    {
        $this->buckets->merge(['invalid' => $codes]);
    }

    public function recordPayment(int $voucherId): void
    {
        $this->vouchersForPayment[] = $voucherId;
    }

    public function getVouchersForPayment(): array
    {
        return $this->vouchersForPayment;
    }

    public function hasPayments(): bool
    {
        return !empty($this->vouchersForPayment);
    }

    /**
     * Returns true if any failure bucket is non-empty.
     */
    public function hasFailures(): bool
    {
        return (bool) Arr::first(
            Arr::except($this->buckets->toArray(), 'success_add'),
            static function ($codes): bool {
                return !empty($codes);
            }
        );
    }

    /**
     * Returns all codes from every failure bucket, flattened.
     * Arr::flatten on an empty result naturally returns [].
     */
    public function getFailureCodes(): array
    {
        return Arr::flatten(
            Arr::except($this->buckets->toArray(), 'success_add')
        );
    }

    /**
     * Delegates to MessageBag::toArray() for logging or serialisation.
     */
    public function toArray(): array
    {
        return $this->buckets->toArray();
    }

    // -------------------------------------------------------------------------
    // Response message
    // -------------------------------------------------------------------------

    /**
     * Constructs a human-readable API response message from the accumulated
     * result buckets. Single-voucher submissions receive a specific message;
     * batches receive a summary. Payment confirmations short-circuit to their
     * own message regardless of individual voucher outcomes.
     */
    public function constructResponseMessage(): array
    {
        if ($this->hasPayments()) {
            return ['message' => trans('api.messages.voucher_payment_requested')];
        }

        // MessageBag::count() totals every message across all keys.
        $total_submitted = $this->buckets->count();

        if ($total_submitted === 1) {
            // Find the single bucket that holds the one code.
            // If two buckets each had one entry, total_submitted would be 2
            // and this branch would not be reached.
            $error_type = array_key_first(
                Arr::where($this->buckets->toArray(), static function ($codes): bool {
                    return count($codes) === 1;
                })
            ) ?? '';

            return match ($error_type) {
                'success_add' => [
                    'message' => trans('api.messages.voucher_success_add'),
                ],
                'success_reject' => [
                    'message' => trans('api.messages.voucher_success_reject'),
                ],
                'own_duplicate' => [
                    'warning' => trans('api.errors.voucher_own_dupe', [
                        'code' => $this->buckets->first('own_duplicate'),
                    ]),
                ],
                'other_duplicate' => [
                    'warning' => trans('api.errors.voucher_other_dupe', [
                        'code' => $this->buckets->first('other_duplicate'),
                    ]),
                ],
                'failed_reject' => [
                    'warning' => trans('api.errors.voucher_failed_reject', [
                        'code' => $this->buckets->first('failed_reject'),
                    ]),
                ],
                'undelivered' => [
                    'warning' => trans('api.errors.voucher_unavailable', [
                        'code' => $this->buckets->first('undelivered'),
                    ]),
                ],
                default => [
                    'error' => trans('api.errors.voucher_unavailable'),
                ],
            };
        }

        return [
            'message' => trans('api.messages.batch_voucher_submit', [
                'success_amount'   => count($this->buckets->get('success_add')),
                'duplicate_amount' => count($this->buckets->get('own_duplicate'))
                    + count($this->buckets->get('other_duplicate')),
                'invalid_amount'   => count($this->buckets->get('invalid'))
                    + count($this->buckets->get('undelivered')),
            ]),
        ];
    }
}
