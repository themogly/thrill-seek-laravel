<?php

namespace App\Filament\Resources\NewsletterCampaigns\Schemas;

use App\Models\CourseDate;
use App\Models\NewsletterCampaign;
use App\Support\AdminImages;
use App\Support\AdminOptions;
use App\Support\NewsletterStarterTemplates;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

/**
 * The block builder. Brand-locked: blocks carry CONTENT and ORDER only — no
 * colour/font controls — so every newsletter renders on-brand and email-safe.
 */
class NewsletterCampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        // Single column so the meta panel and the block builder stack full-width
        // down the page — the builder needs the room, not a squeezed half-column.
        // A sent newsletter is history — what subscribers actually received.
        return $schema->columns(1)
            ->disabled(fn (?NewsletterCampaign $record): bool => (bool) $record?->isSent())
            ->components([
                // Create only: pick a starter to pre-fill the blocks below, then edit.
                // Hidden on edit, where re-picking would wipe the owner's content.
                Select::make('starter_template')
                    ->label('Start from a template')
                    ->options(NewsletterStarterTemplates::options())
                    ->default('blank')
                    ->selectablePlaceholder(false)
                    ->live()
                    ->dehydrated(false)
                    ->visibleOn('create')
                    ->helperText('Pick a starting point — it pre-fills the content below, which you can then edit. Choose “Blank” to build from scratch.')
                    ->afterStateUpdated(fn (?string $state, Set $set): mixed => $set('blocks', NewsletterStarterTemplates::blocks((string) $state)))
                    ->columnSpanFull(),
                Section::make('Newsletter details')
                    ->description('Internal name, subject and preheader. Collapse this to focus on the content.')
                    ->collapsible()
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        TextInput::make('name')
                            ->label('Internal name')
                            ->helperText('For your reference only — not shown to subscribers.')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('subject')
                            ->label('Subject line')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('preheader')
                            ->label('Preheader (optional)')
                            ->helperText('The grey preview line shown after the subject in most inboxes.')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Section::make('Content')
                    ->columnSpanFull()
                    ->components([
                        Builder::make('blocks')
                            ->hiddenLabel()
                            ->addActionLabel('Add a block')
                            ->collapsible()
                            ->blockNumbers(false)
                            ->blocks([
                                Block::make('logo')
                                    ->label('Logo header')
                                    ->icon('heroicon-o-sparkles')
                                    ->schema([]),
                                Block::make('heading')
                                    ->icon('heroicon-o-h1')
                                    ->schema([
                                        TextInput::make('text')->label('Heading')->required()->maxLength(120),
                                        Select::make('level')->label('Size')->options(['h1' => 'Large', 'h2' => 'Medium'])->default('h2'),
                                    ]),
                                Block::make('paragraph')
                                    ->label('Text')
                                    ->icon('heroicon-o-bars-3-bottom-left')
                                    ->schema([
                                        RichEditor::make('text')
                                            ->label('Text')
                                            ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList'])
                                            ->required(),
                                    ]),
                                Block::make('image')
                                    ->icon('heroicon-o-photo')
                                    ->schema([
                                        AdminImages::upload('image')
                                            ->label('Image')
                                            ->disk('public')
                                            ->directory('newsletter')
                                            ->imageResizeMode('contain')
                                            ->imageResizeTargetWidth('1200')
                                            ->required(),
                                        TextInput::make('caption')->label('Caption (optional)')->maxLength(255),
                                        TextInput::make('link')->label('Links to (optional)')->helperText('A site path like /tandem or a full URL.')->maxLength(2048),
                                    ]),
                                Block::make('button')
                                    ->icon('heroicon-o-cursor-arrow-rays')
                                    ->schema([
                                        TextInput::make('label')->label('Button text')->required()->maxLength(60),
                                        TextInput::make('url')->label('Links to')->helperText('A site path like /tandem, /aff, /vouchers or a full URL.')->required()->maxLength(2048),
                                    ]),
                                Block::make('divider')
                                    ->icon('heroicon-o-minus')
                                    ->schema([]),
                                Block::make('two_column')
                                    ->label('Image + text')
                                    ->icon('heroicon-o-view-columns')
                                    ->schema([
                                        AdminImages::upload('image')->label('Image')->disk('public')->directory('newsletter')->imageResizeMode('contain')->imageResizeTargetWidth('800'),
                                        Select::make('image_side')->label('Image on the')->options(['left' => 'Left', 'right' => 'Right'])->default('left'),
                                        TextInput::make('heading')->label('Heading')->maxLength(120),
                                        Textarea::make('text')->label('Text')->rows(3),
                                        TextInput::make('button_label')->label('Button text (optional)')->maxLength(60),
                                        TextInput::make('button_url')->label('Button links to (optional)')->maxLength(2048),
                                    ]),
                                Block::make('latest_news')
                                    ->label('Latest news (automatic)')
                                    ->icon('heroicon-o-newspaper')
                                    ->schema([])
                                    ->columns(1),
                                Block::make('featured_course')
                                    ->label('Featured AFF course')
                                    ->icon('heroicon-o-academic-cap')
                                    ->schema([
                                        Select::make('course_date_id')
                                            ->label('Course')
                                            ->helperText('Leave blank to feature the next open course automatically.')
                                            ->options(fn (mixed $state): array => AdminOptions::bookablePlusCurrent(
                                                CourseDate::upcomingOpen()->with('location'),
                                                $state,
                                                fn (CourseDate $c): string => $c->location->name.' · '.$c->date_range_label,
                                            )),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
