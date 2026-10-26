<?php

namespace App\Services\Sales;

/**
 * Keyword-based extraction of sales signals from ONE customer message. Deliberately not an LLM
 * call: an extra request per message would spend the same RPM/TPM budget the reply needs, and
 * the signals worth tracking (language, level, goal, package, objection, buying intent) are
 * stated plainly by customers. A miss only means the AI is a bit less informed for one turn.
 */
final class LeadSignalExtractor
{
    private const LANGUAGES = [
        'Jerman'  => '/\b(jerman|german|deutsch)\b/iu',
        'Inggris' => '/\b(inggris|english)\b/iu',
        'Prancis' => '/\b(prancis|french|francais|français)\b/iu',
        'Turki'   => '/\b(turki|turkish)\b/iu',
        'Jepang'  => '/\b(jepang|japanese|nihongo)\b/iu',
        'Korea'   => '/\b(korea|korean)\b/iu',
        'Arab'    => '/\b(arab|arabic)\b/iu',
        'Mandarin' => '/\b(mandarin|chinese|hsk)\b/iu',
    ];

    private const GOALS = [
        'kerja'  => '/\b(kerja|bekerja|karir|karier|ausbildung|lowongan)\b/iu',
        'studi'  => '/\b(kuliah|studi|sekolah|beasiswa|universitas|s1|s2)\b/iu',
        'ujian'  => '/\b(ujian|goethe|telc|ielts|toefl|sertifikat resmi|tes bahasa)\b/iu',
        'hobi'   => '/\b(hobi|hobby|liburan|traveling|travelling|iseng|suka aja)\b/iu',
        'pindah' => '/\b(pindah|migrasi|visa|menetap|tinggal di)\b/iu',
    ];

    private const PACKAGES = [
        'Lifetime' => '/\blifetime\b/iu',
        'Private'  => '/\b(private|privat)\b/iu',
        // "bulan" on its own is just a date word; require a number or the word "paket/bulanan".
        'Bulanan'  => '/\b(\d{1,2}\s*bulan|bulanan)\b/iu',
    ];

    private const OBJECTIONS = [
        'harga' => '/\b(mahal|kemahalan|budget|nggak punya uang|ga ada uang)\b/iu',
        'ragu'  => '/\b(mikir|pikir-pikir|pikir pikir|belum yakin|ragu|nanti dulu|nanti aja|bimbang)\b/iu',
        'ribet' => '/\b(ribet|susah daftar|repot|bingung (daftar|bayar|checkout))\b/iu',
    ];

    private const READY = '/\b(mau (daftar|beli|ambil|checkout|order)|cara (daftar|bayar|beli|checkout|pembayaran)(nya)?|gimana (cara )?(daftar|bayar|beli|checkout)|bagaimana (cara )?(daftar|bayar|beli|checkout)|langsung (daftar|beli|checkout)|deal|siap (beli|daftar)|oke (aku |saya )?ambil|link (daftar|checkout|pembayaran))\b/iu';

    /** @return array<string, mixed> only the keys actually found in the message */
    public static function extract(string $message): array
    {
        $signals = [];

        foreach (self::LANGUAGES as $name => $pattern) {
            if (preg_match($pattern, $message)) {
                $signals['language'] = $name;
                break;
            }
        }

        if (preg_match('/\b([abc][12])\b/iu', $message, $m)) {
            $signals['level'] = strtoupper($m[1]);
        } elseif (preg_match('/\b(dari nol|pemula|belum bisa sama sekali|basic|beginner)\b/iu', $message)) {
            $signals['level'] = 'pemula';
        }

        foreach (self::GOALS as $name => $pattern) {
            if (preg_match($pattern, $message)) {
                $signals['goal'] = $name;
                break;
            }
        }

        foreach (self::PACKAGES as $name => $pattern) {
            if (preg_match($pattern, $message)) {
                $signals['package'] = $name;
                break;
            }
        }

        foreach (self::OBJECTIONS as $name => $pattern) {
            if (preg_match($pattern, $message)) {
                $signals['objection'] = $name;
                break;
            }
        }

        if (preg_match(self::READY, $message)) {
            $signals['ready'] = true;
        }

        return $signals;
    }
}
