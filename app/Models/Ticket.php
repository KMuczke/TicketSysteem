<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'naam',
        'beschrijving',
        'status',
        'prioriteit',
        'developer_id',
        'gesloten_op',
    ];

    protected function casts(): array
    {
        return [
            'gesloten_op' => 'datetime',
        ];
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'developer_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'in_behandeling' => 'In behandeling',
            'gesloten' => 'Gesloten',
            default => 'Open',
        };
    }

    public function prioriteitKleur(): string
    {
        return match ($this->prioriteit) {
            'kritiek' => 'red',
            'hoog' => 'amber',
            'gemiddeld' => 'blue',
            default => 'green',
        };
    }
}
