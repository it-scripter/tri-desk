<?php

return [

    'label' => 'Anmeldung',
    'plural_label' => 'Anmeldungen',

    'sections' => [
        'entry' => 'Anmeldung',
        'contact' => 'Kontakt',
        'participants' => 'Teilnehmende',
        'options' => 'Zusatzoptionen',
    ],

    'fields' => [
        'race' => 'Wettbewerb',
        'status' => 'Status',
        'amount' => 'Betrag',
        'price_tier' => 'Preisstufe',
        'reserved_until' => 'Reserviert bis',
        'confirmed_at' => 'Bestätigt am',
        'cancelled_at' => 'Storniert am',
        'contact' => 'Kontakt',
        'contact_email' => 'E-Mail',
        'team_name' => 'Teamname',
        'team_classification' => 'Wertung',
        'participants' => 'Teilnehmende',
        'participants_count' => 'Personen',
        'entered_by_orga' => 'Durch Orga-Team',
        'created_at' => 'Angemeldet am',
        'name' => 'Name',
        'birth_date' => 'Geburtsdatum',
        'gender' => 'Geschlecht',
        'scored_as' => 'Gewertet als',
        'age_group' => 'Altersklasse',
        'guardian' => 'Erziehungsberechtigte Person',
        'option' => 'Option',
        'variant' => 'Variante',
        'price' => 'Preis',
    ],

    'actions' => [
        'cancel' => 'Stornieren',
        'cancel_confirm' => 'Die Anmeldung wird storniert und der Platz freigegeben. Eine Erstattung wird damit noch nicht ausgelöst.',
        'cancelled' => 'Anmeldung storniert.',
    ],

    // Version of the consent text below. Raise it whenever the text
    // changes; every consent is stored with the version it was given to
    // (W-17). Moves to the versioned consent texts of phase 4 (H-03).
    'guardian_consent' => [
        'version' => '2026-11-15',
        'text' => 'Ich bin erziehungsberechtigt und bin mit der Teilnahme des minderjährigen Kindes am Wettbewerb einverstanden.',
    ],

    'rejected' => [
        'edition_closed' => 'Diese Ausgabe ist abgeschlossen; Anmeldungen sind nicht mehr möglich.',
        'not_public' => 'Für diesen Wettbewerb ist keine öffentliche Anmeldung möglich.',
        'not_open_yet' => 'Die Anmeldung ist noch nicht geöffnet.',
        'closed' => 'Der Meldeschluss ist vorbei.',
        'no_price' => 'Für diesen Wettbewerb gilt zurzeit keine Preisstufe; eine Anmeldung ist nicht möglich.',
        'individual_size' => 'Für diesen Wettbewerb meldet sich genau eine Person an.',
        'team_size' => 'Die Staffel bzw. das Team braucht mindestens :min und höchstens :max Mitglieder.',
        'gender_not_allowed' => 'Die Angabe „divers“ ist für diesen Wettbewerb nicht möglich.',
        'scored_as_required' => 'Bitte angeben, ob in der Wertung „männlich“ oder „weiblich“ gestartet wird.',
        'too_young' => 'Das Mindestalter für diesen Wettbewerb ist :age Jahre (nach Jahrgang).',
        'too_old' => 'Das Höchstalter für diesen Wettbewerb ist :age Jahre (nach Jahrgang).',
        'guardian_required' => 'Für Minderjährige sind der Name der erziehungsberechtigten Person und ihr Einverständnis nötig.',
        'no_classification' => 'In dieser Besetzung passt die Staffel in keine angebotene Wertung des Wettbewerbs.',
        'option_not_offered' => 'Die Option :option wird für diesen Wettbewerb nicht angeboten.',
        'variant_required' => 'Bitte für :option eine Variante wählen.',
        'duplicate' => ':name ist für diesen Wettbewerb bereits angemeldet. Änderungen sind über den Link in der Bestätigungsmail möglich.',
        'sold_out' => 'Der Wettbewerb ist ausgebucht.',
        'option_sold_out' => ':option ist ausverkauft.',
        'consent_required' => 'Bitte die Teilnahmebedingungen akzeptieren und den Datenschutzhinweis bestätigen.',
        'field_required' => 'Bitte „:field“ ausfüllen.',
        'not_reserved' => 'Die Anmeldung ist nicht mehr reserviert und kann nicht bestätigt werden.',
    ],

    'expired_count' => '{0} Keine Reservierung verfallen.|{1} Eine Reservierung verfallen.|[2,*] :count Reservierungen verfallen.',

];
