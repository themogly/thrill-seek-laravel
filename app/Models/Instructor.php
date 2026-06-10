<?php

namespace App\Models;

use Database\Factories\InstructorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Instructor extends Model
{
    /** @use HasFactory<InstructorFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'bio',
        'photo',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /** Public URL for the uploaded photo, or null when none is set. */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo ? Storage::disk('public')->url($this->photo) : null);
    }

    /** Display order used by the public site. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
