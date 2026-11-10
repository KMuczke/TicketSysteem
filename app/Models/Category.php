<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /**
     * The badge colours an administrator can choose from.
     *
     * @var array<string, string>
     */
    public const KLEUREN = [
        'red' => 'Rood',
        'orange' => 'Oranje',
        'amber' => 'Amber',
        'lime' => 'Limoen',
        'green' => 'Groen',
        'emerald' => 'Smaragd',
        'teal' => 'Teal',
        'cyan' => 'Cyaan',
        'sky' => 'Luchtblauw',
        'blue' => 'Blauw',
        'indigo' => 'Indigo',
        'violet' => 'Violet',
        'purple' => 'Paars',
        'fuchsia' => 'Fuchsia',
        'pink' => 'Roze',
        'rose' => 'Roze-rood',
        'zinc' => 'Zink',
    ];

    protected $fillable = [
        'naam',
        'slug',
        'kleur',
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
     * The available badge colours as value => label pairs.
     *
     * @return array<string, string>
     */
    public static function colors(): array
    {
        return self::KLEUREN;
    }

    /**
     * The active categories as slug => name pairs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return self::query()
            ->where('is_active', true)
            ->orderBy('naam')
            ->pluck('naam', 'slug')
            ->all();
    }

    /**
     * The active categories, including the current one when it is deactivated.
     *
     * @return array<string, string>
     */
    public static function optionsIncluding(?string $slug): array
    {
        return self::query()
            ->where(function ($query) use ($slug) {
                $query->where('is_active', true);

                if (! blank($slug)) {
                    $query->orWhere('slug', $slug);
                }
            })
            ->orderBy('naam')
            ->pluck('naam', 'slug')
            ->all();
    }

    /**
     * The category a new ticket should start with.
     */
    public static function defaultSlug(): string
    {
        return self::query()
            ->where('is_active', true)
            ->where('slug', 'vraag')
            ->value('slug')
            ?? self::query()
                ->where('is_active', true)
                ->orderBy('naam')
                ->value('slug')
            ?? 'vraag';
    }

    /**
     * A human readable name for a category slug.
     */
    public static function labelFor(?string $slug): string
    {
        if (blank($slug)) {
            return '';
        }

        return self::query()->where('slug', $slug)->value('naam') ?? $slug;
    }

    /**
     * The badge colour for a category slug.
     */
    public static function colorFor(?string $slug): string
    {
        if (blank($slug)) {
            return 'zinc';
        }

        return self::query()->where('slug', $slug)->value('kleur') ?? 'zinc';
    }

    /**
     * A human readable name for the badge colour.
     */
    public function colorLabel(): string
    {
        return self::colors()[$this->kleur] ?? $this->kleur;
    }

    /**
     * The tickets that use this category.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'categorie', 'slug');
    }
}
