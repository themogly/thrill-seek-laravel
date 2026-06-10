<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_whole_pounds_have_no_decimals(): void
    {
        $this->assertSame('£260', Money::formatPence(26000));
        $this->assertSame('£1,750', Money::formatPence(175000));
        $this->assertSame('£0', Money::formatPence(0));
    }

    public function test_fractional_pounds_keep_two_decimals(): void
    {
        $this->assertSame('£24.73', Money::formatPence(2473));
        $this->assertSame('£0.50', Money::formatPence(50));
    }
}
