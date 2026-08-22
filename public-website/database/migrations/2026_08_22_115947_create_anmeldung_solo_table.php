<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anmeldung_solo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turnier_kategorie_id')->constrained('turnier_kategorie')->cascadeOnDelete();
            $table->foreignId('verein_id')->constrained('vereine');
            $table->string('name');
            $table->date('geburtsdatum');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anmeldung_solo');
    }
};
