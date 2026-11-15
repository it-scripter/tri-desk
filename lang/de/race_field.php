<?php

return [

    'label' => 'Zusatzfeld',
    'plural_label' => 'Zusatzfelder',

    'fields' => [
        'label' => 'Bezeichnung',
        'key' => 'Schlüssel',
        'type' => 'Art',
        'scope' => 'Gilt für',
        'choices' => 'Auswahlwerte',
        'help' => 'Hinweis im Formular',
        'is_required' => 'Pflichtfeld',
        'sort' => 'Reihenfolge',
    ],

    'hints' => [
        'key' => 'Interner Name für Exporte, zum Beispiel verein. Nur Kleinbuchstaben, Ziffern, Binde- und Unterstrich.',
        'choices' => 'Jeden Wert mit Enter bestätigen.',
    ],

    'catalogue' => [
        'action' => 'Aus Katalog übernehmen',
        'field' => 'Feld',
    ],

];
