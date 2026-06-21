<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Inject the new {{email}} template variable right after the line that
     * contains {{customer_name}} in every existing notification template.
     * The email line mirrors the formatting of the name line (prefix, padding)
     * so it blends with whatever layout each template already uses.
     */
    public function up(): void
    {
        $templates = DB::table('notification_templates')
            ->where('message_body', 'like', '%{{customer_name}}%')
            ->where('message_body', 'not like', '%{{email}}%')
            ->get(['id', 'message_body']);

        foreach ($templates as $tpl) {
            $lines  = preg_split('/\r\n|\r|\n/', $tpl->message_body);
            $output = [];

            foreach ($lines as $line) {
                $output[] = $line;

                if (str_contains($line, '{{customer_name}}')) {
                    // Derive an email line from the name line: swap the label
                    // word and the placeholder, keeping prefix/style intact.
                    $emailLine = str_replace('{{customer_name}}', '{{email}}', $line);
                    $emailLine = preg_replace('/\bNama\b/iu', 'Email', $emailLine);
                    $emailLine = preg_replace('/\bName\b/iu', 'Email', $emailLine);
                    $output[]  = $emailLine;
                }
            }

            DB::table('notification_templates')
                ->where('id', $tpl->id)
                ->update(['message_body' => implode("\n", $output)]);
        }
    }

    public function down(): void
    {
        $templates = DB::table('notification_templates')
            ->where('message_body', 'like', '%{{email}}%')
            ->get(['id', 'message_body']);

        foreach ($templates as $tpl) {
            $lines = preg_split('/\r\n|\r|\n/', $tpl->message_body);
            $lines = array_values(array_filter(
                $lines,
                fn ($line) => !str_contains($line, '{{email}}')
            ));

            DB::table('notification_templates')
                ->where('id', $tpl->id)
                ->update(['message_body' => implode("\n", $lines)]);
        }
    }
};
