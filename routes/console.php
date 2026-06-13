<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Analisis percakapan WA setiap hari pukul 02:00
// Memproses percakapan kemarin — klasifikasi topik, sentiment, perilaku customer
Schedule::command('analytics:analyse')->dailyAt('02:00');
