<?php

return [

    'scheme' => [
        'label' => 'Altersklassen-Schema',
        'plural_label' => 'Altersklassen-Schemata',
        'fields' => [
            'name' => 'Name',
            'slug' => 'Kürzel',
            'diverse_scoring' => 'Wertung „divers“',
            'description' => 'Beschreibung',
            'age_groups_count' => 'Altersklassen',
        ],
        'hints' => [
            'diverse_scoring' => 'Gilt nur für Wettbewerbe, die die Angabe „divers“ zulassen.',
        ],
    ],

    'label' => 'Altersklasse',
    'plural_label' => 'Altersklassen',

    'fields' => [
        'code' => 'Kürzel',
        'name' => 'Bezeichnung',
        'gender' => 'Geschlecht',
        'min_age' => 'Alter von',
        'max_age' => 'Alter bis',
        'sort' => 'Reihenfolge',
    ],

    'hints' => [
        'gender' => 'Leer lassen, wenn die Klasse für alle gilt.',
        'age' => 'Maßgeblich ist das Alter, das im Wettkampfjahr erreicht wird. Eine leere Grenze bedeutet „und jünger“ bzw. „und älter“.',
    ],

    'all_genders' => 'alle',

];
