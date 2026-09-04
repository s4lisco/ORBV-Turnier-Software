<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EinstellungenTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_kann_logo_und_infotext_pflegen_und_home_zeigt_sie_an(): void
    {
        Storage::fake('public');

        $admin = Admin::create([
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password' => 'geheim123',
            'role' => 'master_admin',
        ]);

        // Ohne Konfiguration zeigt der Header einen Platzhalter.
        $this->get(route('home'))->assertSee('ORBV-Logo (Platzhalter)', false);

        // UploadedFile::fake()->image() braucht die GD-Extension (in der Test-Umgebung
        // nicht installiert); stattdessen ein minimales, aber valides 1x1-PNG verwenden,
        // damit die "image"-Validierungsregel den echten Dateiinhalt akzeptiert.
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $logo = UploadedFile::fake()->createWithContent('logo.png', $pngBytes);

        $response = $this->actingAs($admin, 'admin')->put(route('admin.einstellungen.update'), [
            'info_text' => 'Willkommen beim ORBV-Turnier!',
            'logo' => $logo,
        ]);

        $response->assertRedirect(route('admin.einstellungen.edit'));

        $einstellung = \App\Models\Einstellung::aktuell();
        Storage::disk('public')->assertExists($einstellung->logo_pfad);

        $homeResponse = $this->get(route('home'));
        $homeResponse->assertSee('Willkommen beim ORBV-Turnier!');
        $homeResponse->assertSee($einstellung->logoUrl(), false);
    }
}
