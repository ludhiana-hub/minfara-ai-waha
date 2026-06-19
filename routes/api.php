<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\BotConfigController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\InternalNotificationController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\NotificationLogController;
use App\Http\Controllers\Api\WahaController;
use App\Http\Controllers\Api\WhatsAppController;
use Illuminate\Support\Facades\Route;

// ── WhatsApp webhook — dipanggil oleh WAHA (public) ──────────────────────────
Route::post('/whatsapp/webhook', [WhatsAppController::class, 'webhook']);

// ── FAQ ───────────────────────────────────────────────────────────────────────
// Static routes HARUS di atas /{faq} agar tidak tertangkap sebagai ID
Route::prefix('faqs')->group(function () {
    Route::get('/',              [FaqController::class, 'index']);
    Route::post('/',             [FaqController::class, 'store']);
    Route::post('/reorder',      [FaqController::class, 'reorder']);      // sebelum /{faq}
    Route::post('/bulk-toggle',  [FaqController::class, 'bulkToggle']);   // sebelum /{faq}
    Route::get('/{faq}',         [FaqController::class, 'show']);
    Route::put('/{faq}',         [FaqController::class, 'update']);
    Route::delete('/{faq}',      [FaqController::class, 'destroy']);
    Route::post('/{faq}/toggle', [FaqController::class, 'toggle']);
});

// ── Log ───────────────────────────────────────────────────────────────────────
// stats, stream, export HARUS di atas /{id} jika ada
Route::prefix('logs')->group(function () {
    Route::get('/',       [LogController::class, 'index']);
    Route::get('/stats',  [LogController::class, 'stats']);
    Route::get('/stream', [LogController::class, 'stream']);
    Route::get('/export', [LogController::class, 'export']);
});

// ── WAHA ──────────────────────────────────────────────────────────────────────
Route::prefix('waha')->group(function () {
    Route::get('/status',     [WahaController::class, 'status']);
    Route::post('/send',      [WahaController::class, 'send']);
    Route::post('/reconnect', [WahaController::class, 'reconnect']);
});

// ── Config ────────────────────────────────────────────────────────────────────
Route::get('/config',  [BotConfigController::class, 'index']);
Route::put('/config',  [BotConfigController::class, 'update']);

// ── Notification API (auth via X-Internal-Key header) ────────────────────────
Route::post('/notification/notify', [InternalNotificationController::class, 'send'])
     ->middleware('auth.internal');

// ── Notification Logs ─────────────────────────────────────────────────────────
Route::prefix('notification/logs')->group(function () {
    Route::get('/',     [NotificationLogController::class, 'index']);
    Route::get('/{id}', [NotificationLogController::class, 'show']);
});

// ── Analytics API ─────────────────────────────────────────────────────────────
Route::prefix('analytics')->group(function () {
    Route::get('/summary',  [AnalyticsController::class, 'summary']);
    Route::get('/sessions', [AnalyticsController::class, 'sessions']);
    Route::get('/faq-gaps', [AnalyticsController::class, 'faqGaps']);
    Route::post('/run',     [AnalyticsController::class, 'run'])->middleware('auth.internal');
});
