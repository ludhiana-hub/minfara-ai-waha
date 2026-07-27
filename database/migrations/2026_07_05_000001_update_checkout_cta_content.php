<?php

use App\Jobs\BuildFaqDigestJob;
use App\Models\BotConfig;
use App\Models\FaqMenu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Command 99 berubah makna dari "Hubungi Admin" jadi "Cara Checkout di Website" —
        // arahkan customer checkout mandiri, admin hanya jadi opsi terakhir.
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

        BotConfig::set('ai_system_prompt', $systemPrompt);
        BotConfig::set('footer_faq', "─────────────────\nKetik *0* menu utama | *99* cara checkout");
        BotConfig::set('footer_ai', "─────────────────\n_MinFara AI_ 🤖 _· Languages by Fara_\nKetik *0* menu utama | *99* cara checkout");
        BotConfig::set('fallback_message', "Maaf! 🙏 MinFara AI sedang tidak dapat memproses pertanyaanmu saat ini.\n\nSilakan coba beberapa saat lagi, atau ketik *99* untuk lihat cara checkout di website. Kalau masih butuh bantuan langsung, hubungi admin kami di https://wa.me/6289647897616 😊\n─────────────────\n_MinFara AI_ 🤖 _· Languages by Fara_\nKetik *0* menu utama | *99* cara checkout");

        FaqMenu::updateOrCreate(
            ['command' => '0'],
            [
                'title'   => 'Menu Utama',
                'content' => "Hallo! Selamat datang di *Languages by Fara* 🌍\n\nPlatform belajar *8 bahasa asing* via FlexiLearn — self-paced, kapan saja & di mana saja!\n\nPilih kategori FAQ:\n\n*1* General\n*2* Level & Penempatan Level\n*3* Materi Pembelajaran\n*4* FlexiLearn LMS Languages by Fara\n*5* Pembayaran & Harga\n*6* Tentang Languages by Fara\n*99* Cara Checkout di Website\n\nAtau ketik pertanyaanmu langsung, *MinFara AI* siap bantu! 🤖\n\n📱 @languagesbyfara",
            ]
        );

        FaqMenu::updateOrCreate(
            ['command' => '99'],
            [
                'title'   => 'Cara Checkout & Aktivasi Akun di Website',
                'content' => "🛒 *Cara Checkout & Aktivasi Akun FlexiLearn*\n\n1. Kunjungi website mitfara.com\n2. Login jika sudah punya akun, atau *Daftar* akun baru & isi data diri\n3. Tunggu kode *OTP* yang dikirim ke email untuk verifikasi\n4. Lengkapi data diri di menu *Profil*\n5. Buka *Katalog Kursus*, pilih program FlexiLearn (bulanan/lifetime/bundling) sesuai level, lalu klik *Beli Sekarang*\n6. Di halaman checkout, pilih metode pembayaran yang tersedia, baca syarat & ketentuan, centang, lalu submit\n7. Isi survei pembelian program, lalu lanjut ke halaman payment dan bayar sesuai metode yang dipilih\n8. Setelah pembayaran selesai, kakak diarahkan kembali ke Student Portal — tunggu beberapa menit/jam, akun FlexiLearn akan dikirim via email\n9. Cek email, login ke akun FlexiLearn di lms.mitfara.com, lalu buat password baru\n10. Cari kursus kakak & masukkan *kode enrollment* dari invoice pembayaran di menu Kursus Saya\n\nSetelah kode enrollment dimasukkan, kakak bisa langsung mulai belajar bahasa pilihan di Languages by Fara!\n\n_Masih bingung di salah satu langkah? Hubungi admin kami di https://wa.me/6289647897616_\n─────────────────\nKetik *0* untuk kembali ke menu utama",
            ]
        );

        // FAQ 4.2, 5.13, 5.14 masih mendeskripsikan alur lama (admin buatkan akun manual setelah
        // transfer) — bertentangan dengan alur checkout mandiri Student Portal yang baru. Selaraskan.
        $f4 = "─────────────────\nKetik *4* kembali | *0* menu utama";
        $f5 = "─────────────────\nKetik *5* kembali | *0* menu utama";

        FaqMenu::updateOrCreate(
            ['command' => '4.2'],
            [
                'title'   => 'Bagaimana cara mengakses FlexiLearn?',
                'content' => "Mudah banget kak, sekarang bisa checkout mandiri di website!\n\n1. Daftar/login di mitfara.com\n2. Pilih paket di Katalog Kursus, lalu checkout & bayar\n3. Setelah pembayaran selesai, akun FlexiLearn otomatis dikirim ke email kakak\n4. Login di lms.mitfara.com, lalu masukkan kode enrollment dari invoice\n\nKetik *99* untuk panduan lengkap step-by-step yaa\n{$f4}",
                'parent_command' => '4',
                'is_active' => true,
                'sort_order' => 402,
            ]
        );

        FaqMenu::updateOrCreate(
            ['command' => '5.13'],
            [
                'title'   => 'Apa yang harus dilakukan setelah pembayaran?',
                'content' => "Setelah pembayaran di website berhasil, kakak akan diarahkan kembali ke Student Portal\n\nTunggu beberapa menit/jam, akun FlexiLearn akan dikirim otomatis ke email kakak. Cek email, login di lms.mitfara.com, lalu masukkan kode enrollment dari invoice di menu Kursus Saya\n\nKetik *99* untuk panduan lengkapnya yaa\n{$f5}",
                'parent_command' => '5',
                'is_active' => true,
                'sort_order' => 513,
            ]
        );

        FaqMenu::updateOrCreate(
            ['command' => '5.14'],
            [
                'title'   => 'Berapa lama proses aktivasi akun?',
                'content' => "Setelah pembayaran berhasil, akun FlexiLearn biasanya dikirim otomatis via email dalam beberapa menit hingga maksimal 1x24 jam ya kak\n\nKalau lewat dari itu belum diterima, coba cek folder spam dulu atau hubungi admin kami di https://wa.me/6289647897616\n{$f5}",
                'parent_command' => '5',
                'is_active' => true,
                'sort_order' => 514,
            ]
        );

        BuildFaqDigestJob::dispatchSync();
    }

    public function down(): void
    {
        // Tidak perlu rollback — perubahan konten CTA/copy, bukan struktur data.
    }
};
