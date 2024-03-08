<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\BrowserKitTesting\TestCase as BaseTestCase;

abstract class StoreTestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    /**
     * @param $selector string Selector string to find a bunch of elements
     * @param $text string String you're looking for
     * @param $pos int Postion in the returned element array you think the text will be.
     * @return $this
     */
    public function seeInElementAtPos($selector, $text, $pos)
    {
        $element_text = trim($this->crawler->filter($selector)->eq($pos)->text());
        $this->assertStringContainsString($text, $element_text);
        return $this;
    }
}
