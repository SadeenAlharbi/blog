<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates (or promotes) the first administrator.
 *
 * No password is hard-coded here. The credentials come from the environment:
 *
 *   ADMIN_EMAIL=you@example.com
 *   ADMIN_PASSWORD=your-strong-password
 *   ADMIN_NAME="اسم المشرف"          # optional
 *
 * If ADMIN_EMAIL is not set the seeder does nothing and says so, so running it
 * on a server without those variables can never create a default account with
 * a guessable password.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (blank($email)) {
            $this->command?->warn('AdminUserSeeder: تخطّي — لم يتم ضبط ADMIN_EMAIL في ملف .env');

            return;
        }

        $existing = User::where('email', $email)->first();

        if ($existing) {
            // Explicit assignment: `role` / `is_active` / `is_super_admin` are
            // intentionally not mass-assignable on the User model.
            $existing->role = User::ROLE_ADMIN;
            $existing->is_active = true;
            // The .env-provisioned account is the platform owner: the only
            // administrator allowed to grant or revoke admin access.
            $existing->is_super_admin = true;
            $existing->save();

            $this->command?->info("AdminUserSeeder: تمت ترقية الحساب الحالي ({$email}) إلى مشرف.");

            return;
        }

        if (blank($password)) {
            $this->command?->warn('AdminUserSeeder: تخطّي الإنشاء — لم يتم ضبط ADMIN_PASSWORD في ملف .env');

            return;
        }

        $user = new User();
        $user->name = env('ADMIN_NAME', 'مشرف المنصة');
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->role = User::ROLE_ADMIN;
        $user->is_active = true;
        $user->is_super_admin = true;
        // Provisioned by the platform owner from .env, not by self-registration:
        // there is nobody to open a verification link, so it is verified here.
        $user->email_verified_at = now();
        $user->save();

        $this->command?->info("AdminUserSeeder: تم إنشاء حساب مشرف ({$email}).");
    }
}
