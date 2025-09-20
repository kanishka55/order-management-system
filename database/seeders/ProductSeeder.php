<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'Laptop', 'sku' => 'LAP001', 'price' => 999.99, 'stock_quantity' => 50],
            ['name' => 'Mouse', 'sku' => 'MOU001', 'price' => 29.99, 'stock_quantity' => 200],
            ['name' => 'Keyboard', 'sku' => 'KEY001', 'price' => 79.99, 'stock_quantity' => 150],
            ['name' => 'Monitor', 'sku' => 'MON001', 'price' => 299.99, 'stock_quantity' => 75],
            ['name' => 'Headphones', 'sku' => 'HEAD001', 'price' => 149.99, 'stock_quantity' => 100],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
