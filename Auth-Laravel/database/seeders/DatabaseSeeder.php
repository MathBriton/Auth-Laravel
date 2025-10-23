<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserType;
use App\Enums\UserStatus;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate([
            'cpf' => '17256361742',
        ], [
            'full_name' => 'Master',
            'email' => 'master@master.com',
            'password' => 'master',
            'type' => UserType::MASTER,
            'status' => UserStatus::ACTIVE,
        ]);

        // Executar o seeder de permissões
        $this->call(PermissionSeeder::class);
    }
}
