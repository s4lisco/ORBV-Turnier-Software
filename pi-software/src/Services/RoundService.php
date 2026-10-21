<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;
use PDO;
use RuntimeException;

/**
 * Rundenlogik gemäss docs/turnier-plan.md Abschnitt 5.7:
 * ≤6 Teilnehmer:innen → Final1+Final2 (beide zählen).
 * ≥7 Teilnehmer:innen → Vorrunde → oberes Drittel direkt ins Finale,
 * Rest → Hoffnungsrunde → obere Hälfte zusätzlich ins Finale.
 */
final class RoundService
{
    private const ANZAHL_RICHTER = 5;

    public function starteErsteRunde(int $kategorieId): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM runden WHERE kategorie_id = :kategorie_id');
        $stmt->execute(['kategorie_id' => $kategorieId]);
        if ((int) $stmt->fetch()['c'] > 0) {
            throw new RuntimeException('Für diese Kategorie wurden bereits Runden gestartet.');
        }

        $stmt = $pdo->prepare(
            "SELECT id FROM teilnehmer WHERE kategorie_id = :kategorie_id AND status = 'aktiv' ORDER BY startnummer ASC, id ASC"
        );
        $stmt->execute(['kategorie_id' => $kategorieId]);
        $teilnehmerIds = array_map('intval', array_column($stmt->fetchAll(), 'id'));

        if (count($teilnehmerIds) === 0) {
            throw new RuntimeException('Keine Teilnehmer:innen in dieser Kategorie.');
        }

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS c FROM teilnehmer WHERE kategorie_id = :kategorie_id AND status = 'aktiv' AND startnummer IS NULL"
        );
        $stmt->execute(['kategorie_id' => $kategorieId]);
        if ((int) $stmt->fetch()['c'] > 0) {
            throw new RuntimeException('Bitte zuerst im Dashboard die Startnummern vergeben, bevor die Runde gestartet wird.');
        }

        // Startliste ist immer zufällig (nicht nach Startnummer sortiert), siehe
        // Feedback-Runde 2. final2 bleibt dabei die umgekehrte Reihenfolge von
        // final1, daher wird hier einmal gemischt und für final2 nur gespiegelt.
        shuffle($teilnehmerIds);

        if (count($teilnehmerIds) <= 6) {
            $final1Id = $this->insertRunde($kategorieId, 'final1', 'laufend', 1);
            $this->setRundeTeilnehmer($final1Id, $teilnehmerIds);

            $final2Id = $this->insertRunde($kategorieId, 'final2', 'offen', 2);
            $this->setRundeTeilnehmer($final2Id, array_reverse($teilnehmerIds));

            $this->pruefeUndFreigebenNaechste($final1Id);

            return $final1Id;
        }

        $vorrundeId = $this->insertRunde($kategorieId, 'vorrunde', 'laufend', 1);
        $this->setRundeTeilnehmer($vorrundeId, $teilnehmerIds);

        $this->pruefeUndFreigebenNaechste($vorrundeId);

        return $vorrundeId;
    }

    /** @param int[] $teilnehmerIds */
    public function freigeben(int $rundeId, array $teilnehmerIds): void
    {
        $pdo = Database::connection();
        $insert = $pdo->prepare('INSERT OR IGNORE INTO praesenz_status (runde_id, teilnehmer_id) VALUES (:runde_id, :teilnehmer_id)');

        foreach ($teilnehmerIds as $teilnehmerId) {
            $insert->execute(['runde_id' => $rundeId, 'teilnehmer_id' => $teilnehmerId]);
        }
    }

    /**
     * Vollständige Startliste einer Runde (alle Teilnehmer:innen in Startreihenfolge),
     * unabhängig vom Freigabe-Status – für die Druckansicht (siehe
     * Plan_Feedback_Umsetzung.md 10.1/10.2).
     *
     * @return array<int, array{id:int, startnummer:?int, name:string, name_p2:?string}>
     */
    public function alleTeilnehmerFuerRunde(int $rundeId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT t.id, t.startnummer, t.name, t.name_p2, t.verein_name
             FROM runde_teilnehmer rt
             JOIN teilnehmer t ON t.id = rt.teilnehmer_id
             WHERE rt.runde_id = :runde_id
             ORDER BY rt.reihenfolge ASC'
        );
        $stmt->execute(['runde_id' => $rundeId]);

        return $stmt->fetchAll();
    }

    /**
     * Mischt die Startreihenfolge einer Runde neu (Button "Neu mischen" auf der
     * Startliste, Feedback-Runde 2 – jeder Klick erzeugt eine neue zufällige
     * Reihenfolge, es gibt keinen "ursprünglichen" Zustand zum Zurücksetzen).
     */
    public function mischeStartliste(int $rundeId): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT teilnehmer_id FROM runde_teilnehmer WHERE runde_id = :runde_id');
        $stmt->execute(['runde_id' => $rundeId]);
        $teilnehmerIds = array_map('intval', array_column($stmt->fetchAll(), 'teilnehmer_id'));

        if ($teilnehmerIds === []) {
            return;
        }

        shuffle($teilnehmerIds);

        $update = $pdo->prepare('UPDATE runde_teilnehmer SET reihenfolge = :reihenfolge WHERE runde_id = :runde_id AND teilnehmer_id = :teilnehmer_id');
        foreach (array_values($teilnehmerIds) as $index => $teilnehmerId) {
            $update->execute(['reihenfolge' => $index + 1, 'runde_id' => $rundeId, 'teilnehmer_id' => $teilnehmerId]);
        }
    }

    /**
     * Solo Dance Einsteiger und Paartanz Einsteiger tanzen laut Spezifikation
     * paarweise gleichzeitig (docs/turnier-plan.md 5.4/8.4) – die Startliste
     * muss das entsprechend gruppieren (siehe Plan_Feedback_Umsetzung.md 10.1).
     * Erkennung über Kategorie-Typ + "Einsteiger" im Namen, da die 10 Kategorien
     * fix sind (Plan_Feedback_Umsetzung.md 8.1), der Name aber editierbar bleibt.
     */
    public function tanztPaarweise(string $kategorieTyp, string $kategorieName): bool
    {
        return in_array($kategorieTyp, ['solo', 'paar'], true)
            && str_contains(mb_strtolower($kategorieName), 'einsteiger');
    }

    /** Live-Status: freigegebene Teilnehmer:innen dieser Runde und wie viele der 5 Richter schon bewertet haben. */
    public function liveStatusFuerRunde(int $rundeId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT t.id, t.startnummer, t.name, t.name_p2,
                    (SELECT COUNT(DISTINCT b.wertungsrichter_id) FROM bewertungen b WHERE b.runde_id = :runde_id AND b.teilnehmer_id = t.id) AS anzahl_richter_fertig
             FROM praesenz_status p
             JOIN teilnehmer t ON t.id = p.teilnehmer_id
             WHERE p.runde_id = :runde_id2
             ORDER BY p.freigegeben_am ASC'
        );
        $stmt->execute(['runde_id' => $rundeId, 'runde_id2' => $rundeId]);

        return $stmt->fetchAll();
    }

    public function istRundeVollstaendigBewertet(int $rundeId): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM runde_teilnehmer WHERE runde_id = :runde_id');
        $stmt->execute(['runde_id' => $rundeId]);
        $sollAnzahl = (int) $stmt->fetch()['c'];

        $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM praesenz_status WHERE runde_id = :runde_id');
        $stmt->execute(['runde_id' => $rundeId]);
        $freigegebenAnzahl = (int) $stmt->fetch()['c'];

        if ($freigegebenAnzahl < $sollAnzahl) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT teilnehmer_id FROM runde_teilnehmer WHERE runde_id = :runde_id');
        $stmt->execute(['runde_id' => $rundeId]);

        foreach (array_column($stmt->fetchAll(), 'teilnehmer_id') as $teilnehmerId) {
            if (!$this->istTeilnehmerVollstaendigBewertet($rundeId, (int) $teilnehmerId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Prüft, ob alle 5 Wertungsrichter für diese/n Teilnehmer:in in dieser Runde
     * abgestimmt haben – unabhängig vom status (auch DNF-Teilnehmer:innen erhalten
     * über den "Nicht getanzt"-Button echte Bewertungszeilen je Richter, siehe 8.4).
     */
    private function istTeilnehmerVollstaendigBewertet(int $rundeId, int $teilnehmerId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(DISTINCT wertungsrichter_id) AS c FROM bewertungen WHERE runde_id = :runde_id AND teilnehmer_id = :teilnehmer_id'
        );
        $stmt->execute(['runde_id' => $rundeId, 'teilnehmer_id' => $teilnehmerId]);

        return (int) $stmt->fetch()['c'] >= self::ANZAHL_RICHTER;
    }

    /**
     * Gibt die nächste(n) Startnummer(n) automatisch frei, sobald alle bereits
     * freigegebenen Teilnehmer:innen dieser Runde von allen 5 Richtern bewertet
     * wurden (Plan_Feedback_Umsetzung.md 11.3). Bei "paarweise"-Kategorien
     * (11.4/10.1) werden zwei auf einmal freigegeben, sonst eine. Nach jeder
     * Bewertungsabgabe erneut aufrufen (siehe ScoringController::submit).
     */
    public function pruefeUndFreigebenNaechste(int $rundeId): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT k.typ AS kategorie_typ, k.name AS kategorie_name
             FROM runden r JOIN kategorien k ON k.id = r.kategorie_id
             WHERE r.id = :id'
        );
        $stmt->execute(['id' => $rundeId]);
        $runde = $stmt->fetch();

        if (!$runde) {
            return;
        }

        $stmt = $pdo->prepare('SELECT teilnehmer_id FROM praesenz_status WHERE runde_id = :runde_id');
        $stmt->execute(['runde_id' => $rundeId]);
        $freigegebeneIds = array_map('intval', array_column($stmt->fetchAll(), 'teilnehmer_id'));

        foreach ($freigegebeneIds as $teilnehmerId) {
            if (!$this->istTeilnehmerVollstaendigBewertet($rundeId, $teilnehmerId)) {
                return;
            }
        }

        $stmt = $pdo->prepare(
            'SELECT teilnehmer_id FROM runde_teilnehmer WHERE runde_id = :runde_id
               AND teilnehmer_id NOT IN (SELECT teilnehmer_id FROM praesenz_status WHERE runde_id = :runde_id2)
             ORDER BY reihenfolge ASC'
        );
        $stmt->execute(['runde_id' => $rundeId, 'runde_id2' => $rundeId]);
        $naechste = array_map('intval', array_column($stmt->fetchAll(), 'teilnehmer_id'));

        if ($naechste === []) {
            // Alle Teilnehmer:innen dieser Runde sind freigegeben und von allen
            // 5 Richtern bewertet – die Runde ist fertig, also automatisch
            // abschliessen (das Starten der jeweils nächsten Runde bleibt weiterhin
            // manuell durch den Organisator, siehe RoundController::starten()).
            $this->rundeAbschliessen($rundeId);
            return;
        }

        $batchGroesse = $this->tanztPaarweise($runde['kategorie_typ'], $runde['kategorie_name']) ? 2 : 1;
        $this->freigeben($rundeId, array_slice($naechste, 0, $batchGroesse));
    }

    /**
     * Ermittelt die aktuelle Aufgabe eines Wertungsrichters (Plan_Feedback_Umsetzung.md 11.1/11.2/11.3):
     * - 'bewerten': es gibt aktuell freigegebene Teilnehmer:innen, die dieser Richter noch nicht
     *   bewertet hat (1 oder 2, je nach paarweise) → Bewertungsformular zeigen.
     * - 'warten': alles aktuell Freigegebene wurde von diesem Richter bereits bewertet, aber es
     *   gibt (in irgendeiner laufenden Runde) noch Freigegebenes, auf das andere Richter noch
     *   nicht fertig sind → Warteseite.
     * - 'leerlauf': keine laufende Runde hat aktuell etwas Freigegebenes → Infoseite.
     *
     * @return array{status:string, runde_id?:int, teilnehmer?:array, paarweise?:bool}
     */
    public function aktuelleAufgabeFuerRichter(int $wertungsrichterId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            "SELECT r.id, k.typ AS kategorie_typ, k.name AS kategorie_name
             FROM runden r JOIN kategorien k ON k.id = r.kategorie_id
             WHERE r.status = 'laufend'
             ORDER BY r.id ASC"
        );
        $stmt->execute();
        $laufendeRunden = $stmt->fetchAll();

        $wartetAufRundeId = null;

        foreach ($laufendeRunden as $runde) {
            $rundeId = (int) $runde['id'];

            $stmt2 = $pdo->prepare(
                'SELECT t.id, t.startnummer, t.name, t.name_p2
                 FROM praesenz_status p
                 JOIN teilnehmer t ON t.id = p.teilnehmer_id
                 WHERE p.runde_id = :runde_id
                   AND t.id NOT IN (
                       SELECT teilnehmer_id FROM bewertungen WHERE runde_id = :runde_id2 AND wertungsrichter_id = :wertungsrichter_id
                   )
                 ORDER BY p.freigegeben_am ASC'
            );
            $stmt2->execute(['runde_id' => $rundeId, 'runde_id2' => $rundeId, 'wertungsrichter_id' => $wertungsrichterId]);
            $offenFuerRichter = $stmt2->fetchAll();

            if ($offenFuerRichter !== []) {
                return [
                    'status' => 'bewerten',
                    'runde_id' => $rundeId,
                    'teilnehmer' => $offenFuerRichter,
                    'paarweise' => $this->tanztPaarweise($runde['kategorie_typ'], $runde['kategorie_name']),
                ];
            }

            if ($wartetAufRundeId === null) {
                $stmt3 = $pdo->prepare('SELECT COUNT(*) AS c FROM praesenz_status WHERE runde_id = :runde_id');
                $stmt3->execute(['runde_id' => $rundeId]);
                if ((int) $stmt3->fetch()['c'] > 0) {
                    $wartetAufRundeId = $rundeId;
                }
            }
        }

        if ($wartetAufRundeId !== null) {
            return ['status' => 'warten', 'runde_id' => $wartetAufRundeId];
        }

        return ['status' => 'leerlauf'];
    }

    /** @return array<int, float|null> teilnehmer_id => Gesamtpunktzahl dieser Runde (null für dnf) */
    public function ergebnisseFuerRunde(int $rundeId): array
    {
        return array_map(fn ($e) => $e['gesamt'], $this->ergebnisseDetailFuerRunde($rundeId));
    }

    /** @return array<int, array<string, ?float>> teilnehmer_id => [kriteriumsname => Durchschnitt dieser Runde] (leer bei dnf) */
    public function kriteriumsErgebnisseFuerRunde(int $rundeId): array
    {
        return array_map(fn ($e) => $e['kriterien'], $this->ergebnisseDetailFuerRunde($rundeId));
    }

    /** @return array<int, array{gesamt: ?float, kriterien: array<string, float>}> */
    private function ergebnisseDetailFuerRunde(int $rundeId): array
    {
        $pdo = Database::connection();
        $scoring = new ScoringService();

        $stmt = $pdo->prepare(
            'SELECT rt.teilnehmer_id, t.status, t.kategorie_id FROM runde_teilnehmer rt
             JOIN teilnehmer t ON t.id = rt.teilnehmer_id
             WHERE rt.runde_id = :runde_id'
        );
        $stmt->execute(['runde_id' => $rundeId]);
        $teilnehmer = $stmt->fetchAll();

        $ergebnisse = [];

        foreach ($teilnehmer as $t) {
            $teilnehmerId = (int) $t['teilnehmer_id'];

            if ($t['status'] === 'dnf') {
                $ergebnisse[$teilnehmerId] = ['gesamt' => null, 'kriterien' => []];
                continue;
            }

            $kStmt = $pdo->prepare('SELECT id, name FROM kriterien WHERE kategorie_id = :kategorie_id ORDER BY id');
            $kStmt->execute(['kategorie_id' => $t['kategorie_id']]);
            $kriterien = $kStmt->fetchAll();

            $kriteriumsDurchschnitte = [];
            $kriteriumsWerte = [];
            foreach ($kriterien as $kriterium) {
                $pStmt = $pdo->prepare(
                    'SELECT punkte FROM bewertungen WHERE runde_id = :runde_id AND teilnehmer_id = :teilnehmer_id AND kriterium_id = :kriterium_id'
                );
                $pStmt->execute(['runde_id' => $rundeId, 'teilnehmer_id' => $teilnehmerId, 'kriterium_id' => $kriterium['id']]);
                $werte = array_map('floatval', array_column($pStmt->fetchAll(), 'punkte'));

                if (count($werte) !== self::ANZAHL_RICHTER) {
                    throw new RuntimeException("Teilnehmer:in $teilnehmerId ist für Kriterium {$kriterium['id']} noch nicht vollständig bewertet.");
                }

                $durchschnitt = $scoring->berechneKriteriumsDurchschnitt($werte);
                $kriteriumsDurchschnitte[] = $durchschnitt;
                $kriteriumsWerte[$kriterium['name']] = $durchschnitt;
            }

            $fehlerDurchschnitt = $this->berechneFehlerDurchschnitt($rundeId, $teilnehmerId);
            $kriteriumsWerte['Fehler'] = $fehlerDurchschnitt;

            $ergebnisse[$teilnehmerId] = [
                'gesamt' => round($scoring->berechneRundenGesamt($kriteriumsDurchschnitte) + $fehlerDurchschnitt, 1),
                'kriterien' => $kriteriumsWerte,
            ];
        }

        return $ergebnisse;
    }

    /**
     * Durchschnittlicher Punktabzug aus dem "Fehler"-Feld (Feedback-Runde 2):
     * pro Richter werden alle Minuspunkt-Einträge summiert (Richter ohne
     * Eintrag zählen als 0), dann wie ein Kriterium über die 5 Richter
     * gemittelt (bester/schlechtester gestrichen). Ergebnis ist <= 0.
     */
    private function berechneFehlerDurchschnitt(int $rundeId, int $teilnehmerId): float
    {
        $stmt = Database::connection()->prepare(
            'SELECT SUM(wert) AS summe FROM fehler_eintraege
             WHERE runde_id = :runde_id AND teilnehmer_id = :teilnehmer_id
             GROUP BY wertungsrichter_id'
        );
        $stmt->execute(['runde_id' => $rundeId, 'teilnehmer_id' => $teilnehmerId]);
        $summenProRichter = array_map('floatval', array_column($stmt->fetchAll(), 'summe'));

        // Richter ohne jeden Eintrag zählen als 0 (keine Minuspunkte), damit
        // für die Durchschnittsberechnung immer fünf Werte vorliegen.
        $werte = array_pad($summenProRichter, -self::ANZAHL_RICHTER, 0.0);

        return (new ScoringService())->berechneKriteriumsDurchschnitt($werte);
    }

    /**
     * Vorläufige Rangliste einer laufenden Runde anhand der bisher abgegebenen
     * Bewertungen (Feedback-Runde 2 – jederzeit im Admin-Bereich einsehbar,
     * nicht erst nach Rundenabschluss). Teilnehmer:innen ohne jede Bewertung
     * oder im DNF-Status werden nicht aufgeführt.
     *
     * @return array<int, array{teilnehmer_id:int, gesamt:?float, rang:?int, kriterien:array<string,float>}>
     */
    public function zwischenranglisteFuerRunde(int $rundeId): array
    {
        $pdo = Database::connection();
        $scoring = new ScoringService();

        $stmt = $pdo->prepare(
            'SELECT rt.teilnehmer_id, t.status, t.kategorie_id FROM runde_teilnehmer rt
             JOIN teilnehmer t ON t.id = rt.teilnehmer_id
             WHERE rt.runde_id = :runde_id'
        );
        $stmt->execute(['runde_id' => $rundeId]);
        $teilnehmer = $stmt->fetchAll();

        $ergebnisse = [];
        $kriterienProTeilnehmer = [];

        foreach ($teilnehmer as $t) {
            if ($t['status'] === 'dnf') {
                continue;
            }

            $teilnehmerId = (int) $t['teilnehmer_id'];

            $kStmt = $pdo->prepare('SELECT id, name FROM kriterien WHERE kategorie_id = :kategorie_id ORDER BY id');
            $kStmt->execute(['kategorie_id' => $t['kategorie_id']]);
            $kriterien = $kStmt->fetchAll();

            $kriteriumsDurchschnitte = [];
            $kriteriumsWerte = [];

            foreach ($kriterien as $kriterium) {
                $pStmt = $pdo->prepare(
                    'SELECT punkte FROM bewertungen WHERE runde_id = :runde_id AND teilnehmer_id = :teilnehmer_id AND kriterium_id = :kriterium_id'
                );
                $pStmt->execute(['runde_id' => $rundeId, 'teilnehmer_id' => $teilnehmerId, 'kriterium_id' => $kriterium['id']]);
                $werte = array_map('floatval', array_column($pStmt->fetchAll(), 'punkte'));

                if ($werte === []) {
                    continue;
                }

                $durchschnitt = $scoring->berechnePartiellenDurchschnitt($werte);
                $kriteriumsDurchschnitte[] = $durchschnitt;
                $kriteriumsWerte[$kriterium['name']] = $durchschnitt;
            }

            if ($kriteriumsDurchschnitte === []) {
                continue;
            }

            $fehlerSummenStmt = $pdo->prepare(
                'SELECT SUM(wert) AS summe FROM fehler_eintraege
                 WHERE runde_id = :runde_id AND teilnehmer_id = :teilnehmer_id
                 GROUP BY wertungsrichter_id'
            );
            $fehlerSummenStmt->execute(['runde_id' => $rundeId, 'teilnehmer_id' => $teilnehmerId]);
            $fehlerSummen = array_map('floatval', array_column($fehlerSummenStmt->fetchAll(), 'summe'));
            $fehlerDurchschnitt = $fehlerSummen === [] ? 0.0 : $scoring->berechnePartiellenDurchschnitt($fehlerSummen);
            $kriteriumsWerte['Fehler'] = $fehlerDurchschnitt;

            $ergebnisse[$teilnehmerId] = round($scoring->berechneRundenGesamt($kriteriumsDurchschnitte) + $fehlerDurchschnitt, 1);
            $kriterienProTeilnehmer[$teilnehmerId] = $kriteriumsWerte;
        }

        $rangliste = (new RankingService())->rangliste($ergebnisse);
        foreach ($rangliste as &$eintrag) {
            $eintrag['kriterien'] = $kriterienProTeilnehmer[$eintrag['teilnehmer_id']] ?? [];
        }
        unset($eintrag);

        return $rangliste;
    }

    /** Schliesst eine Runde ab und erstellt bei Bedarf die nächste(n) Runde(n). */
    public function rundeAbschliessen(int $rundeId): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM runden WHERE id = :id');
        $stmt->execute(['id' => $rundeId]);
        $runde = $stmt->fetch();

        if (!$runde) {
            throw new RuntimeException('Runde nicht gefunden.');
        }

        // Bereits abgeschlossen (z.B. automatisch, sobald alle Richter fertig
        // waren, siehe pruefeUndFreigebenNaechste()) – nichts mehr zu tun, damit
        // ein zusätzlicher manueller Klick keine Folgerunden doppelt anlegt.
        if ($runde['status'] === 'abgeschlossen') {
            return;
        }

        if (!$this->istRundeVollstaendigBewertet($rundeId)) {
            throw new RuntimeException('Nicht alle freigegebenen Teilnehmer:innen wurden von allen 5 Richtern bewertet.');
        }

        $ergebnisse = $this->ergebnisseFuerRunde($rundeId);

        $update = $pdo->prepare("UPDATE runden SET status = 'abgeschlossen' WHERE id = :id");
        $update->execute(['id' => $rundeId]);

        // final2 ist bereits beim Start der Kategorie als 'offen' angelegt worden
        // (siehe starteErsteRunde()) – wird hier bewusst NICHT automatisch
        // aktiviert, der Organisator muss jede Runde manuell starten (siehe
        // starteNaechsteRunde()).
        match ($runde['typ']) {
            'vorrunde' => $this->nachVorrunde((int) $runde['kategorie_id'], $ergebnisse),
            'hoffnungsrunde' => $this->nachHoffnungsrunde((int) $runde['kategorie_id'], $ergebnisse),
            default => null,
        };
    }

    /**
     * Startet eine bereits angelegte, aber noch nicht gestartete Runde (status
     * 'offen') – z.B. final2, oder die Hoffnungsrunde/das Finale, nachdem die
     * jeweilige Vorgänger-Runde automatisch abgeschlossen wurde. Das Starten
     * jeder einzelnen Runde ist bewusst ein manueller Schritt des Organisators,
     * es gibt keine automatische Weiterschaltung.
     */
    public function starteNaechsteRunde(int $rundeId): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM runden WHERE id = :id');
        $stmt->execute(['id' => $rundeId]);
        $runde = $stmt->fetch();

        if (!$runde) {
            throw new RuntimeException('Runde nicht gefunden.');
        }

        if ($runde['status'] !== 'offen') {
            throw new RuntimeException('Diese Runde kann nicht gestartet werden (Status: ' . $runde['status'] . ').');
        }

        $pdo->prepare("UPDATE runden SET status = 'laufend' WHERE id = :id")->execute(['id' => $rundeId]);
        $this->pruefeUndFreigebenNaechste($rundeId);
    }

    /**
     * Ob die erste Runde einer Kategorie aktuell noch abgebrochen werden kann
     * (Plan_Feedback_Umsetzung.md 12.3) – für die Anzeige des Abbrechen-Buttons.
     */
    public function kannRundeAbbrechen(int $rundeId): bool
    {
        try {
            $this->pruefeRundeAbbrechbar($rundeId);
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * Bricht die erste Runde einer Kategorie ab, solange noch keine einzige
     * Bewertung abgegeben wurde (Plan_Feedback_Umsetzung.md 12.3). Nur für die
     * erste Runde (vorrunde/final1, inkl. des dabei bereits angelegten
     * final2-Geschwisters) möglich: Folgerunden entstehen automatisch aus einer
     * bereits abgeschlossenen – und damit nicht mehr rückgängig machbaren –
     * Vorgänger-Runde, für sie gibt es daher bewusst keinen Abbruch.
     */
    public function rundeAbbrechen(int $rundeId): void
    {
        $runde = $this->pruefeRundeAbbrechbar($rundeId);
        $kategorieId = (int) $runde['kategorie_id'];

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id FROM runden WHERE kategorie_id = :kategorie_id');
        $stmt->execute(['kategorie_id' => $kategorieId]);
        $rundenIds = array_map('intval', array_column($stmt->fetchAll(), 'id'));

        $pdo->beginTransaction();
        foreach ($rundenIds as $id) {
            $pdo->prepare('DELETE FROM praesenz_status WHERE runde_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM bewertungen WHERE runde_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM runde_teilnehmer WHERE runde_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM runden WHERE id = :id')->execute(['id' => $id]);
        }
        $pdo->commit();
    }

    /** @return array<string, mixed> die Runde, falls abbrechbar */
    private function pruefeRundeAbbrechbar(int $rundeId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM runden WHERE id = :id');
        $stmt->execute(['id' => $rundeId]);
        $runde = $stmt->fetch();

        if (!$runde) {
            throw new RuntimeException('Runde nicht gefunden.');
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM bewertungen WHERE runde_id = :id');
        $stmt->execute(['id' => $rundeId]);
        if ((int) $stmt->fetch()['c'] > 0) {
            throw new RuntimeException('Runde kann nicht abgebrochen werden: Es wurde bereits mindestens eine Bewertung abgegeben.');
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM runden WHERE kategorie_id = :kategorie_id AND status = 'abgeschlossen'");
        $stmt->execute(['kategorie_id' => $runde['kategorie_id']]);
        if ((int) $stmt->fetch()['c'] > 0) {
            throw new RuntimeException('Runde kann nicht abgebrochen werden: Für diese Kategorie wurde bereits eine vorherige Runde abgeschlossen.');
        }

        return $runde;
    }

    private function nachVorrunde(int $kategorieId, array $ergebnisse): void
    {
        $rangliste = (new RankingService())->rangliste($ergebnisse);
        $aktive = array_values(array_filter($rangliste, fn ($r) => $r['gesamt'] !== null));

        $n = count($aktive);
        $direktAnzahl = (int) ceil($n / 3);

        // Bei Punktgleichheit an der Aufstiegsgrenze steigen alle Punktgleichen
        // zusätzlich auf (Quote ist Minimum, siehe Feedback-Runde 2).
        $direktAnzahl = $this->erweitereUmPunktgleiche($aktive, $direktAnzahl);

        $direkte = array_slice($aktive, 0, $direktAnzahl);
        $rest = array_slice($aktive, $direktAnzahl);

        $direkteIds = array_map(fn ($r) => (int) $r['teilnehmer_id'], $direkte);
        shuffle($direkteIds);

        if (count($rest) === 0) {
            $finalId = $this->insertRunde($kategorieId, 'final', 'offen', 2);
            $this->setRundeTeilnehmer($finalId, $direkteIds);
            return;
        }

        $restIds = array_map(fn ($r) => (int) $r['teilnehmer_id'], $rest);
        // Reihenfolge der Hoffnungsrunde ist zufällig, nicht identisch zur
        // Vorrunden-Ranglisten-Reihenfolge (Plan_Feedback_Umsetzung.md 12.2).
        shuffle($restIds);

        $hoffnungsrundeId = $this->insertRunde($kategorieId, 'hoffnungsrunde', 'offen', 2);
        $this->setRundeTeilnehmer($hoffnungsrundeId, $restIds);

        $finalId = $this->insertRunde($kategorieId, 'final', 'offen', 3);
        $this->setRundeTeilnehmer($finalId, $direkteIds);
    }

    private function nachHoffnungsrunde(int $kategorieId, array $ergebnisse): void
    {
        $rangliste = (new RankingService())->rangliste($ergebnisse);
        $aktive = array_values(array_filter($rangliste, fn ($r) => $r['gesamt'] !== null));

        $n = count($aktive);
        $qualifyAnzahl = (int) ceil($n / 2);
        // Bei Punktgleichheit an der Aufstiegsgrenze steigen alle Punktgleichen
        // zusätzlich auf (Quote ist Minimum, siehe Feedback-Runde 2).
        $qualifyAnzahl = $this->erweitereUmPunktgleiche($aktive, $qualifyAnzahl);
        $qualifizierte = array_slice($aktive, 0, $qualifyAnzahl);
        $qualifizierteIds = array_map(fn ($r) => (int) $r['teilnehmer_id'], $qualifizierte);
        shuffle($qualifizierteIds);

        $pdo = Database::connection();
        $finalStmt = $pdo->prepare("SELECT id FROM runden WHERE kategorie_id = :kategorie_id AND typ = 'final'");
        $finalStmt->execute(['kategorie_id' => $kategorieId]);
        $finalRunde = $finalStmt->fetch();

        if (!$finalRunde) {
            throw new RuntimeException('Keine Final-Runde für diese Kategorie gefunden.');
        }

        $finalId = (int) $finalRunde['id'];

        $maxStmt = $pdo->prepare('SELECT IFNULL(MAX(reihenfolge), 0) AS m FROM runde_teilnehmer WHERE runde_id = :runde_id');
        $maxStmt->execute(['runde_id' => $finalId]);
        $reihenfolge = (int) $maxStmt->fetch()['m'];

        $insert = $pdo->prepare('INSERT OR IGNORE INTO runde_teilnehmer (runde_id, teilnehmer_id, reihenfolge) VALUES (:runde_id, :teilnehmer_id, :reihenfolge)');
        foreach ($qualifizierteIds as $teilnehmerId) {
            $reihenfolge++;
            $insert->execute(['runde_id' => $finalId, 'teilnehmer_id' => $teilnehmerId, 'reihenfolge' => $reihenfolge]);
        }
    }

    /**
     * Erweitert eine Aufstiegs-Quote, falls an der Grenze Punktgleichheit
     * herrscht: alle Teilnehmer:innen mit demselben Gesamtscore wie der/die
     * letzte innerhalb der Quote steigen zusätzlich auf (Feedback-Runde 2 –
     * "eher alle Punktgleiche kommen weiter"). $aktive muss absteigend nach
     * 'gesamt' sortiert sein (wie von RankingService::rangliste() geliefert).
     *
     * @param array<int, array{teilnehmer_id:int, gesamt:?float}> $aktive
     */
    private function erweitereUmPunktgleiche(array $aktive, int $anzahl): int
    {
        $n = count($aktive);
        if ($anzahl >= $n || $anzahl <= 0) {
            return $anzahl;
        }

        $grenzScore = $aktive[$anzahl - 1]['gesamt'];
        while ($anzahl < $n && $aktive[$anzahl]['gesamt'] === $grenzScore) {
            $anzahl++;
        }

        return $anzahl;
    }

    private function insertRunde(int $kategorieId, string $typ, string $status, int $reihenfolge): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO runden (kategorie_id, typ, status, reihenfolge) VALUES (:kategorie_id, :typ, :status, :reihenfolge)'
        );
        $stmt->execute(['kategorie_id' => $kategorieId, 'typ' => $typ, 'status' => $status, 'reihenfolge' => $reihenfolge]);

        return (int) $pdo->lastInsertId();
    }

    /** @param int[] $teilnehmerIds */
    private function setRundeTeilnehmer(int $rundeId, array $teilnehmerIds): void
    {
        $pdo = Database::connection();
        $insert = $pdo->prepare('INSERT INTO runde_teilnehmer (runde_id, teilnehmer_id, reihenfolge) VALUES (:runde_id, :teilnehmer_id, :reihenfolge)');

        foreach (array_values($teilnehmerIds) as $index => $teilnehmerId) {
            $insert->execute(['runde_id' => $rundeId, 'teilnehmer_id' => $teilnehmerId, 'reihenfolge' => $index + 1]);
        }
    }
}
