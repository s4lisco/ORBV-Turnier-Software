<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnmeldungPaar extends Model
{
    protected $table = 'anmeldung_paar';

    protected $fillable = [
        'turnier_kategorie_id', 'verein_id', 'name_p1', 'geburtsdatum_p1', 'name_p2', 'geburtsdatum_p2',
    ];

    protected function casts(): array
    {
        return [
            'geburtsdatum_p1' => 'date',
            'geburtsdatum_p2' => 'date',
        ];
    }

    public function turnierKategorie(): BelongsTo
    {
        return $this->belongsTo(TurnierKategorie::class);
    }

    public function verein(): BelongsTo
    {
        return $this->belongsTo(Verein::class);
    }
}
