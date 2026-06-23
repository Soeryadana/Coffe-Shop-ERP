<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRecipe extends Model
{
    protected $fillable = [
        'product_variant_id',
        'ingredient_id',
        'quantity_used'
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
