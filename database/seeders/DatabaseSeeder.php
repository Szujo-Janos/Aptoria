<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('local') && User::query()->doesntExist()) {
            User::create([
                'name' => 'Aptoria Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('ChangeMe123!'),
                'role' => 'admin',
                'locale' => 'en',
                'timezone' => 'Europe/Budapest',
                'password_change_required' => true,
            ]);
        }
    }
}
