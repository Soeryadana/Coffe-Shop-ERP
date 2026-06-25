<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
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
}
