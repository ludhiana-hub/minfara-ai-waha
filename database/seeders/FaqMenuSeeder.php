<?php

namespace Database\Seeders;

use App\Models\FaqMenu;
use Illuminate\Database\Seeder;

class FaqMenuSeeder extends Seeder
{
    public function run(): void
    {
        $f0 = "─────────────────\nKetik *0* menu utama | *99* cara checkout";
        $f1 = "─────────────────\nKetik *1* kembali | *0* menu utama";
        $f2 = "─────────────────\nKetik *2* kembali | *0* menu utama";
        $f3 = "─────────────────\nKetik *3* kembali | *0* menu utama";
        $f4 = "─────────────────\nKetik *4* kembali | *0* menu utama";
        $f5 = "─────────────────\nKetik *5* kembali | *0* menu utama";
        $f6 = "─────────────────\nKetik *6* kembali | *0* menu utama";

        $menus = [

            // ═══════════════════════════════════════════════════════════════
            // MENU 0 — UTAMA  |  MENU 99 — CARA CHECKOUT DI WEBSITE
            // ═══════════════════════════════════════════════════════════════

            [
                'command'        => '0',
                'title'          => 'Menu Utama',
                'content'        => "Hallo! Selamat datang di *Languages by Fara* 🌍\n\nPlatform belajar *8 bahasa asing* via FlexiLearn — self-paced, kapan saja & di mana saja!\n\nPilih kategori FAQ:\n\n*1* General\n*2* Level & Penempatan Level\n*3* Materi Pembelajaran\n*4* FlexiLearn LMS Languages by Fara\n*5* Pembayaran & Harga\n*6* Tentang Languages by Fara\n*99* Cara Checkout di Website\n\nAtau ketik pertanyaanmu langsung, *MinFara AI* siap bantu! 🤖\n\n📱 @languagesbyfara",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 0,
            ],

            [
                'command'        => '99',
                'title'          => 'Cara Checkout & Aktivasi Akun di Website',
                'content'        => "🛒 *Cara Checkout & Aktivasi Akun FlexiLearn*\n\n1. Kunjungi website mitfara.com\n2. Login jika sudah punya akun, atau *Daftar* akun baru & isi data diri\n3. Tunggu kode *OTP* yang dikirim ke email untuk verifikasi\n4. Lengkapi data diri di menu *Profil*\n5. Buka *Katalog Kursus*, pilih program FlexiLearn (bulanan/lifetime/bundling) sesuai level, lalu klik *Beli Sekarang*\n6. Di halaman checkout, pilih metode pembayaran yang tersedia, baca syarat & ketentuan, centang, lalu submit\n7. Isi survei pembelian program, lalu lanjut ke halaman payment dan bayar sesuai metode yang dipilih\n8. Setelah pembayaran selesai, kakak diarahkan kembali ke Student Portal — tunggu beberapa menit/jam, akun FlexiLearn akan dikirim via email\n9. Cek email, login ke akun FlexiLearn di lms.mitfara.com, lalu buat password baru\n10. Cari kursus kakak & masukkan *kode enrollment* dari invoice pembayaran di menu Kursus Saya\n\nSetelah kode enrollment dimasukkan, kakak bisa langsung mulai belajar bahasa pilihan di Languages by Fara!\n\n_Masih bingung di salah satu langkah? Hubungi admin kami di https://wa.me/6289647897616_\n─────────────────\nKetik *0* untuk kembali ke menu utama",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 9999,
            ],

            // ═══════════════════════════════════════════════════════════════
            // LAYER 1 — 6 KATEGORI (sort_order 1001–1006)
            // ═══════════════════════════════════════════════════════════════

            [
                'command'        => '1',
                'title'          => 'General',
                'content'        => "❓ *FAQ Umum — Languages by Fara*\n\nKetik nomor untuk melihat jawaban:\n\n*1.1* Program ini untuk bahasa apa saja?\n*1.2* Apa perbedaan setiap program bahasa?\n*1.3* Bahasa mana yang paling cocok untuk saya?\n*1.4* Apakah bisa belajar lebih dari satu bahasa sekaligus?\n*1.5* Apakah program ini cocok untuk pemula?\n*1.6* Apakah cocok untuk yang sudah pernah belajar?\n*1.7* Apakah ada placement test?\n*1.8* Apakah LMS ini termasuk sertifikat?\n*1.9* Apakah sertifikat diakui internasional?\n*1.10* Bisa membantu persiapan studi ke luar negeri?\n*1.11* Bisa membantu persiapan kerja di luar negeri?\n*1.12* Bisa membantu persiapan pindah ke luar negeri?\n*1.13* Berapa lama durasi belajarnya?\n*1.14* Kapan belajarnya bisa dimulai?\n*1.15* Apakah bisa mulai kapan saja?\n*1.16* Apakah ada jadwal tertentu?\n*1.17* Apakah sistem belajarnya fleksibel?\n*1.18* Apakah ada batas usia peserta?\n*1.19* Apakah ada program untuk anak-anak?\n*1.20* Apakah ada program untuk remaja?\n*1.21* Apakah ada program untuk dewasa?\n*1.22* Harus punya pengalaman belajar bahasa sebelumnya?\n*1.23* Apakah materi bisa diakses melalui HP?\n*1.24* Apakah bisa mengulang materi yang dipelajari?\n*1.25* Apakah ada tugas atau latihan?\n*1.26* Apakah ada mentor yang bisa ditanya?\n*1.27* Apakah semua bahasa sudah tersedia sekarang?\n*1.28* Apakah akan mendapatkan update materi?\n*1.29* Harus selesaikan level sebelumnya untuk naik?\n*1.30* Apakah bisa belajar dari luar Indonesia?\n{$f0}",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 1001,
            ],

            [
                'command'        => '2',
                'title'          => 'Level & Penempatan Level',
                'content'        => "📊 *FAQ Level — Languages by Fara*\n\nKetik nomor untuk melihat jawaban:\n\n*2.1* Apa aja level yang tersedia?\n*2.2* Bagaimana cara menentukan level saya?\n*2.3* Apakah harus mulai dari level dasar dulu?\n*2.4* Apakah bisa langsung masuk level tertentu?\n*2.5* Berapa lama menyelesaikan satu level?\n*2.6* Berapa jam belajar yang dibutuhkan setiap minggu?\n*2.7* Kurikulum Languages by Fara mengacu ke standar apa?\n*2.8* Apa itu CEFR?\n*2.9* Apakah level di LBF sama dengan level ujian resmi?\n*2.10* Kalau sudah pernah belajar, level mana yang diambil?\n{$f0}",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 1002,
            ],

            [
                'command'        => '3',
                'title'          => 'Materi Pembelajaran',
                'content'        => "📖 *FAQ Materi — Languages by Fara*\n\nKetik nomor untuk melihat jawaban:\n\n*3.1* Apa aja yang dipelajari?\n*3.2* Apakah fokus belajarnya grammar?\n*3.3* Apakah ada materi vocabulary?\n*3.4* Apakah ada materi speaking?\n*3.5* Apakah ada materi listening?\n*3.6* Apakah ada materi reading?\n*3.7* Apakah ada materi writing?\n*3.8* Apakah ada latihan pronunciation?\n*3.9* Apakah ada latihan percakapan?\n*3.10* Apakah ada kuis?\n*3.11* Bentuk kuisnya seperti apa?\n*3.12* Apakah ada ujian?\n*3.13* Apakah semua materi bisa diakses berulang kali?\n*3.14* Apakah materi cocok untuk belajar mandiri?\n{$f0}",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 1003,
            ],

            [
                'command'        => '4',
                'title'          => 'FlexiLearn LMS Languages by Fara',
                'content'        => "💻 *FAQ FlexiLearn / LMS — Languages by Fara*\n\nKetik nomor untuk melihat jawaban:\n\n*4.1* Apa itu FlexiLearn?\n*4.2* Bagaimana cara mengakses FlexiLearn?\n*4.3* Apakah FlexiLearn bisa diakses melalui HP?\n*4.4* Apakah FlexiLearn bisa diakses melalui laptop atau komputer?\n*4.5* Apakah ada aplikasi mobile?\n*4.6* Apakah FlexiLearn tersedia 24 jam?\n*4.7* Apakah saya bisa belajar kapan saja?\n*4.8* Apakah video pembelajaran bisa diputar ulang?\n*4.9* Apakah materi bisa diakses berulang kali?\n*4.10* Apakah materi bisa didownload?\n*4.11* Apakah ada progress tracker?\n*4.12* Apakah ada reminder belajar?\n*4.13* Apakah ada forum diskusi?\n*4.14* Siapa itu MinFara?\n*4.15* Apakah ada fitur catatan (notes)?\n*4.16* Apakah ada bookmark materi?\n*4.17* Apakah ada fitur pencarian materi?\n*4.18* Bagaimana jika saya lupa password?\n*4.19* Apakah akun bisa digunakan di lebih dari satu perangkat?\n*4.20* Jika ada kendala saat belajar, hubungi siapa?\n{$f0}",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 1004,
            ],

            [
                'command'        => '5',
                'title'          => 'Pembayaran & Harga',
                'content'        => "💰 *FAQ Harga & Pembayaran — Languages by Fara*\n\nKetik nomor untuk melihat jawaban:\n\n*5.1* Berapa harga program di Languages by Fara?\n*5.2* Apa saja yang didapat pada paket Bahasa Jerman?\n*5.3* Apa saja yang didapat pada paket Bahasa Inggris, Prancis, dll?\n*5.4* Kenapa harga Bahasa Jerman dan bahasa lainnya berbeda?\n*5.5* Apa perbedaan paket 2 bulan, 6 bulan, 12 bulan, dan lifetime?\n*5.6* Apa itu paket Lifetime?\n*5.7* Paket mana yang paling direkomendasikan?\n*5.8* Apakah semua paket mendapatkan sertifikat?\n*5.9* Bagaimana cara melakukan pembayaran?\n*5.10* Pembayarannya transfer ke rekening siapa?\n*5.11* Apakah pembayaran bisa melalui Bank BCA?\n*5.12* Apakah pembayaran bisa melalui Bank Mandiri?\n*5.13* Apa yang harus dilakukan setelah pembayaran?\n*5.14* Berapa lama proses aktivasi akun?\n*5.15* Apakah ada biaya tambahan selain harga paket?\n*5.16* Apakah bisa upgrade paket setelah membeli?\n*5.17* Apakah pembayaran bisa dicicil?\n*5.18* Apakah pembayaran bisa dikembalikan (refund)?\n*5.19* Apakah ada promo atau diskon?\n{$f0}",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 1005,
            ],

            [
                'command'        => '6',
                'title'          => 'Tentang Languages by Fara',
                'content'        => "🌍 *Tentang Languages by Fara*\n\nKetik nomor untuk melihat jawaban:\n\n*6.1* Apa itu Languages by Fara?\n*6.2* Siapa pemilik Languages by Fara?\n*6.3* Apakah Languages by Fara merupakan lembaga resmi?\n*6.4* Apa hubungan Languages by Fara dengan Deutsch lernen mit Fara?\n*6.5* Kapan Deutsch lernen mit Fara berdiri?\n*6.6* Kapan LMS pertama kali diluncurkan?\n*6.7* Kapan Languages by Fara mulai beroperasi?\n*6.8* Apa tujuan hadirnya Languages by Fara?\n*6.9* Apa saja bahasa yang tersedia di Languages by Fara?\n*6.10* Di mana lokasi Languages by Fara?\n*6.11* Apakah Languages by Fara memiliki kantor fisik?\n*6.12* Apakah Languages by Fara hanya menyediakan pembelajaran online?\n*6.13* Mengapa memilih Languages by Fara?\n{$f0}",
                'parent_command' => null,
                'is_active'      => true,
                'sort_order'     => 1006,
            ],

            // ═══════════════════════════════════════════════════════════════
            // LAYER 3 — FAQ UMUM (1.1–1.30) sort_order 101–130
            // ═══════════════════════════════════════════════════════════════

            [
                'command'        => '1.1',
                'title'          => 'Program ini untuk bahasa apa saja?',
                'content'        => "Languages by Fara menyediakan program pembelajaran untuk 8 bahasa asing yaa kak\n\n*Untuk pemula (beginner):*\n- Bahasa Jerman (A1)\n- Bahasa Inggris (Level 1)\n- Bahasa Prancis (A1)\n- Bahasa Turki (A1)\n- Bahasa Jepang (N5)\n- Bahasa Korea (Level 1)\n- Bahasa Arab (Level 1)\n- Bahasa Mandarin (HSK 1)\n\n*Untuk yang sudah memiliki dasar:*\n- Bahasa Jerman (A2 dan B1)\n\nUntuk saat ini, program yang sudah tersedia adalah:\n1. Bahasa Jerman (A1, A2, B1)\n2. Bahasa Inggris (Level 1)\n3. Bahasa Prancis (A1)\n\nSedangkan Bahasa Turki, Jepang, Korea, Arab, dan Mandarin akan segera hadir pada bulan-bulan berikutnya yaa kak\n\nKalau MinFara boleh tahu, kakak tertarik belajar bahasa apa dan untuk tujuan apa nih? Biar MinFara bantu rekomendasikan level yang paling sesuai\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 101,
            ],

            [
                'command'        => '1.2',
                'title'          => 'Apa perbedaan setiap program bahasa?',
                'content'        => "Setiap program bahasa memiliki materi, kosakata, tata bahasa, dan tujuan pembelajaran yang berbeda sesuai dengan bahasa yang dipelajari ya kak\n\nKalau MinFara boleh tahu, kakak sedang tertarik belajar bahasa apa dan untuk tujuan apa nih? Biar MinFara bantu rekomendasikan program yang paling sesuai\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 102,
            ],

            [
                'command'        => '1.3',
                'title'          => 'Bahasa mana yang paling cocok untuk saya?',
                'content'        => "Terkait hal tersebut bisa disesuaikan dengan kebutuhan kakak yaa\n\nKalau MinFara boleh tahu, kakak memiliki tujuan belajar bahasa untuk apa nih?\n- Studi ke luar negeri\n- Kerja di luar negeri\n- Traveling\n- Migrasi atau tinggal di luar negeri\n- Hobi dan pengembangan diri\n\nNanti MinFara bantu rekomendasikan bahasa yang paling cocok yaa kak\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 103,
            ],

            [
                'command'        => '1.4',
                'title'          => 'Apakah bisa belajar lebih dari satu bahasa sekaligus?',
                'content'        => "Bisa banget dong kak!\n\nKakak bisa berlangganan lebih dari satu program bahasa sekaligus. Pilihan paketnya pun beragam dan dapat disesuaikan dengan kebutuhan belajar kakak\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 104,
            ],

            [
                'command'        => '1.5',
                'title'          => 'Apakah program ini cocok untuk pemula?',
                'content'        => "Cocok banget kak!\n\nMateri di Languages by Fara dirancang agar mudah diikuti oleh pemula yang belum memiliki dasar bahasa sama sekali\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 105,
            ],

            [
                'command'        => '1.6',
                'title'          => 'Apakah program ini cocok untuk yang sudah pernah belajar sebelumnya?',
                'content'        => "Tentu bisa kak\n\nKalau kakak sudah pernah belajar sebelumnya, MinFara bisa bantu merekomendasikan level yang sesuai\n\nKakak sebelumnya sudah pernah belajar bahasa tersebut sampai level apa yaa?\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 106,
            ],

            [
                'command'        => '1.7',
                'title'          => 'Apakah ada placement test untuk penempatan level?',
                'content'        => "Untuk saat ini placement test belum tersedia ya kak\n\nNamun MinFara akan membantu merekomendasikan level berdasarkan pengalaman belajar yang sudah kakak miliki sebelumnya\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 107,
            ],

            [
                'command'        => '1.8',
                'title'          => 'Apakah LMS ini termasuk sertifikat?',
                'content'        => "Iya kak!\n\nSetelah kakak menyelesaikan seluruh materi dan tugas sesuai ketentuan program yang diambil, kakak akan mendapatkan Certificate of Completion dari Languages by Fara\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 108,
            ],

            [
                'command'        => '1.9',
                'title'          => 'Apakah sertifikatnya diakui untuk program internasional?',
                'content'        => "Certificate of Completion dari Languages by Fara berfungsi sebagai bukti bahwa kakak telah menyelesaikan program pembelajaran yang tersedia di platform yaa\n\nUntuk kebutuhan resmi seperti visa, universitas, atau pekerjaan internasional, biasanya tetap memerlukan sertifikat resmi dari lembaga penguji yang diakui secara internasional\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 109,
            ],

            [
                'command'        => '1.10',
                'title'          => 'Apakah program ini bisa membantu persiapan studi ke luar negeri?',
                'content'        => "Bisa banget kak!\n\nMateri Languages by Fara dirancang untuk membantu meningkatkan kemampuan bahasa dari level dasar hingga lanjutan sehingga dapat menjadi bekal yang baik untuk persiapan studi di luar negeri\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 110,
            ],

            [
                'command'        => '1.11',
                'title'          => 'Apakah program ini bisa membantu persiapan kerja di luar negeri?',
                'content'        => "Tentu bisa kak!\n\nDengan mempelajari bahasa yang dibutuhkan negara tujuan, kakak dapat meningkatkan kemampuan komunikasi yang sangat bermanfaat untuk persiapan kerja di luar negeri\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 111,
            ],

            [
                'command'        => '1.12',
                'title'          => 'Apakah program ini bisa membantu persiapan pindah ke luar negeri?',
                'content'        => "Bisa banget dong kak\n\nKemampuan bahasa merupakan salah satu bekal penting ketika ingin tinggal atau beradaptasi di negara lain. Melalui Languages by Fara, kakak dapat mempersiapkan kemampuan bahasa tersebut secara bertahap\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 112,
            ],

            [
                'command'        => '1.13',
                'title'          => 'Berapa lama durasi belajarnya?',
                'content'        => "Karena sistem belajarnya fleksibel dan mandiri, durasi belajar dapat disesuaikan dengan kecepatan masing-masing kakak ya\n\nKakak bisa belajar sesuai waktu luang dan target yang ingin dicapai yaa\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 113,
            ],

            [
                'command'        => '1.14',
                'title'          => 'Kapan belajarnya bisa dimulai?',
                'content'        => "Setelah akun aktif dan akses diberikan, kakak bisa langsung mulai belajar kapan saja yaa\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 114,
            ],

            [
                'command'        => '1.15',
                'title'          => 'Apakah bisa mulai kapan saja?',
                'content'        => "Bisa banget kak!\n\nTidak perlu menunggu pembukaan kelas atau jadwal tertentu. Setelah mendapatkan akses, kakak bisa langsung mulai belajar\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 115,
            ],

            [
                'command'        => '1.16',
                'title'          => 'Apakah ada jadwal tertentu?',
                'content'        => "Tidak ada jadwal kelas tetap ya kak\n\nKarena sistem pembelajaran dilakukan melalui LMS, kakak bisa mengatur sendiri waktu belajarnya sesuai aktivitas sehari-hari yaa\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 116,
            ],

            [
                'command'        => '1.17',
                'title'          => 'Apakah sistem belajarnya fleksibel?',
                'content'        => "Iya kak!\n\nSemua materi dapat diakses secara online melalui LMS sehingga kakak bisa belajar kapan saja dan dari mana saja selama memiliki koneksi internet yaa kak\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 117,
            ],

            [
                'command'        => '1.18',
                'title'          => 'Apakah ada batas usia peserta?',
                'content'        => "Tidak ada batas usia khusus ya kak\n\nSiapa saja yang memiliki minat untuk belajar bahasa dapat mengikuti program di Languages by Fara\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 118,
            ],

            [
                'command'        => '1.19',
                'title'          => 'Apakah ada program untuk anak-anak?',
                'content'        => "Saat ini materi Languages by Fara dirancang untuk remaja dan dewasa ya kak\n\nNamun anak-anak tetap dapat mengakses materi dengan pendampingan orang tua apabila diperlukan\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 119,
            ],

            [
                'command'        => '1.20',
                'title'          => 'Apakah ada program untuk remaja?',
                'content'        => "Ada kak!\n\nProgram Languages by Fara dapat diikuti oleh remaja yang ingin mulai belajar bahasa asing untuk sekolah, persiapan studi, maupun pengembangan diri\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 120,
            ],

            [
                'command'        => '1.21',
                'title'          => 'Apakah ada program untuk dewasa?',
                'content'        => "Tentu ada kak\n\nProgram Languages by Fara sangat cocok untuk peserta dewasa yang ingin belajar bahasa untuk kebutuhan kerja, studi, migrasi, traveling, maupun pengembangan diri\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 121,
            ],

            [
                'command'        => '1.22',
                'title'          => 'Apakah saya harus memiliki pengalaman belajar bahasa sebelumnya?',
                'content'        => "Tidak perlu kak\n\nKakak bisa mulai dari level dasar meskipun belum pernah belajar bahasa tersebut sebelumnya\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 122,
            ],

            [
                'command'        => '1.23',
                'title'          => 'Apakah materi bisa diakses melalui HP?',
                'content'        => "Bisa kak!\n\nPlatform Languages by Fara dapat diakses melalui smartphone, tablet, maupun laptop selama terhubung dengan internet yaa kak\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 123,
            ],

            [
                'command'        => '1.24',
                'title'          => 'Apakah saya bisa mengulang materi yang sudah dipelajari?',
                'content'        => "Bisa banget kak\n\nSelama masa akses masih aktif, kakak dapat mengulang materi yang sudah dipelajari kapan saja sesuai kebutuhan\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 124,
            ],

            [
                'command'        => '1.25',
                'title'          => 'Apakah ada tugas atau latihan?',
                'content'        => "Ada kak\n\nSetiap program dilengkapi dengan latihan dan aktivitas belajar yang membantu kakak memahami materi dengan lebih baik\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 125,
            ],

            [
                'command'        => '1.26',
                'title'          => 'Apakah ada mentor yang bisa ditanya?',
                'content'        => "Tentu kak!\n\nSelama menggunakan Languages by Fara, kakak dapat menghubungi MinFara apabila memiliki pertanyaan seputar materi pembelajaran maupun kendala teknis pada LMS\n\nMinFara akan berusaha membantu dan mengarahkan kakak agar proses belajar tetap nyaman dan lancar yaa\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 126,
            ],

            [
                'command'        => '1.27',
                'title'          => 'Apakah semua bahasa sudah tersedia sekarang?',
                'content'        => "Saat ini yang tersedia saat launching adalah:\n- Bahasa Jerman (A1, A2, B1)\n- Bahasa Inggris (Level A1)\n- Bahasa Prancis (A1)\n\nSedangkan program berikut masih dalam tahap pengembangan dan akan segera hadir:\n- Bahasa Turki (A1)\n- Bahasa Jepang (N5)\n- Bahasa Korea (Level 1)\n- Bahasa Arab (Level 1)\n- Bahasa Mandarin (HSK 1)\n\nStay tuned ya kak!\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 127,
            ],

            [
                'command'        => '1.28',
                'title'          => 'Apakah saya akan mendapatkan update materi?',
                'content'        => "Iya kak\n\nTim Languages by Fara akan terus melakukan pengembangan dan penambahan materi secara berkala agar pengalaman belajar kakak semakin maksimal\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 128,
            ],

            [
                'command'        => '1.29',
                'title'          => 'Apakah saya harus menyelesaikan level sebelumnya untuk naik level berikutnya?',
                'content'        => "Sangat disarankan ya kak\n\nKarena materi setiap level disusun secara bertahap, sehingga pemahaman di level sebelumnya akan membantu kakak mengikuti materi di level berikutnya dengan lebih mudah\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 129,
            ],

            [
                'command'        => '1.30',
                'title'          => 'Apakah saya bisa belajar dari luar Indonesia?',
                'content'        => "Tentu bisa kak!\n\nKarena seluruh pembelajaran dilakukan secara online melalui LMS, kakak dapat mengakses materi dari mana saja selama memiliki koneksi internet yang stabil yaa kak\n{$f1}",
                'parent_command' => '1',
                'is_active'      => true,
                'sort_order'     => 130,
            ],

            // ═══════════════════════════════════════════════════════════════
            // LAYER 3 — FAQ LEVEL (2.1–2.10) sort_order 201–210
            // ═══════════════════════════════════════════════════════════════

            [
                'command'        => '2.1',
                'title'          => 'Apa aja level yang tersedia?',
                'content'        => "Haloo kak!\n\nSaat ini level yang tersedia di Languages by Fara adalah:\n\n1. Bahasa Jerman\n   - A1\n   - A2\n   - B1\n2. Bahasa Inggris\n   - Level 1\n3. Bahasa Prancis\n   - A1\n\nSedangkan program berikut masih dalam tahap pengembangan dan akan segera hadir:\n1. Bahasa Turki (A1)\n2. Bahasa Jepang (N5)\n3. Bahasa Korea (Level 1)\n4. Bahasa Arab (Level 1)\n5. Bahasa Mandarin (HSK 1)\n\nKalau MinFara boleh tahu, kakak ingin belajar bahasa apa nih? Nanti MinFara bantu rekomendasikan level yang cocok untuk kakak\n{$f2}",
                'parent_command' => '2',
                'is_active'      => true,
                'sort_order'     => 201,
            ],

            [
                'command'        => '2.2',
                'title'          => 'Bagaimana cara menentukan level saya?',
                'content'        => "Sebelum menentukan level, boleh MinFara tahu apakah kakak sudah pernah belajar bahasa tersebut sebelumnya?\n\nKalau sudah pernah, kakak bisa ceritakan pengalaman belajarnya atau level terakhir yang pernah dipelajari. Nanti MinFara bantu memberikan rekomendasi level yang paling sesuai yaa\n{$f2}",
                'parent_command' => '2',
                'is_active'      => true,
                'sort_order'     => 202,
            ],

            [
                'command'        => '2.3',
                'title'          => 'Apakah harus mulai dari level dasar dulu?',
                'content'        => "Kalau kakak belum pernah belajar bahasa tersebut sebelumnya, MinFara sangat menyarankan untuk memulai dari level paling dasar yaa\n\nDengan begitu, proses belajar akan lebih nyaman dan materi dapat dipahami secara bertahap\n{$f2}",
                'parent_command' => '2',
                'is_active'      => true,
                'sort_order'     => 203,
            ],

            [
                'command'        => '2.4',
                'title'          => 'Apakah bisa langsung masuk level tertentu?',
                'content'        => "Bisa kak\n\nNamun sebelumnya MinFara perlu mengetahui pengalaman belajar kakak terlebih dahulu supaya level yang dipilih sesuai dengan kemampuan saat ini.\n\nBoleh diceritakan apakah kakak pernah belajar bahasa tersebut sebelumnya?\n{$f2}",
                'parent_command' => '2',
                'is_active'      => true,
                'sort_order'     => 204,
            ],

            [
                'command'        => '2.5',
                'title'          => 'Berapa lama menyelesaikan satu level?',
                'content'        => "Hal ini kembali ke ritme belajar masing-masing kakak yaa\n\nJika kakak belajar secara konsisten dan termasuk fast learner, satu level bisa saja diselesaikan dalam waktu sekitar 1–2 bulan.\n\nNamun durasi belajar juga dapat disesuaikan dengan masa akses paket yang kakak pilih dan target belajar yang ingin dicapai\n{$f2}",
                'parent_command' => '2',
                'is_active'      => true,
                'sort_order'     => 205,
            ],

            [
                'command'        => '2.6',
                'title'          => 'Berapa jam belajar yang dibutuhkan setiap minggu?',
                'content'        => "Sistem belajar di Languages by Fara sangat fleksibel kak\n\nKakak bebas menentukan sendiri jadwal dan durasi belajar sesuai kesibukan masing-masing.\n\nSebagai gambaran:\n- 30 menit per hari untuk belajar santai\n- 1–2 jam per hari untuk progres yang lebih cepat\n- Bisa disesuaikan dengan target pribadi kakak\n\nYang terpenting adalah konsisten dalam belajar yaa\n{$f2}",
                'parent_command' => '2',
                'is_active'      => true,
                'sort_order'     => 206,
            ],

            [
                'command'        => '2.7',
                'title'          => 'Kurikulum Languages by Fara mengacu ke standar apa?',
                'content'        => "Materi di Languages by Fara disusun dengan mengacu pada standar internasional yang umum digunakan dalam pembelajaran bahasa asing ya kak\n\n1. Bahasa Jerman, Inggris, Prancis, dan Turki\n   Mengacu pada CEFR (Common European Framework of Reference for Languages)\n2. Bahasa Jepang\n   Mengacu pada JLPT (Japanese Language Proficiency Test)\n3. Bahasa Mandarin\n   Mengacu pada HSK (Hanyu Shuiping Kaoshi)\n4. Bahasa Korea\n   Mengacu pada TOPIK (Test of Proficiency in Korean)\n5. Bahasa Arab\n   Mengacu pada kompetensi bahasa Arab internasional yang umum digunakan dalam pembelajaran bahasa Arab untuk penutur asing\n{$f2}",
                'parent_command' => '2',
                'is_active'      => true,
                'sort_order'     => 207,
            ],

            [
                'command'        => '2.8',
                'title'          => 'Apa itu CEFR?',
                'content'        => "CEFR (Common European Framework of Reference for Languages) adalah standar internasional yang digunakan untuk mengukur kemampuan bahasa asing, terutama bahasa-bahasa Eropa.\n\nLevel CEFR terdiri dari:\n- A1 (Pemula)\n- A2 (Dasar)\n- B1 (Menengah)\n- B2 (Menengah Atas)\n- C1 (Lanjutan)\n- C2 (Mahir)\n\nBahasa Jerman, Inggris, Prancis, dan Turki di Languages by Fara mengikuti kerangka CEFR yaa kak\n{$f2}",
                'parent_command' => '2',
                'is_active'      => true,
                'sort_order'     => 208,
            ],

            [
                'command'        => '2.9',
                'title'          => 'Apakah level di Languages by Fara sama dengan level ujian resmi?',
                'content'        => "Materi Languages by Fara disusun mengacu pada standar level internasional ya kak\n\nNamun perlu diperhatikan bahwa Languages by Fara bukan lembaga penyelenggara ujian resmi. Program ini berfungsi sebagai sarana belajar dan persiapan untuk meningkatkan kemampuan bahasa kakak\n{$f2}",
                'parent_command' => '2',
                'is_active'      => true,
                'sort_order'     => 209,
            ],

            [
                'command'        => '2.10',
                'title'          => 'Kalau sudah pernah belajar, level mana yang sebaiknya saya ambil?',
                'content'        => "Tergantung pengalaman belajar kakak yaa\n\nBoleh diinformasikan:\n- Bahasa yang ingin dipelajari\n- Level terakhir yang pernah dipelajari\n- Apakah pernah mengikuti ujian sertifikasi resmi\n\nNanti MinFara bantu merekomendasikan level yang paling sesuai untuk kakak\n{$f2}",
                'parent_command' => '2',
                'is_active'      => true,
                'sort_order'     => 210,
            ],

            // ═══════════════════════════════════════════════════════════════
            // LAYER 3 — FAQ MATERI (3.1–3.14) sort_order 301–314
            // ═══════════════════════════════════════════════════════════════

            [
                'command'        => '3.1',
                'title'          => 'Apa aja yang dipelajari?',
                'content'        => "Di Languages by Fara, kakak akan mempelajari materi bahasa yang disusun secara bertahap sesuai level yang dipilih\n\nMateri utama berupa video pembelajaran yang berfokus pada grammar (tata bahasa), dilengkapi dengan contoh penggunaan bahasa, kosakata, serta kuis untuk membantu kakak memahami materi dengan lebih baik\n{$f3}",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 301,
            ],

            [
                'command'        => '3.2',
                'title'          => 'Apakah fokus belajarnya grammar?',
                'content'        => "Betul kak\n\nMateri di Languages by Fara berfokus pada pembelajaran grammar atau tata bahasa yang disusun secara sistematis dari level dasar hingga lanjutan\n\nGrammar merupakan fondasi penting dalam memahami dan menggunakan bahasa dengan baik yaa kak\n{$f3}",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 302,
            ],

            [
                'command'        => '3.3',
                'title'          => 'Apakah ada materi vocabulary?',
                'content'        => "Ada kak\n\nDalam setiap pembelajaran, kakak juga akan menemukan berbagai kosakata (vocabulary) yang relevan dengan materi yang sedang dipelajari\n{$f3}",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 303,
            ],

            [
                'command'        => '3.4',
                'title'          => 'Apakah ada materi speaking?',
                'content'        => "Saat ini fokus utama pembelajaran di Languages by Fara adalah pemahaman grammar dan penggunaan bahasa melalui video pembelajaran ya kak\n\nNamun kakak tetap bisa melatih kemampuan speaking secara mandiri dengan mengikuti contoh kalimat dan pengucapan yang terdapat dalam materi\n{$f3}",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 304,
            ],

            [
                'command'        => '3.5',
                'title'          => 'Apakah ada materi listening?',
                'content'        => "Beberapa video pembelajaran dapat membantu kakak membiasakan diri mendengarkan bahasa yang dipelajari ya kak\n\nNamun saat ini belum tersedia program listening khusus atau latihan listening terpisah di LMS\n{$f3}",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 305,
            ],

            [
                'command'        => '3.6',
                'title'          => 'Apakah ada materi reading?',
                'content'        => "Ada kak\n\nKakak akan menemukan berbagai contoh kata, kalimat, dan materi tertulis yang membantu memahami penggunaan bahasa dalam konteks yang dipelajari\n{$f3}",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 306,
            ],

            [
                'command'        => '3.7',
                'title'          => 'Apakah ada materi writing?',
                'content'        => "Dalam beberapa kuis dan latihan, kakak dapat menemukan soal isian singkat maupun esai yang membantu melatih pemahaman dan kemampuan menulis ya kak\n{$f3}",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 307,
            ],

            [
                'command'        => '3.8',
                'title'          => 'Apakah ada latihan pronunciation?',
                'content'        => "Beberapa materi dilengkapi dengan contoh pengucapan yang dapat kakak ikuti secara mandiri ya kak\n{$f3}",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 308,
            ],

            [
                'command'        => '3.9',
                'title'          => 'Apakah ada latihan percakapan?',
                'content'        => "Saat ini belum tersedia sesi latihan percakapan secara langsung ya kak\n\nNamun kakak tetap dapat mempelajari contoh penggunaan bahasa dan kalimat yang terdapat di dalam materi pembelajaran\n{$f3}",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 309,
            ],

            [
                'command'        => '3.10',
                'title'          => 'Apakah ada kuis?',
                'content'        => "Tentu ada dong kak!\n\nSetiap level dilengkapi dengan kuis untuk membantu kakak mengukur pemahaman terhadap materi yang telah dipelajari\n{$f3}",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 310,
            ],

            [
                'command'        => '3.11',
                'title'          => 'Bentuk kuisnya seperti apa?',
                'content'        => "Kuis di Languages by Fara dapat berupa:\n- Pilihan ganda\n- Isian singkat\n- Esai\n\nJenis kuis yang tersedia dapat berbeda pada setiap materi ya kak\n{$f3}",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 311,
            ],

            [
                'command'        => '3.12',
                'title'          => 'Apakah ada ujian?',
                'content'        => "Untuk saat ini belum tersedia ujian akhir ya kak\n\nSaat ini Languages by Fara menyediakan:\n- Video pembelajaran\n- Materi grammar\n- Kosakata (vocabulary)\n- Kuis (pilihan ganda, isian singkat, dan esai)\n{$f3}",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 312,
            ],

            [
                'command'        => '3.13',
                'title'          => 'Apakah semua materi bisa diakses berulang kali?',
                'content'        => "Bisa banget kak!\n\nSelama masa akses paket kakak masih aktif, seluruh materi dapat dipelajari dan diulang kembali kapan saja sesuai kebutuhan\n{$f3}",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 313,
            ],

            [
                'command'        => '3.14',
                'title'          => 'Apakah materi cocok untuk belajar mandiri?',
                'content'        => "Cocok banget kak\n\nLanguages by Fara menggunakan sistem self-paced learning, sehingga kakak bisa belajar kapan saja, di mana saja, dan sesuai kecepatan belajar masing-masing\n{$f3}",
                'parent_command' => '3',
                'is_active'      => true,
                'sort_order'     => 314,
            ],

            // ═══════════════════════════════════════════════════════════════
            // LAYER 3 — FAQ FLEXILEARN / LMS (4.1–4.20) sort_order 401–420
            // ═══════════════════════════════════════════════════════════════

            [
                'command'        => '4.1',
                'title'          => 'Apa itu FlexiLearn?',
                'content'        => "FlexiLearn adalah Learning Management System (LMS) milik Languages by Fara yang digunakan untuk mengakses seluruh materi pembelajaran secara online\n\nMelalui FlexiLearn, kakak dapat belajar kapan saja dan di mana saja selama masa akses paket masih aktif\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 401,
            ],

            [
                'command'        => '4.2',
                'title'          => 'Bagaimana cara mengakses FlexiLearn?',
                'content'        => "Mudah banget kak, sekarang bisa checkout mandiri di website!\n\n1. Daftar/login di mitfara.com\n2. Pilih paket di Katalog Kursus, lalu checkout & bayar\n3. Setelah pembayaran selesai, akun FlexiLearn otomatis dikirim ke email kakak\n4. Login di lms.mitfara.com, lalu masukkan kode enrollment dari invoice\n\nKetik *99* untuk panduan lengkap step-by-step yaa\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 402,
            ],

            [
                'command'        => '4.3',
                'title'          => 'Apakah FlexiLearn bisa diakses melalui HP?',
                'content'        => "Bisa banget dong kak!\n\nFlexiLearn dapat diakses melalui smartphone sehingga kakak bisa belajar dengan lebih fleksibel kapan saja yaa kak\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 403,
            ],

            [
                'command'        => '4.4',
                'title'          => 'Apakah FlexiLearn bisa diakses melalui laptop atau komputer?',
                'content'        => "Bisa kak\n\nFlexiLearn dapat diakses melalui laptop maupun komputer sehingga proses belajar menjadi lebih nyaman yaa kak\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 404,
            ],

            [
                'command'        => '4.5',
                'title'          => 'Apakah ada aplikasi mobile?',
                'content'        => "Untuk saat ini FlexiLearn tersedia dalam bentuk website ya kak\n\nJadi kakak cukup membuka browser di HP, tablet, atau laptop untuk mengakses pembelajaran\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 405,
            ],

            [
                'command'        => '4.6',
                'title'          => 'Apakah FlexiLearn tersedia 24 jam?',
                'content'        => "Betul kak\n\nFlexiLearn dapat diakses 24 jam sehari selama masa akses paket kakak masih aktif yaa\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 406,
            ],

            [
                'command'        => '4.7',
                'title'          => 'Apakah saya bisa belajar kapan saja?',
                'content'        => "Bisa banget yaa kak!\n\nKarena sistem belajar di FlexiLearn bersifat fleksibel (self-paced learning), kakak bebas menentukan sendiri kapan ingin belajar sesuai waktu luang yang dimiliki\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 407,
            ],

            [
                'command'        => '4.8',
                'title'          => 'Apakah video pembelajaran bisa diputar ulang?',
                'content'        => "Tentu bisa dong kak!\n\nSelama masa akses paket masih aktif, video pembelajaran dapat kakak tonton dan putar ulang sesuai kebutuhan belajar\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 408,
            ],

            [
                'command'        => '4.9',
                'title'          => 'Apakah materi bisa diakses berulang kali?',
                'content'        => "Bisa kak\n\nSeluruh materi yang tersedia dapat dipelajari kembali selama masa akses paket kakak masih berlaku\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 409,
            ],

            [
                'command'        => '4.10',
                'title'          => 'Apakah materi bisa didownload?',
                'content'        => "Untuk saat ini materi pembelajaran diakses langsung melalui FlexiLearn ya kak\n\nJadi materi tidak tersedia untuk diunduh (download)\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 410,
            ],

            [
                'command'        => '4.11',
                'title'          => 'Apakah ada progress tracker?',
                'content'        => "Ada kak!\n\nFlexiLearn memiliki fitur progress tracker yang membantu kakak memantau perkembangan belajar dan materi yang sudah diselesaikan yaa\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 411,
            ],

            [
                'command'        => '4.12',
                'title'          => 'Apakah ada reminder belajar?',
                'content'        => "Untuk saat ini belum tersedia fitur reminder belajar otomatis ya kak\n\nNamun karena sistemnya fleksibel, kakak dapat mengatur jadwal belajar sendiri sesuai target yang ingin dicapai\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 412,
            ],

            [
                'command'        => '4.13',
                'title'          => 'Apakah ada forum diskusi?',
                'content'        => "Saat ini belum tersedia forum diskusi antar peserta ya kak\n\nNamun kakak tetap dapat berdiskusi dan bertanya kepada MinFara melalui fitur chat yang tersedia di FlexiLearn yaa\n\nMinFara siap membantu menjawab pertanyaan terkait materi pembelajaran maupun kendala teknis LMS\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 413,
            ],

            [
                'command'        => '4.14',
                'title'          => 'Siapa itu MinFara?',
                'content'        => "MinFara adalah tim support Languages by Fara yang siap membantu kakak selama proses belajar\n\nMinFara tersedia melalui:\n- Chat di FlexiLearn (LMS) untuk pertanyaan materi dan kendala teknis LMS\n- WhatsApp Languages by Fara untuk pertanyaan seputar program, paket, pembayaran, dan informasi umum lainnya\n\nJadi jangan ragu untuk menghubungi MinFara apabila membutuhkan bantuan ya kak\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 414,
            ],

            [
                'command'        => '4.15',
                'title'          => 'Apakah ada fitur catatan (notes)?',
                'content'        => "Untuk saat ini belum tersedia fitur catatan langsung di dalam FlexiLearn ya kak\n\nKakak dapat membuat catatan pribadi menggunakan aplikasi atau media yang biasa digunakan sehari-hari\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 415,
            ],

            [
                'command'        => '4.16',
                'title'          => 'Apakah ada bookmark materi?',
                'content'        => "Untuk saat ini belum tersedia fitur bookmark khusus ya kak\n\nNamun progress belajar kakak akan tetap tersimpan sehingga lebih mudah melanjutkan pembelajaran berikutnya\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 416,
            ],

            [
                'command'        => '4.17',
                'title'          => 'Apakah ada fitur pencarian materi?',
                'content'        => "Untuk saat ini materi disusun berdasarkan bahasa, level, dan topik pembelajaran sehingga mudah untuk dinavigasi ya kak\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 417,
            ],

            [
                'command'        => '4.18',
                'title'          => 'Bagaimana jika saya lupa password?',
                'content'        => "Tidak perlu khawatir kak\n\nKakak dapat menghubungi MinFara dan tim kami akan membantu proses pemulihan akun atau penggantian password\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 418,
            ],

            [
                'command'        => '4.19',
                'title'          => 'Apakah akun FlexiLearn bisa digunakan di lebih dari satu perangkat?',
                'content'        => "Bisa kak\n\nKakak dapat mengakses akun melalui perangkat yang dimiliki, seperti HP, tablet, maupun laptop selama menggunakan akun yang terdaftar\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 419,
            ],

            [
                'command'        => '4.20',
                'title'          => 'Jika mengalami kendala saat belajar harus menghubungi siapa?',
                'content'        => "Apabila kakak mengalami kendala saat belajar, kakak bisa langsung menghubungi MinFara\n\nMelalui fitur chat di FlexiLearn (LMS) untuk:\n- Pertanyaan materi pembelajaran\n- Kendala teknis LMS\n- Kendala akses akun\n- Kendala mengakses materi atau kuis\n\nMelalui WhatsApp Languages by Fara untuk:\n- Informasi program\n- Paket berlangganan\n- Pembayaran\n- Masa akses akun\n- Pertanyaan umum lainnya\n\nMinFara akan dengan senang hati membantu kakak agar pengalaman belajar di Languages by Fara tetap nyaman dan lancar yaa\n{$f4}",
                'parent_command' => '4',
                'is_active'      => true,
                'sort_order'     => 420,
            ],

            // ═══════════════════════════════════════════════════════════════
            // LAYER 3 — FAQ HARGA & PEMBAYARAN (5.1–5.19) sort_order 501–519
            // ═══════════════════════════════════════════════════════════════

            [
                'command'        => '5.1',
                'title'          => 'Berapa harga program di Languages by Fara?',
                'content'        => "Harga program berbeda tergantung bahasa yang dipilih ya kak\n\n*Bahasa Jerman:*\n- A1 Satuan (2 Bulan) — Rp149.000\n- A1 Satuan (6 Bulan) — Rp169.000\n- A1 Satuan (12 Bulan) — Rp189.000\n- Lifetime Basic — Rp199.000\n- Lifetime + 10 Digital Product — Rp299.000\n- Lifetime + 20 Digital Product — Rp399.000\n- Lifetime + 20 Digital Product + 1x Private 60 Menit — Rp599.000\n- Lifetime + 20 Digital Product + 2x Private 90 Menit — Rp699.000\n\n*Bahasa Inggris, Prancis, Turki, Jepang, Korea, Arab, dan Mandarin:*\n- 2 Bulan — Rp189.000\n- 6 Bulan — Rp209.000\n- 12 Bulan — Rp219.000\n- Lifetime — Rp239.000\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 501,
            ],

            [
                'command'        => '5.2',
                'title'          => 'Apa saja yang didapat pada paket Bahasa Jerman?',
                'content'        => "Semua paket Bahasa Jerman mendapatkan:\n- Video pembelajaran\n- Modul pembelajaran\n- 1000+ soal latihan\n- Kuis pembelajaran\n- Certificate of Completion\n\nKhusus paket tertentu, kakak juga akan mendapatkan bonus digital product dan sesi private sesuai paket yang dipilih\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 502,
            ],

            [
                'command'        => '5.3',
                'title'          => 'Apa saja yang didapat pada paket Bahasa Inggris, Prancis, Turki, dll?',
                'content'        => "Untuk program bahasa selain Bahasa Jerman, kakak akan mendapatkan:\n- Video pembelajaran\n- Materi pembelajaran sesuai level\n- Kuis pembelajaran\n- Certificate of Completion\n\nProgram-program ini dirancang khusus untuk membantu kakak mempelajari dasar bahasa secara terstruktur dan fleksibel melalui FlexiLearn\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 503,
            ],

            [
                'command'        => '5.4',
                'title'          => 'Kenapa harga Bahasa Jerman dan bahasa lainnya berbeda?',
                'content'        => "Karena struktur program dan fasilitas yang tersedia berbeda ya kak\n\nProgram Bahasa Jerman saat ini merupakan program yang paling lengkap di Languages by Fara dengan:\n- Beberapa level pembelajaran (A1, A2, B1)\n- Modul pembelajaran\n- 1000+ soal latihan\n- Pilihan paket premium dengan bonus digital product dan sesi private\n\nSedangkan program bahasa lainnya saat ini tersedia pada level dasar (beginner) dan akan terus dikembangkan secara bertahap\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 504,
            ],

            [
                'command'        => '5.5',
                'title'          => 'Apa perbedaan paket 2 bulan, 6 bulan, 12 bulan, dan lifetime?',
                'content'        => "Perbedaannya terletak pada lama akses yang kakak dapatkan\n\n- Paket 2 Bulan → akses selama 2 bulan\n- Paket 6 Bulan → akses selama 6 bulan\n- Paket 12 Bulan → akses selama 12 bulan\n- Paket Lifetime → akses tanpa batas waktu\n\nMateri yang didapat tetap sama, yang berbeda adalah masa aksesnya ya kak\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 505,
            ],

            [
                'command'        => '5.6',
                'title'          => 'Apa itu paket Lifetime?',
                'content'        => "Paket Lifetime adalah paket dengan akses tanpa batas waktu\n\nJadi kakak dapat mengakses materi pembelajaran kapan saja tanpa perlu memperpanjang masa akses\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 506,
            ],

            [
                'command'        => '5.7',
                'title'          => 'Paket mana yang paling direkomendasikan?',
                'content'        => "Kalau kakak ingin belajar tanpa terburu-buru dan dapat mengakses materi kapan saja, MinFara biasanya merekomendasikan paket Lifetime\n\nDengan paket Lifetime, kakak tidak perlu khawatir masa akses habis dan bisa mengulang materi sebanyak yang dibutuhkan\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 507,
            ],

            [
                'command'        => '5.8',
                'title'          => 'Apakah semua paket mendapatkan sertifikat?',
                'content'        => "Iya kak!\n\nSeluruh paket di Languages by Fara sudah termasuk Certificate of Completion yang dapat diperoleh setelah kakak menyelesaikan pembelajaran sesuai ketentuan program yaa\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 508,
            ],

            [
                'command'        => '5.9',
                'title'          => 'Bagaimana cara melakukan pembayaran?',
                'content'        => "Setelah kakak memilih paket yang diinginkan, pembayaran dapat dilakukan ke rekening resmi PT Fara Kreatif Sejahtera ya kak\n\n*Bank Mandiri*\nNomor Rekening: 130-00-2353540-7\na.n. PT Fara Kreatif Sejahtera\n\natau\n\n*Bank BCA*\nNomor Rekening: 4490365864\na.n. PT Fara Kreatif Sejahtera\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 509,
            ],

            [
                'command'        => '5.10',
                'title'          => 'Pembayarannya transfer ke rekening siapa?',
                'content'        => "Seluruh pembayaran dilakukan ke rekening resmi atas nama PT Fara Kreatif Sejahtera, selaku pengelola Languages by Fara ya kak\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 510,
            ],

            [
                'command'        => '5.11',
                'title'          => 'Apakah pembayaran bisa melalui Bank BCA?',
                'content'        => "Bisa kak\n\n*Bank BCA*\nNomor Rekening: 4490365864\na.n. PT Fara Kreatif Sejahtera\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 511,
            ],

            [
                'command'        => '5.12',
                'title'          => 'Apakah pembayaran bisa melalui Bank Mandiri?',
                'content'        => "Bisa kak\n\n*Bank Mandiri*\nNomor Rekening: 130-00-2353540-7\na.n. PT Fara Kreatif Sejahtera\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 512,
            ],

            [
                'command'        => '5.13',
                'title'          => 'Apa yang harus dilakukan setelah pembayaran?',
                'content'        => "Setelah pembayaran di website berhasil, kakak akan diarahkan kembali ke Student Portal\n\nTunggu beberapa menit/jam, akun FlexiLearn akan dikirim otomatis ke email kakak. Cek email, login di lms.mitfara.com, lalu masukkan kode enrollment dari invoice di menu Kursus Saya\n\nKetik *99* untuk panduan lengkapnya yaa\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 513,
            ],

            [
                'command'        => '5.14',
                'title'          => 'Berapa lama proses aktivasi akun?',
                'content'        => "Setelah pembayaran berhasil, akun FlexiLearn biasanya dikirim otomatis via email dalam beberapa menit hingga maksimal 1x24 jam ya kak\n\nKalau lewat dari itu belum diterima, coba cek folder spam dulu atau hubungi admin kami di https://wa.me/6289647897616\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 514,
            ],

            [
                'command'        => '5.15',
                'title'          => 'Apakah ada biaya tambahan selain harga paket?',
                'content'        => "Tidak ada kak\n\nKakak cukup membayar sesuai harga paket yang dipilih, kecuali terdapat promo atau program khusus yang diumumkan secara resmi oleh Languages by Fara\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 515,
            ],

            [
                'command'        => '5.16',
                'title'          => 'Apakah bisa upgrade paket setelah membeli?',
                'content'        => "Bisa dikonsultasikan terlebih dahulu dengan MinFara ya kak\n\nNanti MinFara akan membantu menjelaskan opsi upgrade yang tersedia sesuai paket yang sudah kakak miliki\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 516,
            ],

            [
                'command'        => '5.17',
                'title'          => 'Apakah pembayaran bisa dicicil?',
                'content'        => "Untuk saat ini pembayaran dilakukan secara penuh (full payment) ya kak\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 517,
            ],

            [
                'command'        => '5.18',
                'title'          => 'Apakah pembayaran bisa dikembalikan (refund)?',
                'content'        => "Mohon maaf kak\n\nSeluruh pembayaran yang telah dilakukan tidak dapat dikembalikan (non-refundable)\n\nOleh karena itu, sebelum melakukan pembayaran, MinFara menyarankan kakak untuk memastikan terlebih dahulu bahasa, paket, dan durasi akses yang ingin dipilih\n\nApabila ada pertanyaan mengenai program atau paket yang tersedia, jangan ragu untuk menghubungi MinFara terlebih dahulu yaa\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 518,
            ],

            [
                'command'        => '5.19',
                'title'          => 'Apakah ada promo atau diskon?',
                'content'        => "Pastinya ada yaa kak\n\nLanguages by Fara sesekali mengadakan promo, diskon, maupun program spesial yang dapat membantu kakak mendapatkan harga yang lebih hemat\n\nAgar tidak ketinggalan informasi terbaru, jangan lupa follow media sosial Languages by Fara yaa:\n\nInstagram/TikTok/Threads: languagesbyfara\n\nSemua informasi terkait promo, peluncuran program baru, event, dan update terbaru akan diumumkan melalui media sosial resmi Languages by Fara\n{$f5}",
                'parent_command' => '5',
                'is_active'      => true,
                'sort_order'     => 519,
            ],

            // ═══════════════════════════════════════════════════════════════
            // LAYER 3 — TENTANG LANGUAGES BY FARA (6.1–6.13) sort_order 601–613
            // ═══════════════════════════════════════════════════════════════

            [
                'command'        => '6.1',
                'title'          => 'Apa itu Languages by Fara?',
                'content'        => "Languages by Fara adalah platform pembelajaran bahasa asing berbasis LMS (Learning Management System) yang menyediakan pembelajaran secara fleksibel dan mandiri melalui website\n\nSaat ini Languages by Fara menyediakan pembelajaran untuk berbagai bahasa asing, mulai dari Bahasa Jerman, Inggris, Prancis, hingga bahasa-bahasa lainnya yang akan hadir secara bertahap\n{$f6}",
                'parent_command' => '6',
                'is_active'      => true,
                'sort_order'     => 601,
            ],

            [
                'command'        => '6.2',
                'title'          => 'Siapa pemilik Languages by Fara?',
                'content'        => "Languages by Fara didirikan dan dikembangkan oleh Fara\n\nMelalui Languages by Fara, Fara memiliki visi untuk membuat pembelajaran bahasa asing menjadi lebih mudah diakses, fleksibel, dan terjangkau bagi siapa saja\n{$f6}",
                'parent_command' => '6',
                'is_active'      => true,
                'sort_order'     => 602,
            ],

            [
                'command'        => '6.3',
                'title'          => 'Apakah Languages by Fara merupakan lembaga resmi?',
                'content'        => "Iya kak\n\nLanguages by Fara beroperasi di bawah PT Fara Kreatif Sejahtera, sebuah badan usaha yang menaungi berbagai program dan layanan edukasi bahasa\n{$f6}",
                'parent_command' => '6',
                'is_active'      => true,
                'sort_order'     => 603,
            ],

            [
                'command'        => '6.4',
                'title'          => 'Apa hubungan Languages by Fara dengan Deutsch lernen mit Fara?',
                'content'        => "Languages by Fara merupakan sister brand dari Deutsch lernen mit Fara\n\nDeutsch lernen mit Fara berfokus pada pembelajaran Bahasa Jerman, sedangkan Languages by Fara hadir untuk menyediakan pembelajaran berbagai bahasa asing dalam satu platform\n{$f6}",
                'parent_command' => '6',
                'is_active'      => true,
                'sort_order'     => 604,
            ],

            [
                'command'        => '6.5',
                'title'          => 'Kapan Deutsch lernen mit Fara berdiri?',
                'content'        => "Deutsch lernen mit Fara (DLMF) telah hadir sejak tahun 2020\n\nSejak berdiri, DLMF berfokus pada pembelajaran Bahasa Jerman dan telah membantu banyak pembelajar dalam mempersiapkan studi, karier, maupun pengembangan kemampuan bahasa\n{$f6}",
                'parent_command' => '6',
                'is_active'      => true,
                'sort_order'     => 605,
            ],

            [
                'command'        => '6.6',
                'title'          => 'Kapan LMS pertama kali diluncurkan?',
                'content'        => "LMS pertama kali diluncurkan oleh Deutsch lernen mit Fara pada 17 Agustus 2025\n\nPada saat peluncuran, LMS berfokus pada pembelajaran Bahasa Jerman dan terus berkembang hingga menyediakan materi dari level A1, A2, hingga B1\n{$f6}",
                'parent_command' => '6',
                'is_active'      => true,
                'sort_order'     => 606,
            ],

            [
                'command'        => '6.7',
                'title'          => 'Kapan Languages by Fara mulai beroperasi?',
                'content'        => "Languages by Fara secara resmi memulai operasionalnya melalui kegiatan kick-off pada 17 Juni 2026\n\nPlatform ini hadir sebagai pengembangan dari LMS Bahasa Jerman yang sebelumnya telah dibangun oleh Deutsch lernen mit Fara\n{$f6}",
                'parent_command' => '6',
                'is_active'      => true,
                'sort_order'     => 607,
            ],

            [
                'command'        => '6.8',
                'title'          => 'Apa tujuan hadirnya Languages by Fara?',
                'content'        => "Languages by Fara hadir dengan tujuan untuk memberikan akses pembelajaran bahasa asing yang fleksibel, terjangkau, dan dapat diakses kapan saja melalui sistem belajar mandiri (self-paced learning)\n{$f6}",
                'parent_command' => '6',
                'is_active'      => true,
                'sort_order'     => 608,
            ],

            [
                'command'        => '6.9',
                'title'          => 'Apa saja bahasa yang tersedia di Languages by Fara?',
                'content'        => "Saat ini Languages by Fara mengembangkan pembelajaran untuk:\n- Bahasa Jerman\n- Bahasa Inggris\n- Bahasa Prancis\n- Bahasa Turki\n- Bahasa Jepang\n- Bahasa Korea\n- Bahasa Arab\n- Bahasa Mandarin\n\nProgram akan terus dikembangkan secara bertahap sesuai kebutuhan pembelajar\n{$f6}",
                'parent_command' => '6',
                'is_active'      => true,
                'sort_order'     => 609,
            ],

            [
                'command'        => '6.10',
                'title'          => 'Di mana lokasi Languages by Fara?',
                'content'        => "Languages by Fara dan Deutsch lernen mit Fara berada di bawah naungan PT Fara Kreatif Sejahtera yang beralamat di:\n\nJalan Terusan Sari Asih No. 76, Kota Bandung, Jawa Barat, Indonesia\n{$f6}",
                'parent_command' => '6',
                'is_active'      => true,
                'sort_order'     => 610,
            ],

            [
                'command'        => '6.11',
                'title'          => 'Apakah Languages by Fara memiliki kantor fisik?',
                'content'        => "Iya kak\n\nLanguages by Fara berada di bawah PT Fara Kreatif Sejahtera yang berlokasi di:\n\nJalan Terusan Sari Asih No. 76, Kota Bandung, Jawa Barat, Indonesia\n\nNamun seluruh layanan pembelajaran Languages by Fara saat ini dilakukan secara online melalui platform FlexiLearn\n{$f6}",
                'parent_command' => '6',
                'is_active'      => true,
                'sort_order'     => 611,
            ],

            [
                'command'        => '6.12',
                'title'          => 'Apakah Languages by Fara hanya menyediakan pembelajaran online?',
                'content'        => "Iya kak\n\nSaat ini seluruh materi pembelajaran tersedia secara online melalui FlexiLearn sehingga kakak dapat belajar kapan saja dan dari mana saja selama memiliki akses internet yaa\n{$f6}",
                'parent_command' => '6',
                'is_active'      => true,
                'sort_order'     => 612,
            ],

            [
                'command'        => '6.13',
                'title'          => 'Mengapa memilih Languages by Fara?',
                'content'        => "- Belajar fleksibel kapan saja dan di mana saja\n- Materi terstruktur sesuai level pembelajaran\n- Akses melalui FlexiLearn (LMS)\n- Tersedia berbagai pilihan bahasa asing\n- Dapat belajar secara mandiri sesuai kecepatan masing-masing\n- Didukung oleh pengalaman pengembangan LMS Bahasa Jerman sejak 2025\n\nLanguages by Fara hadir untuk membantu kakak mencapai tujuan belajar bahasa dengan cara yang lebih praktis dan fleksibel\n{$f6}",
                'parent_command' => '6',
                'is_active'      => true,
                'sort_order'     => 613,
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
