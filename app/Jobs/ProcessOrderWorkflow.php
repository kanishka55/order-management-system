<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\PaymentService;
use App\Services\StockService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessOrderWorkflow implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels, Dispatchable;

    public $timeout = 300;
    public $tries = 1; //important: no retries for workflow jobs

    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(StockService $stockService, PaymentService $paymentService): void
    {
        try {
            DB::transaction(function() use ($stockService, $paymentService) {
                // Step 1: Reserve stock
                if(!$stockService->reserveStock($this->order)){
                    $this->failOrder("Insufficient stock");
                    return;
                }

                // Step 2: Update order status
                $this->order->update(['status' => 'processing']);

                // Step 3: Process payment
                $paymentResult = $paymentService->processPayment($this->order);

                if($paymentResult['success']){
                    $this->finalizeOrder($paymentResult);

                } else {
                    $this->rollbackOrder($stockService, $paymentResult['error']);
                }
            });
        } catch (\Exception $e) {
            Log::error('Order workflow failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage()
            ]);

            $this->rollbackOrder(app(StockService::class), $e->getMessage());
        }
    }

    private function finalizeOrder(array $paymentResult)
    {
        $this->order->update([
            'status' => 'paid',
            'payment_data' => $paymentResult,
            'processed_at' => now()
        ]);

        //Update KPIs
        UpdateKPIs::dispatch($this->order, 'order_completed');

        //send success notification
        SendOrderNotification::dispatch($this->order, 'success');

        Log::info('Order finalized successfully', ['order_id' => $this->order->id]);
    }

    private function rollbackOrder(StockService $stockService, string $reason)
    {
        // Release reserved stock
        $stockService->releaseStock($this->order);

        // Update order status
        $this->order->update(['status' => 'failed']);

        $this->failOrder($reason);
    }

    private function failOrder(string $reason)
    {
        $this->order->update([
            'status' => 'failed',
            'payment_data' => ['error' => $reason]
        ]);

        // send failure notification
        SendOrderNotification::dispatch($this->order, 'failed');

         Log::warning('Order failed', [
            'order_id' => $this->order->id,
            'reason' => $reason
        ]);
    }
}
