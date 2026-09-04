<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Kategorie;
use App\Models\Turnier;
use App\Models\Verein;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RanglisteUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_veroeffentlicht_rangliste_und_ueberspringt_fremde_kategorien(): void
    {
        $admin = Admin::create([
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password' => 'geheim123',
            'role' => 'master_admin',
        ]);

        $verein = Verein::create([
            'name' => 'Testverein',
            'email' => 'verein@example.test',
            'username' => 'verein',
            'password' => 'geheim123',
        ]);

        $kategorie = Kategorie::create(['name' => 'Solo Dance Einsteiger', 'typ' => 'solo']);
        $fremdeKategorieId = $kategorie->id + 999;

        $turnier = Turnier::create([
            'name' => 'Testturnier',
            'datum' => now()->addMonth(),
            'adresse' => 'Teststrasse 1',
            'verein_id' => $verein->id,
        ]);
        $turnier->kategorien()->sync([$kategorie->id]);

        $csv = "kategorie_id,kategorie_name,rang,name\n"
            . $kategorie->id . ",Solo Dance Einsteiger,1,Anna Muster\n"
            . $kategorie->id . ",Solo Dance Einsteiger,DNF,Beat Muster\n"
            . $fremdeKategorieId . ",Andere Kategorie,1,Sollte übersprungen werden\n";

        $file = UploadedFile::fake()->createWithContent('rangliste.csv', $csv);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.turniere.rangliste.upload', $turnier), [
                'rangliste_csv' => $file,
            ]);

        $response->assertRedirect(route('admin.turniere.show', $turnier));

        $this->assertDatabaseHas('rangliste_eintraege', [
            'name' => 'Anna Muster',
            'rang' => '1',
        ]);
        $this->assertDatabaseHas('rangliste_eintraege', [
            'name' => 'Beat Muster',
            'rang' => 'DNF',
        ]);
        $this->assertDatabaseMissing('rangliste_eintraege', [
            'name' => 'Sollte übersprungen werden',
        ]);

        // Öffentliche Ergebnisseite zeigt Name + Rang ohne Login, DNF steht unten.
        $publicResponse = $this->get(route('ergebnisse.show', $turnier));
        $publicResponse->assertOk();
        $publicResponse->assertSeeInOrder(['Anna Muster', 'Beat Muster']);
        $publicResponse->assertSee('DNF');

        // Erneuter Upload ersetzt die alte Rangliste dieser Kategorie statt sie zu duplizieren.
        $csvUpdate = "kategorie_id,kategorie_name,rang,name\n"
            . $kategorie->id . ",Solo Dance Einsteiger,1,Claudia Neu\n";
        $fileUpdate = UploadedFile::fake()->createWithContent('rangliste2.csv', $csvUpdate);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.turniere.rangliste.upload', $turnier), [
                'rangliste_csv' => $fileUpdate,
            ]);

        $this->assertDatabaseMissing('rangliste_eintraege', ['name' => 'Anna Muster']);
        $this->assertDatabaseHas('rangliste_eintraege', ['name' => 'Claudia Neu']);
        $this->assertEquals(1, \App\Models\RanglisteEintrag::count());
    }
}
