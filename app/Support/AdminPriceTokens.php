<?php

namespace App\Support;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\HtmlString;

/**
 * The one way to make an admin field accept price tokens (see PriceTokens).
 *
 * Adds a live preview under the field: known tokens show the current price,
 * unknown ones are highlighted. Deliberately NOT a save-blocking rule — a
 * product being hidden or renamed later would otherwise lock every other edit
 * on that settings page; the public side falls back safely instead. Only
 * fields whose page renders them through PriceTokens::render() may use this —
 * the public side must resolve whatever the admin side advertises.
 */
class AdminPriceTokens
{
    /**
     * @template TField of TextInput|Textarea|RichEditor
     *
     * @param  TField  $field
     * @return TField
     */
    public static function field(TextInput|Textarea|RichEditor $field): TextInput|Textarea|RichEditor
    {
        return $field
            ->live(onBlur: true)
            ->helperText(function (mixed $state): HtmlString {
                $tokens = app(PriceTokens::class);
                $text = self::text($state);

                return new HtmlString($tokens->hasTokens($text)
                    ? 'Preview: '.$tokens->preview($text)
                    : 'Tip: type <code>{price:tandem-skydive}</code> (or another product’s reference) to show its live price — see the Help guide.');
            });
    }

    /** A field's state as text: rich-editor state may arrive as a TipTap document array. */
    private static function text(mixed $state): string
    {
        if (is_array($state)) {
            return RichContentRenderer::make($state)->toHtml();
        }

        return is_string($state) ? $state : '';
    }
}
