<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategorie extends Model
{
    protected $table = 'kategorien';

    protected $fillable = ['name', 'typ'];

    public function bewertungen(): HasMany
    {
        return $this->hasMany(KategorieBewertung::class);
    }

    public function kriteriumNamen(): string
    {
        return $this->bewertungen->pluck('name')->implode(';');
    }
}
