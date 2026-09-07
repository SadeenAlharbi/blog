<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

            $existing->role = User::ROLE_ADMIN;
            $existing->is_active = true;

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
        $user->email_verified_at = now();
        $user->save();

        $this->command?->info("AdminUserSeeder: تم إنشاء حساب مشرف ({$email}).");
    }
}
