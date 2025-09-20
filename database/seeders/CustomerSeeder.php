<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            ['name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '+1234567890'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'phone' => '+1234567891'],
            ['name' => 'Mike Johnson', 'email' => 'mike@example.com', 'phone' => '+1234567892'],
            ['name' => 'Sarah Wilson', 'email' => 'sarah@example.com', 'phone' => '+1234567893'],
            ['name' => 'Tom Brown', 'email' => 'tom@example.com', 'phone' => '+1234567894'],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
