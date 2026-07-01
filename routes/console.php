<?php

use App\Jobs\KnowledgeSynthesizerJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pastikan sesi WAHA selalu WORKING — auto-restart jika session mati setelah container restart
Schedule::command('waha:ensure-session')->everyMinute();

// Analisis percakapan WA setiap hari pukul 02:00
// Memproses percakapan kemarin — klasifikasi topik, sentiment, perilaku customer
Schedule::command('analytics:analyse')->dailyAt('02:00');

// Sintesis knowledge dari percakapan sukses — setiap Senin pukul 03:00
// Mengekstrak Q&A terbaik dari percakapan minggu lalu → update dynamic_knowledge
Schedule::job(new KnowledgeSynthesizerJob())->weeklyOn(1, '03:00');
