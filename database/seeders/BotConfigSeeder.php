<?php

namespace Database\Seeders;

use App\Models\BotConfig;
use Illuminate\Database\Seeder;

class BotConfigSeeder extends Seeder
{
    public function run(): void
    {
        $systemPrompt = <<<'PROMPT'
Kamu adalah *MinFara AI*, konsultan Bahasa Jerman dari *Deutsch Lernen mit Fara (DlmF)* — kursus Bahasa Jerman di Bandung. https://mitfara.com | Admin: +62 896-4789-7616

PERANMU: Ahli Bahasa Jerman yang hangat dan asyik diajak ngobrol. Kuasai grammar, kosakata, level A1–C2, sertifikasi Goethe/telc/ÖSD, au pair, Ausbildung, dan studi di Jerman. Konsultasi dengan contoh kalimat nyata — bukan teori kering.

FORMAT WA (WAJIB):
• *bold* untuk info penting | _italic_ untuk kata/kalimat Jerman | • atau - untuk daftar
• Pisahkan paragraf dengan baris kosong
• DILARANG: # heading, tabel, HTML, **double asterisk**
• Emoji secukupnya | Jawaban pendek untuk pertanyaan singkat, detail untuk pertanyaan teknis

CARA MENJAWAB:
1. Fokus pada Bahasa Jerman & program DlmF
2. Bahasa Indonesia — contoh Jerman selalu _italic_ + terjemahan
3. Grammar/vocab → contoh kalimat nyata, jelaskan pola dengan jelas
4. Info DlmF → gunakan INFO di bawah
5. Keputusan (daftar/bayar/jadwal pasti) → arahkan ke *99*
6. Di luar topik Jerman/DlmF → jawab 1–2 kalimat, lalu: "Untuk Bahasa Jerman atau info kursus DlmF, aku siap bantu! 😊"
7. Jangan tambah footer "ketik *0*" — sudah otomatis | Panggil "kamu" | Perkenalkan diri saat pertama menyapa
8. Sisipkan ekspresi Jerman sesekali: _Sehr gut!_ _Wunderbar!_ _Prima!_ _Genau!_ _Kein Problem!_

INFO DlmF:
• *Program:* Reguler A1–B1 (online & offline), Private Grammatik, Persiapan Goethe, Native Speaker, Kinder, FlexiLearn, Au Pair
• *Online:* Rp1.499.000+ (reguler) | Rp895.000+ (private) | Rp149.000+ (FlexiLearn)
• *Offline:* Rp2.099.000+ (reguler) | Rp1.400.000+ (private) | Jl. Terusan Sari Asih 76, Sarijadi, Bandung
• Platform: Microsoft Teams | 5.000+ alumni | Bersertifikasi | Native speaker | Garansi free class | Bundling A1+B1 hemat s/d Rp1.000.000
PROMPT;

        $configs = [
            ['key' => 'bot_name',            'value' => 'MinFara AI',                                    'type' => 'text',     'label' => 'Nama Bot',                        'group' => 'general'],
            ['key' => 'bot_greeting',         'value' => 'halo,hai,hi,hello,hallo,mulai,start,menu,help',  'type' => 'text',     'label' => 'Kata Sapaan (pisah koma)',         'group' => 'general'],
            ['key' => 'ai_enabled',           'value' => 'true',                                           'type' => 'boolean',  'label' => 'Aktifkan AI Fallback',             'group' => 'ai'],
            ['key' => 'ai_provider_order',    'value' => 'groq,gemini,openrouter',                         'type' => 'text',     'label' => 'Urutan Provider AI',               'description' => 'Urutan prioritas provider, dipisah koma. Contoh: groq,gemini,openrouter', 'group' => 'ai'],
            ['key' => 'ai_max_tokens',        'value' => '400',                                            'type' => 'number',   'label' => 'Maksimal Token AI',                'description' => 'Batas token output AI (100-2000)', 'group' => 'ai'],
            ['key' => 'ai_temperature',       'value' => '0.7',                                            'type' => 'number',   'label' => 'Temperatur AI (0.0-1.0)',          'description' => 'Semakin tinggi semakin kreatif, semakin rendah semakin konsisten', 'group' => 'ai'],
            ['key' => 'ai_system_prompt',     'value' => $systemPrompt,                                    'type' => 'textarea', 'label' => 'System Prompt AI',                 'group' => 'ai'],
            ['key' => 'footer_faq',           'value' => "─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                                                                                                            'type' => 'textarea', 'label' => 'Footer Pesan FAQ',                'group' => 'message'],
            ['key' => 'footer_ai',            'value' => "─────────────────\n_MinFara AI_ 🤖 _· Deutsch Lernen mit Fara_\nKetik *0* menu utama | *99* hubungi admin",
                                                                                                            'type' => 'textarea', 'label' => 'Footer Pesan AI',                 'group' => 'message'],
            ['key' => 'fallback_message',     'value' => "Entschuldigung! 🙏 MinFara AI sedang tidak dapat memproses pertanyaanmu saat ini.\n\nSilakan coba beberapa saat lagi, atau ketik *99* untuk langsung terhubung dengan admin MinFara kami. Danke! 😊\n─────────────────\n_MinFara AI_ 🤖 _· Deutsch Lernen mit Fara_\nKetik *0* menu utama | *99* hubungi admin",
                                                                                                            'type' => 'textarea', 'label' => 'Pesan Error AI',                  'group' => 'message'],
            ['key' => 'admin_wa',             'value' => '6289647897616',                                  'type' => 'text',     'label' => 'Nomor WA Admin',                   'description' => 'Format: 628xxx tanpa + dan spasi', 'group' => 'contact'],
            ['key' => 'admin_wa_label',       'value' => 'MinFara Admin',                                  'type' => 'text',     'label' => 'Label Admin WA',                   'group' => 'contact'],
            ['key' => 'office_hours',         'value' => 'Senin–Sabtu, 08.00–20.00 WIB',                   'type' => 'text',     'label' => 'Jam Operasional',                  'group' => 'contact'],
            ['key' => 'waha_url',             'value' => 'http://localhost:3000',                           'type' => 'text',     'label' => 'WAHA Server URL',                  'description' => 'URL server WAHA, contoh: http://localhost:3000', 'group' => 'api'],
            ['key' => 'waha_api_key',         'value' => '',                                                'type' => 'password', 'label' => 'WAHA API Key',                     'description' => 'API Key untuk autentikasi ke server WAHA', 'group' => 'api'],
            ['key' => 'waha_session',         'value' => 'default',                                         'type' => 'text',     'label' => 'WAHA Session Name',                'description' => 'Nama sesi WhatsApp di WAHA', 'group' => 'api'],
            ['key' => 'gemini_api_key',       'value' => '',                                                'type' => 'password', 'label' => 'Gemini API Key',                   'description' => 'API Key Google Gemini dari Google AI Studio', 'group' => 'api'],
            ['key' => 'gemini_model',         'value' => 'gemini-2.0-flash',                               'type' => 'text',     'label' => 'Gemini Model',                     'description' => 'Model Gemini yang digunakan, contoh: gemini-2.0-flash', 'group' => 'api'],
            ['key' => 'groq_api_key',         'value' => '',                                                'type' => 'password', 'label' => 'Groq API Key',                     'description' => 'API Key Groq dari console.groq.com', 'group' => 'api'],
            ['key' => 'groq_model',           'value' => 'llama-3.3-70b-versatile',                        'type' => 'text',     'label' => 'Groq Model',                       'description' => 'Model Groq yang digunakan, contoh: llama-3.3-70b-versatile', 'group' => 'api'],
            ['key' => 'openrouter_api_key',   'value' => '',                                                'type' => 'password', 'label' => 'OpenRouter API Key',               'description' => 'API Key dari openrouter.ai', 'group' => 'api'],
            ['key' => 'openrouter_model',     'value' => 'qwen/qwen3-14b:free',                             'type' => 'text',     'label' => 'OpenRouter Model',                 'description' => 'Semua model bertanda :free GRATIS. Contoh: qwen/qwen3-14b:free', 'group' => 'api'],
        ];

        foreach ($configs as $config) {
            BotConfig::updateOrCreate(['key' => $config['key']], $config);
        }
    }
}
