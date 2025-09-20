<?php

namespace App\Jobs;

use App\Models\Refund;
use App\Services\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessRefund implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels, Dispatchable;

    public $timeout = 300;
    public $tries = 3; 

    /**
     * Create a new job instance.
     */
    public function __construct(public Refund $refund)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentService $paymentService): void
    {
        //idempotency check
        if($this->refund->status !== 'pending'){
            Log::info('Refund already processed', ['refund_id' => $this->refund->id]);
            return;
        }

        try {
            DB::transaction(function() use ($paymentService) {
                //Double check refund is still valid
                $this->refund->refresh();
                if($this->refund->status !== 'pending'){
                    return;
                }

                // Process the refund
                $refundResult = $paymentService->processRefund($this->refund->order, $this->refund->amount);

                if($refundResult['success']) {
                    $this->processSuccessfulRefund($refundResult);
                } else {
                    $this->processFailedRefund($refundResult['error']);
                }
            });
        } catch (\Exception $e) {
            Log::error('Refund processing failed', [
                'refund_id' => $this->refund->id,
                'error' => $e->getMessage()
            ]);

            $this->processFailedRefund($e->getMessage());
        }
    }

    private function processSuccessfulRefund(array $refundResult) 
    {
        //Update refund record
        $this->refund->update([
            'status' => 'processed',
            'metadata' => $refundResult,
            'processed_at' => now(),
        ]);

        //update order refunded amount
        $this->refund->order->increment('refunded_amount', $this->refund->amount);

        //update order status if fully refunded
        if($this->refund->order->refunded_amount >= $this->refund->order->total_amount) {
            $this->refund->order->update(['status' => 'refunded']);
        } else {
            $this->refund->order->update(['status' => 'partially_refunded']);
        }

        //Update KPIs
        UpdateKPIs::dispatch($this->refund->order, 'order_refunded', $this->refund->amount);

        //send notification
        SendRefundNotification::dispatch($this->refund);

        Log::info('Refund processed successfully', ['refund_id' => $this->refund->id, 'amount' => $this->refund->amount]);
    }

    private function processFailedRefund(string $error) 
    {
        $this->refund->update([
            'status' => 'failed',
            'metadata' => ['error' => $error],
            
        ]);

        Log::error('Refund failed', ['refund_id' => $this->refund->id, 'error' => $error]);
    }
}
