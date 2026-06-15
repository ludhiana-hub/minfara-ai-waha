<?php

namespace App\Services;

class NotificationTemplateService
{
    public function render(string $template, array $data): string
    {
        $map = [
            '{{customer_name}}'  => $data['customer_name']  ?? '-',
            '{{program_name}}'   => $data['program_name']   ?? '-',
            '{{amount}}'         => $data['amount']         ?? '-',
            '{{transaction_id}}' => $data['transaction_id'] ?? '-',
            '{{status}}'         => $data['status']         ?? '-',
            '{{date}}'           => now()->format('d M Y, H:i'),
            '{{cms_url}}'        => $data['cms_url']        ?? config('whatsapp.cms_base_url'),
        ];

        return str_replace(array_keys($map), array_values($map), $template);
    }

    /** Return dummy-rendered preview for UI display */
    public function renderPreview(string $template): string
    {
        return $this->render($template, [
            'customer_name'  => 'Budi Santoso',
            'program_name'   => 'Kelas B1 Online',
            'amount'         => 'Rp 1.499.000',
            'transaction_id' => 'TRX-20240601-001',
            'status'         => 'Lunas',
            'cms_url'        => config('whatsapp.cms_base_url') . '/admin/transactions/1',
        ]);
    }
}
