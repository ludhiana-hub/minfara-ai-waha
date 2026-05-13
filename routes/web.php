<?php

use App\Http\Controllers\Cms\DashboardController;
use App\Http\Controllers\Cms\FaqController;
use App\Http\Controllers\Cms\KonfigurasiController;
use App\Http\Controllers\Cms\LogController;
use App\Http\Controllers\Cms\TestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('cms-minfara')->name('cms.')->middleware('localhost')->group(function () {
    Route::get('/',                     [DashboardController::class, 'index'])->name('dashboard');
    Route::get('faq',                   [FaqController::class, 'index'])->name('faq.index');
    Route::get('faq/create',            [FaqController::class, 'create'])->name('faq.create');
    Route::post('faq',                  [FaqController::class, 'store'])->name('faq.store');
    Route::get('faq/{faq}/edit',        [FaqController::class, 'edit'])->name('faq.edit');
    Route::put('faq/{faq}',             [FaqController::class, 'update'])->name('faq.update');
    Route::delete('faq/{faq}',          [FaqController::class, 'destroy'])->name('faq.destroy');
    Route::patch('faq/{faq}/toggle',    [FaqController::class, 'toggle'])->name('faq.toggle');
    Route::post('faq/reorder',          [FaqController::class, 'reorder'])->name('faq.reorder');
    Route::get('konfigurasi',           [KonfigurasiController::class, 'index'])->name('konfigurasi.index');
    Route::post('konfigurasi',          [KonfigurasiController::class, 'update'])->name('konfigurasi.update');
    Route::get('log',                   [LogController::class, 'index'])->name('log.index');
    Route::get('log/{id}',              [LogController::class, 'show'])->name('log.show');
    Route::delete('log/clear',          [LogController::class, 'clear'])->name('log.clear');
    Route::get('test',                  [TestController::class, 'index'])->name('test.index');
    Route::post('test',                 [TestController::class, 'send'])->name('test.send');

    // WAHA status AJAX endpoint
    Route::get('api/waha-status', function () {
        try {
            $wahaUrl  = \App\Models\BotConfig::get('waha_url')     ?: config('services.waha.url', 'http://localhost:3000');
            $wahaKey  = \App\Models\BotConfig::get('waha_api_key') ?: config('services.waha.api_key', '');
            $session  = \App\Models\BotConfig::get('waha_session') ?: config('services.waha.session', 'default');
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->withHeaders(['X-Api-Key' => $wahaKey])
                ->get("{$wahaUrl}/api/sessions/{$session}");
            if ($response->ok()) {
                $data = $response->json();
                return response()->json([
                    'connected' => $data['status'] === 'WORKING',
                    'status'    => $data['status'] ?? 'UNKNOWN',
                ]);
            }
        } catch (\Exception) {}
        return response()->json(['connected' => false, 'status' => 'DISCONNECTED']);
    })->name('api.waha-status');
});
