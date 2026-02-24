<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@kingbamboo.vn'],
            [
                'name'     => 'Admin KBB',
                'email'    => 'admin@kingbamboo.vn',
                'password' => Hash::make('Admin@2026'),
            ]
        );

        $this->command->info('Admin user created: admin@kingbamboo.vn / Admin@2026');
    }
}
