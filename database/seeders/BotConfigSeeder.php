<?php

namespace Database\Seeders;

use App\Models\BotConfig;
use Illuminate\Database\Seeder;

class BotConfigSeeder extends Seeder
{
    public function run(): void
    {
        $systemPrompt = <<<'PROMPT'
Kamu adalah MinFara AI, asisten virtual berbasis kecerdasan buatan milik
Deutsch Lernen mit Fara (DlmF) — platform kursus Bahasa Jerman online & offline
terpercaya di Bandung, Indonesia.
Website: https://mitfara.com | WA Admin: +62 896-4789-7616

IDENTITAS:
- Nama: MinFara AI
- Peran: AI assistant resmi DlmF, siap menjawab pertanyaan 24/7
- Karakter: ramah, antusias, suportif — seperti teman yang tahu segalanya tentang DlmF
- Kamu BUKAN pengganti admin manusia. Untuk keputusan pendaftaran, pembayaran,
  atau jadwal spesifik, selalu arahkan ke admin via *99*.

INFORMASI BISNIS:
- Program: Kelas Reguler A1-B1 (online & offline), Private Grammatik,
  Private Persiapan Goethe, Sprachkurs mit Muttersprachler (native speaker),
  Private Kinder (anak), Deutsch FlexiLearn (asinkronus), Program Au Pair
- Harga Online: mulai Rp1.499.000 (reguler), mulai Rp895.000 (private), mulai Rp149.000 (FlexiLearn)
- Harga Offline: mulai Rp2.099.000 (reguler), mulai Rp1.400.000 (private)
- Lokasi offline: Jl. Terusan Sari Asih No. 76, Sarijadi, Bandung
- Platform online: Microsoft Teams
- Garansi: free class jika belum lulus ujian (S&K berlaku)
- Tutor bersertifikasi, ada native speaker, 5.000+ alumni
- Bundling A1+B1 hemat hingga Rp1.000.000

ATURAN MENJAWAB:
1. Perkenalkan diri sebagai "MinFara AI" jika user baru pertama kali
2. Jawab dalam Bahasa Indonesia yang ramah, hangat, dan profesional
3. Maksimal 3 paragraf, singkat dan langsung ke inti
4. Jika tidak yakin info spesifik → sarankan hubungi admin
5. Jika di luar topik DlmF/bahasa Jerman → tolak sopan, arahkan ke menu
6. Selalu akhiri: "Ketik *0* untuk menu utama atau *99* untuk chat langsung dengan admin."
7. Panggil user dengan "Kamu"
8. Boleh sisipkan kata Jerman sederhana sesekali (contoh: "Sehr gut! 👍")
PROMPT;

        $configs = [
            ['key' => 'bot_name', 'value' => 'MinFara AI', 'type' => 'text', 'label' => 'Nama Bot', 'group' => 'general'],
            ['key' => 'bot_greeting', 'value' => 'halo,hai,hi,hello,hallo,mulai,start,menu,help', 'type' => 'text', 'label' => 'Kata Sapaan (pisah koma)', 'group' => 'general'],
            ['key' => 'ai_enabled', 'value' => 'true', 'type' => 'boolean', 'label' => 'Aktifkan AI Fallback', 'group' => 'ai'],
            ['key' => 'ai_provider', 'value' => 'gemini', 'type' => 'text', 'label' => 'Provider AI (gemini/groq)', 'description' => 'Provider AI yang digunakan: gemini atau groq', 'group' => 'ai'],
            ['key' => 'ai_max_tokens', 'value' => '500', 'type' => 'number', 'label' => 'Maksimal Token AI', 'description' => 'Batas token output AI (100-2000)', 'group' => 'ai'],
            ['key' => 'ai_temperature', 'value' => '0.7', 'type' => 'number', 'label' => 'Temperatur AI (0.0-1.0)', 'description' => 'Semakin tinggi semakin kreatif, semakin rendah semakin konsisten', 'group' => 'ai'],
            ['key' => 'ai_system_prompt', 'value' => $systemPrompt, 'type' => 'textarea', 'label' => 'System Prompt AI', 'group' => 'ai'],
            ['key' => 'footer_faq', 'value' => "─────────────────\nKetik *0* menu utama | *99* hubungi admin", 'type' => 'textarea', 'label' => 'Footer Pesan FAQ', 'group' => 'message'],
            ['key' => 'footer_ai', 'value' => "─────────────────\n_MinFara AI_ 🤖 _· Deutsch Lernen mit Fara_\nKetik *0* menu utama | *99* hubungi admin", 'type' => 'textarea', 'label' => 'Footer Pesan AI', 'group' => 'message'],
            ['key' => 'fallback_message', 'value' => "Entschuldigung! 🙏 MinFara AI sedang tidak dapat memproses pertanyaanmu saat ini.\n\nSilakan coba beberapa saat lagi, atau ketik *99* untuk langsung terhubung dengan admin MinFara kami. Danke! 😊\n─────────────────\n_MinFara AI_ 🤖 _· Deutsch Lernen mit Fara_\nKetik *0* menu utama | *99* hubungi admin", 'type' => 'textarea', 'label' => 'Pesan Error AI', 'group' => 'message'],
            ['key' => 'admin_wa', 'value' => '6289647897616', 'type' => 'text', 'label' => 'Nomor WA Admin', 'description' => 'Format: 628xxx tanpa + dan spasi', 'group' => 'contact'],
            ['key' => 'admin_wa_label', 'value' => 'MinFara Admin', 'type' => 'text', 'label' => 'Label Admin WA', 'group' => 'contact'],
            ['key' => 'office_hours', 'value' => 'Senin–Sabtu, 08.00–20.00 WIB', 'type' => 'text', 'label' => 'Jam Operasional', 'group' => 'contact'],
            ['key' => 'waha_url', 'value' => 'http://localhost:3000', 'type' => 'text', 'label' => 'WAHA Server URL', 'description' => 'URL server WAHA, contoh: http://localhost:3000', 'group' => 'api'],
            ['key' => 'waha_api_key', 'value' => '', 'type' => 'password', 'label' => 'WAHA API Key', 'description' => 'API Key untuk autentikasi ke server WAHA', 'group' => 'api'],
            ['key' => 'waha_session', 'value' => 'default', 'type' => 'text', 'label' => 'WAHA Session Name', 'description' => 'Nama sesi WhatsApp di WAHA', 'group' => 'api'],
            ['key' => 'gemini_api_key', 'value' => '', 'type' => 'password', 'label' => 'Gemini API Key', 'description' => 'API Key Google Gemini dari Google AI Studio', 'group' => 'api'],
            ['key' => 'gemini_model', 'value' => 'gemini-2.0-flash', 'type' => 'text', 'label' => 'Gemini Model', 'description' => 'Model Gemini yang digunakan, contoh: gemini-2.0-flash', 'group' => 'api'],
            ['key' => 'groq_api_key', 'value' => '', 'type' => 'password', 'label' => 'Groq API Key', 'description' => 'API Key Groq dari console.groq.com', 'group' => 'api'],
            ['key' => 'groq_model', 'value' => 'llama-3.3-70b-versatile', 'type' => 'text', 'label' => 'Groq Model', 'description' => 'Model Groq yang digunakan, contoh: llama-3.3-70b-versatile', 'group' => 'api'],
        ];

        foreach ($configs as $config) {
            BotConfig::updateOrCreate(['key' => $config['key']], $config);
        }
    }
}
