<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'MinFara AI Bot API',
    description: 'REST API untuk MinFara WhatsApp AI Bot Services. Endpoint meliputi FAQ, log percakapan, konfigurasi bot, status WAHA, dan Notification API.',
    contact: new OA\Contact(email: 'admin@mitfara.com'),
)]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: 'Server aktif (APP_URL / L5_SWAGGER_CONST_HOST)')]
#[OA\SecurityScheme(
    securityScheme: 'InternalApiKey',
    type: 'apiKey',
    in: 'header',
    name: 'X-Internal-Key',
    description: 'API key untuk Notification API. Nilai dari INTERNAL_API_KEY environment variable, diverifikasi dengan hash_equals().',
)]
#[OA\Tag(name: 'Webhook', description: 'WAHA WhatsApp webhook receiver')]
#[OA\Tag(name: 'FAQ', description: 'Manajemen FAQ dan menu bot')]
#[OA\Tag(name: 'Log', description: 'Log percakapan WhatsApp')]
#[OA\Tag(name: 'WAHA', description: 'Kontrol sesi WAHA WhatsApp API')]
#[OA\Tag(name: 'Config', description: 'Konfigurasi bot')]
#[OA\Tag(name: 'Notification', description: 'Notification API — kirim notifikasi WhatsApp ke tim internal, autentikasi via X-Internal-Key header')]
#[OA\Tag(name: 'Notification Log', description: 'Log pengiriman notifikasi WhatsApp — riwayat status, rendered message, dan respons WAHA')]
#[OA\Tag(name: 'Analytics', description: 'Customer behavior analytics dari hasil analisis AI percakapan WhatsApp')]
abstract class Controller
{
    //
}
