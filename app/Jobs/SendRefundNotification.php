<?php

namespace App\Jobs;

use App\Models\NotificationHistory;
use App\Models\Refund;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendRefundNotification implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels, Dispatchable;

    public $timeout = 60;
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
    public function handle(): void
    {
        $payload = [
            'refund_id' => $this->refund->id,
            'order_id' => $this->refund->order_id,
            'customer_id' => $this->refund->order->customer_id,
            'amount' => $this->refund->amount,
            'type' => $this->refund->type,
            'refund_reference' => $this->refund->refund_reference, 
        ];

        try {
            $this->logRefundNotification($payload);

            NotificationHistory::create([
                'order_id' => $this->refund->order_id,
                'customer_id' => $this->refund->order->customer_id,
                'type' => 'refund_processed',
                'channel' => 'log',
                'status' => 'sent',
                'payload' => $payload,
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            NotificationHistory::create([
                'order_id' => $this->refund->order_id,
                'customer_id' => $this->refund->order->customer_id,
                'type' => 'refund_processed',
                'channel' => 'log',
                'status' => 'failed',
                'payload' => array_merge($payload, ['error' => $e->getMessage()]),
                'sent_at' => null,
            ]);

            throw $e;
        }
    }

    private function logRefundNotification(array $payload) 
    {
        Log::channel('orders')->info("Refund {$payload['refund_reference']} processed successfully", $payload);

    }
}
