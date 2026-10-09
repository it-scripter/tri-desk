<?php

return [

    'label' => 'Benutzer',
    'plural_label' => 'Benutzer',
    'navigation_group' => 'System',

    'sections' => [
        'person' => 'Person',
        'account' => 'Zugang',
        'roles' => 'Rollen',
    ],

    'fields' => [
        'first_name' => 'Vorname',
        'last_name' => 'Nachname',
        'name' => 'Name',
        'email' => 'E-Mail',
        'phone' => 'Mobil',
        'birth_date' => 'Geburtsdatum',
        'password' => 'Passwort',
        'email_verified_at' => 'E-Mail bestätigt',
        'created_at' => 'Angelegt',
        'roles' => 'Rollen',
    ],

    'hints' => [
        'password' => 'Beim Bearbeiten leer lassen, um das bisherige Passwort zu behalten.',
    ],

    'role' => [
        'label' => 'Rollenzuweisung',
        'plural_label' => 'Rollenzuweisungen',
        'fields' => [
            'role' => 'Rolle',
            'scope' => 'Gilt für',
            'scope_type' => 'Art des Kontexts',
        ],
        'scopes' => [
            'global' => 'Alle Wettkämpfe (global)',
            'edition' => 'Eine Ausgabe',
        ],
        'hints' => [
            'scope' => 'Eine globale Zuweisung gilt überall. Eine Ausgabe-Rolle gilt nur dort.',
        ],
    ],

];
