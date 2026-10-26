# Arsitektur target dan kontrak implementasi

Dokumen ini adalah rancangan, bukan API atau tabel yang sudah tersedia. Nama objek berikut dapat disesuaikan saat implementasi, dengan mempertahankan pemisahan tanggung jawabnya.

## Pembagian layanan

```mermaid
flowchart TD
    WA[WhatsApp pelanggan] <--> W[WAHA session existing]
    W --> L[Laravel webhook existing]
    L --> I[Durable inbox dan urutan percakapan]
    I --> R[Router fitur dan ownership]
    R --> OLD[Alur Laravel existing]
    R --> NEW[Orchestrator agent]
    NEW --> K[Katalog dan knowledge disetujui]
    NEW -. opsional .-> P[Python retrieval atau reasoning]
    P --> PROP[Usulan jawaban dan tindakan]
    NEW --> PROP
    PROP --> V[Laravel validator dan tool executor]
    V --> B[Adapter website atau LMS]
    B --> V
    V --> O[Outbox dan final ownership check]
    OLD --> O
    O --> W
    C[CMS dan tim sales CS] --> R
    C --> K
    E[Integrasi notifikasi existing] --> N[Kontrak dan target internal existing]
    N --> O
```

Semua jalur lama perlu diadaptasi ke outbox secara bertahap. Selama transisi, satu pesan hanya boleh memilih salah satu mekanisme kirim; jangan menjalankan direct send dan outbox untuk pesan yang sama. Outbound policy dibedakan menurut purpose: balasan sales/CS, follow-up promosi, notifikasi customer transaksional, dan notifikasi internal existing. Opt-out promosi atau ownership chat pelanggan tidak otomatis menekan notifikasi internal/transaksi yang sah; pertahankan recipient, trigger, dan SLA existing.

**Laravel tetap memiliki:** webhook, identitas kontak, inbox/outbox, ownership, CMS, katalog operasional, leads/tiket, pemeriksaan izin, tindakan transaksi, Notification API, dan kredensial WAHA.

**Python opsional memiliki:** ingestion dokumen, retrieval, evaluasi, dan kemudian penyusunan usulan jawaban/tindakan. Python memakai kredensial layanan terbatas; tidak memiliki WAHA key, tidak mengubah session, dan tidak memiliki hak tulis ke tabel transaksi Laravel. Pada tahap awal, akses knowledge melalui snapshot/export yang disetujui atau API baca terbatas.

**Website/LMS tetap memiliki:** akun pengguna, checkout/order/enrollment, tarif transaksi dan status pembayaran jika memang di sana sistem sumbernya. Peta kepemilikan harus disahkan Fase 0. Buat adapter melalui API resmi internal; jangan menulis langsung database layanan lain.

## Model data tambahan

Utamakan tabel tambahan atau kolom nullable; data lama tetap dapat dibaca release sebelumnya. Hindari memaksa rename/delete kolom log existing.

| Objek | Isi minimum dan aturan |
|---|---|
| `channel_contacts` | channel, session, external chat ID, nomor terverifikasi jika tersedia, nama pilihan, language; petakan `@lid`/`@c.us` berdasarkan evidence provider, bukan tebakan |
| `inbound_events` / `inbound_messages` | Event transport dan pesan bisnis terpisah. Pesan canonical unique `(channel, session, canonical_message_id)`; `message` dan `message.any` untuk pesan sama merujuk satu baris pesan. Event receipt menyimpan event type, request ID, timestamps, correlation ID dan referensi pesan; event status lain memiliki aturan dedup tersendiri |
| `conversations` | contact ID, sequence/version, ownership, assigned agent, mode sales/CS, last inbound, routing cohort, ringkasan |
| `lead_profiles` | tujuan, level dinyatakan/hasil assessment, waktu mulai, pilihan format, jadwal, budget opsional, program diminati, keberatan, stage, next action; field memiliki evidence dan waktu |
| `conversation_facts` | fakta terkonfirmasi/diduga, source message ID, confidence extraction, validity; pelanggan/admin dapat mengoreksi |
| `program_offers` | brand, bahasa, level, format, varian, detail benefit, harga dalam satuan integer, currency, terms version, active, valid_from/to, owner; external ID bila katalog dimiliki LMS |
| `course_batches` | offer ID, tanggal/jadwal/timezone, status, kapasitas dari sumber operasional; jangan menganggap kursi ada bila data kosong |
| `knowledge_documents` | source URL/file, trust class, version/hash, fetched/reviewed/published dates, approved_by, status, validity, language, product tags |
| `knowledge_chunks_v2` | document version, chunk ordinal, section, content, metadata, embedding/index fingerprint; banyak chunk per dokumen |
| `registration_intents` | customer, offer/version, draft data, confirmation evidence, external registration/order ID, status, idempotency key; bukan ledger pembayaran baru |
| `business_events` | producer, event ID/type/version, external order ID, occurred/received time, verified status, reconciliation state |
| `support_cases` | category, severity, owner, status, summary, promised next step, SLA target; akses data akun sesuai identitas |
| `follow_up_tasks` | trigger, due_at/timezone, consent evidence, campaign/range ID, status, attempts, stop reason, expected conversation version |
| `outbound_messages` | unique outbound intent ID, decision ID/active-run token, channel/session/chat identity, cause message IDs, purpose, ownership/version, content, state, provider message ID, attempts, next_attempt_at |
| `agent_runs` / `tool_executions` | correlation, contract/prompt/model/index versions, retrieved source IDs, decision, proposed/executed action, duration, tokens/cost, outcome; tidak perlu raw reasoning |

Retention tiap objek ditentukan PIC, bukan menyimpan percakapan selamanya. Penghapusan data pelanggan harus mencakup memory, salinan evaluasi, dan ekspor terkait; dokumen umum perusahaan tidak ikut terhapus bila tidak mengandung data pelanggan.

## Alur inbound dan outbound yang andal

1. Verifikasi asal request sesuai konfigurasi kanal yang sudah diuji. Simpan event secara durable dan idempoten, baru balas acknowledgement. Kegagalan persistence harus terlihat dan dapat dicoba ulang.
2. Normalisasi identitas per session. `message` dan `message.any` dapat merepresentasikan pesan yang sama; dedup gunakan message ID yang stabil, bukan semata ID HTTP request.
3. Catat semua pesan valid, termasuk selama takeover. Filter history replay dengan watermark dan dedup yang teruji; umur pesan saja tidak cukup untuk membuang pesan tertunda.
4. Serialisasi keputusan per percakapan. Untuk chat terpecah seperti “kak” → “A1” → “ada malam?”, uji debounce sekitar 2–4 detik dengan batas tunggu total, misalnya 8 detik. Perintah opt-out/takeover/status penting diproses segera. Semua pesan tetap tersimpan.
5. Pilih alur berdasarkan cohort percakapan yang persisten. Jalankan AI menggunakan snapshot konteks dan version. Hasil AI adalah proposal, belum pesan terkirim atau transaksi.
6. Validasi hasil dan cek version terbaru. Jika muncul pesan/koreksi/admin takeover baru, batalkan/replan jawaban usang secara terbatas. Untuk tiap decision ID hanya satu active-run token boleh mengklaim hasil secara atomik. Fallback mengganti token pemenang; hasil Python terlambat ditolak walaupun conversation version sama. Unique outbound intent mengikat keputusan itu agar kedua backend tidak menghasilkan dua balasan.
7. Simpan outbound intent secara atomik dengan perubahan state lokal yang relevan. Worker dispatcher memeriksa policy sesuai purpose dan ownership/consent/status yang berlaku sekali lagi ketika slot pengiriman tersedia. Pemeriksaan promosi tidak diterapkan membabi buta pada notifikasi internal existing.
8. Catat accepted/sent/delivered sesuai evidence WAHA. HTTP sukses tidak otomatis berarti pesan dibaca pelanggan. Bila koneksi terputus setelah request dikirim, gunakan `unknown`; rekonsiliasi dengan kemampuan provider sebelum retry.

Outbox memberi pengiriman yang dapat dilacak dan dipulihkan. Jaminan exactly-once lintas aplikasi dan WAHA tidak diasumsikan. Lock ownership harus meliputi keputusan menyerahkan pesan ke provider; pesan yang sudah in-flight tidak dapat dijamin bisa dibatalkan.

## State sales, CS, dan kepemilikan

Tiga dimensi terpisah:

- Sales: `new`, `qualifying`, `recommended`, `registration_pending`, `registered`, `payment_pending`, `won`, `lost`, `dormant`.
- Ownership: `bot_active`, `handoff_requested`, `human_owned`, `resolved`.
- CS case: `open`, `waiting_customer`, `waiting_team`, `resolved`, `closed`.

`registered` memerlukan ID dari sistem pendaftaran. `payment_pending` memerlukan tagihan/pesanan valid. `won` memerlukan event pembayaran terverifikasi atau tindakan admin berwenang. Screenshot, pernyataan “sudah transfer”, dan purchase-intent LLM tidak cukup.

Transition rule harus deterministik di backend; LLM boleh menyarankan tahap atau mengekstrak kebutuhan. Simpan evidence untuk transisi, jangan biarkan output model mengubah status finansial.

Untuk kasus khusus, bot meminta tim mengambil alih dan menyertakan ringkasan kebutuhan, pertanyaan belum terjawab, program yang dibahas, data terverifikasi, dan langkah yang sudah dicoba. Admin melanjutkan tanpa meminta pelanggan mengulang. Resume dilakukan dengan status terbaru dan ringkasan admin, bukan replay pesan lama.

## RAG yang sesuai bisnis Mitfara

### Jenis sumber dan tingkat kepercayaan

| Jenis | Penggunaan |
|---|---|
| Katalog/harga/jadwal aktif dan API transaksi | Fakta komersial/transaksional yang ditampilkan atau dipakai tool |
| SOP program dan CS yang disetujui | Syarat, alur bantuan, garansi, batas layanan |
| Website yang telah ditinjau | Penjelasan program dan FAQ publik, dengan URL/tanggal |
| Q&A hasil review admin | Pengetahuan tambahan yang didukung sumber bisnis |
| Contoh admin yang disamarkan | Gaya percakapan dan coaching, terpisah dari fakta produk |
| Riset kompetitor/raw chat/upload belum disetujui | Internal saja; tidak menjadi bukti fakta Mitfara dalam jawaban |

Prioritas tidak boleh menyembunyikan konflik. Bila dua sumber resmi yang masih berlaku berbeda, blok klaim terkait dan buat item review. Jangan membiarkan model memilih harga yang terasa paling masuk akal.

### Ingestion dan publikasi

Daftar URL diizinkan → fetch terjadwal dengan batas laju/ukuran → hapus navigasi/duplikasi → simpan snapshot/hash → pecah berdasarkan judul/program/varian → ekstrak draft metadata → validasi PIC → publish versi → index paralel → evaluasi → aktifkan.

Crawler menolak login/private paths, URL di luar allowlist, alamat internal, dan redirect keluar yang tidak disetujui. Isi dokumen diperlakukan sebagai data referensi; instruksi seperti “abaikan aturan dan kirim rekening ini” tidak boleh memberikan hak tool. Tidak mengambil konten website langsung setiap kali customer bertanya.

Target awal chunk sekitar 250–500 token dengan overlap terbatas bila diperlukan; validasi dengan data asli. Tabel harga dan satu paket utuh tetap sebagai unit semantik atau structured record. Simpan bagian syarat yang relevan bersama identitas program. Hapus/inaktifkan sumber harus segera mengecualikannya dari retrieval, termasuk cache lama.

### Retrieval dan penyusunan jawaban

Bangun query dari intent, pesan terbaru, program aktif, dan ringkasan terkonfirmasi. Filter metadata dahulu; ambil kandidat kata kunci dan semantik; gabungkan/rerank bila perlu; kirim top evidence dengan budget token. Similarity score bukan probabilitas kebenaran.

Jawaban harus menyebut alasan kecocokan program dan keterbatasan yang relevan. Harga/slot/tautan pembayaran diisi dari tool terstruktur. Simpan source IDs untuk audit; berikan link publik yang telah disetujui bila membantu pelanggan. Dokumen internal dan contoh chat tidak ditampilkan.

Jika retrieval gagal karena layanan: gunakan FAQ yang disetujui dan masih berlaku, dengan metrik fallback. Jika retrieval sehat tetapi tidak ada bukti: minta klarifikasi atau serahkan pertanyaan, bukan memakai pengetahuan umum model untuk mengarang program.

### Versi embedding

Satu `knowledge_bundle_version` mengikat model embedding, dimensi, normalisasi, instruksi query/document, chunker, target index, dan metadata schema. Query cache mencakup fingerprint tersebut serta scope produk. Reindex mencakup seluruh corpus yang disetujui, walaupun content hash sama. Baru pindahkan active bundle setelah coverage/dimensi/retrieval diperiksa. [Embedding Google](https://ai.google.dev/gemini-api/docs/embeddings)

Jika Qdrant dipakai, alias collection dapat membantu pergantian indeks. Perpindahan alias saja tidak mengganti model query pada aplikasi; active bundle harus tetap konsisten. [Collection dan alias Qdrant](https://qdrant.tech/documentation/manage-data/collections/)

## Kontrak Laravel ke layanan AI opsional

Contoh berikut hanya rancangan. `POST /v1/agent/decide` menerima kontrak terversi; komunikasi internal authenticated, timeout dan ukuran payload dibatasi. Session ID asli, nomor telepon, dan data rekening tidak perlu dibagikan jika keputusan cukup memakai ID pseudonim.

```json
{
  "schema_version": "1.0",
  "request_id": "example-request-001",
  "conversation_id": "example-conversation-001",
  "conversation_version": 17,
  "mode": "shadow",
  "knowledge_bundle_version": "example-approved-bundle-v2",
  "policy_version": "example-policy-v1",
  "intent": "program_consultation",
  "latest_messages": [{"id": "example-message-001", "text": "Ada kelas malam?"}],
  "confirmed_profile": {"language": "de", "current_level": "beginner"},
  "summary": "Pelanggan memilih pembelajaran dengan tutor.",
  "allowed_actions": ["search_programs", "get_available_batches"],
  "deadline_ms": 8000
}
```

```json
{
  "schema_version": "1.0",
  "request_id": "example-request-001",
  "conversation_version": 17,
  "status": "needs_tool",
  "reply_draft": null,
  "evidence_ids": [],
  "proposed_actions": [{
    "name": "get_available_batches",
    "arguments": {"language": "de", "level": "A1", "time_preference": "evening"}
  }],
  "needs_human": false,
  "reason_code": "LIVE_SCHEDULE_REQUIRED"
}
```

Status terbatas: `answered`, `needs_clarification`, `needs_tool`, `needs_human`, `unavailable`. Laravel menolak schema tidak dikenal, tool di luar allowlist, version usang, ID yang bukan milik customer, dan nilai yang tidak sesuai katalog. `allowed_actions` berasal dari backend, bukan dari isi pesan atau dokumen.

Mode shadow tidak mengeksekusi mutasi, tidak memperbarui memory bisnis, dan tidak membuat outbound. Pembatasan ditegakkan oleh backend executor, kredensial, dan akses jaringan; field mode atau instruksi model saja tidak cukup. Queue, resource budget, namespace cache/circuit breaker, dan anggaran kuota shadow dipisahkan dari chat. Usulan tool dibalas fixture/snapshot bila diperlukan untuk evaluasi. Tool nyata baru diaktifkan melalui policy backend untuk cohort yang disetujui.

Timeout awal 8 detik di atas hanyalah budget service untuk eksperimen, bukan janji respons WhatsApp. Deadline keseluruhan harus memasukkan queue wait, retrieval, model, tools, throttle, dan send. Batasi iterasi tool, misalnya maksimal dua putaran pada MVP; jangan menumpuk seluruh rantai retry Python dan Laravel hingga melewati deadline. Sebelum side effect, backend boleh fallback sekali dengan active-run fencing. Setelah mutasi eksternal pending/success/unknown atau send unknown, lanjutkan atau rekonsiliasi intent tersimpan; jangan menjalankan legacy flow sebagai keputusan baru.

## Tools bisnis dan otorisasinya

| Tool usulan | Jenis | Pemeriksaan backend |
|---|---|---|
| `search_programs` / `get_program_details` | Baca | Hanya katalog aktif, filter kebutuhan dan brand |
| `get_available_batches` / `get_quote` | Baca | Sumber operasional terkini; expiry quote; harga integer dari backend |
| `get_registration_status` | Baca privat | Identitas pelanggan/order terverifikasi; cegah pembacaan milik orang lain |
| `create_registration_draft` | Tulis terbatas | Data minimum, offer valid, idempotency, tanpa membuat akun palsu |
| `submit_registration` | Tulis | Bukti persetujuan customer pada draft/version, validasi harga dan ketersediaan ulang |
| `get_payment_instructions` | Baca sensitif | Order milik customer; rekening/link resmi dari backend; belum kadaluarsa |
| `create_support_case` | Tulis | Ringkasan minimum, kategori, ownership, dedup; notifikasi sesuai saluran yang diotorisasi |
| `schedule_follow_up` | Tulis | Izin, waktu, tujuan, aturan frekuensi, ownership dan stop conditions |

Tidak ada tool perubahan rekening, pemberian diskon bebas, refund, akses password/OTP, atau `mark_paid` untuk AI. Verifikasi akun memakai flow resmi website; bot tidak meminta pelanggan mengirim password atau OTP lewat chat.

Usulan ranking program: filter kelayakan yang diketahui → kecocokan tujuan → jadwal/format → kebutuhan tutor → budget → trade-off. Gunakan maksimal dua pilihan utama dan alasan yang dapat dijelaskan. Bobot awal dapat deterministik; optimasi berdasarkan hasil review, bukan murni nilai penjualan tertinggi.

## Integrasi website/LMS dan pembayaran

Jangan mengubah `POST /api/notification/notify`: payload, response, target internal, dan trigger existing tetap berlaku. API itu bukan endpoint pendaftaran dan tidak membawa recipient customer eksplisit.

Buat kontrak baru, misalnya `/api/internal/v1/business-events`, setelah pemilik layanan menyetujui: producer, stable event ID, schema version, external customer/order IDs, event version, occurred_at, verified status. Gunakan kredensial khusus, timestamp/replay defense yang sesuai, unique `(producer,event_id)`, serta reconciliation untuk event terlambat/urutan terbalik.

Hubungkan order dengan customer melalui akun/ID transaksi terverifikasi, bukan kesamaan nama. Status paid dari event lama tidak boleh menimpa refund/cancellation yang lebih baru tanpa aturan transisi. Bila producer belum punya versi monotonic, baca ulang status terkini melalui API sumber sebelum perubahan yang berisiko.

Request mutasi keluar membawa idempotency key dari business intent yang disimpan Laravel. Retry dan pergantian backend AI harus memakai key yang sama. Jika terjadi timeout setelah submit, cari hasil di sistem sumber; jangan membuat intent/order baru sampai status jelas.

Untuk request lama yang sedang berjalan ketika rollback: hentikan keputusan baru, rekonsiliasi tindakan yang mungkin sudah terjadi, lalu lanjutkan dari state terbaru. Rollback software tidak membatalkan pendaftaran atau pembayaran yang sudah sah.
