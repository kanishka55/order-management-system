<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class KPIService
{
    private const DAILY_REVENUE_KEY = 'kpi:daily_revenue:';
    private const DAILY_ORDER_COUNT_KEY = 'kpi:daily_order_count:';
    private const CUSTOMER_TOTAL_KEY = 'kpi:customer_total:';
    private const LEADERBOARD_KEY = 'kpi:customer_leaderboard';

    public function updateOrderKPIs(Order $order): void
    {
        $date = $order->created_at->format('Y-m-d');

        //Update daily revenue
        Redis::incrbyfloat(
            self::DAILY_REVENUE_KEY . $date,
            (float) $order->total_amount
        );

        //Update daily order count
        Redis::incr(self::DAILY_ORDER_COUNT_KEY . $date);

        //Update customer total and leaderboard
        $newTotal = Redis::incrbyfloat(
            self::CUSTOMER_TOTAL_KEY . $order->customer_id,
            (float) $order->total_amount
        );

        Redis::zadd(
            self::LEADERBOARD_KEY,
            $newTotal,
            $order->customer_id
        );

        //Set expiration for daily keys (30 days)
        Redis::expire(self::DAILY_REVENUE_KEY . $date, 30 * 24 * 3600);
        Redis::expire(self::DAILY_ORDER_COUNT_KEY . $date, 30 * 24 * 3600);
    }

    public function updateRefundKPIs(Order $order, float $refundAmount): void
    {
        $date = now()->format('Y-m-d');

        // Reduce daily revenue
        Redis::incrbyFloat(
            self::DAILY_REVENUE_KEY . $date,
            -$refundAmount
        );

        // Reduce customer total and update leaderboard
        $newTotal = Redis::incrbyfloat(
            self::CUSTOMER_TOTAL_KEY . $order->customer_id,
            -$refundAmount
        );

        Redis::zadd(
            self::LEADERBOARD_KEY,
            $newTotal,
            $order->customer_id
        );
    }

    public function getDailyKPIs(string $date): array
    {
        $revenue = Redis::get(self::DAILY_REVENUE_KEY . $date) ?? 0;
        $orderCount = Redis::get(self::DAILY_ORDER_COUNT_KEY . $date) ?? 0;

        return [
            'date' => $date,
            'revenue' => (float) $revenue,
            'order_count' => (int) $orderCount,
            'average_order_value' => $orderCount > 0 ? $revenue / $orderCount : 0,
        ];
    }

    public function getTopCustomers(int $limit = 10): array
    {
        $customerIds = Redis::zrevrange(self::LEADERBOARD_KEY, 0, $limit - 1, ['withscores' => true]);

        $customers = [];

        // Iterate over the Redis data and fetch customer details
        foreach ($customerIds as $customerId => $totalSpent) {
            // Fetch the customer details from the database
            $customer = Customer::find($customerId);

            if ($customer) {
                // Store the customer data in the array
                $customers[] = [
                    'customer_id' => $customerId,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'total_spent' => (float) $totalSpent,
                ];
            }
        }

        return $customers;
    }
}