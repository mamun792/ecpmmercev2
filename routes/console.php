<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();



// Schedule::command('app:check-order-delivery-status')
//     ->hourly()
//     ->withoutOverlapping()
//     ->onOneServer();


// Schedule::command('app:check-order-delivery-status')
//     ->everyMinute()
//     ->withoutOverlapping(55)
//     ->onOneServer();


// Schedule::command('app:check-order-delivery-status')->everyMinute(); // remove withoutOverlapping()

Schedule::command('app:check-order-delivery-status')
    ->everyMinute();
  // Ensure it runs on one server only
