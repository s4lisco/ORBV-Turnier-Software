<?php

declare(strict_types=1);

use App\Database;
use App\Services\RoundService;

/**
 * Tatsächliche Startreihenfolge einer Runde (seit Feedback-Runde 2 immer
 * zufällig gemischt, siehe RoundService::starteErsteRunde()) – Tests dürfen
 * sich daher nicht auf die Erfassungsreihenfolge der Teilnehmer:innen-IDs
 * verlassen, sondern müssen die tatsächliche Reihenfolge abfragen.
 */
function orbv_runde_reihenfolge(int $rundeId): array
{
    $stmt = Database::connection()->prepare('SELECT teilnehmer_id FROM runde_teilnehmer WHERE runde_id = :runde_id ORDER BY reihenfolge ASC');
    $stmt->execute(['runde_id' => $rundeId]);

    return array_map('intval', array_column($stmt->fetchAll(), 'teilnehmer_id'));
}

/** Bewertet eine/n Teilnehmer:in durch einen einzelnen Richter (alle Kriterien gleicher Wert). */
function orbv_bewerte_einzeln(int $rundeId, int $teilnehmerId, int $kategorieId, int $richterId, float $punkteProKriterium): void
{
    $pdo = Database::connection();
    $kStmt = $pdo->prepare('SELECT id FROM kriterien WHERE kategorie_id = :kategorie_id');
    $kStmt->execute(['kategorie_id' => $kategorieId]);
    $kriterienIds = array_column($kStmt->fetchAll(), 'id');

    $insert = $pdo->prepare(
        'INSERT INTO bewertungen (runde_id, teilnehmer_id, wertungsrichter_id, kriterium_id, punkte)
         VALUES (:runde_id, :teilnehmer_id, :wertungsrichter_id, :kriterium_id, :punkte)'
    );

    foreach ($kriterienIds as $kriteriumId) {
        $insert->execute([
            'runde_id' => $rundeId,
            'teilnehmer_id' => $teilnehmerId,
            'wertungsrichter_id' => $richterId,
            'kriterium_id' => $kriteriumId,
            'punkte' => $punkteProKriterium,
        ]);
    }
}

test('Automatische Freigabe: nicht-paarweise Kategorie gibt eine/n nach der/dem anderen frei', function () {
    $kategorieId = orbv_seed_kategorie('Solo Dance Mittel', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 3);
    $richterIds = orbv_seed_richter();
    $roundService = new RoundService();

    // starteErsteRunde gibt bereits automatisch die erste Startnummer frei.
    $rundeId = $roundService->starteErsteRunde($kategorieId);
    $reihenfolge = orbv_runde_reihenfolge($rundeId);

    $freigegeben = array_column($roundService->liveStatusFuerRunde($rundeId), 'id');
    assertEquals([$reihenfolge[0]], $freigegeben);

    foreach ($richterIds as $i => $richterId) {
        orbv_bewerte_einzeln($rundeId, $reihenfolge[0], $kategorieId, $richterId, 8.0);
        $roundService->pruefeUndFreigebenNaechste($rundeId);

        $freigegeben = array_column($roundService->liveStatusFuerRunde($rundeId), 'id');
        if ($i < 4) {
            // Erst wenn WIRKLICH alle 5 fertig sind, wird die/der nächste freigegeben.
            assertEquals([$reihenfolge[0]], $freigegeben);
        } else {
            assertEquals([$reihenfolge[0], $reihenfolge[1]], $freigegeben);
        }
    }
});

test('Automatische Freigabe: paarweise Kategorie (Solo Dance Einsteiger) gibt zwei gleichzeitig frei', function () {
    $kategorieId = orbv_seed_kategorie('Solo Dance Einsteiger', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 4);
    $richterIds = orbv_seed_richter();
    $roundService = new RoundService();

    $rundeId = $roundService->starteErsteRunde($kategorieId);
    $reihenfolge = orbv_runde_reihenfolge($rundeId);

    $freigegeben = array_column($roundService->liveStatusFuerRunde($rundeId), 'id');
    assertEquals([$reihenfolge[0], $reihenfolge[1]], $freigegeben);

    foreach ($richterIds as $richterId) {
        orbv_bewerte_einzeln($rundeId, $reihenfolge[0], $kategorieId, $richterId, 8.0);
        orbv_bewerte_einzeln($rundeId, $reihenfolge[1], $kategorieId, $richterId, 7.0);
    }
    $roundService->pruefeUndFreigebenNaechste($rundeId);

    $freigegeben = array_column($roundService->liveStatusFuerRunde($rundeId), 'id');
    assertEquals($reihenfolge, $freigegeben);
});

test('aktuelleAufgabeFuerRichter: bewerten -> warten -> naechste/r nach Abschluss aller Richter', function () {
    $kategorieId = orbv_seed_kategorie('Solo Dance Mittel', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 2);
    $richterIds = orbv_seed_richter();
    $roundService = new RoundService();

    $rundeId = $roundService->starteErsteRunde($kategorieId);
    $reihenfolge = orbv_runde_reihenfolge($rundeId);

    $aufgabe = $roundService->aktuelleAufgabeFuerRichter($richterIds[0]);
    assertEquals('bewerten', $aufgabe['status']);
    assertEquals([$reihenfolge[0]], array_column($aufgabe['teilnehmer'], 'id'));
    assertEquals(false, $aufgabe['paarweise']);

    // Richter 1 bewertet, die anderen 4 noch nicht -> Richter 1 muss warten.
    orbv_bewerte_einzeln($rundeId, $reihenfolge[0], $kategorieId, $richterIds[0], 8.0);
    $roundService->pruefeUndFreigebenNaechste($rundeId);

    $aufgabe = $roundService->aktuelleAufgabeFuerRichter($richterIds[0]);
    assertEquals('warten', $aufgabe['status']);

    // Richter 2-5 bewerten ebenfalls -> automatisch naechste Startnummer frei,
    // Richter 1 bekommt sie direkt zur Bewertung angezeigt.
    foreach (array_slice($richterIds, 1) as $richterId) {
        orbv_bewerte_einzeln($rundeId, $reihenfolge[0], $kategorieId, $richterId, 7.5);
    }
    $roundService->pruefeUndFreigebenNaechste($rundeId);

    $aufgabe = $roundService->aktuelleAufgabeFuerRichter($richterIds[0]);
    assertEquals('bewerten', $aufgabe['status']);
    assertEquals([$reihenfolge[1]], array_column($aufgabe['teilnehmer'], 'id'));
});

test('aktuelleAufgabeFuerRichter: leerlauf, wenn keine Runde laeuft', function () {
    $roundService = new RoundService();
    $richterIds = orbv_seed_richter();

    assertEquals('leerlauf', $roundService->aktuelleAufgabeFuerRichter($richterIds[0])['status']);
});

test('DNF ueber "Nicht getanzt" zaehlt als vollstaendig bewertet und blockiert die automatische Freigabe nicht', function () {
    $kategorieId = orbv_seed_kategorie('Solo Dance Mittel', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 2);
    $richterIds = orbv_seed_richter();
    $roundService = new RoundService();

    $rundeId = $roundService->starteErsteRunde($kategorieId);
    $reihenfolge = orbv_runde_reihenfolge($rundeId);

    foreach ($richterIds as $i => $richterId) {
        orbv_bewerte_einzeln($rundeId, $reihenfolge[0], $kategorieId, $richterId, 0.0);
        if ($i === 0) {
            Database::connection()->prepare("UPDATE teilnehmer SET status = 'dnf' WHERE id = :id")
                ->execute(['id' => $reihenfolge[0]]);
        }
    }
    $roundService->pruefeUndFreigebenNaechste($rundeId);

    $freigegeben = array_column($roundService->liveStatusFuerRunde($rundeId), 'id');
    assertEquals([$reihenfolge[0], $reihenfolge[1]], $freigegeben);
});
