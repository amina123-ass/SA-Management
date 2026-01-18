<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin_si')->first();

        if ($adminRole) {
            User::updateOrCreate(
                ['email' => 'admin@samanagement.com'],
                [
                    'nom' => 'Admin',
                    'prenom' => 'Super',
                    'email' => 'admin@samanagement.com',
                    'telephone' => '+212600000000',
                    'adresse' => 'Rabat, Maroc',
                    'password' => Hash::make('Admin@2024'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'role_id' => $adminRole->id,
                ]
            );
        }
    }
}