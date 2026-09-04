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
     *
     * Die 10 Kategorien sind fest im System verankert (siehe Plan_Feedback_Umsetzung.md
     * 8.1) und werden nicht mehr frei im Admin-Bereich angelegt.
     */
    public function run(): void
    {
        $standardKriterien = ['Tanz', 'Ausdruck', 'Technik'];

        $kategorien = [
            ['name' => 'Solo Dance Einsteiger', 'typ' => 'solo'],
            ['name' => 'Solo Dance Mittel', 'typ' => 'solo'],
            ['name' => 'Solo Dance Pro', 'typ' => 'solo'],
            ['name' => 'Paartanz Einsteiger', 'typ' => 'paar'],
            ['name' => 'Paartanz Mittel', 'typ' => 'paar'],
            ['name' => 'Paartanz Pro', 'typ' => 'paar'],
            ['name' => 'Paartanz Akrobatik', 'typ' => 'paar'],
            ['name' => 'Formation Einsteiger', 'typ' => 'formation'],
            ['name' => 'Formation Mittel', 'typ' => 'formation'],
            ['name' => 'Formation Pro', 'typ' => 'formation'],
        ];

        foreach ($kategorien as $daten) {
            $kategorie = Kategorie::create($daten);
            $kategorie->bewertungen()->createMany(
                array_map(fn (string $name) => ['name' => $name], $standardKriterien)
            );
        }
    }
}
