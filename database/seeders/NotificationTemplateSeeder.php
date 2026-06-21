<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        NotificationTemplate::firstOrCreate(
            ['trigger_key' => 'transaction.created'],
            [
                'name'         => 'Transaksi Baru',
                'is_active'    => true,
                'message_body' => implode("\n", [
                    '🔔 *Transaksi Baru Masuk!*',
                    '',
                    'Nama: {{customer_name}}',
                    'Email: {{email}}',
                    'Program: {{program_name}}',
                    'Total: {{amount}}',
                    'Status: {{status}}',
                    'ID: #{{transaction_id}}',
                    'Waktu: {{date}}',
                    '',
                    '🔗 {{product_url}}',
                ]),
            ]
        );
    }
}
