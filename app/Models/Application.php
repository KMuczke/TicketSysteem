<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    protected $fillable = [
        'naam',
        'url',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * The active applications as id => name pairs.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return self::query()
            ->where('is_active', true)
            ->orderBy('naam')
            ->pluck('naam', 'id')
            ->all();
    }

    /**
     * The tickets that relate to this application.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
