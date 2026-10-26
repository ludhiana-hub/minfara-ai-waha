# Rencana MinFara AI Sales & Customer Service

Tanggal kajian: 8 September 2026. Basis kode: commit `6690c1b`. Status: **rencana pengembangan; belum mengubah aplikasi atau produksi**.

## Keputusan utama

Pertahankan Laravel sebagai pengelola WhatsApp, operasional, data pelanggan, dan transaksi. Perkuat RAG dan alur bisnis terlebih dahulu. Python dapat ditambahkan bertahap sebagai layanan AI terpisah setelah jalur lama stabil dan tersedia pengujian pembanding.

Kemampuan agent berasal dari pengetahuan yang benar, ingatan pelanggan, alur tindakan yang jelas, serta integrasi bisnis yang dapat dipercaya. Mengganti bahasa pemrograman saja tidak menyelesaikan keempat hal tersebut.

Batas otomatisasi yang dipilih pemilik: **AI menangani konsultasi, kualifikasi leads, rekomendasi, follow-up, pendaftaran, dan instruksi pembayaran. Tim menangani kasus khusus.** Konfirmasi pembayaran dilakukan tim atau diterima sebagai status terverifikasi dari sistem transaksi yang disepakati; AI tidak menetapkan lunas berdasarkan percakapan atau foto bukti transfer.

Paket dokumen:

| Dokumen | Kegunaan |
|---|---|
| [AUDIT.md](AUDIT.md) | Kemampuan yang sudah ada, bukti kode, risiko, dan hal yang belum terverifikasi |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Pembagian Laravel/Python, model data, kontrak layanan, RAG, dan tindakan agent |
| [EXECUTION.md](EXECUTION.md) | Backlog berurutan dan instruksi siap digunakan di Codex/Claude |
| [ROLLOUT.md](ROLLOUT.md) | Pengujian, indikator keberhasilan, peluncuran bertahap, dan pemulihan |

## Temuan yang menentukan arah

1. **RAG sudah ada.** `KnowledgeRetrievalService`, `EmbeddingService`, dan `RebuildKnowledgeIndexJob` sudah digunakan oleh `ProcessAiReply`. Pekerjaan berikutnya ialah memperbaiki kesehatan dan kualitasnya.
2. **Ada fondasi operasional yang layak dipertahankan:** multi-provider AI, circuit breaker, FAQ, CMS, human takeover, analisis percakapan, saran pengetahuan yang perlu persetujuan admin, dan Notification API.
3. **Model embedding perlu diperiksa segera.** Kode memanggil `text-embedding-004`; Google menyatakan model itu dihentikan pada 14 Januari 2026. Jika produksi menggunakan kode ini, pencarian semantik berpotensi selalu jatuh ke FAQ digest. Ini inferensi kode dan dokumentasi, bukan hasil pemeriksaan layanan langsung. [Changelog Google](https://ai.google.dev/gemini-api/docs/changelog#january-14-2026)
4. **Arahan produk dalam prompt belum selaras dengan sasaran baru.** Migrasi prompt Agustus memosisikan bot sebagai sales FlexiLearn multibahasa dengan dorongan paket Lifetime. Website menawarkan pula kelas Jerman live, offline, private, dan persiapan ujian. Rekomendasi perlu berdasarkan kecocokan kebutuhan, dengan identitas produk yang jelas. [Program dan harga Mitfara](https://mitfara.com/harga)
5. **Leads dan transaksi belum menjadi alur agent.** Analisis minat beli harian dan notifikasi transaksi bukan pengganti CRM operasional atau API pendaftaran. Integrasi website/LMS sebenarnya harus dipetakan sebelum fitur pendaftaran dibuat.
6. **Risiko layanan harus ditutup dahulu:** retry antrean, deduplikasi singkat, pembaruan session WAHA berkala, akses API, dan proses deployment. Rincian beserta tingkat kepastian ada dalam audit.

Laravel pada lockfile adalah `v13.15.0`, dengan kebutuhan PHP `^8.3`; tidak ada alasan dari hasil kajian ini untuk menjadikan penggantian framework sebagai fase pertama.

## Pengalaman pelanggan yang dituju

AI perlu mengingat kebutuhan yang sudah disampaikan: tujuan belajar, level sekarang, target waktu, pilihan online/offline, ketersediaan jadwal, dan kisaran anggaran jika pelanggan bersedia. Tanyakan hanya informasi berikutnya yang diperlukan, satu atau dua pertanyaan per giliran.

Contoh rancangan percakapan, bukan janji produk:

> Pelanggan: “Saya kerja shift, mau mulai bahasa Jerman dari nol.”
>
> AI: “Kalau jadwalmu berubah-ubah, kita perlu pilih cara belajar yang tetap bisa kamu ikuti. Kamu lebih nyaman belajar mandiri, atau ingin ada tutor yang membimbing langsung?”
>
> Setelah preferensi diketahui, AI membandingkan maksimal dua program aktif yang relevan, menjelaskan alasan rekomendasi dan keterbatasannya, lalu menawarkan langkah pendaftaran bila pelanggan sudah siap.

Gaya jawab yang diinginkan:

- Jawab pertanyaan utama sebelum menawarkan paket. Sapaan singkat pada awal percakapan; tidak mengulang perkenalan setiap balasan.
- Bahasa Indonesia natural, menyesuaikan formalitas pelanggan, emoji secukupnya, tanpa klaim sebagai manusia.
- Tidak wajib menutup setiap jawaban dengan CTA penjualan. Keluhan, bukti pembayaran, dan pertanyaan akademik memerlukan tujuan layanan yang berbeda.
- Tidak mengarang harga, ketersediaan batch, rekening, promo, hasil ujian, keberhasilan visa, atau tenggat aktivasi.
- Bila data kurang: ajukan pertanyaan spesifik, sampaikan bagian yang belum bisa dipastikan, atau buat tugas tim dengan ringkasan konteks.
- Pelanggan lama masuk alur CS: akses LMS, jadwal, materi, sertifikat, atau keluhan. Pemeriksaan data akun perlu verifikasi kepemilikan yang sesuai.

## Tahapan pengembangan

Estimasi berikut adalah ukuran awal untuk satu pengembang berpengalaman dibantu coding agent dan PIC operasional yang tersedia. Durasi bergantung pada akses staging, kualitas data, integrasi LMS, dan waktu pengamatan. Setiap fase naik produksi berdasarkan bukti kelulusan, bukan hanya tanggal selesai.

| Fase | Hasil yang dirasakan | Pekerjaan utama | Estimasi kerja |
|---|---|---|---|
| 0 — Baseline dan lingkungan aman | Kondisi awal terukur; pengembangan tidak memakai layanan live | Peta dependency, staging terisolasi, contoh percakapan tersamarkan, kontrak notifikasi, baseline evaluasi dan rollback | 3–5 hari |
| 1 — Keandalan dan pemulihan RAG | Pesan lebih andal; sumber jawaban dapat diperiksa | Deduplikasi tahan restart, timeout, pengiriman, akses API, deployment terarah, model embedding dan indeks terversi | 1–2 minggu |
| 2 — Pengetahuan produk dan jawaban | Rekomendasi Jerman lebih akurat dan kontekstual | Katalog terstruktur, SOP disetujui, ingestion website, chunking, retrieval kontekstual, evaluasi manusia | 2 minggu |
| 3 — Ingatan, leads, dan CS | Tidak mengulang pertanyaan; admin mendapat konteks lengkap | Profil kontak, state percakapan, pipeline leads, kebutuhan belajar, tiket, human takeover terkoordinasi | 2 minggu |
| 4 — Pendaftaran dan instruksi pembayaran | Pelanggan dapat dibantu sampai langkah transaksi | Adapter website/LMS, tools terbatas, konfirmasi data oleh pelanggan, pendaftaran idempoten, instruksi resmi | 2 minggu |
| 5 — Follow-up dan optimasi hasil | Leads ditindaklanjuti sesuai kebutuhan dan izin | Scheduler tindak lanjut, opt-out, penghentian otomatis, atribusi pendaftaran/lunas, evaluasi rutin | 1–2 minggu |
| P — Python bertahap, opsional | Komponen AI dapat berkembang dan diskalakan sendiri | Service terpisah, shadow, uji pembanding, perpindahan retrieval lalu orchestration bila layak | Tambahan 2–3 minggu |

Perkiraan jalur utama sekitar **9–12 minggu kerja**, ditambah waktu observasi atau ketergantungan eksternal yang belum siap. Fase Python bukan prasyarat untuk mencapai Fase 4. Fase 0–2 memberi manfaat paling awal dan dapat menjadi paket implementasi pertama.

### Fase 0: petakan produksi tanpa mengubahnya

Kumpulkan versi image WAHA aktual, engine, session, topologi server, consumer Notification API, volume pesan/jam, jam sibuk, antrean terlama, error provider, dan cara deploy yang benar-benar dipakai. Repo memiliki konfigurasi Coolify dan Dokploy; keberadaan keduanya tidak membuktikan konfigurasi aktif.

Siapkan staging memakai database/volume/keys/session yang berbeda; outbound mengarah ke mock atau nomor uji yang disepakati. Mulai kumpulan evaluasi 100–150 skenario, dengan porsi konsultasi Jerman, sales, CS, dan kegagalan sistem. Contoh produksi harus disamarkan sebelum dipakai atau dikirim ke evaluator AI.

**Selesai jika:** dependency map, baseline, kontrak lama, dan cara pemulihan terdokumentasi; tidak ada koneksi tulis dari staging ke produksi; tidak ada pesan pelanggan nyata selama pengujian.

### Fase 1: tutup celah yang memengaruhi 24/7

Pecah menjadi PR kecil: konfigurasi timeout dan queue; durable inbox/outbox; autentikasi kompatibel; perbaikan rekonsiliasi WAHA; deployment yang dapat dipulihkan; pemulihan embedding. Jangan membungkus semuanya menjadi satu rilis.

Model embedding baru harus menggunakan indeks baru dengan model, dimensi, preprocessing, serta cache namespace tersendiri. Uji ketersediaan model di akun staging; re-embed seluruh sumber yang disetujui, lalu pindah konfigurasi query dan indeks sebagai satu pasangan. Mengubah nama model saja tidak cukup. [Panduan embedding Google](https://ai.google.dev/gemini-api/docs/embeddings)

Pertahankan FAQ/menu yang masih valid sebagai cadangan, tetapi jangan meneruskan klaim produk lama yang sudah diketahui salah. Tampilkan status retrieval dalam monitoring agar “chat masih menjawab” tidak menyembunyikan kegagalan RAG.

**Selesai jika:** replay pesan tidak menimbulkan jawaban atau pendaftaran ganda dalam skenario uji; kegagalan kirim dapat dipulihkan; Notification API tetap kompatibel; session tidak diperbarui ketika konfigurasi sama; fallback dan versi indeks teruji.

### Fase 2: jadikan pengetahuan dapat dipercaya

Tentukan sumber resmi per jenis fakta. Penjelasan dan FAQ menggunakan RAG. Harga, varian, tanggal berlaku, jadwal, kursi tersedia, instruksi pembayaran, dan status transaksi menggunakan data terstruktur atau API operasional.

Website diserap melalui daftar halaman yang diizinkan, disimpan sebagai draft dengan tanggal dan sumber, dibandingkan dengan versi sebelumnya, lalu disetujui PIC. Halaman berisi banyak kategori tidak boleh menjadi satu potongan besar. Hasil percakapan AI tetap berupa saran pengetahuan yang direview; tidak otomatis menjadi fakta baru.

Uji pencarian dengan konteks: “yang malam ada?” perlu dipadukan dengan program yang dibahas sebelumnya. Filter bahasa, level, format kelas, brand, dan masa berlaku sebelum membandingkan kemiripan. Terapkan pencarian kata kunci + semantik bila hasil evaluasi menunjukkan manfaat; reranker ditambahkan hanya jika peningkatannya sebanding dengan biaya dan latensi.

**Selesai jika:** fakta penting di katalog disetujui PIC; konflik website/prompt tercatat dan diselesaikan; tidak ada salah harga/varian pada set kritis; jawaban lebih baik pada penilaian berpasangan oleh tim.

### Fase 3: ubah percakapan menjadi proses operasional

Buat profil pelanggan dan ringkasan persisten yang menyimpan kebutuhan terkonfirmasi dengan asal informasi dan waktu pembaruan. Pisahkan status penjualan, status tiket CS, dan siapa yang sedang menangani percakapan.

Pipeline penjualan: `new → qualifying → recommended → registration_pending → registered → payment_pending → won`. `won` hanya berasal dari konfirmasi sah. `lost` dan `dormant` memiliki alasan; tiket CS dapat berjalan tanpa memaksa perubahan tahap sales.

Admin melihat satu inbox berisi konteks, rekomendasi, keberatan, tindakan terakhir, tugas berikutnya, dan siapa pemilik percakapan. Pengambilalihan admin membatalkan balasan AI serta follow-up yang masih pending sebelum diserahkan ke provider. Pesan yang sudah in-flight belum tentu bisa dibatalkan. Pembaruan profil memakai urutan percakapan/versi agar pesan lama tidak menimpa kebutuhan terbaru.

**Selesai jika:** konteks tetap ada setelah cache habis/restart; tidak terjadi kebocoran antarkontak; AI tidak menimpa perubahan admin; pengguna dapat meminta bantuan tim dengan konteks lengkap.

### Fase 4: agent melakukan tindakan yang dibatasi

Mulai dari tools baca: cari program, baca detail/harga, baca jadwal, dan cek status pendaftaran milik pelanggan terverifikasi. Berikutnya aktifkan `create_registration_draft`, `submit_registration`, dan `get_payment_instructions` setelah kontrak sistem website/LMS diketahui.

Sebelum submit: pelanggan melihat ringkasan program, varian, peserta, harga berlaku, serta syarat relevan dan menyatakan setuju. Backend memvalidasi ulang data terkini. AI tidak diberi akses SQL langsung, secret pembayaran, perubahan rekening, diskon bebas, refund, atau tombol “lunas”.

Jika API LMS belum tersedia, hasil fase ini dibatasi pada draft pendaftaran dan tautan/form resmi atau tugas admin. **Jangan menyebut “pendaftaran berhasil” tanpa respons sukses dari sistem yang benar-benar mencatat peserta.** Adapter transaksi penuh menjadi dependency eksplisit, bukan diasumsikan sudah tersedia oleh Notification API.

**Selesai jika:** alur staging berakhir pada ID pendaftaran valid dan instruksi resmi; retry menghasilkan objek yang sama; kegagalan integrasi tidak memberi konfirmasi palsu; tim dapat menyelesaikan pengecualian.

### Fase 5: tindak lanjut yang relevan

Follow-up berbasis peristiwa dan izin pelanggan: misalnya diminta mengingatkan besok, belum menyelesaikan formulir, atau menunggu kepastian jadwal. Jadwal awal untuk uji dapat berupa 24 jam dan 72 jam, maksimal dua pengingat per rangkaian; PIC perlu mengesahkan jam operasional dan ketentuan ini sebelum aktif.

Batalkan tindak lanjut jika pelanggan membalas, menolak, memilih opt-out, telah mendaftar/lunas, membuka keluhan, atau sedang ditangani admin. Periksa lagi status tepat sebelum pengiriman. Gunakan antrean dan anggaran kapasitas terpisah dari chat serta notifikasi transaksi.

Keberhasilan diukur dari pendaftaran valid, pembayaran terverifikasi, penyelesaian CS, kualitas jawaban, dan biaya per hasil. Purchase-intent score AI dipakai sebagai sinyal bantuan, bukan bukti closing atau alasan menekan pelanggan.

**Selesai jika:** opt-out dan takeover menghentikan semua promosi tertunda; tidak ada follow-up ganda; status pembayaran berasal dari sistem/tim; dampak bisnis dapat dibandingkan dengan baseline.

## Kapan Python layak ditambahkan?

Gunakan service Python terpisah bila kebutuhan ingestion/evaluasi lebih nyaman dikelola di sana, tim lebih produktif, atau profiling menunjukkan komponen AI perlu diskalakan mandiri. [Deployment container FastAPI](https://fastapi.tiangolo.com/deployment/docker/)

Urutan ekstraksi:

1. Pekerjaan offline: normalisasi dokumen dan evaluasi, memakai data tersamarkan.
2. Retrieval dalam mode shadow: hasil hanya dibandingkan, tidak dikirim ke WhatsApp.
3. Retrieval untuk sebagian percakapan setelah lulus uji; Laravel tetap menyusun/mengirim balasan.
4. Orchestration AI opsional: Python mengusulkan jawaban dan tindakan terstruktur; Laravel memvalidasi dan mengeksekusinya.

Nomor, session, dan webhook WAHA tetap sama. Python tidak memiliki kredensial pengiriman WhatsApp dan tidak menulis tabel transaksi Laravel. Tidak perlu memindahkan MySQL ke PostgreSQL demi RAG. Qdrant merupakan opsi indeks terpisah jika volume dan profiling membenarkannya; MVP bisa menggunakan penyimpanan saat ini dengan desain indeks yang diperbaiki.

**Hentikan migrasi pada tahap yang sudah cukup.** Laravel penuh tetap merupakan hasil yang sah apabila target kualitas, keandalan, dan biaya tercapai.

## Pengendalian ruang lingkup

Fase awal melindungi endpoint webhook, kontrak Notification API, CMS/login, sesi WAHA, volume database, dan integrasi LMS yang berjalan. Perubahan internal yang diperlukan dilakukan melalui adapter dan kontrak kompatibel, dengan pengujian layanan terkait.

Migrasi tidak memiliki jaminan “tanpa bug”. Target yang realistis ialah tanpa pemutusan session yang disengaja pada fase AI, mengurangi cakupan dampak setiap perubahan, dan memiliki pemulihan terukur. Konfigurasi WAHA tidak boleh dianggap perubahan ringan: dokumentasi menyebut update session aktif dapat menghentikan lalu menyalakannya kembali. [Dokumentasi session WAHA](https://waha.devlike.pro/docs/how-to/sessions/)

Kebutuhan yang dicatat untuk Fase 0: pengelola produksi aktif, traffic, kapasitas server, anggaran AI bulanan, API website/LMS, pemilik katalog, PIC konfirmasi pembayaran, SLA CS, aturan follow-up, kebijakan retensi, dan hubungan brand DlmF/LBF/FlexiLearn. Rencana dapat disiapkan sekarang; integrasi yang bergantung pada data tersebut baru diaktifkan setelah tersedia.

## Pembagian peran dan anggaran

- Pemilik bisnis/PIC program: mengesahkan katalog, SOP, batas rekomendasi, dan contoh jawaban.
- Pengembang: kontrak, implementasi kecil, pengujian, pencatatan versi, dan release candidate.
- Operator produksi: pemantauan, rollout terarah, pemulihan, dan backup/restore drill.
- Tim CS/sales: evaluasi percakapan, penanganan pengecualian, dan validasi hasil bisnis.

Belum ada data untuk estimasi rupiah yang dapat dipercaya. Hitung biaya per bulan sebagai biaya hosting tambahan + embedding dokumen berubah + embedding pencarian + token jawaban + evaluasi + percobaan ulang. Pisahkan budget chat, batch, dan shadow; tetapkan batas keras serta peringatan sebelum percobaan produksi. Pilih model dari kualitas jawaban, ketersediaan, latensi, dan biaya aktual pada dataset MinFara, bukan klaim kecepatan di label konfigurasi.

## Langkah pertama yang disarankan

Jalankan **Fase 0**, kemudian PR keandalan dan pemulihan RAG pada **Fase 1**. Mulai kurasi katalog Jerman bersamaan oleh PIC bisnis. Gunakan prompt awal dalam [EXECUTION.md](EXECUTION.md); jangan memulai dengan instruksi umum “migrasikan semua ke Python”.
