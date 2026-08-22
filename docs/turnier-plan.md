# ORBV Turnier-Software – Projektplan

**Für:** ORBV – Ostschweizer Rock'n'Roll und Boogie Woogie Verband (orbv.ch)
**Zweck:** Anmeldung, Durchführung und Auswertung von Tanzturnieren
**Basiert auf:** Gesprächen mit Sascha sowie der Spezifikation "ORBV Turnier App" von Jan Kläger (2026)
**Öffentlicher Bereich läuft auf:** Subdomain `turnier.orbv.ch` (ersetzt orbv.ch nicht, läuft eigenständig daneben)

---

## 1. Architektur-Überblick

Das Projekt besteht aus **zwei getrennten Systemen**, die über einen manuellen Datenexport/-import verbunden sind (kein direkter Netzwerkzugriff zwischen ihnen nötig – wichtig, da der Pi vor Ort offline läuft):

```
┌─────────────────────────┐         CSV-Export/Import        ┌──────────────────────────┐
│  Öffentliche Website     │  ────────────────────────────>   │  Raspberry Pi Software   │
│  (Webhosting, online)    │      (Laptop als Zwischenträger)  │  (lokal, offline, WLAN-AP)│
│                          │                                    │                          │
│  - Vereins-Login         │                                    │  - Turnierdaten-Import  │
│  - Turnier-Anmeldung     │                                    │  - Wertungsrichter-Login │
│  - Admin: Teilnehmer-    │                                    │    (QR-Code + Code)     │
│    liste verwalten       │                                    │  - Bewertungssystem     │
│  - Nachträgliche         │                                    │  - Rundenlogik/Ranking  │
│    Resultatsanzeige      │  <───────────────────────────────  │  - Ergebnis-Export      │
└─────────────────────────┘      Resultate (nach Turnier)      └──────────────────────────┘
```

**Warum getrennte Systeme statt einer direkten Verbindung?**
Der Pi hat vor Ort keinen Internetzugang. Eine Live-Synchronisation wäre unnötig komplex und ein Risiko (Turnierabbruch bei Verbindungsproblemen). Der Datenaustausch läuft stattdessen über Dateien (CSV), die ein Administrator manuell überträgt – einfach, robust, keine Abhängigkeit von Internetverbindungen während des Turniers.

---

## 2. Tech-Stack

| Komponente | Technologie | Begründung |
|---|---|---|
| Öffentliche Website | **Laravel (PHP 8.4) + MySQL/MariaDB** | Läuft auf vorhandenem Webhosting; Eloquent, Auth, Migrations sparen Zeit bei klassischem CRUD (Vereine, Anmeldungen); via Laravel Sail lokal per Docker entwickelbar |
| Pi-Software | **Plain PHP 8.4 + SQLite, einfaches eigenes Routing** | Bewusst schlank statt Framework – geringerer Ressourcenverbrauch auf 1GB RAM, keine Composer-Abhängigkeiten, die offline verwaltet werden müssten, höhere Zuverlässigkeit unter Live-Turnierbedingungen |
| Design (beide Systeme) | **Tailwind CSS**, vorab kompiliert (kein CDN) | Kein Laufzeit-Overhead; CDN-Version funktioniert offline auf dem Pi nicht, daher fertige CSS-Datei ins Repo |
| QR-Code-Erzeugung | PHP-Bibliothek (z.B. `endroid/qr-code`), **lokal eingebunden** | Muss ohne Internet funktionieren – keine externen QR-API-Aufrufe |
| Datenaustausch | **CSV-Export/Import** | Einfachstes Format, von Menschen lesbar/prüfbar, mit PHP (`fgetcsv`/`fputcsv`) ohne Zusatzbibliotheken verarbeitbar |
| Lokale Entwicklung | **Docker** (Laravel Sail für die Website, PHP-Dev-Server im Container für die Pi-Software) | Entwicklung auf dem Mac, sauber getrennte, reproduzierbare Umgebungen für beide unterschiedlichen Stacks |
| Versionierung | Git (GitHub, ein privates Monorepo) | Ein Repo mit den Ordnern `public-website/` und `pi-software/`; kein Branch-Modell, keine getrennten Repos – einfacherer Gesamtüberblick, einfacher für Claude Code über beide Teile hinweg zu arbeiten |

---

## 3. Rollenmodell

| Rolle | System | Zugriff |
|---|---|---|
| **Master-Admin** | Öffentliche Website | Ein Login, das nur der Software-Ersteller (Sascha) kennt; kann weitere Administratoren anlegen |
| **Administrator / ORBV / Turnierbüro** | Beide Systeme | Erstellt Vereins-Logins (inkl. Passwort, nur vom Admin änderbar), erstellt/verwaltet Turniere (CRUD), meldet Tänzer:innen nach Anmeldeschluss an/ab, steuert den Turnierbetrieb, veröffentlicht Ranglisten |
| **Verein** | Öffentliche Website | Login von Admin erstellt (Passwort nur vom Admin änderbar); meldet Teilnehmer:innen (Solo/Paar/Formation) bis 2 Wochen vor Turnier an- und ab; sieht nach dem Turnier Durchschnittswerte pro Kriterium für die eigenen Tänzer:innen |
| **Wertungsrichter** | Pi-Software | Kein Benutzername/Passwort – nur ein pro Turnier und Richter generierter Code; genau **5 Richter pro Turnier**; bewertet Teilnehmer:innen (0–10 pro Kriterium, Kriterien sind je nach Kategorie definiert) |
| **Angehörige / Öffentlichkeit** | Öffentliche Website | Kein Login; sehen nur die veröffentlichte Rangliste (Name + Rang, keine Punkte) |

---

## 4. Datenmodell (Kernentitäten)

### Öffentliche Website (MySQL) – gemäss ERD der Spezifikation

- `kategorie` (id, name, **typ: enum solo/paar/formation**) – existiert unabhängig vom Turnier
- `kategorie_bewertung` (fk_kategorie_id, bewertungskriterium) – zusammengesetzter PK; **Kriterien sind pro Kategorie definiert**, nicht global fix
- `verein` (id, name, email, username, password_hash) – Passwort min. 12 Zeichen inkl. Sonderzeichen, Zahl, Buchstaben; nur vom Admin änderbar
- `turnier` (id, name, beschreibung, adresse, datum, fk_verein_id) – `fk_verein_id` = organisierender Verein (Gastgeber); erstellt wird das Turnier aber nur vom Administrator
- `turnier_kategorie` (fk_turnier_id, fk_kategorie_id) – zusammengesetzter PK, n:m-Verknüpfung, welche Kategorien an welchem Turnier stattfinden
- `anmeldung_solo` (id, fk_turnier_kategorie, fk_verein_id, name, geburtsdatum)
- `anmeldung_paar` (id, fk_turnier_kategorie, fk_verein_id, name_p1, geburtsdatum_p1, name_p2, geburtsdatum_p2)
- `anmeldung_formation` (id, fk_turnier_kategorie, fk_verein_id, name, anzahl_taenzerinnen)

*Hinweis:* Ein Turnier wird erst ab 2 Wochen vor dem Datum den Vereinen zur Anmeldung angezeigt; Vereine können bis 2 Wochen vor dem Turnier an-/abmelden, danach nur noch der Administrator.

### Pi-Software (SQLite) – nach Download von turnier.orbv.ch befüllt, danach eigenständig

- `teilnehmer` (id, startnummer, kategorie_id, typ: solo/paar/formation, name(n), geburtsdatum(en)/anzahl, status: aktiv/-99) – Startnummern fortlaufend pro Kategorie, bleiben das ganze Turnier gleich
- `kategorien` (id, name, typ)
- `kriterien` (id, kategorie_id, name) – pro Kategorie definiert, aus der Website übernommen; Beispielkriterien: *Tanz, Ausdruck, Technik*
- `runden` (id, kategorie_id, typ: vorrunde/hoffnungsrunde/final1/final2, status, reihenfolge)
- `wertungsrichter` (id, turnier_code) – genau 5 pro Turnier, kein Name/Login nötig, nur Code
- `bewertungen` (id, runde_id, teilnehmer_id, wertungsrichter_id, kriterium_id, punkte, abgesendet_am) – **einmal geschrieben, nie mehr veränderbar**
- `praesenz_status` (welche Startnummern gerade "offen" zur Bewertung sind – für die "zwei gleichzeitig"-Funktion, u.a. Pflicht bei Solo Dance Einsteiger und Paartanz Einsteiger)

---

## 5. Kernfunktionen im Detail

### 5.1 Anmeldephase (öffentliche Website)
- Admin legt Verein mit Login an (Name, E-Mail, Benutzername, Passwort min. 12 Zeichen)
- Admin erstellt Turnier (Name, Beschreibung, Adresse, Datum, organisierender Verein) und weist ihm Kategorien zu
- Ab 2 Wochen vor dem Datum wird das Turnier den Vereinen zur Anmeldung angezeigt
- Verein meldet Teilnehmer:innen an: Solo (Name, Geburtsdatum), Paar (Name+Geburtsdatum je Person), Formation (Name, Anzahl Tänzerinnen)
- Bis 2 Wochen vor dem Turnier können Vereine selbst an-/abmelden; danach nur noch der Administrator

### 5.2 Turnier-Setup (Pi-Software)
- Admin lädt die Turnierdaten von turnier.orbv.ch herunter (Datei-Download, z.B. CSV), verbindet Laptop mit dem Pi-WLAN und lädt die Datei in der Pi-Software hoch
- System importiert Teilnehmer:innen, vergibt fortlaufende Startnummern pro Kategorie, legt Kategorien/Kriterien/Runden an
- Admin trägt die 5 Wertungsrichter-Codes ein (oder lässt sie generieren), System erzeugt **einen gemeinsamen QR-Code**, der zur Code-Eingabe-Seite führt

### 5.3 Startseite der Pi-Software
Bewusst so simpel wie möglich gehalten: Beim Aufruf der Pi-Software (egal ob Organisator oder Richter) erscheint **nur eine grosse Auswahl mit zwei Optionen**:
- **"Organisatoren"** → führt direkt zum Organisator-Dashboard
- **"Kampfrichter"** → führt zur Code-Eingabe-Seite

Keine weitere Navigation, keine zusätzlichen Menüs auf dieser Startseite – reduziert Bedienfehler bei technisch weniger versierten Nutzern vor Ort.

### 5.4 Wertungsrichter-Login
- Richter wählt auf der Startseite "Kampfrichter" (entweder direkt, oder nach QR-Code-Scan landet er hier automatisch)
- Gibt seinen individuellen Turnier-Code ein (kein Benutzername/Passwort) → Session gestartet, nur für dieses Turnier gültig
- Es sind **genau 5 Wertungsrichter pro Turnier**; Codes werden vom Organisator im Dashboard pro Richter erstellt und den Richtern vor Ort mitgeteilt

### 5.5 Bewertung während des Turniers
- Richter wählt Startnummer(n) + Kategorie aus → öffnet Bewertungsraster (mehrere gleichzeitig möglich)
- Pro Kriterium: Punkte 0–10 eingeben
- Absenden → **unveränderbar gespeichert**
- Organisator-Dashboard zeigt live, welche Richter für die aktuelle(n) Startnummer(n) schon abgesendet haben
- Sobald alle fertig: Organisator gibt manuell die nächsten Teilnehmer frei

### 5.6 Punkteberechnung
1. Pro Kriterium: bester und schlechtester Wert der 5 Richterwerte streichen
2. Durchschnitt der verbleibenden **drei** Werte berechnen, auf 0.1 runden
3. Die (bereits gerundeten) Kriteriums-Durchschnitte pro Runde zusammenzählen → Gesamtpunktzahl der Runde

**Beispiel aus der Spezifikation** (3 Kriterien, 5 Richterwerte je Kriterium):
- Kriterium Tanz: 6, 4, 8, 10, 6 → bestes/schlechtestes (4, 10) streichen → Ø(6,8,6) = 6.7
- Kriterium Ausdruck: 7, 7, 8, 9, 6 → streichen (6, 9) → Ø(7,7,8) = 7.3
- Kriterium Technik: 3, 5, 7, 7, 8 → streichen (3, 8) → Ø(5,7,7) = 6.3
- **Gesamt: 6.7 + 7.3 + 6.3 = 20.3**

### 5.7 Rundenlogik
- **≤ 6 Anmeldungen pro Kategorie:** zwei Finalrunden (Final 1 + Final 2), alle tanzen zweimal. Endrangliste = Durchschnitt von Final 1 und Final 2, auf 0.1 gerundet
- **≥ 7 Anmeldungen:** zuerst Vorrunde (alle tanzen), danach:
  - oberes Drittel (aufgerundet) → direkt ins Finale
  - Rest → Hoffnungsrunde → aufgerundet obere Hälfte → ebenfalls ins Finale
  - Alle Finalist:innen tanzen **ein** gemeinsames Finale; nur dieses Finalergebnis zählt für die Endrangliste
- Startliste wird nach jeder Runde neu generiert (nur ein Teil tanzt evtl. weiter); bei Final 1/Final 2 wird einfach die Reihenfolge der ersten Runde umgekehrt
- In den Kategorien *Solo Dance Einsteiger* und *Paartanz Einsteiger* tanzen jeweils zwei Personen/Paare gleichzeitig – Bewertungs-UI muss das abbilden (mehrere offene Raster)
- Nicht mehr antretende Teilnehmer:innen: bei allen Kriterien der Wert `-99`, bleiben in der Liste sichtbar (z.B. bei Verletzung)

### 5.8 Endrangliste
- **≤6 Teilnehmer-Fall:** Durchschnitt von Final 1 und Final 2, auf 0.1 gerundet
- **≥7 Teilnehmer-Fall:** nur das Ergebnis der einen Finalrunde zählt
- **Gleichstand:** ist erlaubt und muss nicht verhindert werden (z.B. zwei 1. Plätze) – in diesem Fall wird der nächste Platz trotzdem korrekt weitergezählt (nach zwei 1. Plätzen kommt direkt Platz 3, kein doppelter 2. Platz)

### 5.9 Nach dem Turnier
- Ergebnis-Export vom Pi (Datei) → Admin lädt hoch auf turnier.orbv.ch
- Admin veröffentlicht die Rangliste
- **Zweistufige Sichtbarkeit:**
  - Öffentlich (Angehörige, kein Login): nur **Name + Rang**, keine Punktzahlen
  - Eingeloggte Vereine: zusätzlich die **Durchschnittswerte pro Kriterium** für die eigenen Tänzer:innen (nicht die Einzelwertung der einzelnen Richter)

---

## 6. Offene Punkte (bewusst vertagt)
- Präsentationsview für Beamer/Moderation – später (laut Spezifikation ebenfalls ein Wunsch der Angehörigen, aber nicht MVP)
- Exakte Bewertungskriterien pro Kategorie – Platzhalter (Tanz/Ausdruck/Technik), werden später final pro Kategorie definiert
- Genaues Dateiformat für Download/Upload zwischen turnier.orbv.ch und Pi – wird beim Bau der Import/Export-Funktion konkret festgelegt (CSV als naheliegendste Wahl)
- Zeitplan-Erstellung: laut Spezifikation wird der Zeitplan weiterhin von Hand erstellt, die App startet nur die einzelnen Runden manuell – kein automatischer Zeitplan-Generator im MVP

---

## 7. Umsetzungsplan (Phasen für Claude Code)

Reihenfolge: erst Repo/Grundgerüst, dann komplett die Pi-Software (Offline-App), erst danach die Laravel-Website.

**Phase 0 – Grundgerüst**
- [x] Raspberry Pi 3 B+ als WLAN-Access-Point eingerichtet
- [x] nginx + PHP-FPM 8.4 + SQLite auf dem Pi installiert (Produktivumgebung)
- [ ] Neues GitHub-Repo erstellen (privat)
- [ ] Monorepo-Basisstruktur anlegen: `public-website/`, `pi-software/`, `docs/`, `README.md`
- [ ] Docker-Setup für `pi-software/` (PHP-Dev-Server im Container, SQLite)
- [ ] Docker-Setup für `public-website/` (Laravel Sail: PHP, MySQL/MariaDB)
- [ ] Sicherstellen, dass beide Container-Setups lokal auf dem Mac unabhängig voneinander starten

**Phase 1 – Pi-Software: Startseite & Grundnavigation** ✅
- [x] Einfache Startseite mit zwei grossen Auswahlkacheln: **"Organisatoren"** und **"Kampfrichter"**
- [x] Routing: Organisatoren → Dashboard, Kampfrichter → Code-Eingabe-Seite
- [x] Bewusst keine weitere Navigation auf dieser Ebene
- [x] Tailwind-Build-Pipeline (Node/Tailwind CLI, kompiliertes CSS committed, kein CDN)

**Phase 2 – Pi-Software: Datenmodell & Import** ✅
- [x] SQLite-Schema (alle Tabellen aus Abschnitt 4, inkl. Kriterien pro Kategorie) anlegen
- [x] Import-Funktion für die von turnier.orbv.ch heruntergeladene Datei
- [x] Separate Funktion "Startnummernvergabe" (Turnierbüro löst das am Turniertag manuell aus, fortlaufend pro Kategorie)
- [x] Admin-Login fürs Organisatoren-Dashboard (Setup-Wizard beim ersten Start statt fixer Zugangsdaten)

**Phase 3 – Pi-Software: Wertungsrichter-Flow** ✅
- [x] Turnier-Code-Verwaltung: Organisator erstellt pro Richter einen 4-stelligen Code im Dashboard
- [x] Richter-Login per 4-stelligem Code (kein QR-Code – Code wird manuell eingegeben, siehe Entscheidung unten)
- [x] Bewertungsraster-UI (Startnummer/Kategorie wählen, mehrere Raster gleichzeitig möglich, Kriterien-Eingabe, Absenden = final/unveränderbar)

**Phase 4 – Pi-Software: Turniersteuerung & Berechnung** ✅
- [x] Organisator-Dashboard: Live-Status, welche Richter für aktuelle Startnummer(n) schon bewertet haben
- [x] Manuelle Freigabe der nächsten Startnummer(n) durch Organisator
- [x] Berechnungslogik (bester/schlechtester Wert streichen, Durchschnitt, Rundung)
- [x] Rundenlogik (Vorrunde → Final/Hoffnungsrunde → Final)
- [x] Endrangliste-Berechnung

**Phase 5 – Pi-Software: Export & Testlauf** ✅
- [x] Ergebnis-Export als CSV
- [x] Testlauf mit Beispieldaten (kleine Kategorie, wenige Teilnehmer), simulierter Vorrunde/Hoffnungsrunde/Final-Durchlauf zur Validierung der Rundenlogik (automatisierte Testsuite in `pi-software/tests/`)

> **Abweichung von der ursprünglichen Spezifikation:** Kein QR-Code (Abschnitt 5.2/5.4) – auf
> Wunsch entfällt die Bibliothek `endroid/qr-code`, Kampfrichter geben ihren
> 4-stelligen Code manuell auf der Startseite ein.

**Phase 6 – Öffentliche Website (Laravel): Anmeldesystem** ✅
- [x] Vereins-Login, Admin-Verwaltung (zwei getrennte Guards `admin`/`verein`, Setup-Wizard für Master-Admin analog Pi-Software)
- [x] Teilnehmer-/Formations-Anmeldeformulare (Solo/Paar/Formation, je nach Kategorie-Typ)
- [x] Teilnehmerlisten-Generierung + manuelle Anpassung (Admin kann pro Turnier/Kategorie jederzeit hinzufügen/bearbeiten/löschen)
- [x] CSV-Export für den Pi (spaltengleich zu `CsvImportService::REQUIRED_COLUMNS`, end-to-end mit `pi-software/admin/import` getestet)

> **Klärung mit dem User:** Anmeldefenster war im Plan wörtlich widersprüchlich
> (Sichtbarkeit "ab T-14" vs. Frist "bis T-14"). Umgesetzt: Turnier ist ab
> Erstellung durch den Admin sofort für Vereine sichtbar; Selbstverwaltung
> (an-/abmelden) ist bis 2 Wochen vor dem Turnierdatum möglich, danach nur
> noch der Administrator (`Turnier::istAnmeldungOffenFuerVerein()`).

**Phase 7 – Öffentliche Website: Ergebnisanzeige**
- CSV-Import der Resultate (aus Phase 5)
- Öffentliche/eingeloggte Ansicht der Ergebnisse pro Runde/Kategorie

---

## 8. Empfehlung für den Start mit Claude Code

Beginne mit **Phase 0**, um das Grundgerüst (Repo, Ordnerstruktur, beide Docker-Umgebungen) sauber aufzusetzen – das ist die Basis für alles Weitere. Danach komplett **Phase 1–5** (Pi-Software), da diese das kritischste und komplexeste Teilsystem ist (Bewertungslogik, Unveränderlichkeit der Bewertungen). Erst wenn die Pi-Software steht und mit Testdaten validiert ist, folgt die Laravel-Website (Phase 6–7) – technisch einfacher (klassisches CRUD), profitiert aber vom bereits feststehenden CSV-Format aus der Pi-Software.
