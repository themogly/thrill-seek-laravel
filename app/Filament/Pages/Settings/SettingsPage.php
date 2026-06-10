<?php

namespace App\Filament\Pages\Settings;

use App\Jobs\OptimizeUploadedImage;
use App\Support\ImageOptimization;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Spatie\LaravelSettings\Settings;
use UnitEnum;

/**
 * Base class for admin pages that edit a spatie settings group.
 *
 * @property-read Schema $form
 */
abstract class SettingsPage extends Page
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    protected static string|UnitEnum|null $navigationGroup = 'Site content';

    /** @return class-string<Settings> */
    abstract protected static function settings(): string;

    public function mount(): void
    {
        $this->form->fill(app(static::settings())->toArray());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Save changes')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $settings = app(static::settings());
        $settings->fill($this->form->getState());
        $settings->save();

        $this->queueImageOptimisation($settings);

        Notification::make()
            ->success()
            ->title('Saved')
            ->body('Your changes are live on the site.')
            ->send();
    }

    /** Freshly-uploaded settings images get the same optimisation as model uploads. */
    private function queueImageOptimisation(Settings $settings): void
    {
        $values = $settings->toArray();

        foreach (ImageOptimization::SETTINGS_IMAGE_PROPERTIES[$settings::class] ?? [] as $property) {
            $path = $values[$property] ?? null;

            if (is_string($path) && ImageOptimization::isOptimisablePath($path)) {
                OptimizeUploadedImage::dispatch($path);
            }
        }
    }
}
