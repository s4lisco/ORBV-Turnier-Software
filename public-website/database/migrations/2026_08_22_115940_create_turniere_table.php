<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turniere', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('beschreibung')->nullable();
            $table->string('adresse')->nullable();
            $table->date('datum');
            $table->foreignId('verein_id')->constrained('vereine');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turniere');
    }
};
