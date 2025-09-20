<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessRefund;
use App\Models\Order;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RefundController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
            'type' => 'required|in:full,partial',
        ]);

        // Validate refund amount
        if($validated['amount'] > $order->getRemainingRefundableAmount()) {
            throw ValidationException::withMessages([
                'amount' => 'Refund amount exceeds remaining refundable amount.'
            ]);
        }

        //check if order can be refunded
        if(!in_array($order->status, ['paid', 'partially_refunded'])) {
            throw ValidationException::withMessages([
                'order' => 'Order cannot be refunded in its current status.'
            ]);
        }

        // Create refund
        $refund = Refund::create([
            'order_id' => $order->id,
            'refund_reference' => 'REF-' . Str::random(10),
            'amount' => $validated['amount'],
            'status' => 'pending',
            'type' => $validated['type'],
            'reason' => $validated['reason'],
        ]);

        //dispatch refund processing
        ProcessRefund::dispatch($refund);

        return response()->json($refund, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Refund $refund)
    {
        return response()->json($refund->load('order.customer'));
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
