<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Options for an admin select that lists only what's still bookable (upcoming
 * slots, open courses). The field's CURRENT value is always added back, so a
 * record whose choice has since passed still shows a readable label and still
 * saves: Filament refuses any value that isn't among the options ("The selected
 * jump slot is invalid"). Guard: SelectsKeepTheirCurrentValueTest.
 */
final class AdminOptions
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $bookable  what can be picked now
     * @param  mixed  $current  the field's state (the stored id on edit, null on create)
     * @param  Closure(TModel): string  $label
     * @return array<int|string, string>
     */
    public static function bookablePlusCurrent(Builder $bookable, mixed $current, Closure $label): array
    {
        $options = $bookable->get()->mapWithKeys(fn (Model $model): array => [$model->getKey() => $label($model)])->all();

        if (filled($current) && is_scalar($current) && ! array_key_exists($current, $options)) {
            $model = $bookable->getModel()->newQuery()->find($current);

            if ($model !== null) {
                $options = [$model->getKey() => $label($model).' — no longer bookable'] + $options;
            }
        }

        return $options;
    }
}
