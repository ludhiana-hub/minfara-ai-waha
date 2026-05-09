<?php

namespace Database\Seeders;

use App\Models\FaqMenu;
use Illuminate\Database\Seeder;

class FaqMenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                'command'        => '0',
                'title'          => 'Menu Utama',
                'content'        => "Hallo! Selamat datang di *Deutsch Lernen mit Fara (DlmF)* 🇩🇪\n\nPlatform kursus Bahasa Jerman online & offline terpercaya di Bandung.\nSudah dipercaya *5.000+ alumni* sejak berdiri!\n\nPilih menu di bawah ini:\n\n*1* - Program Kursus\n*2* - Harga & Paket\n*3* - Kelas Online vs Offline\n*4* - Program Au Pair\n*5* - Tentang DlmF & Tutor\n*6* - Cara Daftar & Pembayaran\n*7* - FAQ Umum\n*8* - Kontak & Lokasi\n\nAtau ketik pertanyaanmu langsung, *MinFara AI* siap bantu 24/7! 🤖\n\n🌐 mitfara.com",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 0,
            ],
            [
                'command'        => '1',
                'title'          => 'Program Kursus',
                'content'        => "📚 *Program Kursus di DlmF*\n\nDlmF menyediakan berbagai pilihan belajar Bahasa Jerman:\n\n*1.1* - Kelas Reguler (Online & Offline) A1–B1\n*1.2* - Private Grammatik\n*1.3* - Private Persiapan Ujian Goethe\n*1.4* - Private Speaking (Native Speaker)\n*1.5* - Private Kinder (Anak-anak)\n*1.6* - Deutsch FlexiLearn (Asinkronus)\n*1.7* - Program Au Pair\n\nKetik nomornya untuk info lebih lanjut.\n\n🔗 mitfara.com/program\n─────────────────\nKetik *0* untuk kembali ke menu utama",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 1,
            ],
            [
                'command'        => '1.1',
                'title'          => 'Kelas Reguler',
                'content'        => "🏫 *Kelas Reguler Intensif A1–B1*\n\nTersedia *online* (via Microsoft Teams) maupun *offline* (Bandung).\n\n✅ 20x Pertemuan | 120 menit/sesi\n✅ Kelas 3–8 orang (suasana interaktif)\n✅ Free modul & 20 e-book\n✅ Free 1x sesi dengan native speaker\n✅ 8x simulasi ujian Goethe\n✅ Sertifikat keikutsertaan\n✅ Kurikulum standar Goethe-Institut (A1–B1)\n\n💰 *Harga Online:*\n- A1: Rp1.499.000\n- A2: Rp1.499.000\n- B1: Rp1.699.000\n\n💰 *Harga Offline (Bandung):*\n- A1: Rp2.099.000\n- A2: Rp2.099.000\n- B1: Rp2.250.000\n\nAda juga *Bundling hemat* hingga Rp1.000.000!\nKetik *2* untuk lihat semua harga & bundling.\n\n🔗 mitfara.com/kelas-reguler\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 1,
            ],
            [
                'command'        => '1.2',
                'title'          => 'Private Grammatik',
                'content'        => "✏️ *Private Grammatik*\n\nBelajar 1-on-1 bersama tutor berpengalaman.\nCocok untuk memperdalam struktur kalimat, kata kerja, dan kasus Bahasa Jerman.\n\n✅ 5x Pertemuan | 90 menit/sesi\n✅ 1-on-1 dengan tutor\n✅ Materi sesuai kelas reguler\n✅ Bisa request jadwal\n✅ Free akses rekaman kelas 24/7\n\nTersedia pengantar *Bahasa Indonesia* atau *Bahasa Inggris*.\n\n💰 *Harga Online:*\n- A1 (Ind): Rp975.000 | (Eng): Rp1.150.000\n- A2 (Ind): Rp975.000 | (Eng): Rp1.150.000\n- B1 (Ind): Rp1.095.000 | (Eng): Rp1.270.000\n\n💰 *Harga Offline:*\n- A1 (Ind): Rp1.400.000 | (Eng): Rp1.575.000\n- A2 (Ind): Rp1.400.000 | (Eng): Rp1.575.000\n- B1 (Ind): Rp1.500.000 | (Eng): Rp1.675.000\n\n🔗 mitfara.com/private-grammatik\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 2,
            ],
            [
                'command'        => '1.3',
                'title'          => 'Persiapan Ujian Goethe',
                'content'        => "🎓 *Private Persiapan Ujian Goethe*\n\nPersiapkan dirimu untuk ujian sertifikasi Goethe-Institut secara optimal!\nBisa fokus ke modul tertentu: Hören, Lesen, Schreiben, atau Sprechen.\n\n✅ 5x Pertemuan | 90 menit/sesi\n✅ 1-on-1 dengan tutor\n✅ Bisa request jadwal & materi\n✅ Free akses rekaman 24/7\n✅ Tersedia pengantar Bahasa Indonesia atau Inggris\n\n💰 *Harga Online:*\n- A1 (Ind): Rp975.000 | (Eng): Rp1.150.000\n- A2 (Ind): Rp975.000 | (Eng): Rp1.150.000\n- B1 (Ind): Rp1.095.000 | (Eng): Rp1.270.000\n\n💰 *Harga Offline:*\n- A1 (Ind): Rp1.400.000 | (Eng): Rp1.575.000\n- A2 (Ind): Rp1.400.000 | (Eng): Rp1.575.000\n- B1 (Ind): Rp1.500.000 | (Eng): Rp1.675.000\n\nℹ️ Sertifikat resmi diperoleh melalui ujian Goethe mandiri.\n\n🔗 mitfara.com/persiapan-goethe\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 3,
            ],
            [
                'command'        => '1.4',
                'title'          => 'Private Speaking Native Speaker',
                'content'        => "🗣️ *Sprachkurs mit Muttersprachler*\n\nLatihan berbicara langsung dengan penutur asli Jerman!\nTingkatkan kefasihan, pelafalan, dan pemahaman budaya Jerman secara autentik.\n\n✅ 4x Pertemuan | 60 menit/sesi\n✅ 1-on-1 dengan native speaker\n✅ Bisa request jadwal & materi\n✅ Free akses rekaman 24/7\n✅ Tersedia online & offline\n\n💰 *Harga Online:* Rp1.596.000\n💰 *Harga Offline:* Rp1.676.000\n\n🔗 mitfara.com/speaking-native\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 4,
            ],
            [
                'command'        => '1.5',
                'title'          => 'Private Kinder (Anak-anak)',
                'content'        => "👦👧 *Private Kinder — Kelas Anak-anak*\n\nDirancang khusus untuk anak-anak dengan metode interaktif, permainan edukatif,\ndan aktivitas menyenangkan yang sesuai usia.\n\n✅ 5x Pertemuan | 60 menit/sesi\n✅ 1-on-1 dengan tutor berpengalaman\n✅ Bisa request jadwal\n✅ Free akses rekaman 24/7\n✅ Tersedia pengantar Bahasa Indonesia atau Inggris\n\n💰 *Harga Online:*\n- Pengantar Ind: Rp895.000\n- Pengantar Eng: Rp1.070.000\n\n🔗 mitfara.com/private-kinder\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 5,
            ],
            [
                'command'        => '1.6',
                'title'          => 'Deutsch FlexiLearn Asinkronus',
                'content'        => "💻 *Deutsch FlexiLearn (Asinkronus)*\n\nBelajar Bahasa Jerman kapan saja & di mana saja, tanpa jadwal tetap!\nMateri terstruktur lengkap dengan video, latihan interaktif & evaluasi mandiri.\n\n✅ Akses 24/7 ke semua materi & video\n✅ 1.000+ latihan soal\n✅ Evaluasi & quiz otomatis\n✅ Forum diskusi & chat\n✅ Certificate of completion\n✅ Multi-device access\n✅ Dashboard personal\n\n📦 *Tersedia untuk level A1, A2, dan B1*\n\n💰 *Harga per level (contoh A1):*\n- 2 Bulan: Rp149.000\n- 6 Bulan: Rp169.000\n- 12 Bulan: Rp189.000\n- Lifetime Basic: Rp199.000\n- Lifetime + 10 E-Book: Rp299.000\n- Lifetime + 20 E-Book: Rp399.000\n- Lifetime + 20 E-Book + 1x Private: Rp599.000\n- Lifetime + 20 E-Book + 2x Private: Rp699.000\n\nAda juga paket *Bundling A1-A2, A2-B1, dan A1-B1* lebih hemat!\n\n🔗 mitfara.com/flexilearn\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 6,
            ],
            [
                'command'        => '1.7',
                'title'          => 'Program Au Pair',
                'content'        => "✈️ *Program Au Pair Jerman*\n\nAu Pair adalah program pertukaran budaya internasional.\nKamu tinggal bersama keluarga angkat di Jerman, membantu menjaga anak,\nsambil belajar bahasa & budaya Jerman secara langsung!\n\n*Keuntungan Au Pair:*\n✅ Pengalaman tinggal di Jerman\n✅ Dapat uang saku bulanan\n✅ Belajar bahasa Jerman secara immersive\n✅ Pengalaman internasional bernilai tinggi\n✅ Usia peserta: 18–26 tahun\n\n🔗 mitfara.com/au-pair\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 7,
            ],
            [
                'command'        => '2',
                'title'          => 'Harga dan Paket Bundling',
                'content'        => "💰 *Harga & Paket DlmF*\n\n*KELAS ONLINE (via Microsoft Teams):*\n- Reguler A1/A2: Rp1.499.000\n- Reguler B1: Rp1.699.000\n- Private Grammatik A1/A2: mulai Rp975.000\n- Private Grammatik B1: mulai Rp1.095.000\n- Persiapan Goethe A1/A2: mulai Rp975.000\n- Speaking Native: Rp1.596.000\n- Private Kinder: mulai Rp895.000\n\n*KELAS OFFLINE (Bandung):*\n- Reguler A1/A2: Rp2.099.000\n- Reguler B1: Rp2.250.000\n- Private Grammatik A1/A2: mulai Rp1.400.000\n\n*FLEXILEARN (Asinkronus):*\n- Mulai Rp149.000 / 2 bulan\n\n*BUNDLING HEMAT:*\n- Reguler A1+A2 Online: Rp5.599.000 (hemat ~Rp400rb)\n- Reguler A2+B1 Online: Rp5.999.000 (hemat ~Rp400rb)\n- Reguler A1+B1 Online: Rp8.399.000 (hemat ~Rp1jt!)\n\n🔗 mitfara.com/harga\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 2,
            ],
            [
                'command'        => '3',
                'title'          => 'Kelas Online vs Offline',
                'content'        => "🖥️📍 *Kelas Online vs Offline DlmF*\n\n*KELAS ONLINE:*\n- Platform: Microsoft Teams\n- Bisa dari mana saja\n- Jadwal fleksibel\n- Free akses rekaman kelas 24/7\n- Harga lebih terjangkau\n\n*KELAS OFFLINE (Bandung):*\n- Lokasi: Jl. Terusan Sari Asih No. 76, Sarijadi, Kota Bandung\n- Tatap muka langsung\n- Lebih interaktif secara fisik\n- Cocok untuk yang butuh kedisiplinan ekstra\n\nKeduanya kurikulum & tutor yang sama!\nJumlah siswa reguler: 3–8 orang per kelas.\n\n🔗 mitfara.com/kelas\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 3,
            ],
            [
                'command'        => '4',
                'title'          => 'Program Au Pair',
                'content'        => "✈️ *Program Au Pair Jerman*\n\nAu Pair adalah program pertukaran budaya internasional.\nKamu tinggal bersama keluarga angkat di Jerman, membantu menjaga anak,\nsambil belajar bahasa & budaya Jerman secara langsung!\n\n*Keuntungan Au Pair:*\n✅ Pengalaman tinggal di Jerman\n✅ Dapat uang saku bulanan\n✅ Belajar bahasa Jerman secara immersive\n✅ Pengalaman internasional bernilai tinggi\n✅ Usia peserta: 18–26 tahun\n\n🔗 mitfara.com/au-pair\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 4,
            ],
            [
                'command'        => '5',
                'title'          => 'Tentang DlmF dan Tutor',
                'content'        => "🏫 *Tentang Deutsch Lernen mit Fara (DlmF)*\n\nPlatform pembelajaran Bahasa Jerman terpercaya di Indonesia.\n\n📊 *Statistik DlmF:*\n- 5.000+ Alumni\n- Tutor bersertifikasi (seleksi ketat)\n- Kelas online & offline tersedia\n- Kurikulum standar Goethe-Institut\n\n👨‍🏫 *Tim Tutor:*\n- *Herr Yasin* — German Tutor level C2\n- *Frau Caca* — German Tutor level B1\n- *Herr Farabi* — German Tutor level B1\n- *Frau Dwi* — German Tutor level B1\n- Dan tutor-tutor lainnya\n\n📱 *Sosial Media:*\n- Instagram: @deutschlernen.mit.fara\n- TikTok: @deutschlernen.mit.fara\n- YouTube: Deutsch Lernen mit Fara\n\n🔗 mitfara.com/tentang\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 5,
            ],
            [
                'command'        => '6',
                'title'          => 'Cara Daftar dan Pembayaran',
                'content'        => "📝 *Cara Daftar di DlmF*\n\n1️⃣ Pilih program & level yang sesuai\n   → Ketik *1* untuk lihat program\n   → Ketik *2* untuk lihat harga\n\n2️⃣ Hubungi *MinFara AI* atau admin untuk konfirmasi\n   → Tanya langsung di sini, atau ketik *99* untuk chat dengan admin\n\n3️⃣ Lakukan pembayaran sesuai instruksi\n\n4️⃣ Terima info kelas, jadwal, & materi dari tim DlmF\n\n5️⃣ Mulai belajar! 🎉\n\n*Metode Pembayaran:*\nTransfer Bank / QRIS / Virtual Account\n\n*Garansi DlmF:*\nJika sudah ikut program tapi belum lulus ujian,\nkami berikan *Free Class* (S&K berlaku).\n\n🔗 mitfara.com/daftar\n─────────────────\nKetik *0* menu utama | *99* hubungi admin",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 6,
            ],
            [
                'command'        => '7',
                'title'          => 'FAQ Umum',
                'content'        => "❓ *Pertanyaan yang Sering Ditanyakan*\n\n*Apakah ada sertifikat?*\nYa! Sertifikat keikutsertaan DlmF. Untuk sertifikat resmi, ikuti ujian Goethe mandiri.\n\n*Aplikasi apa untuk kelas online?*\nMicrosoft Teams — semua kelas & grup diskusi terintegrasi.\n\n*Berapa orang per kelas reguler?*\n3–8 orang per kelas.\n\n*Apakah bisa request jadwal?*\nPrivate & FlexiLearn: bisa! Kelas reguler: ikuti jadwal batch.\n\n*Ada garansi tidak?*\nAda! Free class jika belum lulus ujian (S&K berlaku).\n\n*Level apa saja yang tersedia?*\nA1, A2, B1 (reguler). Untuk B2 hubungi admin.\n\nMasih ada pertanyaan? Ketik *99* untuk chat admin!\n\n🔗 mitfara.com/faq\n─────────────────\nKetik *0* menu utama",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 7,
            ],
            [
                'command'        => '8',
                'title'          => 'Kontak dan Lokasi',
                'content'        => "📍 *Kontak & Lokasi DlmF*\n\n*Lokasi Offline:*\nJl. Terusan Sari Asih No. 76\nSarijadi, Sukasari, Kota Bandung\nJawa Barat, Indonesia\n\n*WhatsApp Admin (MinFara):*\n+62 896-4789-7616\n\n*Email:*\ninfo@mitfara.com\n\n*Website:*\n🔗 mitfara.com\n\n*Sosial Media:*\n📸 Instagram: @deutschlernen.mit.fara\n🎵 TikTok: @deutschlernen.mit.fara\n▶️ YouTube: Deutsch Lernen mit Fara\n\n_Jam operasional: Senin–Sabtu, 08.00–20.00 WIB_\n─────────────────\nKetik *0* untuk kembali ke menu utama",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 8,
            ],
            [
                'command'        => '99',
                'title'          => 'Hubungi Admin',
                'content'        => "👋 *Terhubung dengan MinFara!*\n\nKlik link di bawah untuk langsung chat dengan admin kami:\nhttps://wa.me/6289647897616\n\n_Tim kami siap membantu kamu memilih program yang paling sesuai._\n_Jam operasional: Senin–Sabtu, 08.00–20.00 WIB_\n\n🌐 mitfara.com\n─────────────────\nKetik *0* untuk kembali ke menu utama",
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
    }
}
