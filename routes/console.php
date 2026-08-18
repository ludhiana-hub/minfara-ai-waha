<?php

use App\Jobs\KnowledgeSynthesizerJob;
use App\Jobs\RebuildKnowledgeIndexJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pastikan sesi WAHA selalu WORKING — auto-restart jika session mati setelah container restart
Schedule::command('waha:ensure-session')->everyMinute();

// Sebelumnya cuma dijalankan sekali di entrypoint.sh saat container start — kalau webhook
// config drift setelah boot (mis. WAHA session di-recreate), tidak ada yang re-assert sampai
// container di-restart. Command ini idempotent, aman dipanggil berulang.
Schedule::command('waha:ensure-webhook')->everyThirtyMinutes();

// Analisis percakapan WA setiap hari pukul 02:00
// Memproses percakapan kemarin — klasifikasi topik, sentiment, perilaku customer
Schedule::command('analytics:analyse')->dailyAt('02:00');

// Sintesis knowledge & coaching sales dari percakapan kemarin — setiap hari pukul 03:00
// (1 jam setelah analytics:analyse selesai). Mengekstrak Q&A dari percakapan sukses
// + rekomendasi coaching dari percakapan objector/minat rendah → saran pending untuk direview admin.
Schedule::job(new KnowledgeSynthesizerJob())->dailyAt('03:00');

// Jaring pengaman: rebuild index embedding knowledge_chunks tiap malam, jaga-jaga kalau
// ada drift dari perubahan yang gak lewat CMS (misal seed manual di DB).
Schedule::job(new RebuildKnowledgeIndexJob())->dailyAt('03:30');

// Buang baris ai_request_traces lama — hot path chat sudah minim tulisan (mode on_retry),
// tapi profil batch/eval nulis 'always', jadi tetap perlu retensi.
Schedule::command('ai:prune-traces --days=14')->dailyAt('04:00');
