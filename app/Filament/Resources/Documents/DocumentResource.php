<?php

namespace App\Filament\Resources\Documents;

use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Models\Document;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperClip;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & sales';

    protected static ?int $navigationSort = 11;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Name')
                ->helperText('How the document appears when attaching it, e.g. “Medical declaration form”.')
                ->required()
                ->maxLength(255),
            FileUpload::make('file_path')
                ->label('File')
                ->helperText('PDF, Word or image, up to 10 MB.')
                ->disk('local')
                ->directory('documents')
                ->acceptedFileTypes(Document::ALLOWED_MIME_TYPES)
                ->maxSize(Document::MAX_FILE_BYTES / 1024)
                ->storeFileNamesIn('original_filename')
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->afterStateUpdated(function (callable $set, ?TemporaryUploadedFile $state): void {
                    if ($state !== null) {
                        $set('mime_type', $state->getMimeType());
                        $set('size_bytes', $state->getSize());
                    }
                }),
            TextInput::make('mime_type')->hidden()->dehydrated(),
            TextInput::make('size_bytes')->hidden()->dehydrated(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Document')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('original_filename')
                    ->label('File'),
                TextColumn::make('formatted_size')
                    ->label('Size'),
                TextColumn::make('course_messages_count')
                    ->label('Used in messages')
                    ->counts('courseMessages'),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->date()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }
}
