<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin NapasAman Kediri Raya',
                'email' => 'admin@napasaman.id',
                'password' => Hash::make('napasaman2026'),
                'role' => 'admin',
            ],
            [
                'name' => 'Mitra RT/RW & Bank Sampah Mojoroto',
                'email' => 'mitra@napasaman.id',
                'password' => Hash::make('napasaman2026'),
                'role' => 'mitra',
            ],
            [
                'name' => 'Raka Pratama (Mahasiswa Kediri)',
                'email' => 'warga@napasaman.id',
                'password' => Hash::make('napasaman2026'),
                'role' => 'user',
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(['email' => $data['email']], $data);
        }
    }
}
