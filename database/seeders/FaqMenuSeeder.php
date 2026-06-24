<?php

namespace Database\Seeders;

use App\Models\FaqMenu;
use Illuminate\Database\Seeder;

class FaqMenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [

            // ═══════════════════════════════════════════════════════════════
            // LAYER 1 — MENU UTAMA
            // ═══════════════════════════════════════════════════════════════

            [
                'command'        => '0',
                'title'          => 'Menu Utama',
                'content'        => "Hallo! Selamat datang di *Languages by Fara* 🌍\n\nPlatform belajar *8 bahasa asing* via FlexiLearn — self-paced, kapan saja & di mana saja!\n\nPilih menu di bawah ini:\n\n*1* - Program Bahasa\n*2* - Harga & Paket\n*3* - FlexiLearn (Platform)\n*4* - Level & Materi\n*5* - Tentang LBF & Cara Bayar\n\nAtau ketik pertanyaanmu langsung, *MinFara AI* siap bantu 24/7! 🤖\n\n📱 @languagesbyfara",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 0,
            ],

            // ───────────────────────────────────────────────────────────────
            // MENU 1 — Program Bahasa
            // ───────────────────────────────────────────────────────────────

            [
                'command'        => '1',
                'title'          => 'Program Bahasa',
                'content'        => "📚 *Program Bahasa — Languages by Fara*\n\nPilih info yang kamu butuhkan:\n\n*1.1* - Bahasa yang Tersedia Sekarang\n*1.2* - Bahasa yang Segera Hadir\n*1.3* - Untuk Siapa & Sertifikat\n\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 1,
            ],

            // LAYER 2 — Sub-menu 1.x

            [
                'command'        => '1.1',
                'title'          => 'Bahasa yang Tersedia Sekarang',
                'content'        => "📚 *Bahasa Tersedia di LBF (Sekarang)*\n\n🇩🇪 *Bahasa Jerman* — A1 | A2 | B1\nKurikulum: CEFR\n\n🇬🇧 *Bahasa Inggris* — Level 1\nKurikulum: CEFR\n\n🇫🇷 *Bahasa Prancis* — A1\nKurikulum: CEFR\n\n*Semua program mencakup:*\n✅ Video pembelajaran (fokus grammar)\n✅ Kosakata (vocabulary) per topik\n✅ Kuis: pilihan ganda, isian, esai\n✅ Certificate of Completion\n✅ Akses via HP, tablet, laptop\n✅ Progress tracker otomatis\n\nKetik *1.2* untuk bahasa yang segera hadir.\n─────────────────\nKetik *1* kembali | *0* menu utama",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 11,
            ],

            [
                'command'        => '1.2',
                'title'          => 'Bahasa yang Segera Hadir',
                'content'        => "🔜 *Bahasa yang Segera Hadir di LBF*\n\n🇹🇷 Bahasa Turki (A1) — CEFR\n🇯🇵 Bahasa Jepang (N5) — JLPT\n🇰🇷 Bahasa Korea (Level 1) — TOPIK\n🇸🇦 Bahasa Arab (Level 1)\n🇨🇳 Bahasa Mandarin (HSK 1) — HSK\n\n_Program sedang dalam pengembangan. Follow @languagesbyfara untuk update terbaru!_ ✨\n\nKetik *1.1* untuk bahasa yang sudah tersedia sekarang.\n─────────────────\nKetik *1* kembali | *0* menu utama",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 12,
            ],

            [
                'command'        => '1.3',
                'title'          => 'Untuk Siapa dan Sertifikat',
                'content'        => "✅ *Untuk Siapa & Sertifikat LBF*\n\n*Program ini cocok untuk:*\n- Pemula yang belum pernah belajar sama sekali ✅\n- Yang sudah punya dasar & ingin melanjutkan ✅\n- Semua usia — remaja & dewasa ✅\n- Pelajar, mahasiswa, maupun pekerja ✅\n- Belajar dari dalam maupun luar Indonesia ✅\n\nTidak ada batasan usia, tidak perlu pengalaman sebelumnya!\n\n*Sertifikat:*\n🎓 Certificate of Completion — diberikan setelah menyelesaikan semua materi\n✅ Bukti penyelesaian program LBF\n❌ Bukan sertifikat ujian resmi internasional (Goethe, TOEFL, DELF, JLPT, dll)\n\n─────────────────\nKetik *1* kembali | *0* menu utama",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 13,
            ],

            // ───────────────────────────────────────────────────────────────
            // MENU 2 — Harga & Paket
            // ───────────────────────────────────────────────────────────────

            [
                'command'        => '2',
                'title'          => 'Harga dan Paket',
                'content'        => "💰 *Harga & Paket — Languages by Fara*\n\nPilih info yang kamu butuhkan:\n\n*2.1* - Paket Bahasa Jerman\n*2.2* - Paket Bahasa Lainnya\n*2.3* - Cara Bayar & Info Rekening\n\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 2,
            ],

            // LAYER 2 — Sub-menu 2.x

            [
                'command'        => '2.1',
                'title'          => 'Paket Bahasa Jerman',
                'content'        => "🇩🇪 *Paket Bahasa Jerman (per level: A1 / A2 / B1)*\n\n*Paket Berlangganan:*\n- 2 Bulan: Rp149.000\n- 6 Bulan: Rp169.000\n- 12 Bulan: Rp189.000\n\n*Paket Lifetime:*\n- Lifetime Basic: Rp199.000\n- Lifetime + 10 Digital Product: Rp299.000\n- Lifetime + 20 Digital Product: Rp399.000\n- Lifetime + 20 Digital + 1x Private 60': Rp599.000\n- Lifetime + 20 Digital + 2x Private 90': Rp699.000\n\n*Semua paket termasuk:*\n✅ Video pembelajaran & modul\n✅ Kuis & latihan soal\n✅ Certificate of Completion\n\n💡 *Paling direkomendasikan: Lifetime Basic* — akses tanpa batas, bisa diulang kapan saja!\n\nKetik *2.3* untuk cara pembayaran.\n─────────────────\nKetik *2* kembali | *0* menu utama",
                'parent_command' => '2',
                'is_active'      => true,
                'sort_order'     => 21,
            ],

            [
                'command'        => '2.2',
                'title'          => 'Paket Bahasa Lainnya',
                'content'        => "🌍 *Paket Bahasa Inggris, Prancis & Lainnya*\n\n_Berlaku untuk: Inggris, Prancis, Turki, Jepang, Korea, Arab, Mandarin_\n\n*Paket Berlangganan:*\n- 2 Bulan: Rp189.000\n- 6 Bulan: Rp209.000\n- 12 Bulan: Rp219.000\n\n*Paket Lifetime:*\n- Lifetime: Rp239.000\n\n*Semua paket termasuk:*\n✅ Video pembelajaran & modul\n✅ Kuis & latihan soal\n✅ Certificate of Completion\n\n*Perbedaan paket:*\n- 2/6/12 Bulan → akses berakhir sesuai durasi\n- Lifetime → akses permanen, tanpa batas waktu ♾️\n\n💡 Lifetime = belajar santai, tidak perlu khawatir akses habis!\n\nKetik *2.3* untuk cara pembayaran.\n─────────────────\nKetik *2* kembali | *0* menu utama",
                'parent_command' => '2',
                'is_active'      => true,
                'sort_order'     => 22,
            ],

            [
                'command'        => '2.3',
                'title'          => 'Cara Bayar dan Info Rekening',
                'content'        => "💳 *Cara Bayar & Info Rekening*\n\n*Langkah pembayaran:*\n1️⃣ Pilih bahasa & paket\n2️⃣ Transfer ke rekening resmi:\n\n🏦 *Bank Mandiri*\nNo. Rek: 130-00-2353540-7\na.n. PT Fara Kreatif Sejahtera\n\n🏦 *Bank BCA*\nNo. Rek: 4490365864\na.n. PT Fara Kreatif Sejahtera\n\n3️⃣ Kirim bukti transfer ke admin → ketik *99*\n4️⃣ Akun aktif dalam maks 1×24 jam\n\n⚠️ *Penting:*\n- Pembayaran *non-refundable* — tidak bisa dikembalikan\n- Tidak tersedia cicilan — full payment\n- Tidak ada biaya tambahan selain harga paket\n- Pastikan nama program & level benar sebelum bayar\n\n─────────────────\nKetik *2* kembali | *99* hubungi admin",
                'parent_command' => '2',
                'is_active'      => true,
                'sort_order'     => 23,
            ],

            // ───────────────────────────────────────────────────────────────
            // MENU 3 — FlexiLearn Platform
            // ───────────────────────────────────────────────────────────────

            [
                'command'        => '3',
                'title'          => 'FlexiLearn Platform',
                'content'        => "💻 *FlexiLearn — Platform LMS Languages by Fara*\n\nPilih info yang kamu butuhkan:\n\n*3.1* - Apa itu FlexiLearn & Cara Akses\n*3.2* - Materi & Fitur Belajar\n\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 3,
            ],

            // LAYER 2 — Sub-menu 3.x

            [
                'command'        => '3.1',
                'title'          => 'Apa itu FlexiLearn dan Cara Akses',
                'content'        => "💻 *Apa itu FlexiLearn?*\n\nFlexiLearn adalah LMS (Learning Management System) berbasis website milik Languages by Fara — platform belajar bahasa asing secara self-paced, fleksibel, tanpa jadwal tetap.\n\n*Cara mulai belajar:*\n1️⃣ Pilih paket → ketik *2*\n2️⃣ Lakukan pembayaran\n3️⃣ Kirim bukti transfer ke admin → ketik *99*\n4️⃣ Terima info login (maks 1×24 jam)\n5️⃣ Buka FlexiLearn via browser & mulai belajar!\n\n*Akses platform:*\n✅ Via browser (HP, tablet, laptop) — tidak perlu install\n✅ Tersedia 24 jam, 7 hari seminggu\n✅ Bisa akses dari luar Indonesia\n✅ Satu akun bisa di-login dari beberapa perangkat\n\n❌ Belum ada aplikasi mobile\n❌ Materi tidak bisa didownload\n\n_Lupa password atau ada kendala akun? Hubungi admin → ketik *99*_\n\n─────────────────\nKetik *3* kembali | *0* menu utama",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 31,
            ],

            [
                'command'        => '3.2',
                'title'          => 'Materi dan Fitur Belajar',
                'content'        => "📖 *Materi & Fitur Belajar di FlexiLearn*\n\n*Isi materi pembelajaran:*\n✅ Video pembelajaran (fokus utama: grammar)\n✅ Kosakata (vocabulary) per topik\n✅ Contoh kalimat & cara penggunaan\n✅ Kuis: pilihan ganda, isian singkat, esai\n\n*Fitur platform:*\n✅ Video bisa diputar ulang kapan saja\n✅ Materi bisa diulang selama akses aktif\n✅ Progress tracker belajar otomatis\n✅ Chat MinFara di dalam platform untuk tanya materi\n\n*Yang belum tersedia:*\n❌ Dedicated sesi listening atau speaking\n❌ Sesi conversation langsung\n❌ Download materi\n❌ Forum diskusi antar peserta\n❌ Pengingat belajar otomatis\n❌ Aplikasi mobile (akses via browser)\n\n─────────────────\nKetik *3* kembali | *0* menu utama",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 32,
            ],

            // ───────────────────────────────────────────────────────────────
            // MENU 4 — Level & Materi
            // ───────────────────────────────────────────────────────────────

            [
                'command'        => '4',
                'title'          => 'Level dan Materi',
                'content'        => "📊 *Level & Materi — Languages by Fara*\n\nPilih info yang kamu butuhkan:\n\n*4.1* - Level Tersedia & Cara Menentukan Level\n*4.2* - Durasi & Jadwal Belajar\n\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 4,
            ],

            // LAYER 2 — Sub-menu 4.x

            [
                'command'        => '4.1',
                'title'          => 'Level Tersedia dan Cara Menentukan Level',
                'content'        => "📊 *Level Tersedia & Cara Menentukan Level*\n\n*Level yang tersedia saat ini:*\n🇩🇪 Bahasa Jerman: A1 | A2 | B1\n🇬🇧 Bahasa Inggris: Level 1\n🇫🇷 Bahasa Prancis: A1\n_(Level lebih tinggi menyusul)_\n\n*Cara menentukan level:*\n- Belum pernah belajar → mulai dari level dasar\n- Sudah pernah belajar → ceritakan pengalamanmu ke MinFara, kami bantu rekomendasikan\n- Placement test formal belum tersedia\n\n*Standar kurikulum internasional:*\n- CEFR: Jerman | Inggris | Prancis | Turki\n- JLPT: Jepang (N5 = pemula)\n- HSK: Mandarin (HSK 1 = pemula)\n- TOPIK: Korea (Level 1 = pemula)\n\n_LBF bukan lembaga ujian resmi. Program ini untuk belajar mandiri & persiapan, bukan pengganti sertifikasi internasional (Goethe, TOEFL, DELF, dll)._\n\n─────────────────\nKetik *4* kembali | *0* menu utama",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 41,
            ],

            [
                'command'        => '4.2',
                'title'          => 'Durasi dan Jadwal Belajar',
                'content'        => "⏰ *Durasi & Jadwal Belajar*\n\n*Sistem belajar:*\nSelf-paced — tidak ada jadwal tetap. Belajar kapan saja sesuai waktu luang.\n\n*Estimasi waktu per level:*\n- Santai (30 menit/hari) → sekitar 1–2 bulan per level\n- Lebih intens (1–2 jam/hari) → bisa lebih cepat\n\n*Fleksibilitas:*\n✅ Mulai kapan saja setelah akun aktif\n✅ Tidak perlu menunggu batch atau jadwal kelas\n✅ Ulangi materi yang belum dipahami\n✅ Belajar dari mana saja — termasuk dari luar negeri\n✅ Akses 24 jam, 7 hari seminggu\n\n*Saran:*\nSelesaikan level sebelumnya sebelum lanjut ke level berikutnya agar pemahaman lebih solid dan terarah.\n\n─────────────────\nKetik *4* kembali | *0* menu utama",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 42,
            ],

            // ───────────────────────────────────────────────────────────────
            // MENU 5 — Tentang LBF & Pembayaran
            // ───────────────────────────────────────────────────────────────

            [
                'command'        => '5',
                'title'          => 'Tentang LBF dan Pembayaran',
                'content'        => "🏢 *Tentang Languages by Fara*\n\nPilih info yang kamu butuhkan:\n\n*5.1* - Tentang LBF & Perusahaan\n*5.2* - Kontak, Sosmed & Cara Bayar\n\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 5,
            ],

            // LAYER 2 — Sub-menu 5.x

            [
                'command'        => '5.1',
                'title'          => 'Tentang LBF dan Perusahaan',
                'content'        => "🌍 *Tentang Languages by Fara (LBF)*\n\n*Apa itu LBF?*\nLanguages by Fara adalah platform pembelajaran bahasa asing berbasis LMS (FlexiLearn) — belajar secara mandiri, fleksibel, kapan saja & di mana saja.\n\n*Detail perusahaan:*\n👤 *Pendiri:* Fara\n🏢 *Perusahaan:* PT Fara Kreatif Sejahtera\n📍 *Alamat:* Jl. Terusan Sari Asih No. 76, Kota Bandung, Jawa Barat\n🗓️ *Kick-off resmi:* 17 Juni 2026\n\n*Kenapa pilih LBF?*\n✅ Belajar fleksibel — kapan saja, di mana saja\n✅ Materi terstruktur sesuai standar internasional\n✅ 8 bahasa asing dalam satu platform\n✅ Harga terjangkau mulai Rp149.000\n✅ Self-paced — tanpa tekanan jadwal\n✅ Cocok untuk semua usia & semua level\n\n─────────────────\nKetik *5* kembali | *0* menu utama",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 51,
            ],

            [
                'command'        => '5.2',
                'title'          => 'Kontak Sosmed dan Cara Bayar',
                'content'        => "📱 *Kontak & Cara Bayar LBF*\n\n*Hubungi Admin (WhatsApp):*\n➡️ https://wa.me/6289647897616\n⏰ Senin–Sabtu, 08.00–20.00 WIB\n\n*Hubungi admin untuk:*\n- Konsultasi program & pilihan paket\n- Konfirmasi pembayaran & aktivasi akun\n- Reset password / kendala akun\n- Pertanyaan umum lainnya\n\n*Rekening Pembayaran:*\n🏦 Mandiri: 130-00-2353540-7\n🏦 BCA: 4490365864\n_a.n. PT Fara Kreatif Sejahtera_\n\n*Sosial Media:*\n📸 Instagram: @languagesbyfara\n🎵 TikTok: @languagesbyfara\n🧵 Threads: @languagesbyfara\n\n_Ikuti untuk info promo, update program & konten belajar!_\n\n─────────────────\nKetik *5* kembali | *99* hubungi admin",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 52,
            ],

            // ───────────────────────────────────────────────────────────────
            // MENU 99 — Hubungi Admin
            // ───────────────────────────────────────────────────────────────

            [
                'command'        => '99',
                'title'          => 'Hubungi Admin',
                'content'        => "👋 *Chat Langsung dengan Admin Languages by Fara!*\n\n💬 Tap link untuk langsung buka chat WhatsApp:\n\n➡️ https://wa.me/6289647897616\n\n_Tim MinFara siap bantu kamu memilih program & paket yang paling sesuai — konsultasi gratis!_\n\n⏰ _Senin–Sabtu, 08.00–20.00 WIB_\n📱 @languagesbyfara\n─────────────────\nKetik *0* untuk kembali ke menu utama",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 99,
            ],

        ];

        foreach ($menus as $menu) {
            FaqMenu::updateOrCreate(
                ['command' => $menu['command']],
                $menu
            );
        }

        // ── Nonaktifkan menu DlmF & struktur lama ────────────────────────────
        // 1.4–1.7: sisa DlmF yang tidak dipakai ulang
        // 6,7,8: menu DlmF lama
        // 9, 9.x: struktur LBF sementara (sudah dipindah ke 1–5)
        $deactivate = ['1.4','1.5','1.6','1.7','6','7','8','9','9.1','9.2','9.3','9.4','9.5'];
        FaqMenu::whereIn('command', $deactivate)->update(['is_active' => false]);
    }
}
