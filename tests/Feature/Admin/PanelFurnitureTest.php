<?php

namespace Tests\Feature\Admin;

use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Tests\TestCase;

/**
 * The back office looks built for the owner, not like a framework default: no
 * stock dashboard widgets, and a primary colour that was chosen (OVERNIGHT-DEFAULT —
 * ANSWERED 9 Oct (see DECISIONS)) rather than Filament's default amber.
 */
class PanelFurnitureTest extends TestCase
{
    public function test_the_dashboard_carries_no_stock_filament_widgets(): void
    {
        $widgets = Filament::getPanel('admin')->getWidgets();

        $this->assertNotContains(AccountWidget::class, $widgets);
        $this->assertNotContains(FilamentInfoWidget::class, $widgets);
    }

    public function test_the_panel_primary_is_not_the_framework_default(): void
    {
        $this->assertNotSame(Color::Amber, Filament::getPanel('admin')->getColors()['primary']);
    }
}
