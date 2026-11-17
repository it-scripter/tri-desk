# Entwicklungsstand

Stand: 17.11.2026. Diese Datei ist die Übergabe zwischen Arbeitssitzungen: Was liegt wo, was ist geprüft,
was kommt als Nächstes. Sie wird am Ende jeder Sitzung fortgeschrieben. Fachliche Entscheidungen stehen
nicht hier, sondern im Pflichtenheft (`docs/toc.md`), die Begründungen im Code und in den Commits.

## Branches

Noch ist nichts in `main` gemergt. Die Branches bauen aufeinander auf und werden in dieser Reihenfolge
geprüft und gemergt:

| Nr. | Branch | Inhalt (Anforderungs-IDs) | Baut auf | Auf `origin` |
| --- | --- | --- | --- | --- |
| 1 | `feature/phase-1-fundament` | Setup, Rollen, Panels, Wettkampf-Kontext, Audit-Log (W-01, W-02, C-01, B-03) | `main` | ja |
| 2 | `feature/create-admin-command` | Befehl `users:create-admin`, Projekt-README | 1 | nein |
| 3 | `feature/phase-2a-preise-fristen` | Preisstufen, Zusatzoptionen, Storno-Regeln, Altersklassen (W-03, W-04, W-05) | 1 | nein |
| 4 | `feature/edition-copy` | Ausgabe aus Vorjahr kopieren (W-01, B-01); Spezifikation W-18 | 3 | nein |
| 5 | `feature/phase-2b1-anmeldung-modell` | Anmeldung: Datenmodell, Reservierung, Regeln (W-12, W-16, W-17, W-18, W-22) | 4 | nein |
| 6 | `feature/phase-2b2-anmeldeformular` | Öffentliches Formular, Zusatzfelder, Bestätigungsmail, Magic-Link (W-10, W-11, W-15) | 5 | nein |
| 7 | `feature/registration-test-mode` | Testmodus der Anmeldung; Einverständnis Minderjähriger zum Anmeldedatum (W-17) | 6 | ja |

Branch 2 zweigt direkt von Phase 1 ab und ist unabhängig von 3 bis 7. Diese Datei liegt auf Branch 7,
dem obersten des Stapels.

**Prüfung durch einen Menschen:** Laut `CLAUDE.md` vor jedem Merge nötig, wenn Zahlung, Rechte oder
Datenschutz betroffen sind. Das trifft auf alle sieben Branches zu (neue Rechte bzw. Policies; ab 5
personenbezogene Daten). Geprüft ist noch keiner.

## Was noch niemand angesehen hat

- Das öffentliche Anmeldeformular und den Testmodus im Browser, auch auf dem Smartphone. Getestet ist
  beides nur über Feature-Tests, die das HTML prüfen.
- Barrierefreiheit von Hand (Tastatur, Screenreader). Die Formulare sind dafür gebaut (Labels,
  `aria-describedby`, Fehlerübersicht mit Fokus), geprüft ist das nicht.
- Den Versand der Bestätigungsmail in Mailpit.

## Lokal nach dem Wechsel auf einen Branch

- `ddev artisan migrate` – die Migrationen sind nur additiv.
- `ddev artisan db:seed --class=RoleSeeder`, sobald ein Branch neue Rechte bringt (Branches 3, 4 und 5).
  Schreibt in die Datenbank und wird deshalb von Hand ausgeführt.
- Entwickler-Konten und Beispieldaten: `ddev artisan migrate:fresh --seed` (löscht die lokale Datenbank).
  Beispielpreise und Altersklassen liefert der `DemoPricingSeeder` ab Branch 3, nur lokal.
- Mails und geplante Aufgaben: `ddev artisan schedule:work`, Mails landen in Mailpit.
- Nach Änderungen an SCSS oder JS: `ddev npm run build`.
- Einrichtung und Konten stehen in der `README.md` (Branch 2).

## Erledigt

| ID | Was | Branch |
| --- | --- | --- |
| W-01, B-01 | Wettkampf mit Ausgaben; Ausgabe aus Vorjahr kopieren, Termine verschoben nach Wettkampftag | 1, 4 |
| W-02 | Wettbewerbe mit freier Disziplinfolge | 1 |
| W-03 | Preisstufen nach Datum, Zusatzoptionen mit Varianten und Kontingent | 3 |
| W-04 | Melde- und Ummeldeschluss, Storno-Regeln in Stufen | 1, 3 |
| W-05 | Altersklassen-Schemata, Berechnung nach Jahrgang, Wertung „divers“ | 3 |
| W-10 | Mehrstufiges Formular; Zahlung fehlt noch (2c) | 6 |
| W-11 | Zusatzfelder aus Katalog oder frei, je Person oder je Anmeldung | 6 |
| W-12 | Staffel- und Teamanmeldung mit allen Mitgliedern | 5 |
| W-15 | Bestätigungsmail mit Magic-Link; dort Ansehen und Stornieren | 6 |
| W-16 | Dublettenprüfung | 5 |
| W-17 | Einverständnis der Erziehungsberechtigten, maßgeblich zum Anmeldedatum | 5, 7 |
| W-18 | Wertung von Staffeln und Teams aus den Mitgliedern | 4, 5 |
| W-22 | Reservierung mit Zahlungsfrist, Verfall per Scheduler; Bestätigung durch Zahlung fehlt (2c) | 5 |
| C-01 | Wettkampf-Kontext aus Pfad bzw. Domain | 1 |
| B-03 | Audit-Log | 1 |

Dazu ohne eigene ID: Testmodus der Anmeldung (Branch 7), Befehl für den ersten Super-Admin (Branch 2).

## Als Nächstes

1. **Paket 2c „Zahlung“** (W-20 bis W-22, W-25 bis W-27, B-16) – fehlt für den Anmeldestart am
   15.02.2027. Voraussetzung: PayPal-Sandbox-Zugangsdaten (Client-ID, Secret) in der `.env`; die Datei
   pflegt ein Mensch. Für SEPA werden Gläubiger-ID und Mandatstext des Veranstalters gebraucht.
   `RegistrationService::confirm()` ist der Einstieg für die bestätigte Zahlung.
2. **Paket 2b-3:** Sammelanmeldung (W-13) und Warteliste (W-14).
3. **Paket 2b-4:** Daten ändern und Ummelden über den Magic-Link (W-15). Dabei Minderjährigkeit zum
   Zeitpunkt der Ummeldung prüfen und die Team-Wertung (W-18) neu berechnen.
4. **Phase 3:** Vorlagen-Engine (H-40 bis H-44, B-02); die Bestätigungsmail ist bis dahin ein fester Text.

## Bekannte Lücken

- **Lücke bei Staffelmitgliedern:** Bleibt ein optionales Mitglied in der Mitte leer und ein späteres ist
  ausgefüllt, kann eine Fehlermeldung beim falschen Mitglied stehen.
- **Testmodus und eigene Domains:** Der Testmodus braucht dieselbe Session wie das Orga-Panel. Bekommt ein
  Wettkampf eine eigene Domain (C-01), muss der Start anders gelöst werden.
- **Versteckte Wettbewerbe:** Im Testmodus erscheinen nur öffentliche Wettbewerbe.
- **Testanmeldung als Dublette:** Gibt es schon eine echte Anmeldung mit denselben Daten, wird die
  Testanmeldung als Dublette abgelehnt.
- **Listen und Exporte:** Sie müssen Testanmeldungen ausschließen – dafür den Scope `Registration::real()`
  verwenden.
- **Name in den Listen:** Wie eine Zeile ohne Namen in Starter- und Ergebnislisten aussieht, ist mit Phase 6
  festzulegen (Pflichtenheft 4.4).
- **JavaScript ohne Tests:** Das Projekt hat keine JavaScript-Tests; die Altersprüfung im Formular wurde
  einmalig mit Node gegengeprüft.

## Offene Fragen

Stehen im Pflichtenheft, Kapitel 11: Ergebnis-Datenbank (Spalte „Entw“, AK-Kürzel, Zugriffsweg) sowie die
tatsächlichen Preise und das verbindliche Altersklassen-Schema für 2027.
