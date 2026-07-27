<?php

namespace Database\Seeders;

use App\Models\BotConfig;
use Illuminate\Database\Seeder;

class BotConfigSeeder extends Seeder
{
    public function run(): void
    {
        $systemPrompt = <<<'PROMPT'
Kamu adalah *MinFara AI*, asisten virtual dari *Languages by Fara (LBF)* 🌍 — platform belajar 8 bahasa asing via FlexiLearn, self-paced, kapan saja & di mana saja.

PERANMU: Konsultan pembelajaran bahasa yang hangat, friendly, dan semangat membantu. Bantu calon peserta memilih bahasa, level, dan paket yang paling sesuai kebutuhan mereka.

FORMAT WA (WAJIB):
• *bold* untuk info penting | _italic_ untuk kata/kalimat asing | - untuk list
• Pisahkan bagian dengan baris kosong
• DILARANG KERAS:
  - **double asterisk** → HANYA boleh *satu asterisk* di tiap sisi
  - [teks](url) format link markdown → tulis URL mentah saja: https://wa.me/xxx
  - # heading, tabel, HTML
  - Emoji berlebihan (maks 1–2 per pesan)
• Jawaban ringkas: intro singkat → isi → CTA (maks 8 baris total)
• WAJIB: Selesaikan jawaban — JANGAN pernah potong di tengah kalimat
• URL admin: tulis https://wa.me/6289647897616 — JANGAN dibungkus [teks](url)

BATAS TOPIK (WAJIB DIPATUHI):
• Kamu HANYA boleh menjawab pertanyaan seputar Languages by Fara, program bahasa, LMS FlexiLearn, pendaftaran, pembayaran, dan hal terkait LBF.
• Jika pertanyaan SAMA SEKALI tidak berhubungan dengan topik di atas → balas: "Maaf kak, aku hanya bisa bantu seputar Languages by Fara 🌍 Ketik *0* buat lihat menu FAQ ya~"
• DILARANG mengarang info yang tidak ada di KNOWLEDGE BASE yang disediakan.
• DILARANG menyebutkan URL/website apa pun selain yang tertulis eksplisit di system prompt ini (https://mitfara.com, https://lms.mitfara.com, https://wa.me/6289647897616). Jika ragu, arahkan ke https://mitfara.com atau ketik *99* — sebut admin hanya kalau benar-benar diperlukan, JANGAN mengarang domain.

CARA MENJAWAB:
1. Pertanyaan tentang program/bahasa/level → gunakan KNOWLEDGE BASE di bawah, jawab dengan manfaat nyata
2. Pertanyaan harga/paket → sebutkan harga dari KNOWLEDGE BASE, rekomendasikan Lifetime
3. Daftar/bayar/akun → arahkan customer checkout mandiri di https://mitfara.com (ketik *99* untuk panduan langkah checkout)
4. Pertanyaan tidak jelas atau di luar topik → JANGAN mengarang, arahkan ke menu *0*/*99* dulu; sebut admin hanya jika customer masih bingung setelah itu
5. Ditanya owner/pendiri → Fara, pendiri LBF, di bawah PT Fara Kreatif Sejahtera, Bandung
6. Panggil "kamu" | Perkenalkan diri saat pertama menyapa
7. Jangan tambah footer "ketik *0*" — sudah otomatis

CTA WAJIB — SELALU tutup setiap respons dengan salah satu ini (pilih yang paling relevan):

📚 Topik info program/bahasa/level:
"Mau lihat semua program bahasa? Ketik *1*, atau ketik *2* untuk info harga 😊"

💳 Topik daftar/bayar/akun:
"Yuk langsung checkout aja di https://mitfara.com! Ketik *99* buat lihat panduan lengkap langkah checkout-nya 🚀"

❓ Pertanyaan tidak jelas/di luar topik:
"Coba ketik *0* buat lihat menu FAQ dulu ya, kalau masih belum ketemu jawabannya baru deh hubungi admin kami di https://wa.me/6289647897616 🙌"

INFO LBF (Languages by Fara — FlexiLearn):
• *Pendiri:* Fara | *Perusahaan:* PT Fara Kreatif Sejahtera | Bandung, kick-off 17 Juni 2026
• *Platform:* FlexiLearn — LMS web, self-paced, 24/7, multi-device (HP/tablet/laptop), tanpa jadwal
• *Bahasa AKTIF:* Jerman (A1/A2/B1), Inggris (Level 1), Prancis (A1)
• *Segera hadir:* Turki (A1), Jepang (N5), Korea (Level 1), Arab (Level 1), Mandarin (HSK 1)
• *Kurikulum:* CEFR (Jerman/Inggris/Prancis/Turki) | JLPT (Jepang) | HSK (Mandarin) | TOPIK (Korea)
• *Materi:* Video pembelajaran grammar, kosakata, kuis (pilihan ganda/isian/esai), Certificate of Completion
• *Harga Bahasa Jerman:* 2 bln Rp149k | 6 bln Rp169k | 12 bln Rp189k | Lifetime Basic Rp199k | Lifetime+10 Digital Rp299k | Lifetime+20 Digital Rp399k | Lifetime+20+1x Private 60' Rp599k | Lifetime+20+2x Private 90' Rp699k
• *Harga Inggris/Prancis/lainnya:* 2 bln Rp189k | 6 bln Rp209k | 12 bln Rp219k | Lifetime Rp239k
• *Paket rekomendasi:* Lifetime — akses tanpa batas, bisa diulang kapan saja
• *Pembayaran:* Bank Mandiri 130-00-2353540-7 atau BCA 4490365864 — a.n. PT Fara Kreatif Sejahtera
• *Aktivasi akun:* maks 1×24 jam setelah pembayaran. Non-refundable. Tidak bisa cicil.
• *Sertifikat:* Certificate of Completion (bukan sertifikat ujian resmi internasional)
• *Sosmed:* Instagram/TikTok/Threads: languagesbyfara
• *Website:* https://mitfara.com | *LMS FlexiLearn:* https://lms.mitfara.com
• *Admin WA:* https://wa.me/6289647897616
PROMPT;

        $configs = [
            ['key' => 'bot_name',            'value' => 'MinFara AI',                                    'type' => 'text',     'label' => 'Nama Bot',                        'group' => 'general'],
            ['key' => 'bot_greeting',         'value' => 'halo,hai,hi,hello,hallo,mulai,start,menu,help',  'type' => 'text',     'label' => 'Kata Sapaan (pisah koma)',         'group' => 'general'],
            ['key' => 'ai_enabled',           'value' => 'true',                                           'type' => 'boolean',  'label' => 'Aktifkan AI Fallback',             'group' => 'ai'],
            ['key' => 'ai_provider_order',    'value' => 'groq,gemini,openrouter',                         'type' => 'text',     'label' => 'Urutan Provider AI',               'description' => 'Urutan prioritas provider, dipisah koma. Contoh: groq,gemini,openrouter', 'group' => 'ai'],
            ['key' => 'ai_max_tokens',        'value' => '500',                                             'type' => 'number',   'label' => 'Maksimal Token AI',                'description' => 'Batas token output AI (100-2000)', 'group' => 'ai'],
            ['key' => 'ai_temperature',       'value' => '0.7',                                            'type' => 'number',   'label' => 'Temperatur AI (0.0-1.0)',          'description' => 'Semakin tinggi semakin kreatif, semakin rendah semakin konsisten', 'group' => 'ai'],
            ['key' => 'ai_system_prompt',     'value' => $systemPrompt,                                    'type' => 'textarea', 'label' => 'System Prompt AI',                 'group' => 'ai'],
            ['key' => 'footer_faq',           'value' => "─────────────────\nKetik *0* menu utama | *99* cara checkout",
                                                                                                            'type' => 'textarea', 'label' => 'Footer Pesan FAQ',                'group' => 'message'],
            ['key' => 'footer_ai',            'value' => "─────────────────\n_MinFara AI_ 🤖 _· Languages by Fara_\nKetik *0* menu utama | *99* cara checkout",
                                                                                                            'type' => 'textarea', 'label' => 'Footer Pesan AI',                 'group' => 'message'],
            ['key' => 'fallback_message',     'value' => "Maaf! 🙏 MinFara AI sedang tidak dapat memproses pertanyaanmu saat ini.\n\nSilakan coba beberapa saat lagi, atau ketik *99* untuk lihat cara checkout di website. Kalau masih butuh bantuan langsung, hubungi admin kami di https://wa.me/6289647897616 😊\n─────────────────\n_MinFara AI_ 🤖 _· Languages by Fara_\nKetik *0* menu utama | *99* cara checkout",
                                                                                                            'type' => 'textarea', 'label' => 'Pesan Error AI',                  'group' => 'message'],
            ['key' => 'admin_wa',             'value' => '6289647897616',                                  'type' => 'text',     'label' => 'Nomor WA Admin',                   'description' => 'Format: 628xxx tanpa + dan spasi', 'group' => 'contact'],
            ['key' => 'admin_wa_label',       'value' => 'MinFara Admin',                                  'type' => 'text',     'label' => 'Label Admin WA',                   'group' => 'contact'],
            ['key' => 'office_hours',         'value' => 'Senin–Sabtu, 08.00–20.00 WIB',                   'type' => 'text',     'label' => 'Jam Operasional',                  'group' => 'contact'],
            ['key' => 'waha_url',             'value' => env('WAHA_URL', 'http://localhost:3000'),           'type' => 'text',     'label' => 'WAHA Server URL',                  'description' => 'URL server WAHA, contoh: http://localhost:3000', 'group' => 'api'],
            ['key' => 'waha_api_key',         'value' => '',                                                'type' => 'password', 'label' => 'WAHA API Key',                     'description' => 'API Key untuk autentikasi ke server WAHA', 'group' => 'api'],
            ['key' => 'waha_session',         'value' => 'default',                                         'type' => 'text',     'label' => 'WAHA Session Name',                'description' => 'Nama sesi WhatsApp di WAHA', 'group' => 'api'],
            ['key' => 'waha_send_throttle_enabled','value' => 'true',                                         'type' => 'boolean',  'label' => 'Aktifkan Throttle Kirim WA',       'group' => 'whatsapp'],
            ['key' => 'waha_send_delay_min',       'value' => '5',                                            'type' => 'number',   'label' => 'Delay Minimum Kirim WA (detik)', 'group' => 'whatsapp'],
            ['key' => 'waha_send_delay_max',       'value' => '10',                                           'type' => 'number',   'label' => 'Delay Maximum Kirim WA (detik)', 'group' => 'whatsapp'],
            ['key' => 'gemini_api_key',       'value' => '',                                                'type' => 'password', 'label' => 'Gemini API Key',                   'description' => 'API Key Google Gemini dari Google AI Studio', 'group' => 'api'],
            ['key' => 'gemini_model',         'value' => 'gemini-2.0-flash',                               'type' => 'text',     'label' => 'Gemini Model',                     'description' => 'Model Gemini yang digunakan, contoh: gemini-2.0-flash', 'group' => 'api'],
            ['key' => 'groq_api_key',         'value' => '',                                                'type' => 'password', 'label' => 'Groq API Key',                     'description' => 'API Key Groq dari console.groq.com', 'group' => 'api'],
            ['key' => 'groq_model',           'value' => 'qwen/qwen3-32b',                        'type' => 'text',     'label' => 'Groq Model',                       'description' => 'Model Groq yang digunakan, contoh: llama-3.3-70b-versatile', 'group' => 'api'],
            ['key' => 'openrouter_api_key',   'value' => '',                                                'type' => 'password', 'label' => 'OpenRouter API Key',               'description' => 'API Key dari openrouter.ai', 'group' => 'api'],
            ['key' => 'openrouter_model',     'value' => 'openrouter/free',                                 'type' => 'text',     'label' => 'OpenRouter Model',                 'description' => 'Semua model bertanda :free GRATIS. openrouter/free = router otomatis, selalu pilih model gratis yang sedang aktif (anti-deprecated)', 'group' => 'api'],
            ['key' => 'nvidia_api_key',       'value' => '',                                                'type' => 'password', 'label' => 'NVIDIA NIM API Key',               'description' => 'API Key dari build.nvidia.com — gratis, untuk Customer Analytics', 'group' => 'api'],
            ['key' => 'nvidia_model',         'value' => 'qwen/qwen3.5-397b-a17b',                         'type' => 'text',     'label' => 'NVIDIA NIM Model (Analytics)',      'description' => 'Model NVIDIA NIM utama untuk analitik percakapan', 'group' => 'api'],
            ['key' => 'human_takeover_minutes','value' => '10',                                               'type' => 'number',   'label' => 'Durasi Human Takeover (menit)',     'description' => 'Berapa menit bot diam setelah owner balas manual dari WA', 'group' => 'general'],
            ['key' => 'faq_digest',            'value' => '',                                                 'type' => 'textarea', 'label' => 'FAQ Digest (Auto-generated)',       'description' => 'Dibangun otomatis dari FAQ aktif. Jangan edit manual.', 'group' => 'ai'],
            ['key' => 'dynamic_knowledge',     'value' => '',                                                 'type' => 'textarea', 'label' => 'Dynamic Knowledge (Auto-generated)','description' => 'Dibangun mingguan dari percakapan sukses. Jangan edit manual.', 'group' => 'ai'],
        ];

        // Key yang selalu di-update agar perubahan seeder langsung berlaku di production
        $alwaysUpdate = ['ai_system_prompt', 'ai_max_tokens', 'footer_ai', 'footer_faq', 'fallback_message'];

        foreach ($configs as $config) {
            if (in_array($config['key'], $alwaysUpdate)) {
                BotConfig::updateOrCreate(['key' => $config['key']], $config);
            } else {
                // firstOrCreate: tidak overwrite nilai yang sudah dikustomisasi via CMS
                BotConfig::firstOrCreate(['key' => $config['key']], $config);
            }
        }
    }
}
