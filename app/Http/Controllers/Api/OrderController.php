<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    public function index()
    {
        $orders = Order::with('items.productVariant.product', 'diningTable', 'user')
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->createOrder(
            $request->validated(),
            $request->user()->id
        );

        return new OrderResource(
            $order->load('items.productVariant.product', 'diningTable')
        );
    }

    public function show(Order $order)
    {
        return new OrderResource(
            $order->load('items.productVariant.product', 'diningTable', 'payments')
        );
    }

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order,
        InventoryService $inventoryService
    ) {
        $validated = $request->validated();
        $previousStatus = $order->status;
        $newStatus = $validated['status'];

        $order->update(['status' => $newStatus]);

        // deduct stock only the moment it transisitons into completed
        if ($newStatus === 'completed' && $previousStatus !== 'completed') {
            try {
                $inventoryService->deductStockForOrder($order);
            } catch (InsufficientStockException $e) {
                // roll back the statsu change since stock was'nt sufficient
                $order->update(['status' => $previousStatus]);

                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        // if order is cancelled and was dine in, free up the table
        if ($newStatus === 'cancelled' & $order->table_id) {
            $order->diningTable->update(['status' => 'available']);
        }

        return new OrderResource(
            $order->load('items.productVariant.product', 'diningTable')
        );
    }
}
