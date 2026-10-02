# ORBV Turnier-Software

Software für den Ostschweizer Rock'n'Roll Tanzverband (orbv.ch) zur Anmeldung,
Durchführung und Auswertung von Tanzturnieren.

Der vollständige Projektplan (Architektur, Tech-Stack, Datenmodell, Phasen)
steht in [`docs/turnier-plan.md`](docs/turnier-plan.md).

## Struktur (Monorepo)

- `public-website/` – Öffentliche Anmelde-Website (Laravel 13 / PHP 8.4, MySQL), lokal via Laravel Sail
- `pi-software/` – Offline-Turniersoftware für den Raspberry Pi (Plain PHP 8.4, SQLite), lokal via Docker
- `docs/` – Projektdokumentation

## Lokale Entwicklung

Beide Systeme sind unabhängig voneinander per Docker startbar.

### public-website (Laravel Sail)

```bash
cd public-website
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

`artisan storage:link` legt den Symlink `public/storage → storage/app/public`
an; ohne ihn werden hochgeladene Dateien (z.B. das Vereins-/Verband-Logo unter
„Header-Einstellungen") zwar gespeichert, aber nicht ausgeliefert (404). Der
Symlink liegt nicht im Git-Repo (`.gitignore`) und muss daher nach jedem
frischen Checkout einmalig neu erzeugt werden.

Danach erreichbar unter http://localhost. Beim ersten Aufruf von `/admin`
führt ein Setup-Wizard einmalig durch die Anlage des Master-Admin-Zugangs
(analog zum Muster der Pi-Software). Vereins-Logins legt der Admin danach
unter „Vereine" an.

Zwei getrennte Logins/Guards: `/admin/login` (Turnierbüro/Verband) und
`/verein/login` (Vereine, Anmeldung von Tänzer:innen).

Hinweis: `FORWARD_DB_PORT=3307` in `.env`/`.env.example`, falls Port 3306
lokal bereits von einer anderen MySQL-Instanz belegt ist.

### pi-software

```bash
cd pi-software
docker-compose up -d
docker-compose exec app php database/migrate.php   # SQLite-Schema anlegen

npm install
npm run build                                       # Tailwind-CSS kompilieren
```

Danach erreichbar unter http://localhost:8080. Beim ersten Aufruf führt ein
Setup-Wizard einmalig durch die Anlage des Organisator-Admin-Zugangs.

Tests (eigener, Composer-freier Mini-Test-Runner):

```bash
docker-compose exec app php tests/run.php
```

In Produktion läuft die Pi-Software offline auf einem Raspberry Pi 3 B+ mit
nginx + PHP-FPM 8.4 + SQLite (siehe `docs/turnier-plan.md`, Abschnitt 2). Vor
dem Deployment `npm run build` ausführen und das kompilierte
`public/css/app.css` mitausliefern.

### Drucker (Startliste & Endrangliste, siehe Plan_Feedback_Umsetzung.md 10.2)

Startliste und Endrangliste werden als normale HTML-Seiten mit `@media print`-
Styles gerendert; gedruckt wird über den Browser-Druckdialog (Button
"Drucken" bzw. `window.print()`) – es gibt kein separates PDF-Rendering.

Der USB-Drucker hängt am Pi, bedient wird die Pi-Software aber typischerweise
von einem anderen Gerät im lokalen Netz (Laptop/Tablet des Turnierbüros) aus
per Browser. Damit dessen Browser-Druckdialog den am Pi hängenden Drucker
anbieten kann, muss der Drucker über CUPS im lokalen Netz freigegeben werden:

- Raspberry Pi OS Lite bringt CUPS **nicht** vorinstalliert mit (Headless-Image).
  Einmalig installieren: `sudo apt install cups printer-driver-all` (deckt die
  meisten gängigen USB-Drucker über Gutenprint/IPP-Everywhere ab; bei exotischen
  Modellen ggf. herstellerspezifisches Treiberpaket nötig – vorher mit dem
  tatsächlichen Druckermodell prüfen).
- Freigabe für das lokale Netz aktivieren: `sudo cupsctl --share-printers`,
  Admin-User zur Gruppe `lpadmin` hinzufügen, Drucker unter
  `http://<pi-ip>:631` oder per `lpadmin` einrichten und als Standard setzen.
- Anschliessend bindet jedes Gerät im selben Netz den freigegebenen Drucker
  wie einen normalen Netzwerkdrucker ein (IPP) und kann via Browser-Druckdialog
  darauf drucken – unabhängig davon, dass er physisch per USB am Pi hängt.

Dies ist eine einmalige Hardware-/Netzwerk-Einrichtung vor Ort und lässt sich
nicht im Code vorwegnehmen; vor dem ersten Turnier mit dem tatsächlichen
Drucker-Modell durchtesten.
