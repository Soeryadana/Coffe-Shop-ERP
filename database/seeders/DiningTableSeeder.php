<?php

namespace Database\Seeders;

use App\Models\DiningTable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DiningTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            DiningTable::create([
                'table_number' => 'T' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'status' => 'available',
            ]);
        }
    }
}
