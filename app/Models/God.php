<?php

namespace App\Models;

use App\Support\GodName;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class God extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'type', 'short_description', 'description', 'fact', 'image_path', 'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'date'];
    }

    // Записываем одновременно отображаемое имя и его уникальный поисковый ключ.
    // Работает также при создании через Tinker, не только через веб-форму.
    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => [
                'name' => GodName::normalize((string) $value),
                'name_key' => GodName::key((string) $value),
            ],
        );
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value) : null,
            set: fn ($value) => $value ? Carbon::parse($value)->toDateTimeString() : null,
        );
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value) : null,
            set: fn ($value) => $value ? Carbon::parse($value)->toDateTimeString() : null,
        );
    }
}
