<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);

        User::create([
            'name' => 'Dewa Cashier',
            'email' => 'cashier@coffeeshop.test',
            'password' => bcrypt('password'),
            'role' => 'cashier',
        ]);

        User::create([
            'name' => 'Barista One',
            'email' => 'barista@coffeeshop.test',
            'password' => bcrypt('password'),
            'role' => 'barista',
        ]);
    }
}
