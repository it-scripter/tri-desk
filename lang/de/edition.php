<?php

return [

    'label' => 'Ausgabe',
    'plural_label' => 'Ausgaben',

    'sections' => [
        'basics' => 'Stammdaten',
        'schedule' => 'Termine und Fristen',
        'days' => 'Wettkampftage',
    ],

    'fields' => [
        'competition' => 'Wettkampf',
        'year' => 'Jahr',
        'number' => 'Ausgabe-Nr.',
        'display_name' => 'Anzeigename',
        'slug' => 'Pfad-Kürzel',
        'status' => 'Status',
        'starts_on' => 'Beginn',
        'ends_on' => 'Ende',
        'registration_opens_at' => 'Anmeldestart',
        'registration_closes_at' => 'Meldeschluss',
        'transfer_deadline_at' => 'Ummeldeschluss',
        'is_current' => 'Aktuelle Ausgabe',
        'note' => 'Hinweis',
        'races_count' => 'Wettbewerbe',
    ],

    'hints' => [
        'number' => 'Nur ausfüllen, wenn die Ausgaben gezählt werden, zum Beispiel 38 für den 38. Volkstriathlon.',
        'display_name' => 'Abweichender Name dieser Ausgabe, zum Beispiel BerlinMan für Ausgaben bis 2024. Leer lassen, wenn der Name des Wettkampfs gilt.',
        'is_current' => 'Je Wettkampf kann nur eine Ausgabe die aktuelle sein; die bisherige wird automatisch abgewählt.',
    ],

    'validation' => [
        'not_editable' => 'Diese Ausgabe ist abgeschlossen und kann nicht mehr bearbeitet werden.',
    ],

    'copy' => [
        'action' => 'Ausgabe kopieren',
        'heading' => ':source als Vorlage kopieren',
        'description' => 'Übernommen werden Wettkampftage, Wettbewerbe, Preisstufen, Zusatzoptionen und Stornoregeln. Alle Termine verschieben sich um den Abstand zum neuen Wettkampftag. Die neue Ausgabe startet als „Geplant“.',
        'starts_on' => 'Neuer Wettkampftag',
        'starts_on_hint' => 'Erster Tag der neuen Ausgabe. Hat die Vorlage kein Datum, bleiben alle Termine der Kopie leer.',
        'success' => 'Ausgabe :edition angelegt.',
    ],

    'day' => [
        'label' => 'Wettkampftag',
        'plural_label' => 'Wettkampftage',
        'fields' => [
            'date' => 'Datum',
            'label' => 'Bezeichnung',
            'sort' => 'Reihenfolge',
        ],
        'hints' => [
            'date' => 'Bei historischen Ausgaben darf das Datum leer bleiben, wenn nur der Wochentag bekannt ist.',
        ],
    ],

];
