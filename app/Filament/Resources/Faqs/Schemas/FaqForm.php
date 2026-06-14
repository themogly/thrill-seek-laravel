<?php

namespace App\Filament\Resources\Faqs\Schemas;

use App\Enums\FaqPage;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('page')
                ->label('Shown on')
                ->options(FaqPage::class)
                ->required()
                ->helperText('Each page has its own FAQs.'),
            TextInput::make('question')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            RichEditor::make('answer')
                ->label('Answer')
                ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList'])
                ->required()
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->label('Published (shown on the site)')
                ->default(true),
        ]);
    }
}
