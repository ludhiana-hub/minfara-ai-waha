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
        $email = env('SUPER_ADMIN_EMAIL', 'admin@mitfara.com');

        if (User::where('email', $email)->exists()) {
            return;
        }

        $password = env('SUPER_ADMIN_PASSWORD');
        $generated = false;

        if (!$password) {
            $password  = Str::password(16);
            $generated = true;
        }

        User::create([
            'name'           => env('SUPER_ADMIN_NAME', 'Super Admin'),
            'email'          => $email,
            'password'       => $password,
            'is_super_admin' => true,
        ]);

        if ($generated) {
            // Password acak hanya muncul SEKALI di sini — tidak disimpan plaintext di mana pun.
            // Untuk deploy produksi, set SUPER_ADMIN_PASSWORD di env sebelum seed supaya tidak
            // bergantung baca log ini.
            $this->command?->warn("Super admin dibuat: {$email} / password: {$password}");
            $this->command?->warn('CATAT password ini sekarang — tidak akan ditampilkan lagi. Segera login dan ganti password.');
            Log::warning('SuperAdminSeeder: generated random password for new super admin — check console output at seed time.', ['email' => $email]);
        } else {
            $this->command?->info("Super admin dibuat: {$email} (password dari SUPER_ADMIN_PASSWORD env)");
        }
    }
}
