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
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

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
