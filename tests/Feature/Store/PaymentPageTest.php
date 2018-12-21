<?php

use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PaymentPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    public function setUp()
    {

    }

    /** @test */
    public function itShowsAnErrorWhenPaymentLinkBad()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
          );
    }

    /** @test */
    public function itShowsPaymentRequestDetailsOnlyForValidPaymentUUIDs()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
          );
    }

    /** @test */
    public function itShowsPayButtonWhenOnlyPaymentIsUnpaid()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
          );
    }

    /** @test */
    public function itShowsPayemntDetailsOnlyWhenPaymentIsPaid()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
          );
    }

}
