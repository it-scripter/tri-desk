<?php

return [

    'label' => 'Stornoregel',
    'plural_label' => 'Stornoregeln',

    'fields' => [
        'valid_until' => 'Storno bis',
        'refund_percent' => 'Erstattung in %',
        'fee' => 'Bearbeitungsgebühr',
        'note' => 'Hinweis',
    ],

    'hints' => [
        'valid_until' => 'Es gilt die früheste Stufe, deren Frist noch nicht abgelaufen ist. Nach der letzten Stufe wird nichts erstattet.',
        'fee' => 'Wird von der Erstattung abgezogen, in Euro.',
    ],

];
