<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessOrderWorkflow;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $orders = Order::with('customer', 'orderItems.product')
        ->when($request->status, fn($q, $status) => $q->where('status', $status))
        ->when($request->customer_id, fn($q, $customerId) => $q->where('customer_id', $customerId))
        ->orderBy('created_at', 'desc')
        ->paginate($request->get('per_page', 15));

        return response()->json($orders);
    }

    public function show(Order $order) 
    {
        return response()->json($order->load(['customer', 'orderItems.product', 'refunds']));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_number' => 'required|unique:orders,order_number',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $totalAmount = 0;
        foreach($validated['items'] as $item) {
            $totalAmount += $item['quantity'] * $item['unit_price'];
        }

        $order = Order::create([
            'customer_id' => $validated['customer_id'],
            'order_number' => $validated['order_number'],
            'status' => 'pending',
            'total_amount' => $totalAmount,
        ]);

        foreach($validated['items'] as $item) {
            $order->orderItems()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        //dispatch workflow
        ProcessOrderWorkflow::dispatch($order);

        return response()->json($order->load('orderItems'), 201);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
