<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\KPIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateKPIs implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels, Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order, public string $action, public ?float $amount = null) //action is 'order_completed', 'order_refunded'
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(KPIService $kpiService): void
    {
        switch($this->action) {
            case 'order_completed':
                $kpiService->updateOrderKPIs($this->order);
                break;

            case 'order_refunded':
                $kpiService->updateRefundKPIs($this->order, $this->amount);
                break;
        }
    }
}
