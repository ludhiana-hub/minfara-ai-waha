<?php

use App\Models\BotConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-customer sales memory. Facts the customer volunteered in chat only: no payment
        // data, no message bodies. Pruned after 90 days (see LeadProfile::prunable()).
        Schema::create('lead_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('from_number', 100)->unique();
            $table->string('contact_name')->nullable();
            $table->string('language', 40)->nullable();
            $table->string('level', 40)->nullable();
            $table->string('goal', 40)->nullable();
            $table->string('package_interest', 40)->nullable();
            $table->json('objections')->nullable();
            $table->string('stage', 30)->default('new');
            $table->timestamp('checkout_pushed_at')->nullable();
            $table->timestamps();

            $table->index('updated_at');
        });

        BotConfig::firstOrCreate(['key' => 'ai_sales_memory_enabled'], [
            'value'       => 'true',
            'type'        => 'boolean',
            'label'       => 'Ingatan Sales per Customer',
            'description' => 'AI mengingat bahasa, level, tujuan, paket, dan keberatan customer, lalu menyesuaikan arahan sales per tahap sampai checkout di web.',
            'group'       => 'ai',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_profiles');
        BotConfig::where('key', 'ai_sales_memory_enabled')->delete();
    }
};
