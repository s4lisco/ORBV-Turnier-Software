<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategorie_bewertungen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategorie_id')->constrained('kategorien')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['kategorie_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategorie_bewertungen');
    }
};
