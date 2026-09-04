<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Einzige Konfigurationszeile für den öffentlichen Header (Plan_Feedback_Umsetzung.md 9.4):
     * Logo und Infotext sind im Admin pflegbar, ohne Code-Änderung.
     */
    public function up(): void
    {
        Schema::create('einstellungen', function (Blueprint $table) {
            $table->id();
            $table->string('logo_pfad')->nullable();
            $table->text('info_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('einstellungen');
    }
};
