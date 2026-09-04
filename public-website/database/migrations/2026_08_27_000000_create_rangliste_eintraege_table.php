<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rangliste_eintraege', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turnier_kategorie_id')->constrained('turnier_kategorie')->cascadeOnDelete();
            // "DNF" oder eine Rangzahl (als String gespeichert, siehe pi-software
            // CsvExportService::exportRanglisteFuerUpload).
            $table->string('rang');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rangliste_eintraege');
    }
};
