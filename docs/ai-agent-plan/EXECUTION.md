# Backlog dan instruksi eksekusi untuk Codex / Claude

Gunakan dokumen ini bersama README, AUDIT, ARCHITECTURE, dan ROLLOUT di folder yang sama. Semua item di bawah **belum dikerjakan** pada kajian ini. Kerjakan satu item yang dapat direview per PR; jangan menjadikan seluruh roadmap satu perubahan besar.

## Aturan kerja per tugas

1. Baca instruksi repository yang berlaku dan periksa perubahan pengguna. Validasi ulang bukti audit terhadap commit terkini.
2. Nyatakan ID backlog, dependency, file yang akan disentuh, acceptance criteria, dan rencana pemulihan. Gunakan branch terpisah, misalnya `codex/agent-t01-baseline`.
3. Lindungi kontrak Notification API, webhook/session WAHA, CMS, dan layanan eksternal. Akses produksi hanya sesuai otorisasi sesi kerja; instruksi menjalankan roadmap bukan izin mengubah seluruh layanan.
4. Gunakan DB/cache/queue/keys/WAHA uji terisolasi. Jangan menjalankan seeding/migrate/test terhadap produksi atau menjalankan scheduler existing sekadar untuk mencoba kode.
5. Tambah fitur dengan flag off dan schema kompatibel. Jangan sekaligus upgrade PHP/Laravel, mengganti engine WAHA, atau mengubah database layanan lain.
6. Jalankan pengujian yang sesuai risiko. Laporkan hasil aktual, yang tidak dapat diuji, dan dependency yang belum ada. Jangan mengklaim integrasi LMS berhasil hanya berdasarkan mock.
7. Siapkan diff, test evidence, migration/release steps dan rollback konkret. Deployment dilakukan hanya bila memang termasuk instruksi/otorisasi saat eksekusi. Tanpa otorisasi deploy, selesaikan release candidate yang dapat direview terlebih dahulu.
8. Perbarui catatan handoff: perubahan, flag/config baru, test, migrasi, risiko tersisa, serta item berikutnya. Secret dan raw data pelanggan tidak dicatat dalam dokumen.

## Backlog berurutan

### T01 — Baseline, kontrak, dan staging (Fase 0)

**Dependency:** tidak ada. **Lingkup:** dokumentasi operasional, test fixtures tersamarkan, konfigurasi staging baru yang terisolasi.

- Identifikasi deployment aktif, image digest/engine WAHA, worker/scheduler, queue timeout efektif, consumer API, kapasitas/traffic dan pemilik katalog/LMS.
- Dokumentasikan kontrak `/api/notification/notify` termasuk target internal; bedakan dari business events/notification customer.
- Siapkan dataset awal dan baseline; catat ketergantungan akses yang belum tersedia secara eksplisit.

**Lulus:** isolasi terbukti, dependency map dan kontrak disetujui, baseline bisa diulang. **Pemulihan:** tidak ada perubahan layanan produksi.

### T02 — Release dan rekonsiliasi WAHA yang terkendali (Fase 1)

**Dependency:** T01. **File terkait:** `docker/entrypoint.sh`, compose yang aktif, `EnsureWahaWebhook.php`, `ReConfigureWahaWebhookJob.php`, `WahaEnsureSessionCommand.php`, `routes/console.php`.

Pecah menjadi PR deployment dan PR reconciler. Buat immutable build, single migration release step, readiness, seed eksplisit; bandingkan/preserve session config sebelum mutasi, satu lock recovery, pin image aktif tanpa upgrade otomatis.

**Lulus:** startup test tidak menjalankan side effect tak terkait; config sama tidak memicu PUT; release gagal terlihat. **Pemulihan:** image/config sebelumnya; tidak reset session volume. Perubahan setting WAHA yang perlu restart menjadi rilis terpisah.

### T03 — Autentikasi dan invalidasi config kompatibel (Fase 1)

**Dependency:** T01; koordinasikan deployment dengan T02. **File terkait:** `routes/api.php`, controller config/log/WAHA, middleware auth, pemanggil CMS terkait.

Petakan caller, tambahkan auth/role dan masking; ganti global cache flush dengan invalidasi terarah. Webhook HMAC diverifikasi pada raw body bila versi/koneksi mendukung. Rancang transisi signature tanpa memutus sender sah; masa transisi harus terbatas dan sumber legacy tetap dibatasi jaringan/kredensial yang dapat dipercaya.

**Lulus:** akses tak berwenang ditolak, caller sah dan UI lolos, state dedup/takeover tidak terhapus. **Pemulihan:** revert perubahan spesifik; jangan membuka kembali endpoint rahasia sebagai fallback umum.

### T04 — Queue, durable inbox, dan ordering (Fase 1)

**Dependency:** T01/T02. **File terkait:** webhook, `ProcessAiReply`, config queue, migrasi inbox/conversation baru, worker config.

Persist pesan sebelum acknowledgement; unique canonical message ID per session; pisahkan event transport; bawa correlation dan event ID sepanjang job; checkpoint replay; pencatatan selama takeover. Selaraskan timeout reservation seluruh job. Tambahkan serialisasi dan debounce terukur per kontak.

**Lulus:** replay/restart/dua worker/outage/pesan terpecah lolos. **Pemulihan:** matikan routing baru, simpan inbox untuk rekonsiliasi; pastikan job payload kompatibel binary sebelumnya.

### T05 — Outbox, delivery state, dan ownership (Fase 1)

**Dependency:** T04. **File terkait:** `WhatsAppService`, `WhatsAppSendThrottle`, `ProcessAiReply`, notification job/adapter, takeover controller dan model.

Tambahkan unique outbound intent + atomic decision winner; klasifikasi sent/retryable/permanent/unknown; delayed dispatch; pemeriksaan ownership sesaat sebelum send. Policy menurut purpose agar notifikasi internal existing tidak ikut dihentikan oleh opt-out promosi pelanggan. Hindari mengganti semua send path sekaligus; migrasikan dengan fixture kontrak.

**Lulus:** tidak resend buta, false result tidak dianggap sukses, takeover selama throttle membatalkan pending, notifications setara. **Pemulihan:** stop dispatcher fitur baru dan rekonsiliasi unknown; jangan mengaktifkan direct send sekaligus untuk intent sama.

### T06 — Pemulihan model embedding dan indeks berversi (Fase 1)

**Dependency:** T01, isolasi batch pada T02/T04 sesuai deployment. **File terkait:** `EmbeddingService`, `KnowledgeRetrievalService`, `RebuildKnowledgeIndexJob`, konfigurasi AI, schema knowledge v2.

Audit model aktif memakai docs/provider akun uji. Tambahkan fingerprint model/dimensi/preprocessing/chunker; query cache namespace; rebuild seluruh corpus disetujui ke indeks baru. Active bundle berpindah atomik dengan konfigurasi query; sediakan fallback FAQ yang valid dan metrik retrieval.

**Lulus:** model tersedia, coverage indeks lengkap, dimensi/fingerprint cocok, model sama-dimensi yang berbeda ditolak, rollback tidak mencampur vektor. **Pemulihan:** bundle sehat sebelumnya atau FAQ aman, bukan model yang sudah shutdown.

### T07 — Katalog program dan knowledge governance (Fase 2)

**Dependency:** T01; T06 untuk indeks baru. **File terkait:** model/migration baru, CMS katalog/review, training/knowledge controller, ingestion job.

PIC mengesahkan brand, varian, harga, format, syarat, periode, jadwal source dan rekening source. Import website sebagai draft bertanggal; tandai konflik; pisahkan coaching/kompetitor/raw chat dari fakta jawaban. Buat SOP verifikasi/refresh dan invalidasi konten. Sediakan service baca katalog/quote lokal dari data yang sudah disetujui sebagai fondasi T08; belum memberikan akses status akun atau transaksi eksternal.

**Lulus:** seluruh offer eligible memiliki source dan owner; data belum sah tidak muncul pada tools/transaksi. **Pemulihan:** unpublished draft/versi terakhir valid; tanpa overwrite massal seed/prompt.

### T08 — RAG kontekstual dan gaya sales/CS (Fase 2)

**Dependency:** T06/T07. **File terkait:** retrieval, reply composition, prompt versioning, evaluation harness dan dataset.

Chunk berdasarkan bagian/program; filter metadata; query memakai konteks; hybrid retrieval/rerank hanya jika terbukti membantu. Pisahkan persona, SOP, fakta dan konteks. Hilangkan rekomendasi Lifetime/CTA universal dari prompt versi baru; harga dari service katalog approved T07. Status akun/transaksi yang belum memiliki adapter diteruskan ke tim, tanpa klaim status. Perbaiki truncation dan fallback tanpa bukti.

**Lulus:** gerbang ROLLOUT untuk retrieval, fakta produk, gaya, dan regresi fitur existing tercapai; reviewer bisnis menilai kecocokan/natural, profil eval terisolasi. Uji transaksi/follow-up/Python belum berlaku pada item ini. **Pemulihan:** flag/prompt/bundle terakhir valid; legacy prompt yang salah fakta tidak dipakai ulang.

### T09 — Memory, pipeline leads, dan inbox tim (Fase 3)

**Dependency:** T04/T05/T07/T08. **File terkait:** model lead/conversation/facts, context builder, CMS inbox.

Simpan kebutuhan terkonfirmasi dengan evidence; stage deterministik; ringkasan persisten; timeline next step; koreksi pengguna; identitas per session. Tampilkan alasan rekomendasi dan status berdasarkan sumber.

**Lulus:** tidak lupa setelah TTL/restart, tidak bocor antarcontact, koreksi terbaru menang, stage tanpa evidence ditolak. **Pemulihan:** tampilan/state baru dinonaktifkan tanpa menghapus catatan inbound.

### T10 — CS cases dan handoff eksplisit (Fase 3)

**Dependency:** T05/T09. **File terkait:** takeover controller/model, support cases, inbox tim, reply dispatcher.

Assignment, reason, SLA, status, ringkasan admin, resume eksplisit. Pesan selama takeover tetap dicatat; hentikan replay otomatis pesan pre-pause untuk kasus khusus. Verifikasi kepemilikan sebelum tool membaca akun/order.

**Lulus:** cache/DB/ownership konsisten, admin tidak perlu mengulang konteks, pending usang batal, data akun lain tidak terbuka. **Pemulihan:** nonaktifkan auto-resume agent dan pertahankan penanganan tim.

### T11 — Adapter website/LMS dan tools baca (Fase 4)

**Dependency:** T07/T09/T10; kontrak dari pemilik website/LMS tersedia. **File terkait:** business adapter baru, DTO/validation, service-auth baru, fixtures consumer.

Perluas interface service lokal T07 dengan adapter remote katalog/quote/schedule/status resmi. Gunakan fake adapter untuk replay test; lakukan contract test ke endpoint staging yang diizinkan untuk membuktikan integrasi nyata. Tandai capability tidak tersedia bila API nyata belum ada. Jangan mengartikan Notification API sebagai checkout API.

**Lulus:** contract test dan integrasi staging riil; scope akun aman; data stale ditolak. **Pemulihan:** baca katalog approved/handoff; tanpa klaim jadwal/akun yang tidak terverifikasi.

### T12 — Pendaftaran dan instruksi pembayaran (Fase 4)

**Dependency:** T05/T10/T11. **File terkait:** registration intents, tool executor/policy, audit, confirmation flow, outbox.

Draft → ringkasan → persetujuan customer → validasi ulang → submit idempoten → simpan external ID → instruksi resmi. Rekonsiliasi timeout. Batasi tools dan putaran; AI tidak mark_paid/refund/diskon bebas. Gunakan tautan resmi jika pendaftaran harus dilakukan mandiri.

**Lulus:** replay tak membuat dua order; harga berubah meminta konfirmasi baru; tidak ada pendaftaran sukses palsu; bukti transfer masuk kasus tim. **Pemulihan:** actions off, rekonsiliasi intent in-flight, lanjutkan manual tanpa membatalkan transaksi sah.

### T13 — Business events dan hasil penjualan terverifikasi (Fase 4–5)

**Dependency:** T11/T12; producer event terpercaya. **File terkait:** endpoint baru, business event inbox, order mapping, lead transitions, analytics.

Producer/event ID unique, auth/replay protection, event ordering/version, account matching, dan reconciliation. Catat registered/payment_pending/paid berdasarkan sistem, terpisah dari skor analitik AI. Pertahankan behavior notifikasi target internal existing.

**Lulus:** duplicate/late/spoofed events tertangani; paid hanya sah; tidak salah pelanggan. **Pemulihan:** ingest simpan untuk replay, state finansial tidak diturunkan tanpa evidence; tidak purge event.

### T14 — Follow-up customer yang terkontrol (Fase 5)

**Dependency:** T05/T10/T13; PIC menetapkan izin, jam dan frekuensi. **File terkait:** follow-up tasks, scheduler khusus, dispatcher policy, CMS consent/opt-out.

Jadwalkan berdasarkan intent pelanggan dan event; cancel saat reply/paid/complaint/takeover/opt-out. Recheck pada waktu kirim. Resource dan queue terpisah; jangan memakai internal `payment.reminder` sebagai bukti scheduler customer sudah ada.

**Lulus:** stop conditions dan retry aman; SLA chat/notifikasi tidak memburuk; hasil bisnis dan keluhan dipantau. **Pemulihan:** followups off dan batalkan pending berdasarkan purpose, tanpa menekan notifikasi transaksi internal.

### T15 — Pilot Python offline dan shadow (Fase P opsional)

**Dependency:** T02/T06/T08; kontrak dan baseline jelas. **Lingkup:** direktori/service AI terpisah, deployment manifest terpisah, adapter Laravel; tidak mengganti entrypoint existing.

Mulai ingestion/evaluation, lalu retrieval shadow. Hard-disable write/send pada backend policy dan credentials; batasi resource/queue/cache/circuit quota. Ukur kualitas, latency, biaya dan kemudahan maintenance terhadap baseline Laravel.

**Lulus:** shadow tidak mempunyai efek bisnis dan tidak mengurangi kapasitas chat; manfaat terukur. **Pemulihan:** hentikan sidecar/shadow; seluruh layanan existing berjalan tanpa dependency wajib pada Python.

### T16 — Python canary dan keputusan kelanjutan (Fase P opsional)

**Dependency:** T05/T15; bila orchestration tools ikut dipindah, wajib T12/T13. **Lingkup:** routing adapter, flags, strict schema, active-run fencing.

Pindahkan retrieval dahulu, kemudian reasoning jika layak. Sticky cohort; fallback sebelum side effect saja; Python tetap tanpa akses WAHA/tulis DB transaksi. Uji timeout, late result, invalid output, overload, dan rollback.

**Lulus:** gerbang kualitas/keandalan/biaya lulus; satu pemenang reply; failure injection berhasil. **Pemulihan:** backend flag kembali Laravel dengan state/intent terjaga. Jika manfaat tidak cukup, dokumentasikan keputusan tetap Laravel.

## Urutan dan pekerjaan paralel

Jalur utama: T01 → T02/T03 → T04 → T05, dengan T06 setelah baseline/isolation tersedia. Kurasi bisnis T07 dapat dimulai paralel oleh PIC; aktivasi retrieval T08 menunggu T06/T07. Lanjutkan T09 → T10 → T11 → T12 → T13 → T14.

T15 bisa dieksperimenkan setelah T08 tanpa menahan target bisnis Laravel; T16 menunggu semua guard terkait. Jangan dua agent mengedit webhook/outbound yang sama bersamaan. Pisahkan PR review katalog dari PR queue/deployment agar kegagalan mudah dilacak.

## Prompt awal siap digunakan

Salin instruksi berikut ke coding agent pada repository ini:

```text
Pelajari docs/ai-agent-plan/README.md, AUDIT.md, ARCHITECTURE.md,
EXECUTION.md, dan ROLLOUT.md. Kerjakan hanya T01 (Fase 0).

Tujuan proyek: MinFara menjadi AI Sales dan CS untuk program bahasa Jerman;
AI sampai pendaftaran dan instruksi pembayaran, tim menangani kasus khusus.
Laravel dan WAHA existing tetap berjalan. Ini tugas persiapan implementasi,
bukan izin deployment atau migrasi produksi.

Validasi ulang source dan instruksi repository. Susun dependency map,
inventaris konfigurasi non-secret, kontrak Notification API dan webhook,
daftar data bisnis yang dibutuhkan, rencana staging terisolasi, fixture
sintetis/tersamarkan, serta baseline evaluasi yang dapat dijalankan aman.
Jangan baca/cetak .env secrets atau data pribadi pelanggan.
Jangan menjalankan scheduler, migrate, seed, restart, atau mengirim WhatsApp
terhadap lingkungan produksi. Jangan ubah source runtime pada T01.

Bedakan informasi terverifikasi dari asumsi dan akses yang belum tersedia.
Lanjutkan pekerjaan persiapan yang tidak terblokir sambil mencatat dependency.
Hasilkan artifact reviewable, perubahan yang dilakukan, pemeriksaan yang
benar-benar dijalankan, keterbatasan, dan urutan PR Fase 1 berdasarkan temuan.
```

## Prompt implementasi satu item

```text
Baca paket docs/ai-agent-plan dan instruksi repository terbaru.
Kerjakan hanya [ID Txx] pada EXECUTION.md; dependency yang diwajibkan harus
terbukti selesai lebih dahulu. Periksa git diff dan pertahankan pekerjaan lain.

Sebelum edit, sebutkan perilaku yang diperbaiki, file yang relevan, kontrak
existing yang terdampak, dan acceptance criteria dari backlog/ROLLOUT.
Implementasikan perubahan terkecil yang memenuhi tujuan, di belakang feature
flag off bila applicable. Semua migrasi additive dan kompatibel rolling release.
Jangan mengganti WAHA session/engine/nomor, mengubah kontrak notifikasi internal,
menyentuh DB layanan lain, atau memperluas task ke rewrite penuh.

Siapkan dan jalankan replay/unit test pada environment disposable dengan HTTP
outbound di-mock. Contract/integration test dapat memakai endpoint staging yang
diizinkan dan data uji terisolasi. Blok koneksi produksi. Tambahkan test
perilaku/failure mode yang diperlukan; catat hasil aktual dan test yang belum
dapat dijalankan. Untuk integrasi eksternal yang belum tersedia, gunakan adapter
fake yang jelas dan laporkan bahwa integrasi riil belum selesai.

Selesaikan diff, migration/release checklist, flags, test evidence, rollback,
dan catatan handoff. Jangan deploy kecuali tugas ini secara eksplisit sudah
diotorisasi untuk deploy. Jika sudah diotorisasi, ikuti rollout terarah dan
gerbang kualitas; jangan meminta ulang izin yang sudah diberikan.
```

## Format catatan penyelesaian

```text
Task: Txx
Commit/release candidate:
Perilaku sebelum → sesudah:
File/kontrak yang berubah:
Feature flags dan default:
Schema/payload backward compatibility:
Test yang dijalankan dan hasil:
Integrasi nyata yang sudah/belum diverifikasi:
Indikator dan ambang rollout:
Rollback dan cara menangani in-flight/unknown:
Dependency/risiko tersisa:
Task berikutnya:
```

Review sebelum merge perlu membandingkan diff terhadap acceptance criteria item tersebut. Coding agent berikutnya harus bisa melanjutkan dari evidence yang tertulis, tanpa menganggap semua fase telah selesai.
