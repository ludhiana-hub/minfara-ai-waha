<?php

namespace App\Services\Sales;

use App\Models\LeadProfile;

/**
 * Builds the per-turn "what do I know about this customer + what should this reply do" block that
 * ProcessAiReply appends to the system prompt. Stage instructions only reference facts and URLs
 * that already exist in the base prompt / FAQ 99 (mitfara.com checkout flow) — nothing invented.
 */
final class SalesPlaybook
{
    public static function block(LeadProfile $profile): string
    {
        $stage = $profile->stage ?: LeadProfile::STAGE_NEW;

        $lines = ["=== KONTEKS SALES CUSTOMER INI (internal, jangan disebut ke customer) ==="];

        $known = array_filter([
            'Bahasa'      => $profile->language,
            'Level'       => $profile->level,
            'Tujuan'      => $profile->goal,
            'Paket dibahas' => $profile->package_interest,
            'Keberatan sebelumnya' => $profile->objections ? implode(', ', $profile->objections) : null,
        ]);

        $lines[] = $known
            ? 'Yang sudah diketahui: ' . implode(' | ', array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($known), $known))
            : 'Belum ada info apa pun tentang customer ini.';
        $lines[] = 'Tahap: ' . $stage;
        $lines[] = self::instruction($profile, $stage);
        $lines[] = 'Aturan umum: maksimal SATU pertanyaan per balasan. Jangan tanya ulang hal yang sudah diketahui di atas. Jangan mengarang urgensi, diskon, atau jaminan.';

        return implode("\n", $lines);
    }

    private static function instruction(LeadProfile $profile, string $stage): string
    {
        $missing = array_values(array_filter([
            $profile->level ? null : 'level',
            $profile->goal ? null : 'tujuan',
        ]));

        return match ($stage) {
            LeadProfile::STAGE_NEW =>
                'Sapa singkat dan hangat, lalu tanyakan SATU hal: bahasa apa yang ingin dipelajari dan untuk apa (kerja/kuliah/ujian/hobi). Belum waktunya mendorong checkout.',

            LeadProfile::STAGE_EXPLORING =>
                'Jawab pertanyaannya dulu dengan KNOWLEDGE BASE. Lalu tanyakan SATU info yang masih kosong'
                . ($missing ? ' (' . implode(' atau ', array_map(fn ($m) => "tanyakan {$m}", $missing)) . ')' : '')
                . ' supaya bisa merekomendasikan paket yang pas. Belum perlu menempel link checkout.',

            LeadProfile::STAGE_QUALIFIED =>
                'Informasi sudah cukup. Rekomendasikan SATU paket yang paling cocok dari KNOWLEDGE BASE, dengan alasan singkat yang nyambung ke tujuan customer, lalu tanya apakah mau lanjut checkout di web.',

            LeadProfile::STAGE_OBJECTION =>
                'Customer menyampaikan keberatan' . ($profile->currentObjection ? " soal {$profile->currentObjection}" : '') . '. Tangani dulu: akui perasaannya, jawab dengan fakta dari KNOWLEDGE BASE, lalu tanya apakah itu sudah menjawab. JANGAN langsung menempel link checkout dan jangan memaksa.',

            LeadProfile::STAGE_READY_TO_CHECKOUT =>
                "Customer siap lanjut. Beri langkah checkout singkat dan konkret:\n"
                . "1) Buka https://mitfara.com, login atau daftar\n"
                . "2) Di Katalog Kursus pilih paket yang sesuai, klik Beli Sekarang\n"
                . "3) Pilih metode pembayaran di halaman checkout lalu bayar\n"
                . "Sebut ketik *99* untuk panduan lengkap, dan tawarkan bantu kalau ada kendala di salah satu langkah. "
                . "Kalau paket belum jelas, konfirmasi dulu paket yang dipilih sebelum langkah di atas. "
                . "Sebut transfer manual hanya jika customer bilang tidak bisa bayar lewat web, jangan menawarkannya duluan.",

            LeadProfile::STAGE_CHECKOUT_SENT =>
                'Link checkout sudah diberikan sebelumnya. JANGAN tempel ulang link kecuali customer memintanya. Jawab pertanyaannya, lalu tanyakan dengan ringan apakah ada kendala di langkah checkout dan tawarkan panduan *99*. Kalau ada kendala pembayaran lewat web, baru boleh menyebut transfer manual atau admin.',

            default => '',
        };
    }
}
