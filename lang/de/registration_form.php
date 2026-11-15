<?php

return [

    'title' => 'Anmeldung',

    'steps' => [
        'race' => 'Wettbewerb',
        'people' => 'Personen',
        'options' => 'Zusatzoptionen',
        'consents' => 'Zustimmungen',
        'summary' => 'Prüfen und absenden',
    ],

    'progress' => 'Schritt :current von :total',

    'race' => [
        'intro' => 'Bitte den Wettbewerb wählen.',
        'price' => 'Aktueller Preis: :price',
        'per_team' => 'je Staffel bzw. Team',
        'sold_out' => 'Ausgebucht',
        'none' => 'Für diese Ausgabe ist zurzeit keine Anmeldung möglich.',
        'register' => 'Anmelden',
    ],

    'people' => [
        'contact' => 'Kontakt für Rückfragen und Bestätigung',
        'contact_hint' => 'An diese Adresse schicken wir die Bestätigung mit dem Link zur Anmeldung.',
        'first_name' => 'Vorname',
        'last_name' => 'Nachname',
        'email' => 'E-Mail',
        'team_name' => 'Name der Staffel bzw. des Teams',
        'person' => 'Person',
        'member' => 'Mitglied :number',
        'member_optional' => 'Mitglied :number (optional)',
        'birth_date' => 'Geburtsdatum',
        'gender' => 'Geschlecht',
        'scored_as' => 'Wertung bei „divers“',
        'scored_as_hint' => 'Nur nötig bei der Angabe „divers“: In welcher Wertung soll gestartet werden?',
        'guardian' => 'Nur für Minderjährige',
        'guardian_hint' => 'Wer am Wettkampftag noch nicht 18 ist, braucht das Einverständnis einer erziehungsberechtigten Person.',
        'guardian_name' => 'Name der erziehungsberechtigten Person',
        'details' => 'Weitere Angaben',
    ],

    'options' => [
        'intro' => 'Diese Zusatzoptionen können mitgebucht werden.',
        'variant' => 'Auswahl',
        'variant_placeholder' => 'Bitte wählen',
        'sold_out' => 'ausverkauft',
    ],

    // Versions of the consent texts. Raise a version whenever its text
    // changes; every consent is stored with the version it was given to.
    // Moves to the versioned consent texts of phase 4 (H-03).
    'consents' => [
        'terms' => [
            'version' => '2026-11-15',
            'label' => 'Ich akzeptiere die Teilnahmebedingungen. Dazu gehören die Stornoregeln dieser Ausgabe und das Einverständnis mit Foto- und Videoaufnahmen am Wettkampftag sowie deren Veröffentlichung.',
        ],
        'privacy' => [
            'version' => '2026-11-15',
            'label' => 'Ich habe den Datenschutzhinweis zur Kenntnis genommen.',
        ],
        'hide_name' => [
            'label' => 'Meinen bzw. unseren Namen nicht in den öffentlichen Starter- und Ergebnislisten zeigen.',
            'hint' => 'Ausgenommen sind die Plätze 1 bis 3 einer Altersklasse oder Gesamtwertung: Sie werden immer mit Namen genannt. Bei Staffeln und Teams gilt die Wahl für alle Mitglieder; der Teamname ist immer öffentlich.',
        ],
        'cancellation' => 'Stornoregeln dieser Ausgabe',
        'cancellation_step' => 'Storno bis :date: :percent % Erstattung',
        'cancellation_fee' => 'abzüglich :fee Bearbeitungsgebühr',
        'cancellation_none' => 'Danach ist keine Erstattung mehr möglich.',
    ],

    'summary' => [
        'intro' => 'Bitte die Angaben prüfen. Mit dem Absenden ist die Anmeldung verbindlich.',
        'race' => 'Wettbewerb',
        'price_tier' => 'Preisstufe',
        'total' => 'Gesamtbetrag',
        'submit' => 'Verbindlich anmelden',
        'payment_note' => 'Der Platz wird nach dem Absenden für :hours Stunden reserviert. Wie bezahlt wird, steht in der Bestätigung.',
        'edit' => 'Ändern',
    ],

    'done' => [
        'title' => 'Anmeldung eingegangen',
        'reserved' => 'Der Platz ist bis :date reserviert.',
        'confirmed' => 'Die Anmeldung ist bestätigt.',
        'mail' => 'Eine Bestätigung mit dem Link zur Anmeldung ist an :email unterwegs.',
    ],

    'manage' => [
        'title' => 'Ihre Anmeldung',
        'status' => 'Status',
        'reserved_until' => 'Reserviert bis :date',
        'cancel' => 'Anmeldung stornieren',
        'cancel_refund' => 'Bei einer Stornierung jetzt würden nach den Stornoregeln :amount erstattet.',
        'cancel_no_refund' => 'Bei einer Stornierung jetzt ist nach den Stornoregeln keine Erstattung mehr möglich.',
        'cancel_confirm' => 'Ja, ich möchte die Anmeldung verbindlich stornieren.',
        'cancelled' => 'Die Anmeldung wurde storniert.',
        'not_cancellable' => 'Diese Anmeldung kann nicht mehr storniert werden.',
        'changes_later' => 'Daten ändern und Ummelden sind hier bald ebenfalls möglich. Bis dahin hilft das Orga-Team weiter.',
    ],

    'navigation' => [
        'back' => 'Zurück',
        'next' => 'Weiter',
    ],

    'errors' => [
        'title' => 'Bitte die markierten Angaben prüfen.',
        'expired' => 'Die Sitzung ist abgelaufen. Bitte die Anmeldung neu beginnen.',
        'spam' => 'Die Anmeldung konnte nicht angenommen werden. Bitte erneut versuchen.',
    ],

    'mail' => [
        'subject' => 'Anmeldung :race – :edition',
        'greeting' => 'Hallo :name,',
        'received' => 'vielen Dank für die Anmeldung zum Wettbewerb :race der Ausgabe :edition.',
        'reserved' => 'Der Platz ist bis :date reserviert. Die Angaben zur Zahlung folgen; ohne Zahlungseingang wird der Platz danach wieder frei.',
        'confirmed' => 'Die Anmeldung ist bestätigt.',
        'participants' => 'Angemeldet',
        'total' => 'Betrag',
        'link' => 'Über diesen Link lässt sich die Anmeldung jederzeit ansehen und stornieren:',
        'link_warning' => 'Der Link ist persönlich. Bitte nicht weitergeben – wer ihn kennt, kann die Anmeldung verwalten.',
        'closing' => 'Sportliche Grüße',
    ],

];
