<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Turnier extends Model
{
    protected $table = 'turniere';

    protected $fillable = ['name', 'beschreibung', 'adresse', 'datum', 'verein_id'];

    protected function casts(): array
    {
        return [
            'datum' => 'date',
        ];
    }

    public function verein(): BelongsTo
    {
        return $this->belongsTo(Verein::class);
    }

    public function turnierKategorien(): HasMany
    {
        return $this->hasMany(TurnierKategorie::class);
    }

    public function kategorien(): BelongsToMany
    {
        return $this->belongsToMany(Kategorie::class, 'turnier_kategorie')
            ->withTimestamps();
    }

    /** Meldeschluss für Vereine: 2 Wochen vor dem Turnierdatum (docs/turnier-plan.md 5.1). */
    protected function anmeldeschluss(): Attribute
    {
        return Attribute::get(fn () => $this->datum->copy()->subDays(14));
    }

    public function istAnmeldungOffenFuerVerein(): bool
    {
        return now()->lessThanOrEqualTo($this->anmeldeschluss);
    }
}
