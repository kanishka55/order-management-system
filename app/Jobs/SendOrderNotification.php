<?php

namespace App\Jobs;

use App\Models\NotificationHistory;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderNotification implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels, Dispatchable;

    public $timeout = 60;
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order, public string $type) //type: 'success' or 'failed'
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $notificationType = $this->type === 'success' ? 'order_success' : 'order_failed';

        $payload = [
            'order_id' => $this->order->id,
            'customer_id' => $this->order->customer_id,
            'status' => $this->order->status,
            'total' => $this->order->total_amount,
            'order_number' => $this->order->order_number,
        ];

        try {
            // For this demo, used log instead of sending actual emails
            //in production can be used mail services.

            $this->logNotification($payload);

            //store notification history
            NotificationHistory::create([
                'order_id' => $this->order->id,
                'customer_id' => $this->order->customer_id,
                'type' => $notificationType,
                'channel' => 'log', // or 'email'
                'status' => 'sent',
                'payload' => $payload,
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Store failed notification history
            NotificationHistory::create([
                'order_id' => $this->order->id,
                'customer_id' => $this->order->customer_id,
                'type' => $notificationType,
                'channel' => 'log',
                'status' => 'failed',
                'payload' => array_merge($payload, ['error' => $e->getMessage()]),
                'sent_at' => null,
            ]);

            throw $e;
        }
    }

    private function logNotification(array $payload)
    {
        $message = $this->type === 'success' ? "Order {$payload['order_number']} processed successfully" : "Order {$payload['order_number']} processing failed";

        Log::channel('orders')->info($message, $payload);
    }
}
