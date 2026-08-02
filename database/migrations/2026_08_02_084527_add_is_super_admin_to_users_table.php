<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // CMS ini tidak punya pendaftaran publik — satu-satunya cara punya akun adalah
            // di-seed sebagai super admin (lihat SuperAdminSeeder) atau dibuat manual oleh
            // super admin lain nantinya. Flag ini disiapkan untuk kalau suatu saat ada
            // tingkat admin yang lebih rendah; untuk sekarang semua user yang ada = admin.
            $table->boolean('is_super_admin')->default(false)->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });
    }
};
