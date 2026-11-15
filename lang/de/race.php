<?php

return [

    'label' => 'Wettbewerb',
    'plural_label' => 'Wettbewerbe',

    'sections' => [
        'basics' => 'Stammdaten',
        'format' => 'Format',
        'limits' => 'Teilnahme',
        'scoring' => 'Wertung',
    ],

    'fields' => [
        'edition' => 'Ausgabe',
        'edition_day' => 'Wettkampftag',
        'name' => 'Name',
        'slug' => 'Pfad-Kürzel',
        'type' => 'Art',
        'discipline_sequence' => 'Disziplinfolge',
        'starts_at' => 'Startzeit',
        'participant_limit' => 'Teilnehmerlimit',
        'min_age' => 'Mindestalter',
        'max_age' => 'Höchstalter',
        'sort' => 'Reihenfolge',
        'is_public' => 'Öffentlich sichtbar',
        'age_group_scheme' => 'Altersklassen-Schema',
        'allows_diverse' => 'Angabe „divers“ zulassen',
        'team_classifications' => 'Team-Wertungen',
        'team_size_min' => 'Mitglieder mindestens',
        'team_size_max' => 'Mitglieder höchstens',
        'mixed_min_male' => 'Mixed: mindestens männlich',
        'mixed_min_female' => 'Mixed: mindestens weiblich',
    ],

    'hints' => [
        'discipline_sequence' => 'Reihenfolge der Disziplinen, zum Beispiel Schwimmen – Rad – Lauf. Sie bestimmt Wechselzonen, Splitzeiten und Ergebnislisten.',
        'participant_limit' => 'Leer lassen, wenn die Teilnahme nicht begrenzt ist.',
        'edition_day' => 'Nur nötig, wenn die Ausgabe mehrere Tage umfasst.',
        'allows_diverse' => 'Meisterschaften werden ohne „divers“ ausgetragen.',
        'team_classifications' => 'In welchen Wertungen Staffeln und Teams starten können. Keine Auswahl bedeutet: alle. Passt eine Staffel in keine Wertung, startet sie in „Offen“ (W-18).',
        'team_size' => 'Leer lassen, wenn es keine Grenze gibt; mindestens sind es zwei.',
        'mixed_min' => 'Ab wie vielen Mitgliedern je Geschlecht eine Staffel als Mixed gilt, zum Beispiel 2 und 2 bei einer Meisterschaft.',
    ],

];
