<?php

return [

    'label' => 'Preisstufe',
    'plural_label' => 'Preisstufen',

    'fields' => [
        'name' => 'Bezeichnung',
        'amount' => 'Preis',
        'valid_from' => 'Gültig ab',
        'valid_until' => 'Gültig bis',
        'orga_only' => 'Nur durch Orga-Team',
    ],

    'hints' => [
        'amount' => 'Preis einer Anmeldung in Euro. Bei Staffeln und Teams gilt er für das ganze Team.',
        'valid_from' => 'Leer lassen, wenn die Stufe von Anfang an gilt.',
        'valid_until' => 'Leer lassen, wenn die Stufe bis zum Schluss gilt. Darf über den Meldeschluss hinausreichen, etwa für Nachmeldungen vor Ort.',
        'orga_only' => 'Das öffentliche Anmeldeformular bietet diese Stufe nie an, zum Beispiel für Nachmeldungen am Wettkampftag.',
    ],

    'validation' => [
        'overlap' => 'Der Zeitraum überschneidet sich mit einer anderen Preisstufe dieses Wettbewerbs.',
    ],

];
