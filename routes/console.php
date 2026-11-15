<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Unpaid places are released once their payment window ran out (W-22). A
// place past its window already stops counting, so five minutes of delay
// only affect the status shown, never the places available.
Schedule::command('registrations:expire')->everyFiveMinutes()->withoutOverlapping();
