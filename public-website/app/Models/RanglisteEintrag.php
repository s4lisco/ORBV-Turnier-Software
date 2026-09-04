<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RanglisteEintrag extends Model
{
    protected $table = 'rangliste_eintraege';

    protected $fillable = ['turnier_kategorie_id', 'rang', 'name'];

    public function turnierKategorie(): BelongsTo
    {
        return $this->belongsTo(TurnierKategorie::class);
    }

    public function istDnf(): bool
    {
        return $this->rang === 'DNF';
    }
}
