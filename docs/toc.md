# Pflichtenheft Wettkampf- & Helferportal

Entwurf v0.1 · Oct 8, 2026 · @Thorsten

## 1. Einleitung

Ein Veranstalter betreibt auf einer Plattform mehrere Wettkämpfe (z. B. Triathlon, Lauf) mit Anmeldung, Ergebnissen, Helferorganisation und eigener Startseite je Wettkampf. Bestehende Teillösungen für Bezahlung und Urkundendruck werden übernommen und integriert, nicht neu gebaut.

### 1.1 Ziele

- Teilnehmende melden sich online an und bezahlen in einem Durchgang.
- Starter- und Ergebnislisten sind ohne Handarbeit öffentlich.
- Helfende verwalten ihre Daten selbst; Teamleitungen planen Einsätze und kommunizieren im System statt per Mail-Verteiler und Excel.
- Das Orga-Team pflegt Inhalte und Texte selbst, ohne Entwickler.

### 1.2 Zielgruppen

| Gruppe | Nutzt vor allem | Gerät |
| --- | --- | --- |
| Teilnehmende | Anmeldung, Bezahlung, Starter-/Ergebnislisten, Urkunde | überwiegend Smartphone |
| Helfende | Registrierung, Profil, Einsatzinfos, Nachrichten | überwiegend Smartphone |
| Teamleitungen | Helferzuordnung, Posten, Kommunikation | Desktop und Smartphone (am Wettkampftag) |
| Orga-Team eines Wettkampfs | Wettkampf-Admin, Inhalte, Listen, Texte | Desktop |
| Admin des Veranstalters | alle Wettkämpfe, Benutzer, Rechte, Systemeinstellungen | Desktop |
| Öffentlichkeit | Startseiten, News, Ergebnisse | alle |

### 1.3 Abgrenzung

- Keine Zeitmessung und vorerst kein Ergebnisimport: Ergebnisse kommen aus der bestehenden Ergebnis-Datenbank des Veranstalters.
- Keine eigene Zahlungsabwicklung: PayPal wickelt Online-Zahlungen ab, Lastschriften zieht der Veranstalter selbst über seine Bank ein; das System verarbeitet nur Status und Rückmeldungen.
- Keine Übernahme von Bestandsdaten im ersten Schritt: Helferlisten und Vorjahresanmeldungen stammen aus verschiedenen Quellen und werden später gesondert betrachtet (voraussichtlich Excel-Import).
- Einsprachig (Deutsch); Mehrsprachigkeit wird nicht verbaut, aber nicht umgesetzt.

### 1.4 Begriffe

| Begriff | Bedeutung | Beispiel |
| --- | --- | --- |
| Veranstalter | Betreiber der Plattform, ein Mandant | Triathlon-Verein |
| Wettkampf | Wiederkehrende Marke mit eigener Startseite | BerlinWoMen |
| Ausgabe | Ein Jahrgang eines Wettkampfs mit Datum | BerlinWoMen 2027 |
| Wettbewerb | Einzelne Startklasse innerhalb einer Ausgabe | Sprintdistanz, Staffel, Kids |
| Organisationseinheit (OE) | Bereich der Helferorganisation | Radfahren, Verpflegung |
| Unterstruktur | Gliederung innerhalb einer OE, beliebig tief | Rad Abschnitt 1 |
| Posten | Konkreter Einsatzort mit Zeiten, Hinweisen, Karte, Bildern | Streckenposten A |
| Tageseinsatz | Einsatz einer helfenden Person an einem Wettkampftag an einem Posten; der Posten kann je Tag wechseln | Sa: Streckenposten A, So: Zielverpflegung |

### 1.5 Mengengerüst

Das System muss vor allem eine große zweitägige Veranstaltung tragen; die übrigen sind deutlich kleiner.

| Veranstaltung | Dauer | Wettbewerbe | Teilnehmende | Helfende |
| --- | --- | --- | --- | --- |
| BerlinWoMen | 2 Tage | Mitteldistanz, Mitteldistanz-Staffel, AquaBike, Jedermensch/Sprint, Swim & Run (Kids) | ca. 1.600 | ca. 400 |
| Volkstriathlon | offen | offen | offen | offen |
| Schüler-Duathlon | offen | offen | offen | offen |
| Weitere bei Bedarf (z. B. Swim & Run) | offen | offen | offen | offen |

Folgen für die Umsetzung:

- Eine Ausgabe kann mehrere Tage umfassen; Wettbewerbe, Helfereinsätze sowie Auf- und Abbau sind einzelnen Tagen zugeordnet.
- Die Disziplinfolge ist je Wettbewerb frei konfigurierbar: Triathlon (Schwimmen–Rad–Lauf), Duathlon (Lauf–Rad–Lauf), AquaBike (Schwimmen–Rad), Swim & Run (Schwimmen–Lauf). Wechselzonen, Splitzeiten und Ergebnislisten richten sich danach.
- Staffeln, Kinder- und Schülerwettbewerbe sind Kernfälle: Teamanmeldung (W-12), Einverständnis Erziehungsberechtigter (W-17) und Sammelanmeldung durch Schule oder Verein (W-13) sind Muss-Anforderungen.
- Lastspitze ist der Anmeldestart der BerlinWoMen; pro Jahr rund 2.000–2.500 Anmeldungen und 400–500 Helfende – für Laravel und MariaDB unkritisch.

## 2. Technologie-Entscheidung

Entscheidung vom 08.10.2026: **Laravel 13 mit Filament 5** als Basis, ergänzt um ein schlankes eigenes CMS-Modul. Rund 80 % der Anforderungen sind Anwendungslogik (Rollen, Zuordnungen, Workflows, Mails, Fristen), nicht Content – dafür ist ein Applikations-Framework die bessere Grundlage als ein CMS.

### 2.1 Vergleich

| Kriterium | Laravel 13 + Filament 5 | TYPO3 v14 LTS | MODX 3.2 | Symfony 7/8 + EasyAdmin |
| --- | --- | --- | --- | --- |
| Stand / Support | März 2026; Security bis Q1 2028, danach jährliches Major-Upgrade | 14.3 LTS seit 21.04.2026; Security bis 30.06.2029 | 3.2.1 seit Mai 2026 | 7.4 LTS bzw. 8.x |
| Rollen & Rechte pro OE | sehr gut (Policies, spatie/laravel-permission) | gut im Backend, im Frontend Eigenbau | schwach, Eigenbau | sehr gut (Voter) |
| Admin-Oberflächen (CRUD, Filter, Export) | sehr schnell mit Filament | Backend-Module per Extbase, mehr Aufwand | Eigenbau (ExtJS-Manager) | gut mit EasyAdmin |
| Mails, Queues, Scheduler | eingebaut | Scheduler + Mail-API, keine Queue im Core | per Extras/Cron | eingebaut (Messenger) |
| Redaktion / CMS | Eigenbau-Modul nötig | stärkste Seite, Multi-Site eingebaut | stark, sehr leichtgewichtig | Eigenbau nötig |
| Eignung für Claude Code | sehr hoch (verbreitet, konventionsstark, gut testbar) | mittel (TypoScript, TCA, viele Sonderwege) | niedrig (kleines Ökosystem) | hoch |
| Know-how im Team | prüfen | vorhanden (TYPO3-Projekt auf v13) | prüfen | prüfen |

Quellen: [Laravel 13](https://laravel-news.com/laravel-13-released), [TYPO3 v14 LTS](https://news.typo3.com/press-releases/typo3-releases-v14-lts), [MODX Blog](https://modx.com/blog/), [Filament 5 / Laravel 13 Starter](https://packagist.org/packages/filakitphp/basev5)

### 2.2 Begründung

- **Laravel** liefert Authentifizierung, E-Mail-Verifizierung, Passwort-Reset, Policies, Queues, Scheduler, Mail-Templates, Validierung und Tests out of the box.
- **Filament** erzeugt beide Admin-Interfaces (Veranstalter, Orga-Team) als Panels mit Tabellen, Filtern, Formularen, Bulk-Aktionen und Exporten – das spart den größten Einzelblock an Arbeit.
- **Öffentliches Frontend** und Helferportal bleiben wie gewünscht Blade-Templates mit SCSS und Vanilla JS. Filament nutzt intern Livewire/Alpine; das betrifft nur die Admin-Oberfläche.
- **CMS-Funktion** ist überschaubar (Seiten und Inhaltsblöcke je Wettkampf, News, Medien) und als eigenes Modul mit Filament-Builder in wenigen Tagen umsetzbar.
- **MODX** ist für Content sehr angenehm, aber Helferportal, Rechte pro OE und Kommunikation wären fast komplett Eigenbau in einem kleinen Ökosystem.

### 2.3 Verworfene Alternative TYPO3

Nicht gewählt trotz vorhandenem Know-how, weil Entwicklungstempo und Eignung für Claude Code schwerer wiegen. TYPO3 v14 wäre die richtige Wahl gewesen, wenn das vorhandene Team-Know-how und eine starke Redaktion schwerer gewogen hätten als das Entwicklungstempo. Dann: je Wettkampf eine Site, Helfende als fe\_users, Fachlogik als Extbase-Extension. Nachteil: Frontend-Portal und Teamleiter-Oberflächen sind weitgehend Eigenbau.

### 2.4 Stack

- PHP ≥ 8.3, MariaDB/MySQL, Apache mit DocumentRoot auf `/public`
- Laravel 13, Filament 5, spatie/laravel-permission, spatie/laravel-medialibrary, spatie/laravel-activitylog
- Frontend: Blade, SCSS (Vite-Build), Vanilla JS (ES-Module), Leaflet + OpenStreetMap für Karten
- PDF: bestehender Urkundendruck mit TCPDF und setasign/FPDI; dieselben Bibliotheken auch für Rechnungen und Einsatzpläne; Excel-Exporte: PhpSpreadsheet (z. B. über Laravel Excel oder Filament-Exporter)
- Tests: Pest; Code-Stil: Pint; statische Analyse: Larastan
- Betrieb: Entwicklung mit DDEV; Start auf Shared Hosting mit Cron für Scheduler und Queue (Datenbank-Queue, per Cron abgearbeitet); später ggf. eigener Server mit dauerhaftem Queue-Worker

## 3. Rollen & Rechtekonzept

Rechte werden immer im Kontext vergeben: global, je Ausgabe eines Wettkampfs oder je Organisationseinheit. Eine Person kann mehrere Rollen haben (z. B. Helferin und gleichzeitig Teamleiterin Verpflegung).

| Rolle | Geltungsbereich | Darf |
| --- | --- | --- |
| Super-Admin (Veranstalter) | global | alles; Wettkämpfe anlegen, Orga-Teams berufen, Systemeinstellungen, Rechte, Logs |
| Orga-Team | eine Ausgabe | Wettbewerbe, Preise, Anmeldungen, Listen, Inhalte, Mailtexte, OEs, Teamleitungen dieser Ausgabe |
| Redaktion | eine Ausgabe oder ein Wettkampf | nur Seiten, News, Medien |
| Teamleitung | eine oder mehrere OEs | Helfende der eigenen OE sehen, zuordnen, anschreiben; Posten und Tagesbesetzung pflegen |
| Helfende | eigenes Konto | Profil, Zustimmungen, Verfügbarkeit, Einsatzinfos, Nachrichten an eigene Teamleitung |
| Teilnehmende | eigene Anmeldungen | Anmeldung einsehen/ändern bis Meldeschluss, Ummeldung, Urkunde herunterladen |
| Gast | öffentlich | Startseiten, Starter- und Ergebnislisten |

- Teamleitungen sehen nur Daten, die sie für die Einsatzplanung brauchen (Name, Kontakt, Verfügbarkeit, Shirtgröße, Volljährigkeit) – keine Daten anderer OEs.
- Teilnehmende haben vorerst kein dauerhaftes Konto: Jede Anmeldung wird über einen Magic-Link aus der Bestätigungsmail verwaltet. Das Datenmodell lässt eine spätere Verknüpfung mit einem Konto offen.
- Rechte werden technisch über Policies geprüft, nicht nur durch Ausblenden von Menüpunkten.

## 4. Modul 1 – Wettkampf & Anmeldung

Anforderungen sind mit IDs versehen (W = Wettkampf), damit Claude Code und Tests sich darauf beziehen können. Priorität: M = Muss, S = Soll, K = Kann.

### 4.1 Stammdaten

| ID | Anforderung | Prio |
| --- | --- | --- |
| W-01 | Wettkampf mit Ausgaben je Jahr; Ausgabe kopierbar aus Vorjahr (Wettbewerbe, Preise, OEs, Posten, Texte) | M |
| W-02 | Wettbewerbe je Ausgabe: Name, Distanzen, Startzeit, Teilnehmerlimit, Altersgrenzen, Einzel/Staffel/Team, Disziplinfolge (siehe 1.5), Wettkampftag | M |
| W-03 | Preisstufen nach Datum (Early Bird, Normal, Nachmeldung) und Zusatzoptionen (Shirt, Pasta-Party, Zeitmessungschip-Miete) | M |
| W-04 | Meldeschluss, Ummeldeschluss, Stornoregeln je Ausgabe | M |
| W-05 | Altersklassen-Schema hinterlegbar (z. B. nach DTU/DLV), automatische Berechnung aus Geburtsjahr | M |
| W-06 | Gutscheincodes (prozentual/absolut, Kontingent, Gültigkeit) | S |

### 4.2 Anmeldung

| ID | Anforderung | Prio |
| --- | --- | --- |
| W-10 | Mehrstufiges Anmeldeformular: Wettbewerb → Personendaten → Zusatzoptionen → Zustimmungen → Zahlung → Bestätigung | M |
| W-11 | Konfigurierbare Zusatzfelder je Wettbewerb (Verein, Lizenznummer, Startpassnummer, erwartete Zeit, Notfallkontakt) | M |
| W-12 | Staffel-/Teamanmeldung durch eine Person mit Angabe aller Mitglieder | M |
| W-13 | Sammelanmeldung (Verein oder Schule meldet mehrere Personen, z. B. für den Schüler-Duathlon) | M |
| W-14 | Warteliste bei ausgebuchtem Wettbewerb, Nachrücken mit Zahlungsfrist | S |
| W-15 | Bestätigungsmail mit Magic-Link zur Selbstverwaltung (Daten ändern, Ummelden auf andere Person/Wettbewerb, Stornieren) | M |
| W-16 | Dublettenprüfung (Name + Geburtsdatum + Wettbewerb) | S |
| W-17 | Minderjährige: Einverständnis Erziehungsberechtigter erfassen | M |

### 4.3 Bezahlung (PayPal, SEPA-Lastschrift)

**Entscheidung:** Neuanbindung an die PayPal REST-API (Orders v2), keine Portierung. Zum Start ist PayPal der einzige Online-Zahlungsanbieter. Die bisherige MODX-Erweiterung mhPayPal (letzter Stand 2013) nutzt die Classic-NVP-API mit Express Checkout (API-Version 57.0, Anmeldung per API-Benutzer/Passwort/Signatur). Diese Schnittstelle gilt als Altlast und erhält keine neuen Funktionen. Umsetzung über das offizielle PayPal-SDK für PHP oder ein gepflegtes Laravel-Paket, gekapselt in einem Adapter, damit später bei Bedarf ein weiterer Anbieter (z. B. für Wero) angebunden werden kann. SEPA-Lastschrift läuft ohne Zahlungsdienstleister: Das System speichert das Mandat, der Einzug erfolgt durch den Veranstalter über seine Bank (W-25 bis W-27). Übernommen wird nur der fachliche Ablauf: Bestellung anlegen, zu PayPal weiterleiten, Rückkehr, Zahlung abschließen, Webhook verarbeiten. Voraussetzung: neue REST-Zugangsdaten (Client-ID, Secret) aus dem PayPal-Developer-Dashboard.

| ID | Anforderung | Prio |
| --- | --- | --- |
| W-20 | Anbindung PayPal REST-API (Orders v2) über offizielles SDK oder gepflegtes Paket; alle Zahlarten über eine gemeinsame Schnittstelle (`PaymentGateway`-Interface), damit später weitere Anbieter ergänzt werden können | M |
| W-21 | Status je Zahlung: offen, Mandat erteilt, eingezogen, bezahlt, fehlgeschlagen, zurückgebucht, erstattet; PayPal-Webhooks idempotent; Lastschrift-Status manuell oder per Sammelaktion im Orga-Admin | M |
| W-22 | Anmeldung gilt nach Zahlungseingang (PayPal) bzw. erteiltem Lastschriftmandat als bestätigt; unbezahlte Plätze verfallen nach X Stunden | M |
| W-23 | Rechnung/Quittung als PDF mit fortlaufender Nummer | S |
| W-24 | Erstattungen anstoßen und protokollieren | S |
| W-25 | SEPA-Lastschrift: IBAN und Kontoinhaber mit Prüfung erfassen, elektronisches SEPA-Mandat speichern (Mandatsreferenz, Gläubiger-ID, Zeitstempel, Mandatstext), Vorabankündigung per Mail | M |
| W-26 | Einzug durch den Veranstalter selbst: Download der Bankangaben (Kontoinhaber, IBAN, Mandatsreferenz, Mandatsdatum, Betrag, Verwendungszweck) als Excel über ein Export-Profil (B-16); exportierte Datensätze werden markiert, damit nichts doppelt eingezogen wird | M |
| W-27 | Rücklastschriften manuell im Orga-Admin erfassen: Status zurücksetzen, Mail mit PayPal-Zahlungslink, Bankgebühr optional weiterberechnen, Startberechtigung nach Frist sperren | M |
| W-28 | Später bei Bedarf: weiterer Zahlungsdienstleister für Wero oder Kreditkarte. Wero ist seit Herbst 2025 im deutschen Onlinehandel u. a. über Stripe, Unzer, Payone, VR Payment verfügbar ([Quelle](https://www.it-finanzmagazin.de/epi-startet-wero-im-deutschen-onlinehandel-und-gewinnt-erste-grosse-haendler-236442/)) | K |
| W-29 | Zahlarten je Ausgabe und Wettbewerb aktivierbar, mit Zeitfenster (z. B. Lastschrift nur bis 14 Tage vor dem Wettkampf, danach nur PayPal) | S |

### 4.4 Starter- und Ergebnislisten

| ID | Anforderung | Prio |
| --- | --- | --- |
| W-30 | Startnummernvergabe automatisch (Nummernkreise je Wettbewerb) oder manuell; Sperrnummern | M |
| W-31 | Öffentliche Starterliste je Wettbewerb, filterbar, sortierbar, mit Suchfeld; Sichtbarkeit pro Person abwählbar (DSGVO) | M |
| W-32 | Export für Zeitnahme über Export-Profil (Excel) | M |
| W-33 | Ergebnisdaten vorerst aus der bestehenden eigenen Ergebnis-Datenbank übernehmen per Synchronisations-Job in die eigenen Ergebnistabellen des Portals; das Portal liest nie direkt aus der Alt-Datenbank (Details unter der Tabelle); ein direkter Import der Zeitnahme-Daten ins Portal ist für später vorgesehen, aber nicht Teil des ersten Schritts | M |
| W-34 | Ergebnislisten gesamt, nach Geschlecht, Altersklasse, Splitzeiten je nach Disziplinfolge; DNF/DNS/DSQ | M |
| W-35 | Ergebnisse vorläufig/offiziell markieren, Korrekturen nach Veröffentlichung protokollieren | S |
| W-36 | Persönliche Ergebnisseite mit Urkunden-Download | S |
| W-37 | Historische Ergebnisse (27.520 Einträge in 98 Wettbewerben seit 1989) einmalig ins Portal übernehmen und als Ergebnisarchiv je Wettkampf und Jahr anzeigen; Suche nach Name und Verein | S |

**Bestehende Ergebnis-Datenbank (Tabelle `Alle`):** eine flache Tabelle für alle Jahre und Wettbewerbe, MyISAM, latin1 mit einzelnen utf8mb4-Spalten. Felder: Platz, AK, AKPl, StNr, Name, Verein, je Disziplin Platz und Zeit (Swim, Bike, Kanu, Run), Total, Entw, wk.

- **Synchronisation statt Live-Zugriff:** Ein Job liest neue oder geänderte Zeilen und schreibt sie normalisiert ins Portal (Ergebnis an Wettbewerb und Ausgabe, Splits je Disziplin). Die Quelle steckt hinter einer Schnittstelle (`ResultSource`), damit später ein Direktimport sie ohne Umbau ersetzen kann.
- **Zuordnung über `wk`:** Die Spalte enthält Veranstaltung, Wettbewerb und Jahr als freien Text (z. B. „BerlinMan Jedermenschtri 1992“). Eine Zuordnungstabelle im Orga-Admin verknüpft jeden `wk`-Wert mit Ausgabe und Wettbewerb; innerhalb davon wird über StNr mit der Anmeldung verknüpft. Für die 98 bisherigen Werte (Volkstriathlon ab 1989, Volxtri4kids, BerlinMan, MiniMan, Schüler-Duathlon) gibt es eine geprüfte Zuordnung als Excel-Datei (wk-zuordnung.xlsx) mit allen Zusammenführungen (MiniMan 2021, Jedermensch-Sprint 2008); sie dient als Seed-Daten. Die BerlinWoMen ist Nachfolgerin des BerlinMan: Im Portal ist das ein Wettkampf, dessen Ausgaben bis 2024 den Anzeigenamen „BerlinMan“ tragen. Der 32. Volkstriathlon (2020/21) ist wegen Corona ausgefallen. Neue Ausgaben bekommen ein einheitliches Schema für wk, z. B. „2027 Volkstriathlon“.
- **Datenbereinigung beim Übernehmen:** Name in Vor- und Nachname trennen (Regel prüfen); Geschlecht aus AK ableiten; Zeit 00:00:00 mit Platz 0 als „keine Zeit“ werten; Zeichensatz nach utf8mb4 wandeln; Kanu als eigene Disziplin für alte Formate behalten.
- **Auffälligkeit Entw:** Die Spalte enthält offenbar den Platzierungsverlauf nach jeder Disziplin (z. B. „89-61-2“), teils von Excel in ein Datum verwandelt (z. B. „02.01.2001“ statt „2-1-1“). Das Portal berechnet den Verlauf künftig selbst aus den Einzelplätzen.

### 4.5 Urkunden (Integration bestehender Lösung)

Der bestehende Urkundendruck ist reines PHP mit einer XML-Datei als Konfiguration und einer PDF-Vorlage; er nutzt TCPDF und setasign/FPDI. Er wird nahezu unverändert als eigener Service übernommen, beide Bibliotheken kommen per Composer ins Projekt. Hinweise für die Umsetzung: TCPDF wird vom Hersteller nur noch gepflegt, nicht weiterentwickelt; die kostenlose FPDI-Version liest nur PDF-Vorlagen ohne komprimierte Objekt-Streams (bis PDF 1.4), neue Vorlagen also entsprechend speichern.

| ID | Anforderung | Prio |
| --- | --- | --- |
| W-40 | Bestehenden Urkundendruck (PHP, XML-Konfiguration, PDF-Vorlage) als Service übernehmen; Daten aus Ergebnissen über Platzhalter; XML-Konfiguration und PDF-Vorlage je Wettbewerb im Orga-Admin hochladbar | M |
| W-41 | Einzeldownload für Teilnehmende, Sammeldruck (PDF) für Siegerehrung je Altersklasse | M |

### 4.6 Vor-Ort-Funktionen

| ID | Anforderung | Prio |
| --- | --- | --- |
| W-50 | Check-in/Startunterlagenausgabe: Suche nach Name/Startnummer, Abhaken, Hinweis auf offene Zahlung oder fehlende Unterlagen; mobil nutzbar | S |
| W-51 | Nachmeldung vor Ort mit Barzahlungs-Kennzeichnung | K |

## 5. Modul 2 – Helferportal

Helfende haben ein dauerhaftes Konto über alle Ausgaben hinweg; je Ausgabe melden sie sich erneut für einen oder mehrere Einsatztage. So bleibt die Helferdatenbank von Jahr zu Jahr erhalten.

### 5.1 Registrierung & Konto

| ID | Anforderung | Prio |
| --- | --- | --- |
| H-01 | Registrierung mit E-Mail-Bestätigung (Double-Opt-in); Login per Passwort oder Magic-Link | M |
| H-02 | Profil: Name, Kontakt (E-Mail, Mobil), Geburtsdatum (daraus Volljährigkeit), T-Shirt-Größe aus Liste, Verein/Gruppe, Notfallkontakt | M |
| H-03 | Zustimmungen versioniert speichern (Datenschutz, Foto-/Videoaufnahmen, Weitergabe an Teamleitung, Newsletter) mit Zeitstempel und Textversion; Widerruf jederzeit | M |
| H-04 | Mindestalter als vererbte Regel: Standard je Ausgabe, abweichend festlegbar je OE, Unterstruktur und Posten; die speziellste Regel gilt (z. B. Ausgabe 14, Zielverpflegung 12, Radstrecke 18). Maßgeblich ist das Alter am Einsatztag; bei der Zuordnung prüft das System die Regel. Minderjährige: Einverständnis Erziehungsberechtigter | M |
| H-05 | Verfügbarkeit je Ausgabe: Einsatztage (ein Tag oder beide), Wunsch-OE (1.–3. Wahl), Qualifikationen (Erste Hilfe, Führerschein, Rettungsschwimmer, Motorrad) | M |
| H-06 | Gruppenanmeldung (Verein, Schulklasse, Firma) mit Ansprechperson | S |
| H-07 | Konto löschen bzw. anonymisieren durch Helfende selbst | M |

### 5.2 Organisationsstruktur & Posten

| ID | Anforderung | Prio |
| --- | --- | --- |
| H-10 | OEs je Ausgabe anlegen (Startnummernausgabe, Schwimmen, Rad, Lauf, Verpflegung, Ziel, Auf-/Abbau …) | M |
| H-11 | Unterstrukturen als Baum beliebiger Tiefe (Rad → Abschnitt 1 → Streckenposten A) | M |
| H-12 | Posten mit Beschreibung, Hinweisen, Ausrüstung, Treffpunkt, Koordinaten (Kartenansicht, Link zu Navigation), Bildern zur Standortbeschreibung | M |
| H-13 | Tagesbesetzung je Posten: Einsatzzeiten des Tages (Treffpunkt, Beginn, Ende), Soll-Anzahl, Mindestalter, nötige Qualifikation; ein Posten kann an beiden Tagen oder nur an einem besetzt sein | M |
| H-14 | Mehrere Teamleitungen je OE; Teamleitung kann auf Unterstruktur begrenzt sein | M |
| H-15 | Übersichtskarte aller Posten einer OE bzw. der Ausgabe | S |
| H-16 | Posten und Tagesbesetzung aus Vorjahr übernehmen | M |

### 5.3 Einsatzplanung

| ID | Anforderung | Prio |
| --- | --- | --- |
| H-20 | Zuordnung je Einsatztag: Helfende → OE → Posten durch die Teamleitung; an zwei Tagen sind verschiedene Posten, auch in verschiedenen OEs, möglich (Drag & Drop oder Auswahlliste) | M |
| H-21 | Helfende geben je Tag Wünsche an (OE, Posten, gemeinsam mit bestimmten Personen); kein Selbsteintrag, die Einteilung macht die Teamleitung | S |
| H-22 | Konfliktprüfung: Doppelzuordnung am selben Tag, nicht gemeldeter Einsatztag, Mindestalter, Qualifikation | M |
| H-23 | Besetzungsübersicht: Soll/Ist je Posten und Tag, Ampel, offene Stellen | M |
| H-24 | Helfende bestätigen ihre Einteilung; Absage mit Grund | S |
| H-25 | Einsatzplan für Helfende (Web + PDF + iCal-Datei) mit Posteninfos und Karte | M |
| H-26 | Listen für Teamleitung: Anwesenheit, Telefonliste, Shirt-Bedarf, Verpflegungsbedarf (Excel über Export-Profil, PDF) | M |
| H-27 | Check-in am Wettkampftag (anwesend/nicht erschienen) | S |

### 5.4 Kommunikation & Verlauf

| ID | Anforderung | Prio |
| --- | --- | --- |
| H-30 | Nachrichten im System zwischen Helfenden und ihren Teamleitungen (Thread je Person bzw. je OE); Benachrichtigung per E-Mail mit Link | M |
| H-31 | Rundschreiben der Teamleitung an OE, Unterstruktur, Posten oder Einsatztag; Orga-Team an alle | M |
| H-32 | Kommunikationsverlauf je Person: Nachrichten, versendete System-Mails, Zuordnungsänderungen, Zustimmungen – chronologisch | M |
| H-33 | Interne Notizen der Teamleitung zur Person (für Helfende nicht sichtbar) | S |
| H-34 | Antworten per E-Mail landen im Thread (Reply-to-Adresse mit Token) | K |
| H-35 | Optional SMS/Messenger für kurzfristige Infos am Wettkampftag | K |

### 5.5 Automatische E-Mails

| ID | Anforderung | Prio |
| --- | --- | --- |
| H-40 | Mailvorlagen im System pflegbar (Betreff, Text mit einfachem Editor), je Ausgabe überschreibbar | M |
| H-41 | Platzhalter, z. B. `{vorname}`, `{wettkampf}`, `{posten}`, `{einsatz_beginn}`, `{treffpunkt}`, `{teamleitung}`, `{link_profil}`; Vorschau mit Testdaten | M |
| H-42 | Auslöser: Registrierung, Zuordnung, Änderung, Erinnerung X Tage vorher, Dankeschön X Tage danach, Reaktivierung vor neuer Ausgabe | M |
| H-43 | Versandprotokoll mit Status (versendet, Bounce) | M |
| H-44 | Gleiche Vorlagen-Engine auch für Teilnehmenden-Mails (Bestätigung, Zahlungserinnerung, Startinfos, Ergebnis/Urkunde) | M |

## 6. Module 3–5 – CMS und Admin-Interfaces

### 6.1 CMS (Modul 3)

| ID | Anforderung | Prio |
| --- | --- | --- |
| C-01 | Eigene Startseite je Wettkampf als Unterpfad der Portal-Domain (`/berlinwomen`); vorbereitet für spätere eigene Domains oder Subdomains: URLs nur über benannte Routen, Wettkampf-Kontext per Middleware aus dem Pfad (später aus dem Host), Helferportal und Admin bleiben auf der Portal-Domain | M |
| C-02 | Seiten aus Inhaltsblöcken: Text, Bild, Galerie, Hero mit Countdown, Call-to-Action (Anmelden/Helfen), Wettbewerbs-Übersicht (automatisch), Karte, Downloads, FAQ, Sponsorenleiste | M |
| C-03 | Seitenbaum und Menü je Wettkampf; Portal-Startseite des Veranstalters aus CMS-Modulen; Teaser je Wettkampf (Bild, Datum, Anmeldestatus, Link) werden automatisch aus den Wettkampfdaten gespeist | M |
| C-04 | News je Wettkampf mit Veröffentlichungsdatum | S |
| C-05 | Medienverwaltung mit Bildgrößen (WebP), Alt-Text-Pflicht | M |
| C-06 | Entwurf/Vorschau/Veröffentlichen; Versionen wiederherstellen | S |
| C-07 | Gestaltung je Wettkampf: Logo, Farben (CSS-Variablen), Headerbild | S |
| C-08 | SEO-Felder, Open-Graph-Bild, sprechende URLs, Sitemap | S |

### 6.2 Admin des Veranstalters (Modul 4)

- Wettkämpfe und Ausgaben anlegen, archivieren, kopieren
- Benutzer und Rollen verwalten, Orga-Teams berufen
- Globale Vorlagen: Mailtexte, Zustimmungstexte, Rechtstexte (Impressum, Datenschutz), Altersklassen-Schemata
- Systemeinstellungen: Bezahlanbieter, Mailversand (SMTP), Absender, Logos
- Auswertungen über alle Wettkämpfe: Teilnehmende, Umsätze, Helferzahlen je Jahr
- Aktivitäts- und Fehlerprotokoll, Datenschutz-Aufgaben (Auskunft, Löschung)

### 6.3 Admin des Orga-Teams (Modul 5)

- Dashboard der Ausgabe: Anmeldestand je Wettbewerb, Zahlungsstatus, Helferbesetzung (Soll/Ist), offene Aufgaben
- Wettbewerbe, Preise, Fristen, Zusatzfelder
- Anmeldungen suchen, bearbeiten, ummelden, stornieren, erstatten; Notizen
- Startnummern, Starterlisten, Exporte, Ergebnisse, Urkunden
- OEs, Teamleitungen, Posten, Tagesbesetzung, Helferübersicht
- Inhalte der Wettkampf-Seiten, Mailvorlagen der Ausgabe

Beide Admin-Bereiche werden als getrennte Filament-Panels umgesetzt (`/admin`, `/orga`); Teamleitungen erhalten ein drittes, reduziertes Panel (`/team`) oder eine mobile Ansicht im Frontend.

## 7. Zusätzliche Basisfunktionen (Vorschläge)

Diese Punkte stehen nicht in der Ausgangsliste, sparen aber erfahrungsgemäß viel Arbeit oder Ärger. Bitte je Zeile entscheiden: übernehmen, später, streichen.

| ID | Vorschlag | Nutzen | Vorschlag Prio |
| --- | --- | --- | --- |
| B-01 | Ausgabe aus Vorjahr klonen (Wettbewerbe, Preise, OEs, Posten, Texte) | Jahresstart in Minuten statt Tagen | M |
| B-02 | Zentrale Vorlagen-Engine für Mails und PDFs mit Platzhaltern | Ein Mechanismus für Helfer-, Teilnehmer- und Urkundentexte | M |
| B-03 | Audit-Log (wer hat was wann geändert) | Nachvollziehbarkeit bei Ummeldungen, Zahlungen, Ergebnissen | M |
| B-04 | DSGVO-Werkzeuge: Auskunft als Export, Löschfristen automatisch (z. B. Helferdaten 2 Jahre nach letzter Aktivität), Anonymisierung von Altdaten | Rechtssicherheit ohne Handarbeit | M |
| B-05 | Rollenwechsel/„Ansicht als“ für Admins | Support ohne Passwortweitergabe | S |
| B-06 | Zwei-Faktor-Login für Admin, Orga, Teamleitung | Schutz der personenbezogenen Daten | S |
| B-07 | Globale Suche im Admin (Name, Startnummer, E-Mail) | Schnelle Hilfe am Wettkampftag | S |
| B-08 | Statistik-Dashboard (Anmeldeverlauf vs. Vorjahr, Herkunft, Altersstruktur) | Planung und Sponsorenberichte | S |
| B-09 | Helfer-Dankeschön-Funktionen: Helferstunden-Zähler, Teilnahmebestätigung als PDF, Einladung Helferfest | Bindung der Helfenden | S |
| B-10 | Sponsorenverwaltung mit Logos je Wettkampf und Ebene | Pflege an einer Stelle | K |
| B-11 | Teilnehmer-Infopaket: Startunterlagen-Info, Wettkampfbesprechung, Streckenpläne als Mail kurz vorher | Weniger Rückfragen | S |
| B-12 | Live-Ticker/Live-Ergebnisse über Import-Intervall | Attraktivität am Wettkampftag | K |
| B-13 | Offline-fähige Check-in-Ansicht (PWA) für Startnummernausgabe | Funktioniert bei schlechtem Netz | K |
| B-14 | API (read-only) für Starter-/Ergebnislisten | Anbindung Zeitnahme, Apps, Presse | K |
| B-15 | Wartungsmodus und Statusseite je Wettkampf (z. B. „Anmeldung ausgebucht“) | Klare Kommunikation | S |
| B-16 | Export-Profile: gespeicherte Vorlagen mit Spaltenauswahl, Reihenfolge, Spaltenüberschriften, Filtern und Zahlen-/Datumsformat; Zielformat zunächst Excel (.xlsx); je Ausgabe kopierbar. Beispiele: Lastschrift-Einzug, Zeitnahme, Helferlisten, Shirt-Bedarf | Ein Mechanismus für alle Listen statt fest programmierter Exporte | M |

## 8. Datenmodell

&#91;embedded content: Kernentitäten · Wettkampf, Helfer, Kommunikation\]

Die Ausgabe ist der zentrale Bezugspunkt: Wettbewerbe, OEs und Mailvorlagen hängen an ihr. Die Person existiert ausgabenübergreifend und kann gleichzeitig Teilnehmende, Helfende und Teamleitung sein.

| Entität | Wichtige Felder | Hängt an |
| --- | --- | --- |
| Wettkampf | Name, Slug, Domain, Theme | Veranstalter |
| Ausgabe | Jahr, Datum, Status, Fristen, Ausgabe-Nr., Anzeigename (z. B. BerlinMan bis 2024) | Wettkampf |
| Wettbewerb | Typ, Distanzen, Startzeit, Limit, Altersgrenzen | Ausgabe |
| Preisstufe, Option, Gutschein | Betrag, gültig von/bis, Kontingent | Wettbewerb bzw. Ausgabe |
| Anmeldung | Personendaten, Zusatzfelder, Status, Startnummer, Token | Wettbewerb, optional Person |
| Zahlung | Betrag, Anbieter-Referenz, Status, Erstattungen | Anmeldung |
| Ergebnis | Zeiten, Splits, Platzierungen, Status (DNF …) | Anmeldung |
| Person | Name, E-Mail, Geburtsdatum, Shirtgröße, 2FA | – |
| Rollenzuweisung | Rolle, Kontext (global/Ausgabe/OE) | Person |
| Zustimmung | Art, Textversion, Zeitpunkt, Widerruf | Person |
| Helfereinsatz | Einsatztage, Wünsche je Tag, Qualifikationen | Person, Ausgabe |
| OE / Unterstruktur | Name, Eltern-Knoten, Teamleitungen | Ausgabe |
| Posten | Beschreibung, Hinweise, Koordinaten, Bilder | OE-Knoten |
| Tagesbesetzung | Einsatztag, Einsatzzeiten, Soll, Mindestalter, Qualifikation | Posten |
| Zuordnung | Status (geplant, bestätigt, abgesagt, anwesend) | Tagesbesetzung, Helfereinsatz |
| Nachricht / Thread | Absender, Empfänger(gruppe), Text, gelesen | Person, OE |
| Mailvorlage | Auslöser, Betreff, Text, Platzhalter | global oder Ausgabe |
| Versandprotokoll | Vorlage, Empfänger, Zeitpunkt, Status | Person bzw. Anmeldung |
| Seite / Block / Medium | Typ, Inhalt, Reihenfolge, Veröffentlichung | Wettkampf |

## 9. Nicht-funktionale Anforderungen

| Bereich | Anforderung |
| --- | --- |
| Datenschutz | DSGVO-konform: Datenminimierung, versionierte Einwilligungen, Löschkonzept, AV-Verträge mit Bezahl- und Mailanbieter, Hosting in der EU, keine externen Ressourcen ohne Einwilligung (Fonts lokal, Karten-Tiles prüfen) |
| Sicherheit | OWASP Top 10 abgedeckt (CSRF, XSS, SQL-Injection über ORM), Passwort-Hashing (bcrypt/argon2), Rate-Limiting bei Login und Anmeldung, Captcha-freie Spam-Abwehr (Honeypot, Zeitprüfung), Rechteprüfung serverseitig |
| Last | Anmeldestart als Spitze: mind. 200 gleichzeitige Anmeldungen ohne Überbuchung (Plätze transaktional reservieren) |
| Performance | Öffentliche Seiten < 1 s (Cache), Listen mit mehreren Tausend Einträgen paginiert/gefiltert ohne Wartezeit |
| Mobil | Alle öffentlichen und Helfer-Seiten mobile-first; Teamleitungs-Ansichten am Smartphone bedienbar |
| Barrierefreiheit | WCAG 2.1 AA für öffentliche Seiten und Formulare (BFSG-relevant, da Bezahlvorgang) |
| Mails | Versand über Queue, SPF/DKIM/DMARC, Bounce-Verarbeitung, Versandlimits des Providers beachten |
| Betrieb | Entwicklung lokal mit DDEV (PHP 8.4, MariaDB, Mailpit). Start auf Shared Hosting: Apache, PHP ≥ 8.3 auch auf der Kommandozeile, SSH, Composer, Cron jede Minute, DocumentRoot auf /public einstellbar. Queue über den Datenbank-Treiber, per Cron abgearbeitet statt mit dauerhaftem Worker. Kein Node auf dem Server (Assets vorab bauen). Tägliches DB-Backup mit Rücksicherungstest, Staging-System. Server-Bedarf nach der Testphase neu bewerten |
| Qualität | Automatisierte Tests für Anmeldung, Zahlung, Rechte, Mail-Auslöser; CI-Pipeline; Fehler-Monitoring (z. B. Sentry/Flare) |
| Sprache | Deutsch; alle Texte über Übersetzungsdateien, damit Mehrsprachigkeit später möglich bleibt |

## 10. Umsetzung mit Claude Code

Claude Code arbeitet am zuverlässigsten in kleinen, testbaren Schritten mit klaren Konventionen. Das Pflichtenheft wird deshalb ins Repository übernommen und in Arbeitspakete pro Anforderungs-ID zerlegt.

### 10.1 Phasen

1. **Fundament:** Projekt-Setup, Auth, Rollen/Policies, Mandant/Wettkampf/Ausgabe, Filament-Panels, Audit-Log, CI
2. **Anmeldung & Bezahlung:** Wettbewerbe, Preise, Anmeldeformular, PayPal REST (Orders v2), SEPA-Mandate, Export-Profile, Bestätigungsmails (W-01 bis W-24)
3. **Vorlagen-Engine & Mails:** Platzhalter, Vorlagen, Auslöser, Versandprotokoll (H-40 bis H-44, B-02)
4. **Helferportal Basis:** Registrierung, Profil, Zustimmungen, OEs, Posten, Tagesbesetzung (H-01 bis H-16)
5. **Einsatzplanung & Kommunikation:** Zuordnung, Konflikte, Einsatzpläne, Nachrichten, Verlauf (H-20 bis H-35)
6. **Listen & Ergebnisse:** Startnummern, Starterlisten, Exporte, Anbindung Ergebnis-DB, Urkunden (W-30 bis W-41)
7. **CMS & Gestaltung:** Seiten, Blöcke, News, Medien, Theme je Wettkampf (C-01 bis C-08)
8. **Härtung:** DSGVO-Werkzeuge, Lasttest Anmeldestart, Barrierefreiheits-Prüfung, Go-live

### 10.2 Repository

```
/docs/pflichtenheft.md        – dieses Dokument (exportiert)
/docs/adr/                    – Architekturentscheidungen
/app/Domain/Competition/      – Wettkampf, Ausgabe, Wettbewerb, Anmeldung
/app/Domain/Volunteer/        – Helfende, OE, Posten, Tagesbesetzung, Zuordnung
/app/Domain/Messaging/        – Nachrichten, Vorlagen, Versand
/app/Domain/Content/          – Seiten, Blöcke, Medien
/app/Integrations/Payment/    – PayPal-Adapter, SEPA-Mandate
/app/Integrations/Certificate/– Adapter Urkundendruck (TCPDF/FPDI)
/app/Integrations/Results/    – ResultSource: Alt-DB-Synchronisation
/docs/data/wk-zuordnung.csv   – Seed: Zuordnung wk → Wettkampf/Ausgabe/Wettbewerb
/resources/views/, /resources/scss/, /resources/js/
/tests/Feature/, /tests/Unit/
CLAUDE.md
```

### 10.3 Inhalte der CLAUDE.md

- DDEV-Setup, Stack, Versionen und Befehle, jeweils über ddev ausgeführt (`composer test`, `npm run build`, `php artisan migrate:fresh --seed`)
- Konventionen: Domänen-Ordner, Benennung, deutschsprachige Fachbegriffe im UI, englischer Code
- Regeln: jede Anforderungs-ID bekommt mind. einen Feature-Test; keine Rechteprüfung nur im UI; keine externen CDNs
- Vorgehen: erst Plan, dann Migration + Model + Policy + Test, dann UI
- Seed-Daten: eine Beispiel-Ausgabe mit Wettbewerben, OEs, Posten, 50 Helfenden, 200 Anmeldungen

### 10.4 Arbeitsweise

- Ein Arbeitspaket = ein Branch = eine Gruppe von Anforderungs-IDs.
- Claude Code erhält je Paket: die IDs, die betroffenen Entitäten und die Akzeptanzkriterien.
- Review durch Menschen vor jedem Merge, besonders bei Zahlung, Rechten und Datenschutz.

### 10.5 Zeitplan (Vorschlag)

Feste Termine: Anmeldestart für den Volkstriathlon am Feb 15, 2027, zunächst ohne Helferportal; Wettkampftag ist der 27.06.2027. Die BerlinWoMen findet erst 2028 wieder statt. Alles andere ist ein Vorschlag.

&#91;embedded content: Zeitplan-Vorschlag · Oktober 2026 bis April 2027\]

Die Entwicklungsblöcke enden eine Woche vor dem Anmeldestart, damit Zeit für PayPal im Live-Modus und Korrekturen bleibt.

**Umfang zum Anmeldestart (MVP):**

- Wettkampf, Ausgabe, Wettbewerbe, Preise, Fristen (W-01 bis W-05)
- Anmeldeformular mit Zusatzfeldern, Staffeln, Einverständnis bei Minderjährigen (W-10 bis W-12, W-15, W-17)
- PayPal, SEPA-Mandat mit Excel-Export der Bankangaben (W-20 bis W-22, W-25 bis W-27), Export-Profile (B-16)
- Bestätigungsmails mit Magic-Link über die Vorlagen-Engine (H-40 bis H-44 für Teilnehmende)
- Öffentliche Starterliste (W-31), Wettkampf-Startseite und einfache Portal-Startseite (C-01 bis C-03, C-05)
- Rollen, Orga-Admin für die Ausgabe, Rechtstexte und Einwilligungen

**Bis zum Wettkampftag am 27.06.2027 nötig:** Startnummern, Zeitnahme-Export, Ergebnisse, Urkunden, Check-in (W-30 bis W-50).

**Danach:** Helferportal bis Ende April fertig, Pilotbetrieb beim Volkstriathlon (Phasen 4 und 5). Bis zur BerlinWoMen 2028 bleibt ein Jahr für CMS-Ausbau, Auswertungen und die Erfahrungen aus dem Pilot (Phasen 7 und 8).

## 11. Offene Fragen

- [ ] Ergebnisse: dauerhaft aus der bestehenden Ergebnis-DB lesen oder künftig direkt ins Portal importieren? Für den Start nötig: Bedeutung der Spalte Entw bestätigen, Liste der AK-Kürzel, Zugriffsweg zur Alt-DB (gleicher Server?).
