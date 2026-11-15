<?php

return [

    'label' => 'Zusatzoption',
    'plural_label' => 'Zusatzoptionen',

    'fields' => [
        'name' => 'Bezeichnung',
        'description' => 'Beschreibung',
        'price' => 'Preis',
        'quota' => 'Kontingent',
        'races' => 'Nur für Wettbewerbe',
        'sort' => 'Reihenfolge',
        'is_active' => 'Aktiv',
        'variants' => 'Varianten',
        'variant_name' => 'Variante',
        'variant_quota' => 'Kontingent',
    ],

    'hints' => [
        'quota' => 'Leer lassen, wenn die Option nicht begrenzt ist.',
        'races' => 'Leer lassen, wenn die Option allen Wettbewerben der Ausgabe angeboten wird.',
        'variants' => 'Auswahl innerhalb der Option, zum Beispiel Shirtgrößen.',
    ],

    'all_races' => 'alle',

];
