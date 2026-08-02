<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the one super admin account this CMS ships with. There is no public
     * registration route — every additional account has to be created manually
     * (or via a future seeder) by someone who already has access.
     */
    public function run(): void
    {
        $email    = env('SUPER_ADMIN_EMAIL', 'admin@mitfara.com');
        $password = env('SUPER_ADMIN_PASSWORD');
        $existing = User::where('email', $email)->first();

        if ($existing) {
            // Operator sengaja set SUPER_ADMIN_PASSWORD (mis. untuk reset password yang hilang) —
            // sinkronkan. docker/entrypoint.sh menjalankan `db:seed --force` di SETIAP start
            // container, jadi ganti env + redeploy adalah cara pemulihan resmi tanpa perlu masuk
            // DB manual.
            if ($password) {
                $existing->update(['password' => $password]);
                $this->command?->info("Super admin '{$email}' sudah ada — password disinkronkan dari SUPER_ADMIN_PASSWORD env.");
            }

            return;
        }

        if (!$password) {
            if (app()->environment('production')) {
                // JANGAN generate password acak di sini untuk production. entrypoint.sh menjalankan
                // `db:seed --force` non-interaktif di setiap container start — password acak cuma
                // muncul sekali di stdout container yang gampang terlewat/ter-rotate, dan begitu
                // hilang, akun ini terkunci permanen (persis kejadian yang bikin comment ini ada).
                // Wajib set SUPER_ADMIN_PASSWORD eksplisit dulu sebelum akun boleh dibuat.
                $this->command?->error("SUPER_ADMIN_PASSWORD belum di-set — super admin '{$email}' TIDAK dibuat. Set SUPER_ADMIN_PASSWORD di .env produksi lalu deploy ulang.");
                Log::error('SuperAdminSeeder: refused to create super admin in production without SUPER_ADMIN_PASSWORD set.', ['email' => $email]);

                return;
            }

            // Lokal/dev: nyaman untuk langsung coba tanpa siapkan env dulu.
            $password = Str::password(16);
            $this->command?->warn("Super admin dibuat: {$email} / password: {$password}");
            $this->command?->warn('CATAT password ini sekarang — tidak akan ditampilkan lagi. Segera login dan ganti password.');
        } else {
            $this->command?->info("Super admin dibuat: {$email} (password dari SUPER_ADMIN_PASSWORD env)");
        }

        User::create([
            'name'           => env('SUPER_ADMIN_NAME', 'Super Admin'),
            'email'          => $email,
            'password'       => $password,
            'is_super_admin' => true,
        ]);
    }
}
