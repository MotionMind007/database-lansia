<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $admin = Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
        $surveyor = Role::firstOrCreate(['name' => 'surveyor', 'guard_name' => 'web']);
        $verifikator = Role::firstOrCreate(['name' => 'verifikator', 'guard_name' => 'web']);

        // Create default Administrator
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@lansiapapua.id'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make($this->passwordFromEnvironment('LANSIA_ADMIN_PASSWORD')),
                'is_active' => true,
            ]
        );
        $adminUser->assignRole($admin);

        // Create default Surveyor
        $surveyorUser = User::firstOrCreate(
            ['email' => 'surveyor@lansiapapua.id'],
            [
                'name' => 'Surveyor Demo',
                'username' => 'surveyor',
                'password' => Hash::make($this->passwordFromEnvironment('LANSIA_SURVEYOR_PASSWORD')),
                'is_active' => true,
            ]
        );
        $surveyorUser->assignRole($surveyor);

        // Create default Verifikator
        $verifikatorUser = User::firstOrCreate(
            ['email' => 'verifikator@lansiapapua.id'],
            [
                'name' => 'Verifikator Demo',
                'username' => 'verifikator',
                'password' => Hash::make($this->passwordFromEnvironment('LANSIA_VERIFIKATOR_PASSWORD')),
                'is_active' => true,
            ]
        );
        $verifikatorUser->assignRole($verifikator);
    }

    private function passwordFromEnvironment(string $key): string
    {
        $password = env($key);

        if (is_string($password) && strlen($password) >= 12) {
            return $password;
        }

        $this->command?->warn("{$key} is missing or shorter than 12 characters; generated a random password for this seeded user.");

        return Str::password(32);
    }
}
