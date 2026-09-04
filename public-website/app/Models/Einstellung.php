<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Singleton-artige Konfiguration für den öffentlichen Header (Logo + Infotext),
 * siehe Plan_Feedback_Umsetzung.md 9.4.
 */
class Einstellung extends Model
{
    protected $table = 'einstellungen';

    protected $fillable = ['logo_pfad', 'info_text'];

    public static function aktuell(): self
    {
        return static::query()->firstOrCreate([]);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_pfad ? Storage::disk('public')->url($this->logo_pfad) : null;
    }
}
