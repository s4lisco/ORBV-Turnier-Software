<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KategorieBewertung extends Model
{
    protected $table = 'kategorie_bewertungen';

    protected $fillable = ['kategorie_id', 'name'];

    public function kategorie(): BelongsTo
    {
        return $this->belongsTo(Kategorie::class);
    }
}
