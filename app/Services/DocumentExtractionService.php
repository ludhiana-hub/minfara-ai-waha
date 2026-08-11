<?php

namespace App\Services;

use App\Models\BotConfig;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentExtractionService
{
    /**
     * @throws \RuntimeException kalau ekstensi tidak didukung atau parsing gagal
     */
    public function extract(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        return match ($ext) {
            'pdf'                    => $this->extractPdf($file->getRealPath()),
            'docx'                   => $this->extractDocx($file->getRealPath()),
            'txt', 'csv'             => $this->extractText($file->getRealPath()),
            'jpg', 'jpeg', 'png', 'webp' => $this->extractImage($file->getRealPath(), $file->getMimeType()),
            default => throw new \RuntimeException("Format file .{$ext} belum didukung."),
        };
    }

    private function extractPdf(string $path): string
    {
        try {
            $parser = new PdfParser();
            $text   = $parser->parseFile($path)->getText();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Gagal membaca PDF: ' . $e->getMessage());
        }

        $text = trim($text);
        if ($text === '') {
            throw new \RuntimeException('PDF tidak mengandung teks yang bisa dibaca (kemungkinan hasil scan/gambar).');
        }

        return $text;
    }

    private function extractDocx(string $path): string
    {
        try {
            $document = IOFactory::load($path);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Gagal membaca DOCX: ' . $e->getMessage());
        }

        $lines = [];
        foreach ($document->getSections() as $section) {
            $this->collectDocxText($section, $lines);
        }

        $text = trim(implode("\n", array_filter($lines, fn($l) => trim($l) !== '')));
        if ($text === '') {
            throw new \RuntimeException('DOCX tidak mengandung teks yang bisa dibaca.');
        }

        return $text;
    }

    private function collectDocxText(AbstractContainer $container, array &$lines): void
    {
        foreach ($container->getElements() as $element) {
            if ($element instanceof Text) {
                $lines[] = $element->getText();
            } elseif ($element instanceof TextRun) {
                $runText = '';
                foreach ($element->getElements() as $child) {
                    if ($child instanceof Text) {
                        $runText .= $child->getText();
                    }
                }
                $lines[] = $runText;
            } elseif ($element instanceof AbstractContainer) {
                $this->collectDocxText($element, $lines);
            }
        }
    }

    private function extractText(string $path): string
    {
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            throw new \RuntimeException('File teks kosong atau gagal dibaca.');
        }

        // Pastikan valid UTF-8 supaya aman disimpan & dikirim ke LLM.
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        }

        return trim($content);
    }

    /**
     * OCR lewat Gemini Vision — reuse API key yang sudah dikonfigurasi untuk chat AI,
     * tanpa perlu instal binary OCR (tesseract) di server.
     */
    private function extractImage(string $path, ?string $mimeType): string
    {
        $apiKey = BotConfig::get('gemini_api_key') ?: (config('services.gemini.key') ?? '');
        if (!$apiKey) {
            throw new \RuntimeException('OCR gambar butuh Gemini API Key — isi dulu di Konfigurasi > API.');
        }

        $model  = BotConfig::get('gemini_model') ?: (config('services.gemini.model') ?? 'gemini-2.0-flash');
        $mime   = $mimeType ?: 'image/jpeg';
        $base64 = base64_encode(file_get_contents($path));

        $payload = [
            'contents' => [[
                'role'  => 'user',
                'parts' => [
                    ['text' => 'Ekstrak SEMUA teks yang terlihat pada gambar ini apa adanya, tanpa komentar tambahan. Kalau ini screenshot chat WhatsApp, pertahankan urutan & pengirim pesan.'],
                    ['inline_data' => ['mime_type' => $mime, 'data' => $base64]],
                ],
            ]],
            'generationConfig' => ['maxOutputTokens' => 2000, 'temperature' => 0.1],
        ];

        try {
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                $payload
            );
        } catch (\Throwable $e) {
            throw new \RuntimeException('OCR gagal — koneksi ke Gemini bermasalah: ' . $e->getMessage());
        }

        if ($response->failed()) {
            Log::error('DocumentExtractionService: OCR gagal', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('OCR gagal — Gemini API mengembalikan error (status ' . $response->status() . ').');
        }

        $text = trim((string) $response->json('candidates.0.content.parts.0.text'));
        if ($text === '') {
            throw new \RuntimeException('OCR tidak menemukan teks pada gambar ini.');
        }

        return $text;
    }
}
