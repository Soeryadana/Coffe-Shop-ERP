<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'price',
        'sku'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function recipes()
    {
        return $this->hasMany(ProductRecipe::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
