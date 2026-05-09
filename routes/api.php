<?php

use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::post('/whatsapp/webhook', [WhatsAppController::class, 'handle']);
