<?php 

namespace App\Services;

use App\Models\Order;

class StockService
{
    public function reserveStock(Order $order): bool
    {
        $canReserve = true;

        //check if all items can be reserved
        foreach($order->orderItems as $item) {
            if($item->product->stock_quantity < $item->quantity) {
                $canReserve = false;
                break;
            }
        }

        if(!$canReserve) {
            return false;
        }

        //reserve stock for all items
        foreach($order->orderItems as $item) {
            $item->product->reserveStock($item->quantity);
        }

        return true; 
    }

    public function releaseStock(Order $order): void
    {
        foreach($order->orderItems as $item) {
            $item->product->releaseStock($item->quantity);
        }
    }
}