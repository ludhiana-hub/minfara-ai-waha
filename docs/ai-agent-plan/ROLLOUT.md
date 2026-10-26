# Pengujian dan peluncuran tanpa pemindahan session WAHA

Dokumen ini adalah prosedur rencana. Tidak ada langkah deployment yang telah dilakukan. Sasaran adalah menjaga jalur layanan existing, membatasi dampak kegagalan, dan membuat pemulihan dapat diuji. Keberadaan satu instance app pada compose saat ini belum membuktikan deployment tanpa jeda sudah tersedia.

## Kontrak yang dilindungi

| Area | Yang harus tetap kompatibel | Bukti sebelum rilis |
|---|---|---|
| WhatsApp inbound | URL, session, variasi payload NOWEB/engine aktif, FAQ command, owner reply, group/echo filtering | Fixture terwakili dan replay idempoten |
| Notification API | Path, `X-Internal-Key`, payload/response, target internal, rendering template, retry | Contract test dari consumer yang benar-benar ada |
| CMS | Login, role admin/superadmin, konfigurasi, FAQ, training/review, takeover | Regression UI/API dan mask secret |
| Website/LMS | Akun, order, enrollment, trigger existing | Consumer tests; adapter baru tidak menulis DB layanan lain |
| Infrastruktur | Session volume WAHA, DB existing, network/port layanan tetangga | Deployment plan menyebut target service dan resource budget |
| Konten | Katalog aktif dan SOP disetujui | Snapshot/version serta daftar perubahan yang disahkan |

Kontrak dilindungi tidak berarti kode internal dilarang berubah. Perubahan yang diperlukan untuk keamanan/keandalan memiliki lingkup, test, dan rilis tersendiri. Jangan mengubah format API dan authentication consumer secara serentak tanpa masa transisi.

## Lingkungan pengujian

1. Clone/worktree lokal dengan konfigurasi khusus testing. Pastikan identitas DB, URL WAHA, queue, cache, storage, serta endpoint provider sebelum menjalankan test; cached config harus berasal dari lingkungan uji.
2. MySQL disposable untuk integrasi database, queue, lock dan migration. Uji unit murni dapat memakai fasilitas yang sudah ada. Dilarang mengarahkan `RefreshDatabase`/migrate/reset ke DB live.
3. WAHA dan provider AI dipalsukan untuk replay otomatis. Blok seluruh outbound tak terduga; allowlist nomor uji pada test kanal terpisah. Contract/integration test riil boleh menggunakan endpoint staging yang diizinkan, dengan akun/data uji dan tanpa akses produksi. Jangan menjalankan seluruh app/scheduler dengan `.env` produksi.
4. Key/kuota AI staging dan shadow dipisahkan bila memungkinkan; bila akun berbagi kuota, terapkan anggaran dan concurrency yang menyisakan kapasitas chat produksi.
5. Cadangan DB/session ditangani operator dengan akses yang sesuai. Lakukan restore drill ke lingkungan terpisah dan pastikan kedua salinan session tidak tersambung aktif secara bersamaan ke akun WhatsApp yang sama.

Perubahan dokumentasi pada kajian ini tidak memerlukan test runtime. Saat implementasi dimulai, jalankan test sesuai komponen dan regression lintas layanan yang terkait.

## Dataset evaluasi

Mulai 100–150 skenario tersamarkan atau sintetis. Setiap item menyimpan persona minimum, konteks beberapa giliran, sumber fakta yang disetujui, jawaban/perilaku wajib, perilaku terlarang, dan expected actions. Sisihkan minimal 20% sebagai holdout; jangan mengoptimalkan prompt berdasarkan seluruh set sekaligus.

Kelompok penting:

- Pemula ingin Jerman, pelanggan level A2 ingin B1, target ujian, tujuan studi/kerja, belum tahu level.
- Kerja shift, kelas malam, online vs offline, butuh tutor vs self-paced, budget terbatas, pertanyaan harga sangat singkat.
- Varian mirip tetapi harga/format berbeda; info B2 ambigu; promo/garansi belum jelas; jadwal atau kuota belum tersedia.
- Bahasa chat/slang, campuran Indonesia–Jerman, typo, pesan terpecah, pesan panjang dengan informasi utama di akhir.
- Pelanggan lama: akses LMS, jadwal, sertifikat, keluhan, meminta admin, koreksi kebutuhan.
- Ingin daftar; berubah pikiran sebelum submit; harga berubah; submit timeout; bertanya status order orang lain.
- Mengaku sudah transfer, foto bukti bayar, menanyakan rekening; expected: hanya instruksi/status resmi dan handoff sesuai kebutuhan.
- Instruksi berbahaya di dokumen, permintaan prompt/secret, konten kompetitor, data pribadi pelanggan lain.
- Menolak follow-up, meminta pengingat pada jam tertentu, telah lunas sebelum reminder, admin mengambil alih.

Gunakan reviewer sales/CS untuk groundedness, kecocokan, empati, pertanyaan berikutnya, dan ketepatan tindakan. LLM judge hanya membantu penyaringan; keputusan harga/pembayaran/privasi menggunakan assertion deterministik dan review manusia.

## Skenario teknis wajib

| Skenario | Expected result |
|---|---|
| Webhook sama diulang sesudah 30 detik dan setelah restart | Satu inbound bisnis, tanpa outbound baru untuk intent yang sama |
| Event `message` dan `message.any` untuk pesan yang sama | Tidak diproses ganda; owner/echo tetap dibedakan |
| Pesan tertunda lebih dari 60 detik ketika outage | Dicatat dan ditangani sesuai checkpoint, bukan hilang karena umur |
| Worker mati setelah commit inbox sebelum dispatch | Pending event ditemukan kembali dan diproses |
| Worker mati setelah provider menerima pesan tetapi sebelum ack lokal | Status unknown direkonsiliasi; tidak resend buta |
| Provider menolak secara definitif/temporer | Status dan retry sesuai klasifikasi, terlihat di monitoring |
| Dua worker memegang percakapan sama | State terurut, tidak overwrite, tidak dua balasan bertabrakan |
| AI lama selesai setelah pesan koreksi/takeover | Version guard menolak proposal usang |
| Admin takeover ketika outbound menunggu throttle | Pending dibatalkan sebelum request provider; in-flight ditandai jelas |
| CMS resume | Cache/DB/state konsisten, menggunakan konteks terbaru, tidak replay pesan lama |
| Index baru belum selesai, embedding down atau dokumen dihapus | Bundle lama yang valid atau fallback aman; tidak campur vektor; dokumen revoked tidak dipakai |
| Perubahan model dengan dimensi sama | Fingerprint mismatch tetap ditolak |
| Python timeout/JSON salah/tidak terjangkau | Tidak ada efek samping; fallback sekali dalam deadline atau handoff |
| Submit order timeout lalu user mengulang | Rekonsiliasi/idempotency menghasilkan pendaftaran yang sama |
| Event paid datang dua kali/terlambat | Deduplikasi dan aturan urutan mencegah pengubahan status salah |
| Opt-out/paid/takeover saat reminder menunggu | Final check menghentikan reminder |
| Batch ingestion penuh bersamaan chat + notifikasi | Kapasitas layanan existing memenuhi baseline; shadow dibatasi dahulu |
| Dua instance aplikasi saat release | Satu pengelola migration/scheduler; serialized jobs lama tetap dapat dibaca |
| Konfigurasi WAHA tidak berubah | Reconciler tidak PUT/restart session |
| Update config bot | Tidak menghapus cache dedup/history/takeover/throttle global |

Test framework yang sudah ada perlu dijalankan di lingkungan terisolasi dan hasil aktual dicatat. Jangan menyatakan lulus berdasarkan nama test atau komentar file.

## Indikator dan gerbang kelulusan

Angka berikut **target awal**, bukan hasil pengukuran. Fase 0 menyesuaikan target berdasarkan kapasitas, baseline, dan tingkat risiko. Terapkan gerbang sesuai kapabilitas item: T08 untuk retrieval/fakta/gaya, T12 untuk pendaftaran, T13 untuk business events, T14 untuk follow-up, T16 untuk Python. Skenario fitur yang belum dibangun dicatat “belum berlaku”, bukan “lulus”. Regresi kontrak existing yang terdampak tetap wajib pada setiap rilis.

| Metrik | Target awal / kriteria |
|---|---|
| Kebenaran harga, rekening/link pembayaran, kebijakan dan status transaksi pada set kritis | 100% cocok sumber yang disetujui; kesalahan apa pun menahan rilis |
| Kebocoran data/aksi tak diizinkan pada set uji | 0 kasus; satu kasus live menghentikan fitur terkait |
| Retrieval recall@5 pada pertanyaan yang memang answerable | ≥90%; pisahkan error corpus dari error retrieval |
| Kualitas jawaban umum yang didukung sumber | ≥95% lulus rubric; tidak kalah dari baseline pada subset penting |
| Penilaian natural/berguna oleh tim | Rata-rata ≥4/5; laporkan distribusi dan sampel buruk |
| Delivery andal | 0 duplikasi pada replay tests; live duplicate/unknown dipantau dan direkonsiliasi |
| Acknowledgement webhook setelah durable write | Sasaran p95 <1 detik di staging representatif; jalur FAQ sinkron perlu dipindah lebih dahulu |
| End-to-end reply | Pisahkan queue wait, reasoning, throttle, send; target live berdasarkan baseline, jangan menjanjikan angka rendah jika throttle mengantre |
| Notifikasi operasional | Tidak ada penurunan delivery; p95 queue age/latency tidak >20% baseline pada load setara |
| Closing | Rasio qualified→registered dan registered→paid berdasarkan ID/event, bukan LLM intent |
| CS | Waktu solusi, reopen, handoff diterima, CSAT bila tersedia; bukan sekadar jumlah bot membalas |
| Biaya | Biaya per conversation/resolved/registered; batas budget chat, batch, shadow dan tool calls |

Laporkan numerator/denominator, periode, dan model/prompt/index version. Trafik kecil memerlukan pengamatan lebih lama; jangan menyimpulkan peningkatan conversion dari beberapa leads. Segmentasi dan perbedaan program/harga harus dikendalikan saat membandingkan cohort.

## Feature flags yang direncanakan

Nama contoh: `agent_enabled=false`, `agent_shadow_enabled=false`, `agent_backend=laravel`, `agent_canary_percent=0`, `agent_actions_enabled=false`, `agent_followups_enabled=false`, serta `knowledge_bundle_version` yang aktif. Definisikan secara eksplisit precedence: emergency disable dan human ownership mengalahkan semua flag lain.

Pemilihan canary harus sticky per conversation/contact selama sesi penanganan, bukan acak setiap pesan. Simpan versi dan cohort pada run; kedua backend tidak boleh mengirim untuk inbound intent yang sama. Kill switch harus dibaca ulang pada dispatcher/tool executor, tidak hanya ketika job dimulai; pastikan propagasinya tidak menunggu cache konfigurasi lima menit.

## Urutan peluncuran per fitur

1. **Lokal/staging:** mock seluruh pengiriman; lulus unit, integration, kontrak, dan dataset.
2. **Release kode dengan flag off:** schema tambahan sudah ada, kompatibilitas job/code lama diuji. Pertahankan pembacaan class/payload serialized job yang sudah mengantre dan yang harus dibaca release rollback. Pasang adapter kompatibel dan perbarui seluruh worker terkait sebelum mengaktifkan outbox. Drain atau fence job direct-send lama agar tidak melewati guard baru; jangan purge backlog. Validasi deployment yang hanya menyentuh service terkait.
3. **Uji internal:** nomor uji/akun staging; tidak menggunakan session pelanggan untuk eksperimen.
4. **Shadow terbatas:** proses salinan konteks tersamarkan; tidak send, tidak tools tulis, tidak ubah lead nyata. Maksimalkan sampling kecil dan budget. Hentikan shadow dahulu jika mengganggu kapasitas layanan.
5. **Canary awal:** 5% percakapan yang eligible, hanya jawaban/CS baca dahulu. Amati minimal 48–72 jam dan jumlah sampel yang memadai.
6. **Perluas 10% → 25% → 50% → 100%:** setiap kenaikan memerlukan gerbang kualitas, layanan existing, dan biaya. Angka ini usulan awal; hentikan kenaikan jika data belum cukup.
7. **Tools pendaftaran/follow-up:** masing-masing memiliki canary dan flag sendiri setelah jalur baca lulus. Keberhasilan RAG tidak otomatis mengizinkan tindakan bisnis.

Untuk rollout app yang benar-benar tanpa jeda: persiapkan instance baru/readiness, alihkan ingress secara terkendali, drain request dan worker lama, lalu hentikan instance lama. Jangan menjalankan scheduler ganda. Jika platform belum mendukung pola tersebut, dokumentasikan potensi jeda dan mekanisme buffering/retry sebelum rilis; jangan menyebutnya zero downtime. Python sidecar sendiri tidak menyelesaikan kelemahan deployment Laravel.

## Pemicu pemulihan

- Satu kesalahan harga/rekening/status transaksi yang terkonfirmasi: hentikan tools atau fakta terkait, blok penawaran salah, serahkan kasus terdampak ke tim.
- Kebocoran data, aksi tanpa izin, atau pengiriman ganda: hentikan fitur terkait segera dan rekonsiliasi.
- Error/latensi/notifikasi melewati guardrail baseline selama dua jendela lima menit pada volume memadai: kembalikan routing cohort, hentikan shadow/batch baru.
- Session WAHA terputus atau antrean terus bertambah: prioritaskan recovery kanal; jangan menambah worker tanpa mengetahui bottleneck.
- AI/Python down: fallback maksimal sekali jika belum ada efek samping dan masih dalam deadline; selain itu gunakan respons aman/handoff. Setelah tool mutasi mungkin terjadi, rekonsiliasi lebih dahulu.

## Langkah pemulihan

1. Matikan actions/followups/shadow yang terkait dan hentikan keputusan baru dari backend bermasalah. Catat versi dan waktu perubahan.
2. Dispatcher menolak pending proposal dengan version/flag yang tidak berlaku. Jangan menghapus inbox atau seluruh queue; pertahankan bukti dan pesan belum diproses.
3. Periksa tindakan in-flight: cari pendaftaran dan pesan berstatus unknown pada sumbernya. Jangan menjalankan ulang efek samping dari backend lama.
4. Kembalikan backend/prompt/bundle ke versi terakhir yang **masih valid**. Model yang sudah shutdown atau fakta yang telah dicabut tidak menjadi fallback yang sah; gunakan FAQ aman/handoff jika tidak ada versi sehat.
5. Jika perlu rollback binary, gunakan release sebelumnya yang kompatibel dengan schema tambahan; jangan drop tabel atau rollback migration destruktif di jalur darurat.
6. Verifikasi inbound, menu, takeover, Notification API, dan backlog. Rekonsiliasi order dan outbound unknown; siapkan daftar kasus tim dengan data minimum.
7. Analisis penyebab, tambahkan skenario regresi relevan, uji ulang fitur tersebut sebelum canary berikutnya.

Target awal latihan: flag dinonaktifkan dan pending baru dicegah dalam <1 menit; pemulihan routing dalam <5 menit, bergantung mekanisme platform. Waktu menyelesaikan in-flight/unknown tidak dapat dijanjikan sama; ukur terpisah.

Jangan menggunakan restart/logout WAHA, reset volume session, flush cache global, queue purge, atau restore DB penuh sebagai respons rutin terhadap bug agent. Restore DB hanya prosedur insiden tersendiri karena dapat menghilangkan transaksi baru.
