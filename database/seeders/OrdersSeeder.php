<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrdersSeeder extends Seeder
{

    public function run(): void
    {
        $start = strtotime('2025-01-01 00:00:00');
        $end   = strtotime('2025-10-08 23:59:59');
        for ($i = 0; $i < 50; $i++) {
            $randomTimestamp = mt_rand($start, $end);

            Order::create([
                'first_name' => 'admin',
                'last_name' => "admin",
                'middle_name' => "any",
                'email' => 1,
                'total_price' => mt_rand(1000, 25000),
                'status' => 'Paid',
                'session_id' => 1,
                'mobile_phone' => "+380123456789",
                'shipping_city' => "Київ",
                'shipping_warehouse' => "Відділення №3 (до 30 кг на одне місце): вул. Слобожанська,13",
                'created_at' => date('Y-m-d H:i:s', $randomTimestamp),
            ]);
        }
    }
}
