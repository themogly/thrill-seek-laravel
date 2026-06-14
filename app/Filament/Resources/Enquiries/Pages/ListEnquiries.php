<?php

namespace App\Filament\Resources\Enquiries\Pages;

use App\Enums\EnquiryStatus;
use App\Filament\Resources\Enquiries\EnquiryResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListEnquiries extends ListRecords
{
    protected static string $resource = EnquiryResource::class;

    /** Quick-triage tabs; "Needs reply" leads so pending replies are front and centre. */
    public function getTabs(): array
    {
        $needsReply = [EnquiryStatus::New->value, EnquiryStatus::CustomerReplied->value];

        return [
            'needs_reply' => Tab::make('Needs reply')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', $needsReply))
                ->badge(fn (): int => EnquiryResource::getModel()::query()->whereIn('status', $needsReply)->count())
                ->badgeColor('danger'),
            'new' => Tab::make('New')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', EnquiryStatus::New->value)),
            'replied' => Tab::make('Replied')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', EnquiryStatus::Replied->value)),
            'closed' => Tab::make('Closed')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', EnquiryStatus::Closed->value)),
            'all' => Tab::make('All'),
        ];
    }
}
