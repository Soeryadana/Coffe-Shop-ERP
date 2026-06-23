<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coffee = Category::where('name', 'Coffee')->first();
        $nonCoffee = Category::where('name', 'Non-Coffee')->first();
        $snacks = Category::where('name', 'Snacks')->first();

        // Espresso-based
        $cappuccino = Product::create([
            'category_id' => $coffee->id,
            'name' => 'Cappuccino',
            'description' => 'Espresso with steamed milk foam',
            'is_active' => true,
        ]);

        ProductVariant::create(['product_id' => $cappuccino->id, 'name' => 'Hot - Regular', 'price' => 22000]);
        ProductVariant::create(['product_id' => $cappuccino->id, 'name' => 'Iced - Regular', 'price' => 25000]);

        $latte = Product::create([
            'category_id' => $coffee->id,
            'name' => 'Cafe Latte',
            'description' => 'Espresso with steamed milk',
            'is_active' => true,
        ]);

        ProductVariant::create(['product_id' => $latte->id, 'name' => 'Hot - Regular', 'price' => 23000]);
        ProductVariant::create(['product_id' => $latte->id, 'name' => 'Iced - Regular', 'price' => 26000]);

        // Non-coffee
        $matcha = Product::create([
            'category_id' => $nonCoffee->id,
            'name' => 'Matcha Latte',
            'description' => 'Japanese green tea latte',
            'is_active' => true,
        ]);

        ProductVariant::create(['product_id' => $matcha->id, 'name' => 'Iced - Regular', 'price' => 27000]);

        // Snacks
        $croissant = Product::create([
            'category_id' => $snacks->id,
            'name' => 'Butter Croissant',
            'description' => 'Classic French pastry',
            'is_active' => true,
        ]);

        ProductVariant::create(['product_id' => $croissant->id, 'name' => 'Regular', 'price' => 18000]);
    }
}
