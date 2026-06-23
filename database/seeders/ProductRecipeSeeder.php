<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductRecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $beans = Ingredient::where('name', 'Coffee Beans')->first();
        $milk = Ingredient::where('name', 'Fresh Milk')->first();
        $matcha = Ingredient::where('name', 'Matcha Powder')->first();
        $syrup = Ingredient::where('name', 'Sugar Syrup')->first();
        $ice = Ingredient::where('name', 'Ice Cubes')->first();
        $cup12 = Ingredient::where('name', 'Cup 12oz')->first();
        $cup16 = Ingredient::where('name', 'Cup 16oz')->first();
        $dough = Ingredient::where('name', 'Croissant Dough')->first();

        // Cappuccino - Hot
        $this->addRecipe('Cappuccino', 'Hot - Regular', [
            [$beans, 18],
            [$milk, 150],
            [$cup12, 1],
        ]);

        // Cappuccino - Iced
        $this->addRecipe('Cappuccino', 'Iced - Regular', [
            [$beans, 18],
            [$milk, 150],
            [$ice, 100],
            [$cup16, 1],
        ]);

        // Cafe Latte - Hot
        $this->addRecipe('Cafe Latte', 'Hot - Regular', [
            [$beans, 18],
            [$milk, 200],
            [$cup12, 1],
        ]);

        // Cafe Latte - Iced
        $this->addRecipe('Cafe Latte', 'Iced - Regular', [
            [$beans, 18],
            [$milk, 200],
            [$ice, 100],
            [$cup16, 1],
        ]);

        // Matcha Latte - Iced
        $this->addRecipe('Matcha Latte', 'Iced - Regular', [
            [$matcha, 15],
            [$milk, 200],
            [$syrup, 20],
            [$ice, 100],
            [$cup16, 1],
        ]);

        // Croissant
        $this->addRecipe('Butter Croissant', 'Regular', [
            [$dough, 1],
        ]);
    }

    private function addRecipe(string $productName, string $variantName, array $items): void
    {
        $variant = ProductVariant::whereHas('product', function ($query) use ($productName) {
            $query->where('name', $productName);
        })->where('name', $variantName)->first();

        if (!$variant) {
            return;
        }

        foreach ($items as [$ingredient, $quantity]) {
            ProductRecipe::create([
                'product_variant_id' => $variant->id,
                'ingredient_id' => $ingredient->id,
                'quantity_used' => $quantity,
            ]);
        }
    }
}
