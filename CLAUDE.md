# trideskapp

Portal für Wettkampf-Anmeldungen, Helferportal und Wettkampf-Webseiten eines Triathlon-Veranstalters
(Volkstriathlon, BerlinWoMen, Schüler-Duathlon).

**Maßgeblich ist das Pflichtenheft: `docs/toc.md`.** Jede Anforderung hat eine ID
(W-xx Wettkampf, H-xx Helfer, C-xx CMS, B-xx Basisfunktionen). Beziehe dich in Plänen, Commits und
Tests auf diese IDs. Bei Widersprüchen zwischen Code und Pflichtenheft: nachfragen, nicht raten.

## Stack

- PHP 8.4 (Produktion ≥ 8.3), Laravel 13, Filament 5, MariaDB
- spatie/laravel-permission, spatie/laravel-medialibrary, spatie/laravel-activitylog
- Frontend: Blade, SCSS über Vite, Vanilla JS (ES-Module), Leaflet + OpenStreetMap. Kein Tailwind,
  kein Alpine, kein Livewire im öffentlichen Frontend (Filament nutzt sie intern nur im Admin).
- PDF: TCPDF + setasign/FPDI (bestehender Urkundendruck). Excel: PhpSpreadsheet.
- Zahlung: PayPal REST API (Orders v2); SEPA-Lastschrift ohne Zahlungsdienstleister (Mandat + Excel-Export).
- Tests: Pest. Stil: Pint. Statische Analyse: Larastan.

## Entwicklung (DDEV)

Alle Befehle laufen über ddev:

- `ddev start` / `ddev launch`
- `ddev composer …`, `ddev artisan …`, `ddev npm …`
- Tests: `ddev artisan test` (bzw. `ddev exec vendor/bin/pest`)
- Stil: `ddev exec vendor/bin/pint`
- Analyse: `ddev exec vendor/bin/phpstan analyse`
- Neu aufsetzen: `ddev artisan migrate:fresh --seed`
- Mails lokal: Mailpit (`ddev mailpit`)

## Betrieb (Ziel: Shared Hosting)

- Kein dauerhafter Queue-Worker: Queue-Treiber `database`, Abarbeitung über den Scheduler per Cron.
- Kein Node auf dem Server: Assets werden vor dem Deployment gebaut.
- DocumentRoot zeigt auf `/public`.

## Struktur

- `app/Domain/Competition/` – Wettkampf, Ausgabe, Wettbewerb, Anmeldung, Startnummern, Ergebnisse
- `app/Domain/Volunteer/` – Helfende, OE (Baum), Posten, Tagesbesetzung, Zuordnung
- `app/Domain/Messaging/` – Nachrichten, Mailvorlagen mit Platzhaltern, Versandprotokoll
- `app/Domain/Content/` – Seiten, Blöcke, Medien
- `app/Integrations/Payment/` – `PaymentGateway`-Interface, PayPal-Adapter, SEPA-Mandate
- `app/Integrations/Certificate/` – Urkundendruck (XML-Konfiguration + PDF-Vorlage)
- `app/Integrations/Results/` – `ResultSource`-Interface, Synchronisation aus der Alt-Ergebnis-DB
- `docs/data/wk-zuordnung.csv` – geprüfte Zuordnung der 98 historischen wk-Werte (Seed)

## Konventionen

- Code, Klassen, Tabellen und Spalten auf Englisch; UI-Texte auf Deutsch über Übersetzungsdateien.
- Begriffe: Wettkampf = `Competition`, Ausgabe = `Edition`, Wettbewerb = `Race`,
  Anmeldung = `Registration`, OE = `OrgUnit`, Posten = `Post`, Tagesbesetzung = `PostDay`,
  Zuordnung = `Assignment`, Helfende = `Volunteer`.
- Links nur über benannte Routen erzeugen, nie Pfade fest einbauen (C-01: spätere eigene Domains).
- Der aktuelle Wettkampf wird per Middleware aus dem Pfad ermittelt (`/{competition:slug}/…`).

## Regeln

- Erst planen, dann umsetzen. Reihenfolge: Migration → Model → Policy → Feature-Test → UI.
- Jede Anforderungs-ID bekommt mindestens einen Feature-Test.
- Rechte immer serverseitig über Policies prüfen, nie nur durch Ausblenden im UI.
- Personenbezogene Daten nur so weit speichern, wie das Pflichtenheft es verlangt (DSGVO).
- Keine externen CDNs oder Fonts; alles lokal ausliefern.
- Geld als Integer in Cent speichern.
- Zahlungs-Webhooks idempotent verarbeiten.
- Vor jedem Commit: Tests, Pint und PHPStan grün.

## Arbeitsweise

- Ein Arbeitspaket = ein Branch = eine Gruppe von Anforderungs-IDs (siehe Pflichtenheft 10.1 und 10.5).
- Änderungen an Zahlung, Rechten und Datenschutz werden vor dem Merge von einem Menschen geprüft.
