<?php

namespace Tests\Unit\Wrapper;

use App\Wrappers\SecretStreamWrapper;
use Illuminate\Foundation\Testing\TestCase;
use Tests\CreatesApplication;

class StreamWrapperTest extends TestCase
{
    use CreatesApplication;

    public function testInvocationErrors()
    {
        $ssw = new SecretStreamWrapper();

        $result = $ssw->stream_open("any/path", "r", null);
        $this->assertFalse($result);

        $result = $ssw->stream_open("any/path", "w", null);
        $this->assertFalse($result);
    }
}
