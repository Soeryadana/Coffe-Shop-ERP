<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function createOrder(array $data, int $userId): Order
    {
        return DB::transaction(function () use ($data, $userId) {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'order_type' => $data['order_type'],
                'table_id' => $data['table_id'] ?? null,
                'user_id' => $userId,
                'status' => 'pending',
                'subtotal' => 0,
                'discount' => $data['discount'] ?? 0,
                'total' => 0,
            ]);

            $subtotal = 0;

            foreach ($data['items'] as $itemData) {
                $variant = ProductVariant::findOrFail($itemData['product_variant_id']);

                $lineTotal = $variant->price * $itemData['quantity'];
                $subtotal += $lineTotal;

                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'quantity' => $itemData['quantity'],
                    'price' => $variant->price, // snapshot price at time of order
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            $discount = $data['discount'] ?? 0;
            $total = $subtotal - $discount;

            $order->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
            ]);

            if ($data['order_type'] === 'dine_in' && !empty($data['table_id'])) {
                // dd([
                //     'order_table_id' => $order->table_id,
                //     'data_table_id' => $data['table_id'],
                //     'dining_table_exists' => \App\Models\DiningTable::find($data['table_id']),
                //     'relation_result' => $order->diningTable,
                // ]);
                $order->diningTable->update(['status' => 'occupied']);
            }

            return $order;
        });
    }

    private function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $random = Str::upper(Str::random(4));

        return "ORD-{$date}-{$random}";
    }
}
