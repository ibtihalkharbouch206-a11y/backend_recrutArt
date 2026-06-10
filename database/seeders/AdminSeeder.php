<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'nom' => 'Admin',
            'email' => 'admin@artisanrecruit.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);
    }
}
