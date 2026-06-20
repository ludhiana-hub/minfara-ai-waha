<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename the {{cms_url}} template variable to {{product_url}} in all
     * existing notification templates. The render map & API param were renamed
     * in code, so stored templates still using {{cms_url}} would no longer be
     * replaced and leak the raw placeholder into sent WhatsApp messages.
     */
    public function up(): void
    {
        DB::table('notification_templates')
            ->where('message_body', 'like', '%{{cms_url}}%')
            ->update([
                'message_body' => DB::raw("REPLACE(message_body, '{{cms_url}}', '{{product_url}}')"),
            ]);
    }

    public function down(): void
    {
        DB::table('notification_templates')
            ->where('message_body', 'like', '%{{product_url}}%')
            ->update([
                'message_body' => DB::raw("REPLACE(message_body, '{{product_url}}', '{{cms_url}}')"),
            ]);
    }
};
