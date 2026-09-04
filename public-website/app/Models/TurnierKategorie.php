<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bewusst ein eigenständiges Model (keine Pivot/AsPivot-Nutzung): Pivot's
 * getForeignKey()-Override würde die eigenen hasMany-Relationen zu den drei
 * Anmeldungs-Typen brechen, da diese über die normale Foreign-Key-Erkennung
 * laufen. turnier_kategorie hat daher einen surrogaten id-PK und wird nur
 * über die explizite hasMany-Relation auf Turnier angesprochen (nicht über
 * ->using() auf belongsToMany).
 */
class TurnierKategorie extends Model
{
    protected $table = 'turnier_kategorie';

    protected $fillable = ['turnier_id', 'kategorie_id'];

    public function turnier(): BelongsTo
    {
        return $this->belongsTo(Turnier::class);
    }

    public function kategorie(): BelongsTo
    {
        return $this->belongsTo(Kategorie::class);
    }

    public function anmeldungenSolo(): HasMany
    {
        return $this->hasMany(AnmeldungSolo::class);
    }

    public function anmeldungenPaar(): HasMany
    {
        return $this->hasMany(AnmeldungPaar::class);
    }

    public function anmeldungenFormation(): HasMany
    {
        return $this->hasMany(AnmeldungFormation::class);
    }

    public function ranglisteEintraege(): HasMany
    {
        return $this->hasMany(RanglisteEintrag::class);
    }
}
