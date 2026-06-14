<?php

namespace App\Filament\Resources\UnmatchedInboundMessages\Pages;

use App\Filament\Resources\UnmatchedInboundMessages\UnmatchedInboundMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListUnmatchedInboundMessages extends ListRecords
{
    protected static string $resource = UnmatchedInboundMessageResource::class;
}
