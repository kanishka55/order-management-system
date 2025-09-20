<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Str;

class PaymentService
{
    public function processPayment(Order $order)
    {
        // Simulate payment processing
        sleep(1); // simulate delay
        
        //simulate 90% success rate
        $success = rand(1, 100) <= 90;

        if($success) {
            return [
                'success' => true,
                'transaction_id' => Str::uuid()->toString(),
                'amount' => $order->total_amount,
                'processed_at' => now()->toISOString(),
            ];
        } 

        return [
            'success' => false,
            'error' => 'Payment gateway error: ' . $this->getRandomError(),
        ];
    }

    public function processRefund(Order $order, float $amount)
    {
        // Simulate refund processing
        sleep(1); // simulate delay

        $success = rand(1, 100) <= 95; // Higher success rate for refunds

        if($success) {
            return [
                'success' => true,
                'refund_id' => Str::uuid()->toString(),
                'amount' => $amount,
                'processed_at' => now()->toISOString(),
            ];
        }

        return [
            'success' => false,
            'error' => 'Refund failed: ' . $this->getRandomError(),
        ];

    }

    private function getRandomError(): string
    {
        $errors = [
            'Insufficient funds',
            'Card expired',
            'Network error',
            'Invalid card details',
            'Payment gateway timeout',
        ];

        return $errors[array_rand($errors)];
    }
}