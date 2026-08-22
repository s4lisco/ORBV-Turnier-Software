<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anmeldung_paar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turnier_kategorie_id')->constrained('turnier_kategorie')->cascadeOnDelete();
            $table->foreignId('verein_id')->constrained('vereine');
            $table->string('name_p1');
            $table->date('geburtsdatum_p1');
            $table->string('name_p2');
            $table->date('geburtsdatum_p2');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anmeldung_paar');
    }
};
