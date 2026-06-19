<?php

namespace App\Services;

use App\Models\ConversationAnalysis;
use Illuminate\Support\Facades\Artisan;

class AnalyticsRunner
{
    /**
     * Jalankan analisis untuk satu tanggal secara synchronous,
     * lalu kembalikan statistik hasil nyata.
     *
     * Sumber-tunggal yang dipakai CMS controller (web) maupun
     * API controller (mode sync) agar konsisten untuk microservices.
     *
     * @return array{date:string, analyzed:int, failed:int, empty:bool}
     */
    public function runForDate(string $date, bool $force = true): array
    {
        @set_time_limit(300); // analisis NVIDIA per sesi bisa lama

        $args = ['--date' => $date];
        if ($force) {
            $args['--force'] = true;
        }
        Artisan::call('analytics:analyse', $args);

        $analyses = ConversationAnalysis::forDate($date)->get();
        $total    = $analyses->count();
        $failed   = $analyses->where('topic', 'Gagal dianalisis')->count();

        return [
            'date'     => $date,
            'analyzed' => $total,
            'failed'   => $failed,
            'empty'    => $total === 0,
        ];
    }
}
