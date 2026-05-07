<?php

namespace Tests\Unit\Services\TransitionProcessor;

use App\Services\TransitionProcessor\TransitionResponse;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Unit tests for TransitionResponse.
 *
 * No database access is required; Tests\TestCase is extended only for the
 * application container so that trans() resolves correctly via the translator
 * service. Fake translation lines are added in setUp so that message content
 * assertions are deterministic and independent of the real lang files.
 */
class TransitionResponseTest extends TestCase
{
    private TransitionResponse $response;

    protected function setUp(): void
    {
        parent::setUp();

        $this->response = new TransitionResponse();

        // Stub every translation string consumed by constructResponseMessage()
        // so assertions on message content are predictable without real lang files.
        app('translator')->addLines([
            'api.messages.voucher_payment_requested' => 'payment_requested',
            'api.messages.voucher_success_add' => 'success_add',
            'api.messages.voucher_success_reject' => 'success_reject',
            'api.errors.voucher_own_dupe' => 'own_dupe :code',
            'api.errors.voucher_other_dupe' => 'other_dupe :code',
            'api.errors.voucher_failed_reject' => 'failed_reject :code',
            'api.errors.voucher_unavailable' => 'unavailable :code',
            'api.messages.batch_voucher_submit' => 'success::success_amount|dupes::duplicate_amount|invalid::invalid_amount',
        ], 'en');
    }

    public function testAddCodePopulatesTheNamedBucket(): void
    {
        $this->response->addCode('success_add', 'TST00001');

        $this->assertSame(['TST00001'], $this->response->toArray()['success_add']);
    }

    public function testAddCodeAccumulatesMultipleCodesInTheSameBucket(): void
    {
        $this->response->addCode('own_duplicate', 'TST00001');
        $this->response->addCode('own_duplicate', 'TST00002');

        $this->assertSame(['TST00001', 'TST00002'], $this->response->toArray()['own_duplicate']);
    }

    public function testAddCodeCanPopulateDifferentBucketsIndependently(): void
    {
        $this->response->addCode('success_add', 'TST00001');
        $this->response->addCode('own_duplicate', 'TST00002');

        $this->assertSame(['TST00001'], $this->response->toArray()['success_add']);
        $this->assertSame(['TST00002'], $this->response->toArray()['own_duplicate']);
    }

    public function testAddCodeThrowsForAnUnrecognisedBucketName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nonexistent_bucket');

        $this->response->addCode('nonexistent_bucket', 'TST00001');
    }

    public function testAddInvalidMergesCodesIntoTheInvalidBucket(): void
    {
        $this->response->addInvalid(['TST00001', 'TST00002']);

        $this->assertSame(['TST00001', 'TST00002'], $this->response->toArray()['invalid']);
    }

    public function testAddInvalidAppendsOnSuccessiveCalls(): void
    {
        $this->response->addInvalid(['TST00001']);
        $this->response->addInvalid(['TST00002']);

        $this->assertSame(['TST00001', 'TST00002'], $this->response->toArray()['invalid']);
    }

    public function testAddInvalidDoesNotAffectOtherBuckets(): void
    {
        $this->response->addCode('success_add', 'TST00001');
        $this->response->addInvalid(['TST00002']);

        $this->assertSame(['TST00001'], $this->response->toArray()['success_add']);
    }

    public function testHasFailuresReturnsFalseOnAFreshResponse(): void
    {
        $this->assertFalse($this->response->hasFailures());
    }

    public function testHasFailuresReturnsFalseWithOnlySuccessAddCodes(): void
    {
        $this->response->addCode('success_add', 'TST00001');

        $this->assertFalse($this->response->hasFailures());
    }

    /**
     * Every bucket except success_add counts as a failure so that callers
     * (e.g. BundleController::collectBundle) can detect any unclean outcome.
     *
     * Note: success_reject appearing here is intentional — in the collect
     * context a rejection means the voucher was not collected, which IS a
     * failure from the store's perspective.
     *
     * @dataProvider nonSuccessAddBucketProvider
     */
    public function testHasFailuresReturnsTrueForEveryNonSuccessAddBucket(string $bucket): void
    {
        $this->response->addCode($bucket, 'TST00001');

        $this->assertTrue($this->response->hasFailures());
    }

    public static function nonSuccessAddBucketProvider(): array
    {
        return array_map(
            static function (string $b) {
                return [$b];
            },
            array_values(array_filter(
                TransitionResponse::BUCKETS,
                static function (string $b) {
                    return !in_array($b, TransitionResponse::SUCCESS_BUCKETS);
                }
            ))
        );
    }

    public function testHasFailuresReturnsTrueWhenOnlyInvalidBucketIsPopulated(): void
    {
        $this->response->addInvalid(['TST00001']);

        $this->assertTrue($this->response->hasFailures());
    }

    public function testGetFailureCodesReturnsEmptyArrayWhenNoCodesHaveBeenAdded(): void
    {
        $this->assertSame([], $this->response->getFailureCodes());
    }

    public function testGetFailureCodesExcludesSuccessAddCodes(): void
    {
        $this->response->addCode('success_add', 'TST00001');

        $this->assertSame([], $this->response->getFailureCodes());
    }

    public function testGetFailureCodesReturnsFlattenedCodesFromAllFailureBuckets(): void
    {
        $this->response->addCode('own_duplicate', 'TST00001');
        $this->response->addCode('other_duplicate', 'TST00002');
        $this->response->addInvalid(['TST00003']);

        $codes = $this->response->getFailureCodes();

        $this->assertContains('TST00001', $codes);
        $this->assertContains('TST00002', $codes);
        $this->assertContains('TST00003', $codes);
        $this->assertCount(3, $codes);
    }

    public function testGetFailureCodesDoesNotIncludeSuccessAddAlongsideFailures(): void
    {
        $this->response->addCode('success_add', 'TST00001');
        $this->response->addCode('own_duplicate', 'TST00002');

        $codes = $this->response->getFailureCodes();

        $this->assertNotContains('TST00001', $codes);
        $this->assertContains('TST00002', $codes);
    }

    public function testHasPaymentsReturnsFalseOnAFreshResponse(): void
    {
        $this->assertFalse($this->response->hasPayments());
    }

    public function testHasPaymentsReturnsTrueAfterASingleRecordPaymentCall(): void
    {
        $this->response->recordPayment(42);

        $this->assertTrue($this->response->hasPayments());
    }

    public function testGetVouchersForPaymentReturnsAllRecordedIdsInOrder(): void
    {
        $this->response->recordPayment(1);
        $this->response->recordPayment(2);
        $this->response->recordPayment(3);

        $this->assertSame([1, 2, 3], $this->response->getVouchersForPayment());
    }

    public function testGetVouchersForPaymentReturnsEmptyArrayInitially(): void
    {
        $this->assertSame([], $this->response->getVouchersForPayment());
    }

    public function testToArrayOnlyContainsKeysWhoseCodesHaveBeenAdded(): void
    {
        // MessageBag::toArray() returns only populated keys.
        // An empty response has no keys at all.
        $this->assertSame([], $this->response->toArray());

        $this->response->addCode('success_add', 'TST00001');
        $this->response->addCode('own_duplicate', 'TST00002');

        $array = $this->response->toArray();

        $this->assertArrayHasKey('success_add', $array);
        $this->assertArrayHasKey('own_duplicate', $array);
        $this->assertArrayNotHasKey('invalid', $array);
    }

    public function testToArrayReflectsAddedCodes(): void
    {
        $this->response->addCode('success_add', 'TST00001');
        $this->response->addCode('own_duplicate', 'TST00002');

        $array = $this->response->toArray();

        $this->assertContains('TST00001', $array['success_add']);
        $this->assertContains('TST00002', $array['own_duplicate']);
    }

    public function testPaymentMessageShortCircuitsAllOtherOutcomes(): void
    {
        // Even with concurrent failures the payment path takes priority.
        $this->response->recordPayment(1);
        $this->response->addCode('own_duplicate', 'TST00001');

        $result = $this->response->constructResponseMessage();

        $this->assertArrayHasKey('message', $result);
        $this->assertSame('payment_requested', $result['message']);
    }

    public function testSingleSuccessAddYieldsSuccessMessage(): void
    {
        $this->response->addCode('success_add', 'TST00001');

        $result = $this->response->constructResponseMessage();

        $this->assertArrayHasKey('message', $result);
        $this->assertSame('success_add', $result['message']);
    }

    public function testSingleSuccessRejectYieldsSuccessMessage(): void
    {
        $this->response->addCode('success_reject', 'TST00001');

        $result = $this->response->constructResponseMessage();

        $this->assertArrayHasKey('message', $result);
        $this->assertSame('success_reject', $result['message']);
    }

    public function testSingleOwnDuplicateYieldsWarningContainingTheCode(): void
    {
        $this->response->addCode('own_duplicate', 'TST00001');

        $result = $this->response->constructResponseMessage();

        $this->assertArrayHasKey('warning', $result);
        $this->assertStringContainsString('TST00001', $result['warning']);
    }

    public function testSingleOtherDuplicateYieldsWarningContainingTheCode(): void
    {
        $this->response->addCode('other_duplicate', 'TST00001');

        $result = $this->response->constructResponseMessage();

        $this->assertArrayHasKey('warning', $result);
        $this->assertStringContainsString('TST00001', $result['warning']);
    }

    public function testSingleFailedRejectYieldsWarningContainingTheCode(): void
    {
        $this->response->addCode('failed_reject', 'TST00001');

        $result = $this->response->constructResponseMessage();

        $this->assertArrayHasKey('warning', $result);
        $this->assertStringContainsString('TST00001', $result['warning']);
    }

    public function testSingleUndeliveredYieldsWarningContainingTheCode(): void
    {
        $this->response->addCode('undelivered', 'TST00001');

        $result = $this->response->constructResponseMessage();

        $this->assertArrayHasKey('warning', $result);
        $this->assertStringContainsString('TST00001', $result['warning']);
    }

    public function testSingleInvalidCodeFallsThroughToErrorKey(): void
    {
        // 'invalid' has no explicit arm in the match — it hits the default branch.
        $this->response->addInvalid(['TST00001']);

        $result = $this->response->constructResponseMessage();

        $this->assertArrayHasKey('error', $result);
    }

    public function testBatchSubmissionYieldsCountSummaryMessage(): void
    {
        // 2 successes, 1 own-duplicate, 1 undelivered (treated as invalid in summary).
        $this->response->addCode('success_add', 'TST00001');
        $this->response->addCode('success_add', 'TST00002');
        $this->response->addCode('own_duplicate', 'TST00003');
        $this->response->addCode('undelivered', 'TST00004');

        $result = $this->response->constructResponseMessage();

        $this->assertArrayHasKey('message', $result);
        // Our stub: 'success::success_amount|dupes::duplicate_amount|invalid::invalid_amount'
        $this->assertStringContainsString('success:2', $result['message']);
        $this->assertStringContainsString('dupes:1', $result['message']);
        $this->assertStringContainsString('invalid:1', $result['message']);
    }

    public function testTwoCodesInDifferentBucketsProducesBatchSummaryNotSingleVoucherMessage(): void
    {
        // total_submitted = 2, so the single-voucher path is skipped.
        $this->response->addCode('success_add', 'TST00001');
        $this->response->addCode('own_duplicate', 'TST00002');

        $result = $this->response->constructResponseMessage();

        // Batch path always returns 'message', never 'warning'.
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayNotHasKey('warning', $result);
    }

    /**
     * failed_payout has no explicit arm in the single-voucher match expression
     * so it falls through to the default branch, which produces the generic
     * 'error' key. This mirrors the already-tested behaviour for 'invalid' and
     * pins it explicitly for the failed_payout bucket so any future addition of
     * a specific arm is noticed.
     */
    public function testSingleFailedPayoutFallsThroughToErrorKey(): void
    {
        $this->response->addCode('failed_payout', 'TST00001');

        $result = $this->response->constructResponseMessage();

        $this->assertArrayHasKey('error', $result);
        $this->assertArrayNotHasKey('warning', $result);
        $this->assertArrayNotHasKey('message', $result);
    }
}
