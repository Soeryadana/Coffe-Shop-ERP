<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustmentRequest;
use App\Http\Requests\StoreIngredientRequest;
use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngredientController extends Controller
{
    public function index()
    {
        return response()->json(Ingredient::all());
    }

    public function store(StoreIngredientRequest $request)
    {
        $ingredient = Ingredient::create($request->validated());

        return response()->json($ingredient, 201);
    }

    public function show(Ingredient $ingredient)
    {
        return response()->json($ingredient->load('stockMovements'));
    }

    public function update(StoreIngredientRequest $request, Ingredient $ingredient)
    {
        $ingredient->update($request->validated());

        return response()->json($ingredient);
    }

    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();

        return response()->json(null, 204);
    }

    public function adjustStock(StockAdjustmentRequest $request, Ingredient $ingredient)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $ingredient, $request) {
            if ($validated['type'] === 'in') {
                $ingredient->increment('stock_quantity', $validated['quantity']);
            } else {
                // 'out' or 'adjustment' both reduce the stock
                $ingredient->decrement('stock_quantity', $validated['quantity']);
            }

            StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? null,
                'user_id' => $request->user()->id
            ]);
        });

        return response()->json($ingredient->fresh());
    }
}
