<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('fixed-assets:dispatch-maintenance')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('backup:clean')
    ->dailyAt('01:00')
    ->withoutOverlapping();

Schedule::command('backup:run')
    ->dailyAt('01:30')
    ->withoutOverlapping();
