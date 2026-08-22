<?php

namespace Database\Seeders;

use App\Models\Kategorie;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Admin und Vereine werden bewusst
     * nicht geseedet (Admin per Setup-Wizard, Vereine legt der Admin an).
     */
    public function run(): void
    {
        $solo = Kategorie::create(['name' => 'Solo Dance Einsteiger', 'typ' => 'solo']);
        $solo->bewertungen()->createMany([
            ['name' => 'Tanz'],
            ['name' => 'Ausdruck'],
            ['name' => 'Technik'],
        ]);

        $paar = Kategorie::create(['name' => 'Paartanz Einsteiger', 'typ' => 'paar']);
        $paar->bewertungen()->createMany([
            ['name' => 'Tanz'],
            ['name' => 'Ausdruck'],
            ['name' => 'Technik'],
        ]);

        $formation = Kategorie::create(['name' => 'Formation Hauptklasse', 'typ' => 'formation']);
        $formation->bewertungen()->createMany([
            ['name' => 'Tanz'],
            ['name' => 'Ausdruck'],
            ['name' => 'Technik'],
        ]);
    }
}
