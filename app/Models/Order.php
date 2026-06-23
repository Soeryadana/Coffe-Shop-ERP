<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'order_type',
        'dining_table_id',
        'user_id',
        'status',
        'subtotal',
        'discount',
        'total'
    ];

    public function table()
    {
        return $this->belongsTo(DiningTable::class, 'table_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
