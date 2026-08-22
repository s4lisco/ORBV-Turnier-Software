<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnier_kategorie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turnier_id')->constrained('turniere')->cascadeOnDelete();
            $table->foreignId('kategorie_id')->constrained('kategorien');
            $table->timestamps();

            $table->unique(['turnier_id', 'kategorie_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnier_kategorie');
    }
};
