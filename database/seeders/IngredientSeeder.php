<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ingredients = [
            ['name' => 'Coffee Beans', 'unit' => 'g', 'stock_quantity' => 5000, 'min_stock_alert' => 500],
            ['name' => 'Fresh Milk', 'unit' => 'ml', 'stock_quantity' => 10000, 'min_stock_alert' => 1000],
            ['name' => 'Matcha Powder', 'unit' => 'g', 'stock_quantity' => 1000, 'min_stock_alert' => 100],
            ['name' => 'Sugar Syrup', 'unit' => 'ml', 'stock_quantity' => 3000, 'min_stock_alert' => 300],
            ['name' => 'Ice Cubes', 'unit' => 'g', 'stock_quantity' => 20000, 'min_stock_alert' => 2000],
            ['name' => 'Cup 12oz', 'unit' => 'pcs', 'stock_quantity' => 200, 'min_stock_alert' => 20],
            ['name' => 'Cup 16oz', 'unit' => 'pcs', 'stock_quantity' => 200, 'min_stock_alert' => 20],
            ['name' => 'Croissant Dough', 'unit' => 'pcs', 'stock_quantity' => 50, 'min_stock_alert' => 10],
        ];

        foreach ($ingredients as $ingredient) {
            Ingredient::create($ingredient);
        }
    }
}
