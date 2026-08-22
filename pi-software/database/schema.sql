-- ORBV Pi-Software – SQLite-Schema
-- Siehe docs/turnier-plan.md Abschnitt 4 ("Pi-Software").

CREATE TABLE IF NOT EXISTS admin (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS kategorien (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    typ TEXT NOT NULL CHECK (typ IN ('solo', 'paar', 'formation'))
);

CREATE TABLE IF NOT EXISTS kriterien (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kategorie_id INTEGER NOT NULL REFERENCES kategorien(id),
    name TEXT NOT NULL,
    UNIQUE (kategorie_id, name)
);

CREATE TABLE IF NOT EXISTS teilnehmer (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    startnummer INTEGER,
    kategorie_id INTEGER NOT NULL REFERENCES kategorien(id),
    typ TEXT NOT NULL CHECK (typ IN ('solo', 'paar', 'formation')),
    name TEXT NOT NULL,
    geburtsdatum TEXT,
    name_p2 TEXT,
    geburtsdatum_p2 TEXT,
    anzahl_taenzerinnen INTEGER,
    verein_name TEXT,
    status TEXT NOT NULL DEFAULT 'aktiv' CHECK (status IN ('aktiv', '-99'))
);

CREATE TABLE IF NOT EXISTS runden (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kategorie_id INTEGER NOT NULL REFERENCES kategorien(id),
    typ TEXT NOT NULL CHECK (typ IN ('vorrunde', 'hoffnungsrunde', 'final1', 'final2', 'final')),
    status TEXT NOT NULL DEFAULT 'offen' CHECK (status IN ('offen', 'laufend', 'abgeschlossen')),
    reihenfolge INTEGER NOT NULL
);

-- Startliste je Runde: welche Teilnehmer:innen in dieser Runde antreten und
-- in welcher Reihenfolge (bei final1/final2 wird die Reihenfolge umgekehrt).
CREATE TABLE IF NOT EXISTS runde_teilnehmer (
    runde_id INTEGER NOT NULL REFERENCES runden(id),
    teilnehmer_id INTEGER NOT NULL REFERENCES teilnehmer(id),
    reihenfolge INTEGER NOT NULL,
    PRIMARY KEY (runde_id, teilnehmer_id)
);

CREATE TABLE IF NOT EXISTS wertungsrichter (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    turnier_code TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS bewertungen (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    runde_id INTEGER NOT NULL REFERENCES runden(id),
    teilnehmer_id INTEGER NOT NULL REFERENCES teilnehmer(id),
    wertungsrichter_id INTEGER NOT NULL REFERENCES wertungsrichter(id),
    kriterium_id INTEGER NOT NULL REFERENCES kriterien(id),
    punkte REAL NOT NULL,
    abgesendet_am TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (runde_id, teilnehmer_id, wertungsrichter_id, kriterium_id)
);

CREATE TABLE IF NOT EXISTS praesenz_status (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    runde_id INTEGER NOT NULL REFERENCES runden(id),
    teilnehmer_id INTEGER NOT NULL REFERENCES teilnehmer(id),
    freigegeben_am TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (runde_id, teilnehmer_id)
);
