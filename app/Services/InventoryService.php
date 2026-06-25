<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\StockMovement;
use Exception;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function deductStockForOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            //load items with variant's recipe and ingredient
            $order->load('items.productVariant.recipes.ingredient');

            foreach ($order->items as $item) {
                $recipes = $item->productVariant->recipes;

                foreach ($recipes as $recipe) {
                    $ingredient = $recipe->ingredient;
                    $totalUsed = $recipe->quantity_used * $item->quantity;

                    if ($ingredient->stock_quantity < $totalUsed) {
                        throw new InsufficientStockException(
                            "Insufficient stock for ingredient: {$ingredient->name}"
                        );
                    }

                    // lock row to avoid race conditions on concurrent orders
                    $ingredient = Ingredient::where('id', $recipe->ingredient_id)
                        ->lockForUpdate()
                        ->first();

                    $ingredient->decrement('stock_quantity', $totalUsed);

                    StockMovement::create([
                        'ingredient_id' => $ingredient->id,
                        'type' => 'out',
                        'quantity' => $totalUsed,
                        'reason' => "Order #{$order->order_number}",
                        'user_id' => auth()->id(),
                    ]);
                }
            }
        });
    }
}
