<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Product;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_summary_price_label_formats(): void
    {
        $tandem = Product::factory()->tandem()->create(['price_pence' => 26000]);
        $aff = Product::factory()->aff()->create(['price_pence' => 175000]);
        $coachingFrom = Product::factory()->coaching()->create(['price_pence' => 6000]);
        $coachingEnquiry = Product::factory()->coaching()->create(['price_pence' => 6000, 'show_from_price' => false]);
        $noPrice = Product::factory()->coaching()->create(['price_pence' => null]);

        $this->assertSame('from £260', $tandem->summary_price_label);
        $this->assertSame('£1,750', $aff->summary_price_label);
        $this->assertSame('from £60', $coachingFrom->summary_price_label);
        $this->assertSame('Price on enquiry', $coachingEnquiry->summary_price_label);
        $this->assertSame('Price on enquiry', $noPrice->summary_price_label);
    }

    public function test_formatted_deposit(): void
    {
        $aff = Product::factory()->aff()->create(['deposit_pence' => 30000]);

        $this->assertSame('£300', $aff->formatted_deposit);
    }
}
