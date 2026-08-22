<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnmeldungFormation extends Model
{
    protected $table = 'anmeldung_formation';

    protected $fillable = ['turnier_kategorie_id', 'verein_id', 'name', 'anzahl_taenzerinnen'];

    public function turnierKategorie(): BelongsTo
    {
        return $this->belongsTo(TurnierKategorie::class);
    }

    public function verein(): BelongsTo
    {
        return $this->belongsTo(Verein::class);
    }
}
