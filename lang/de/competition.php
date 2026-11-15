<?php

return [

    'label' => 'Wettkampf',
    'plural_label' => 'Wettkämpfe',
    'navigation_group' => 'Wettkämpfe',

    'fields' => [
        'name' => 'Name',
        'slug' => 'Pfad-Kürzel',
        'domain' => 'Eigene Domain',
        'is_active' => 'Aktiv',
        'sort' => 'Reihenfolge',
        'editions_count' => 'Ausgaben',
        'current_edition' => 'Aktuelle Ausgabe',
    ],

    'hints' => [
        'slug' => 'Teil der Adresse, zum Beispiel volkstriathlon für /volkstriathlon.',
        'domain' => 'Nur ausfüllen, wenn der Wettkampf später unter einer eigenen Domain erreichbar sein soll.',
        'sort' => 'Kleinere Zahlen stehen auf der Portal-Startseite weiter oben.',
    ],

    'validation' => [
        'reserved_slug' => 'Dieses Pfad-Kürzel ist vom System belegt und kann nicht verwendet werden.',
    ],

    // Public start page of a competition.
    'home' => [
        'no_edition' => 'Keine aktuelle Ausgabe',
        'no_edition_published' => 'Für diesen Wettkampf ist noch keine Ausgabe veröffentlicht.',
        'status' => 'Status: :status',
        'register' => 'Zur Anmeldung',
        'register_race' => 'Anmelden',
        'races' => 'Wettbewerbe',
        'no_races' => 'Die Wettbewerbe dieser Ausgabe stehen noch nicht fest.',
        'back' => 'Zurück zur Übersicht',
    ],

];
