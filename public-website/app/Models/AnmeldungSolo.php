<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnmeldungSolo extends Model
{
    protected $table = 'anmeldung_solo';

    protected $fillable = ['turnier_kategorie_id', 'verein_id', 'name', 'geburtsdatum'];

    protected function casts(): array
    {
        return [
            'geburtsdatum' => 'date',
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
