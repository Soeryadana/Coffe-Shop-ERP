<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('variants', 'category')->get();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        $product = DB::transaction(function () use ($validated) {
            $product = Product::create([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'image' => $validated['image'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['variants'] as $variant) {
                $product->variants()->create($variant);
            }

            return $product;
        });

        return new ProductResource($product->load('variants', 'category'));
    }

    public function show(Product $product)
    {
        return new ProductResource($product->load('variants', 'category'));
    }

    public function update(StoreProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $product) {
            $product->update([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'image' => $validated['image'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $product->variants()->delete();
            foreach ($validated['variants'] as $variant) {
                $product->variants()->create($variant);
            }
        });

        return new ProductResource($product->load('variants', 'category'));
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
