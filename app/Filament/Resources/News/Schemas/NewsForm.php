<?php

namespace App\Filament\Resources\News\Schemas;

use App\Models\CourseDate;
use App\Support\AdminDates;
use App\Support\ImageCrop;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class NewsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Article')
                ->columns(2)
                ->components([
                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        // Auto-fill the slug from the title while creating; the
                        // owner can still edit it.
                        ->afterStateUpdated(function (string $operation, mixed $state, Set $set): void {
                            if ($operation === 'create') {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Web address (slug)')
                        ->helperText('Auto-filled from the title; edit if you like. Must be unique.')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->rule('alpha_dash'),
                    Textarea::make('lead')
                        ->label('Lead (optional)')
                        ->helperText('A short intro shown under the title. Leave blank to omit it.')
                        ->rows(2)
                        ->maxLength(500)
                        ->columnSpanFull(),
                    RichEditor::make('body')
                        ->label('Body')
                        ->required()
                        ->columnSpanFull(),
                    ImageCrop::ratio(
                        FileUpload::make('featured_image')
                            ->label('Featured image (optional)')
                            ->helperText('Shown on the news cards — crop to 16:10.')
                            ->disk('public')
                            ->directory('news')
                            ->columnSpanFull(),
                        '16:10',
                    ),
                ]),
            Section::make('Publishing')
                ->columns(2)
                ->components([
                    Toggle::make('published')
                        ->label('Published')
                        ->helperText('Drafts and future-dated posts are hidden from the public site.'),
                    AdminDates::dateTime('published_at')
                        ->label('Publish date')
                        ->default(now())
                        ->required(fn (Get $get): bool => (bool) $get('published'))
                        ->helperText('The article appears publicly from this date/time.'),
                    TextInput::make('byline')
                        ->label('Byline (optional)')
                        ->helperText('e.g. “By the G-Force team”.')
                        ->maxLength(255),
                    Select::make('course_date_id')
                        ->label('Link an AFF course (optional)')
                        ->helperText('Surfaces the course’s live dates and places-left with a book button.')
                        ->searchable()
                        ->options(fn (): array => CourseDate::upcomingOpen()
                            ->with('location')
                            ->get()
                            ->mapWithKeys(fn (CourseDate $course): array => [
                                $course->id => $course->location->name.' · '.$course->date_range_label,
                            ])
                            ->all()),
                ]),
            Section::make('Search engines & sharing (optional)')
                ->components([
                    TextInput::make('seo_title')
                        ->label('SEO title')
                        ->helperText('Defaults to the article title if left blank.')
                        ->maxLength(255),
                    Textarea::make('seo_description')
                        ->label('SEO description')
                        ->rows(2)
                        ->maxLength(255),
                ]),
        ]);
    }
}
