<?php

return [

    'label' => 'Wettbewerb',
    'plural_label' => 'Wettbewerbe',

    'sections' => [
        'basics' => 'Stammdaten',
        'format' => 'Format',
        'limits' => 'Teilnahme',
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
    ],

    'hints' => [
        'discipline_sequence' => 'Reihenfolge der Disziplinen, zum Beispiel Schwimmen – Rad – Lauf. Sie bestimmt Wechselzonen, Splitzeiten und Ergebnislisten.',
        'participant_limit' => 'Leer lassen, wenn die Teilnahme nicht begrenzt ist.',
        'edition_day' => 'Nur nötig, wenn die Ausgabe mehrere Tage umfasst.',
    ],

];
