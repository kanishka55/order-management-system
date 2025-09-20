<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class ProcessOrderImport implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $orderData)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach($this->orderData as $data) {
            try {
                DB::transaction(function() use ($data) {
                    $this->processOrder($data);
                });
            } catch (\Exception $e) {
                Log::error('Order import failed', [
                    'data' => $data,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function processOrder(array $data)
    {
        // create or find customer
        $customer = Customer::firstOrCreate(
            ['email' => $data['customer_email']],
            ['name' => $data['customer_name']]
        );

        //create order
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => $data['order_number'],
            'status' => 'pending',
            'total_amount' => $data['total_amount'],
        ]);

        //Crate order items if any
        $items = json_decode($data['items'], true);

        foreach($items as $item) {
            $product = Product::where('sku', $item['sku'])->first();

            if($product) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        }

        // dispatch order workflow
        ProcessOrderWorkflow::dispatch($order);
    }
}
