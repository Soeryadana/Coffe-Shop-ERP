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
        User::create([
            'name' => 'Admin',
            'email' => 'admin@coffeeshop.test',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        User::create([
            'name' => 'Dewa Cashier',
            'email' => 'cashier@coffeeshop.test',
            'password' => Hash::make('password'),
            'role' => 'cashier',
        ]);

        User::create([
            'name' => 'Barista One',
            'email' => 'barista@coffeeshop.test',
            'password' => Hash::make('password'),
            'role' => 'barista',
        ]);
    }
}
