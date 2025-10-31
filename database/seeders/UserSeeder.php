<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['employee', 'spv', 'instructor', 'admin', 'leader'];

        foreach ($roles as $role) {
            User::create([
                'name' => ucfirst($role) . ' User',
                'email' => $role . '@example.com',
                'password' => Hash::make('password'),
                'NRP' => 1234567,
                'section' => 'LID',
                'role' => $role,
            ]);
        }
    }
}
