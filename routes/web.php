<?php

use App\Http\Controllers\Cms\AnalyticsController;
use App\Http\Controllers\Cms\DashboardController;
use App\Http\Controllers\Cms\FaqController;
use App\Http\Controllers\Cms\FaqSuggestionController;
use App\Http\Controllers\Cms\KonfigurasiController;
use App\Http\Controllers\Cms\LogController;
use App\Http\Controllers\Cms\NotificationLogController;
use App\Http\Controllers\Cms\NotificationTargetController;
use App\Http\Controllers\Cms\NotificationTemplateController;
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

    // ── Notification System ───────────────────────────────────────────────────
    Route::resource('notification-templates', NotificationTemplateController::class)
         ->except(['show']);
    Route::resource('notification-targets', NotificationTargetController::class)
         ->except(['show']);
    Route::patch('notification-targets/{notificationTarget}/toggle', [NotificationTargetController::class, 'toggle'])
         ->name('notification-targets.toggle');
    Route::get('notification-logs', [NotificationLogController::class, 'index'])
         ->name('notification-logs.index');

    // ── Analytics ─────────────────────────────────────────────────────────────
    Route::get('analytics',      [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::post('analytics/run', [AnalyticsController::class, 'run'])->name('analytics.run');

    // ── FAQ Suggestions ───────────────────────────────────────────────────────
    Route::get('faq-suggestions',               [FaqSuggestionController::class, 'index'])->name('faq-suggestions.index');
    Route::get('faq-suggestions/{faqSuggestion}/approve', [FaqSuggestionController::class, 'approve'])->name('faq-suggestions.approve');
    Route::patch('faq-suggestions/{faqSuggestion}/reject', [FaqSuggestionController::class, 'reject'])->name('faq-suggestions.reject');

    // WAHA status AJAX endpoint
    Route::get('api/waha-status', [DashboardController::class, 'wahaStatus'])->name('api.waha-status');
});
