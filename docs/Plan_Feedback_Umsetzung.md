# ORBV Turnier App – Umsetzungsplan Feedback-Runde 1

**Kontext:** Der erste Prototyp (Phasen 1–7) wurde getestet. Dieses Dokument bündelt das
Feedback aus dem Test und definiert, wie es in die bestehende Codebasis eingebaut wird.
Alle offenen Fragen wurden vorab geklärt (siehe "Entscheidungen" pro Punkt).

**Betroffene Teile des Systems:**
- `public-website/` (Laravel, turnier.orbv.ch) – Admin-Bereich + öffentlicher Bereich
- `pi-software/` (PHP 8.4 + SQLite, Raspberry Pi) – Turnierbetrieb vor Ort

---

## Phase 8 – Datenmodell-Anpassungen (Grundlage für alles Weitere)

Diese Änderungen zuerst umsetzen, da spätere Phasen darauf aufbauen.

### 8.1 Kategorien fix hinterlegen
- Die 10 Kategorien werden **fest im System verankert** (Seed/Migration), keine freie
  Erstellung mehr möglich:
  - Solo Dance: Einsteiger, Mittel, Pro
  - Paartanz: Einsteiger, Mittel, Pro, Akrobatik
  - Formation: Einsteiger, Mittel, Pro
- Admin-UI "Kategorie erstellen" entfernen. "Kategorie bearbeiten" bleibt (nur Name/Typ/
  Bewertungskriterien editierbar), "Kategorie löschen" wird entfernt/deaktiviert.
- Jede Kategorie erhält eine fixe, unveränderliche ID (bereits im Datenmodell durch PK
  gegeben – hier explizit sicherstellen, dass diese ID nirgends neu vergeben/verändert
  werden kann und in CSV-Exporten/Pi-Sync stabil bleibt).

### 8.2 Startnummer-Logik ändern
- Bisherige Regel (fortlaufend **innerhalb** der Kategorie) wird ersetzt durch:
  **eine durchgehende Nummerierung über das gesamte Turnier**, kategorieunabhängig.
- Migration/Anpassung der Vergabe-Logik auf dem Pi.

### 8.3 Terminologie
- "Kampfrichter" → "Wertungsrichter" (Datenbank-Kommentare, UI-Texte, Variablennamen
  wo sinnvoll, mindestens alle sichtbaren Labels).
- Paartanz-Felder: "Person 1" / "Person 2" → "Tänzer:in 1" / "Tänzer:in 2" (Anmeldeformular
  Public + Admin-Erfassungsmaske + Datenbankspalten-Labels, sofern ohne Breaking Change
  möglich – sonst nur Anzeige-Labels).

### 8.4 DNF-Mechanismus (ersetzt -99-Konzept aus Spezifikation)
- Da -99 bisher nicht implementiert wurde, wird stattdessen direkt der neue Ansatz gebaut:
  - Button **"Nicht getanzt"** auf dem Bewertungsbogen jedes Wertungsrichters.
  - Bei Klick: im Hintergrund werden alle Bewertungskriterien dieser/s Tänzer*in mit 0
    Punkten gespeichert, Teilnahme wird intern als `dnf = true` markiert.
  - In der Rangliste erscheinen DNF-Teilnehmer:innen **zuunterst**, unabhängig von der
    Punktzahl, mit klarer "DNF"-Kennzeichnung statt Rang.

---

## Phase 9 – Public App (turnier.orbv.ch)

### 9.1 Vereins-Erfassungsmaske vs. Admin-Massenmutationsliste
- **Vereine:** eigene, fokussierte Erfassungsmaske pro Tänzer:in/Paar/Formation (wie
  bisher, nur mit angepassten Feldlabels gem. 8.3).
- **Admin:** neue/angepasste Ansicht als **Liste aller angemeldeten Teilnehmenden** eines
  Turniers mit Inline-Bearbeiten, Erfassen und Löschen (Massenmutation), statt der
  bisherigen Einzelerfassungsmaske.
- Beide greifen auf dieselben Backend-Endpunkte zu, UI unterscheidet sich.

### 9.2 CSV-Export "Turnier" umbenennen
- Bestehender CSV-Export (für Pi-Datenaustausch) bleibt funktional unverändert.
- Nur das Naming in der UI (Button-Label, Dateiname) anpassen auf einen klareren Begriff,
  z. B. "Turnier-Export (CSV)" – genauen Wortlaut mit Sascha kurz abstimmen beim Bauen.

### 9.3 Rangliste-Upload auf turnier.orbv.ch (neuer Mechanismus)
- **Wichtig:** Dies ist ein **zusätzlicher** Mechanismus, unabhängig vom bestehenden
  Offline-Upload-Workflow auf dem Pi (der bleibt wie er ist).
- Ablauf:
  1. Auf dem Pi kann nach Turnierende pro Kategorie eine Rangliste generiert und als
     Datei (CSV) heruntergeladen werden.
  2. Diese Datei wird anschliessend manuell vom Admin auf turnier.orbv.ch hochgeladen.
  3. Nach Upload wird die Rangliste dort veröffentlicht (Name + Rang, wie in der
     ursprünglichen Spezifikation festgelegt).
- Neuer Admin-Menüpunkt "Rangliste hochladen" pro Turnier/Kategorie.
- Format der Ranglisten-CSV auf dem Pi definieren (Kategorie, Name, Rang) – konsistent
  mit dem Parser auf der Public-Seite.

### 9.4 Header mit Logo und ORBV-Infos
- Neuer Header-Bereich (Public + evtl. Admin) mit:
  - Platzhalter für ORBV-Logo (Sascha liefert das Bild nach; Platzhalter-Grafik/Alt-Text
    vorerst einsetzen, Layout so bauen, dass Logo einfach ausgetauscht werden kann).
  - Kurze Infozeile/-block zum ORBV (Platzhaltertext, später anpassbar oder über
    Konfigurationsfeld im Admin editierbar – Entscheidung beim Bauen: statisch vs.
    editierbar, Vorschlag: einfaches Konfigurationsfeld im Admin, damit Sascha das Logo
    und den Text später selbst pflegen kann, ohne Code-Änderung).

---

## Phase 10 – Pi-Software: Startliste & Druck

### 10.1 Startliste pro Kategorie
- Startliste wird zu Beginn **pro Kategorie** erstellt (nicht mehr turnierweit als Ganzes).
- Solo Dance Einsteiger & Paartanz Einsteiger: zwei Tänzer:innen/Paare tanzen gleichzeitig
  (bereits in Spezifikation vorgesehen – hier nochmals sicherstellen, dass die Startlisten-
  Generierung das korrekt abbildet).

### 10.2 Druckfunktion
- Startliste und Endrangliste jeweils über **CSS-optimierte Druckansicht** (Browser-
  Druckdialog, `@media print`-Styles), kein separates PDF-Rendering.
- Drucker ist per USB am Pi angeschlossen (Standard-Systemdrucker über CUPS o. Ä. –
  technische Prüfung, ob auf Raspberry Pi OS Lite bereits vorhanden/nachinstallierbar).
- Startliste: einfach formatiertes Dokument, **separat pro Kategorie** druckbar.
- Endrangliste: druckbar für die Siegerehrung (nach Turnierende pro Kategorie).

---

## Phase 11 – Pi-Software: Wertungsrichter-Workflow (grösster Umbau)

### 11.1 Kein Übersichts-/Auswahlbildschirm mehr
- Wertungsrichter-Ansicht öffnet **direkt** den/die nächste(n) Tänzer:in/Paar aus der
  aktiven Startliste der laufenden Kategorie – kein manuelles Auswählen mehr.

### 11.2 Leerlauf-Zustand
- Falls aktuell kein Tänzer/keine Tänzerin "offen" ist (z. B. Runde noch nicht gestartet,
  oder alle bereits bewertet) → Infoseite anzeigen ("Warte auf nächste Runde" o. Ä.).

### 11.3 Synchroner Rundenablauf zwischen den 5 Richtern
- Nach Abgabe der eigenen Bewertung: **Warteseite**, bis alle 5 Wertungsrichter ihre
  Bewertung für den/die aktuelle(n) Tänzer:in abgegeben haben.
- Erst danach schalten alle gemeinsam zum/zur nächsten Tänzer:in weiter.
- Technische Umsetzung: **einfaches Polling** (z. B. alle 2 Sekunden Status abfragen), kein
  WebSocket/Push nötig – passt zum Offline-/lokalen Netzwerk-Kontext auf dem Pi.

### 11.4 Zwei gleichzeitige Bewertungsraster
- Gilt für **alle** Solo- und Paar-Kategorien (Einsteiger, Mittel, Pro, bei Paar zusätzlich
  Akrobatik) – **nicht** für Formation (Formation tanzt einzeln, ein Bewertungsraster).
- Wertungsrichter-Ansicht zeigt in diesen Fällen zwei Bewertungsraster parallel an (für
  die zwei gleichzeitig tanzenden Teilnehmenden/Paare).
- Warteseite (11.3) wartet entsprechend auf die Abgabe **beider** Bewertungen pro Richter,
  bevor der Richter als "fertig" gilt.

### 11.5 "Nicht getanzt"-Button
- Siehe 8.4 – auf jedem Bewertungsraster verfügbar, unabhängig ob Einzel- oder
  Doppel-Ansicht.

---

## Phase 12 – Turniermodi & Ablaufsteuerung

### 12.1 ≤ 6 Teilnehmende (2 Finalrunden)
- Neu: Reihenfolge in **Final 2 wird gegenüber Final 1 umgekehrt** (bereits in der
  allgemeinen Spezifikation für "nach jeder Runde" vorgesehen – hier explizit für den
  ≤6-Fall sicherstellen).

### 12.2 ≥ 7 Teilnehmende (Vorrunde → Final/Hoffnungsrunde → Final)
- 1. Drittel aus der Vorrunde kommt direkt in den Final (aufgerundet, wie in Spezifikation).
- **Neu:** Reihenfolge in der Hoffnungsrunde ist **zufällig**, nicht mehr identisch zur
  Vorrunden-Reihenfolge (Randomisierungs-Logik bei Hoffnungsrunden-Startlisten-
  Generierung einbauen).
- Aufgerundete bessere Hälfte der Hoffnungsrunde kommt zusätzlich in den Final
  (unverändert gem. Spezifikation).

### 12.3 Ablaufsteuerung Runden
- Rundenstart pro Kategorie und aktuelle Runde wird explizit durch den Organisator/
  Turnierbüro ausgelöst (Button im Pi-Admin-Bereich).
- Eine gestartete Runde kann **abgebrochen** werden, solange noch **keine** Bewertung von
  **keinem** Wertungsrichter abgegeben wurde (sobald eine erste Bewertung existiert, ist
  Abbruch nicht mehr möglich – zu klären/testen: klare Fehlermeldung beim Versuch).

### 12.4 Endrangliste – Hoffnungsrunden-Ausgeschiedene
- Teilnehmende, die in der Hoffnungsrunde ausgeschieden sind, werden in der
  Endrangliste **zuunterst** aufgelistet.
- **Sortierung innerhalb dieser Gruppe:** nach ihrer Punktzahl aus der Hoffnungsrunde
  (bestes Hoffnungsrunden-Ergebnis zuerst).

---

## Umsetzungsreihenfolge (Empfehlung für Claude Code)

1. **Phase 8** – Datenmodell/Grundlagen (Kategorien fix, Startnummer-Logik, Terminologie,
   DNF-Mechanismus) – alles andere baut darauf auf.
2. **Phase 11** – Wertungsrichter-Workflow (grösster funktionaler Umbau, sollte isoliert
   getestet werden können, bevor Turniermodi-Logik (12) darauf aufsetzt).
3. **Phase 12** – Turniermodi & Ablaufsteuerung (Rundenabbruch, Hoffnungsrunde
   randomisieren, Endrangliste-Sortierung).
4. **Phase 10** – Startliste & Druck (kann parallel zu 11/12 laufen, ist UI-seitig
   unabhängiger).
5. **Phase 9** – Public App (Vereins-/Admin-Masken, CSV-Naming, Rangliste-Upload, Header)
   – als letztes, da am wenigsten Abhängigkeiten zu Pi-Logik.

## Offene technische Detailfragen für Claude Code während der Umsetzung
- Exakter Wortlaut für CSV-Export-Button/Dateiname (9.2).
- Ob der Header-Infotext (9.4) statisch oder über ein Admin-Konfigurationsfeld gepflegt
  wird – Vorschlag: editierbares Feld, damit Logo/Text später ohne Code-Deploy angepasst
  werden können.
- Exaktes CSV-Format für den neuen Ranglisten-Upload (9.3) – muss zwischen
  Pi-Export und Public-Website-Import konsistent definiert werden.
- Prüfen, ob USB-Druckerunterstützung auf Raspberry Pi OS Lite bereits vorhanden ist
  oder ein CUPS-Setup zusätzlich nötig ist (10.2).

---

# Feedback-Runde 2

Nach dem ersten Testlauf (Phasen 8-12 oben) kam eine zweite Feedback-Runde zurück. Diese
Sektion dokumentiert die Umsetzung und hält explizit fest, welche Entscheidungen aus
Runde 1 dabei überschrieben wurden.

**Mit dem Nutzer vorab geklärt (ausgeklammert aus diesem Plan):** Kriterien-Umbenennung
und Altersverifikation bei der Anmeldung werden vorerst **nicht** umgesetzt.

## 13. Startliste
- Spaltenkopf "Paar" (Tabelle bei paarweise tanzenden Kategorien) → "Runde" umbenannt,
  Unterzeile mit dem Rundentyp unter der Überschrift entfernt
  (`pi-software/src/Views/admin/startliste.php`).
- Reihenfolge ist jetzt **immer zufällig**: `RoundService` mischt die Teilnehmer:innen
  vor dem Anlegen jeder Runde (`starteErsteRunde`, `nachVorrunde`, `nachHoffnungsrunde`).
  final2 bleibt dabei weiterhin die umgekehrte Reihenfolge von final1 (Runde-1-Spezifikation
  12.1 bleibt bestehen), da beide aus demselben gemischten Array abgeleitet werden.
- Neuer Button "Neu mischen" auf der Startliste (`RoundService::mischeStartliste()`,
  Route `POST /admin/runden/{rundeId}/startliste/mischen`) – jeder Klick erzeugt eine neue
  zufällige Reihenfolge, es gibt keinen "ursprünglichen" Zustand.

## 14. Zwischenrangliste während laufender Runde
- Neue Methode `RoundService::zwischenranglisteFuerRunde()`: vorläufige Rangliste anhand der
  bisher abgegebenen Bewertungen (ab 3 Richterwerten wird wie final bester/schlechtester
  gestrichen, darunter einfacher Durchschnitt). Neue Admin-Ansicht
  `admin/zwischenrangliste.php`, Route `GET /admin/runden/{rundeId}/zwischenrangliste`,
  verlinkt von der Rundensteuerung.

## 15. Tie-Break beim Aufstieg
- Bei Punktgleichheit genau an der Aufstiegsgrenze (Vorrunde→Final/Hoffnungsrunde,
  Hoffnungsrunde→Final) steigen **alle** Punktgleichen zusätzlich auf – die Quote
  (`ceil(n/3)` bzw. `ceil(n/2)`) ist damit ein Minimum, keine feste Obergrenze mehr
  (`RoundService::erweitereUmPunktgleiche()`, verwendet in `nachVorrunde()` und
  `nachHoffnungsrunde()`).

## 16. "Fehler"-Minuspunkte-Feld
- Neue Tabelle `fehler_eintraege` (pro Richter beliebig viele Einträge aus {0, -1, -5, -10}
  je Teilnehmer:in und Runde, additiv). UI im Bewertungsformular der Wertungsrichter
  (`judge/scoring_form.php`): Auswahlfeld + "+ hinzufügen", laufende Summenanzeige.
- Berechnung (`RoundService::berechneFehlerDurchschnitt()`): pro Richter werden alle
  Einträge summiert (fehlende Richter = 0), die 5 Richter-Summen werden wie ein Kriterium
  verrechnet (bester/schlechtester gestrichen, Rest gemittelt). Der Durchschnitt wird zur
  Gesamtpunktzahl der Runde addiert (ist ≤ 0) und als zusätzliches "Fehler"-Feld neben
  Tanz/Ausdruck/Technik/Gesamt angezeigt.

## 17. Hoffnungsrunde-Ausgeschiedene: gemeinsamer Rang
- **Überschreibt Runde-1-Punkt 12.4:** Statt individueller Rangnummern nach
  Hoffnungsrunden-Ergebnis erhalten jetzt **alle** in der Hoffnungsrunde ausgeschiedenen
  Teilnehmer:innen denselben Rang (`Anzahl Finalist:innen + 1`). Die Anzeige-Reihenfolge
  innerhalb dieser Gruppe bleibt weiterhin nach Hoffnungsrunden-Ergebnis sortiert
  (`RankingService::endranglisteFuerKategorie()`).

## 18. DNF nicht mehr auf der Rangliste
- **Überschreibt Runde-1-Punkt 8.4:** DNF-Teilnehmer:innen wurden bisher zuunterst mit
  "DNF" statt Rang angezeigt. Jetzt erscheinen sie **gar nicht** mehr auf der Rangliste –
  weder im Admin-Bereich (`admin/results.php`) noch im CSV-Export für turnier.orbv.ch
  (`CsvExportService`) – (`RankingService::rangliste()` filtert sie vollständig heraus).

## 19. Startnummernvergabe: Liste fehlender Nummern
- Neue Ansicht `admin/startnummern_fehlend.php` (Route
  `GET /admin/startnummern/fehlend`, verlinkt vom Dashboard): listet alle Teilnehmer:innen
  ohne zugewiesene Startnummer, für die manuelle Ausgabe der Nummern-Karten am Check-in.

## 20. Ersteinrichtung nicht erneut nach Reset
- `TurnierResetService::alleDatenLoeschen()` löscht die `admin`-Tabelle nicht mehr –
  der Wertungsbüro-Login bleibt nach einem Reset bestehen. `ResetController` leitet danach
  auf `/admin/login` statt `/admin/setup` weiter.

## 21. "Organisator" → "Wertungsbüro"
- Alle sichtbaren Labels umbenannt (Dashboard-Titel, Login-Seite, Ersteinrichtung,
  Hinweistexte). Routen (`/organisatoren`) und interne Code-Kommentare bleiben
  unverändert, es handelt sich um eine reine Anzeige-Änderung.

## 22. Einzelne Teilnehmer:innen löschen
- Neue Ansicht `admin/teilnehmer_liste.php` je Kategorie (Route
  `GET /admin/kategorien/{kategorieId}/teilnehmer`, verlinkt aus der Rundenübersicht) mit
  Lösch-Button je Teilnehmer:in (`POST /admin/teilnehmer/{teilnehmerId}/loeschen`,
  `TeilnehmerController`). Löscht die Person inkl. abhängiger Bewertungen/Fehler-Einträge/
  Rundenzuordnungen.

## 23. Veranstalter-Ansicht: Anzahl Personen pro Kategorie
- Die bestehende Admin-Turnieransicht (`admin/turniere/show.blade.php`) zeigt je Kategorie
  jetzt explizit die Anzahl angemeldeter Personen an (Solo = Anzahl Anmeldungen, Paar =
  Anzahl × 2, Formation = Summe `anzahl_taenzerinnen`).

## 24. Vereinsseite: Rang pro eigenem/er Tänzer:in
- `Verein/TurnierController::show()` lädt zusätzlich die hochgeladene Rangliste der
  Kategorie und gleicht sie über den Namen mit der eigenen Anmeldung ab (es gibt keine
  gemeinsame Teilnehmer-ID zwischen Pi und Public-Website). Bei Treffer wird der Rang als
  Badge neben dem Namen angezeigt (`verein/turniere/show.blade.php`). Eine Punktzahl wird
  nicht angezeigt, da das aktuelle CSV-Format (`kategorie_id,kategorie_name,rang,name,
  verein_name`) keine Punktzahl enthält.
