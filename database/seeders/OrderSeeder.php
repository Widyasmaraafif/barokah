<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo orders with correct item snapshots and totals.
 *
 * Idempotent: matched by order_number, safe to re-run. Stock is not
 * decremented here so re-seeding never drains the demo catalog.
 */
class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customer = User::query()->where('email', 'customer@barokah.local')->first();
        $products = Product::query()->whereIn('slug', [
            'keripik-pisang-original',
            'hijab-paris-premium',
            'kerudung-bergo-maryam',
            'pashmina-kaos-basic',
        ])->get()->keyBy('slug');

        if ($products->count() < 4) {
            return;
        }

        foreach ($this->demoOrders($customer?->id) as $orderData) {
            $lines = $orderData['lines'];
            unset($orderData['lines']);

            $itemsSubtotal = 0.0;
            $prepared = [];

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $products[$line['slug']];
                $lineTotal = (float) $product->price * $line['quantity'];
                $itemsSubtotal += $lineTotal;

                $prepared[] = [
                    'product' => $product,
                    'quantity' => $line['quantity'],
                    'line_total' => $lineTotal,
                ];
            }

            $shippingFee = (float) $orderData['shipping_fee'];
            $orderData['subtotal'] = number_format($itemsSubtotal, 2, '.', '');
            $orderData['total'] = number_format($itemsSubtotal + $shippingFee, 2, '.', '');

            /** @var Order $order */
            $order = Order::query()->updateOrCreate(
                ['order_number' => $orderData['order_number']],
                $orderData
            );

            foreach ($prepared as $row) {
                /** @var Product $product */
                $product = $row['product'];

                OrderItem::query()->updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'seller_id' => $product->seller_id,
                        'product_name_snapshot' => $product->name,
                        'product_slug_snapshot' => $product->slug,
                        'price_snapshot' => $product->price,
                        'quantity' => $row['quantity'],
                        'subtotal' => number_format((float) $row['line_total'], 2, '.', ''),
                    ]
                );
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function demoOrders(?int $customerId): array
    {
        return [
            [
                'order_number' => 'BRK-DEMO-0001',
                'user_id' => $customerId,
                'customer_name' => 'Nurul Buyer',
                'customer_address' => 'No. 8, Jalan SS2, Petaling Jaya',
                'customer_state' => 'Selangor',
                'customer_post_code' => '47300',
                'customer_phone' => '017-1234567',
                'customer_email' => 'customer@barokah.local',
                'currency_code' => 'MYR',
                'shipping_fee' => '5.00',
                'status' => OrderStatus::PendingPayment,
                'shipping_method' => 'fixed',
                'shipping_provider' => null,
                'notes' => 'Demo pending order (multi-seller).',
                'expired_at' => now()->addDay(),
                'lines' => [
                    ['slug' => 'keripik-pisang-original', 'quantity' => 2],
                    ['slug' => 'hijab-paris-premium', 'quantity' => 1],
                ],
            ],
            [
                'order_number' => 'BRK-DEMO-0002',
                'user_id' => null,
                'customer_name' => 'Tetamu Melaka',
                'customer_address' => 'No. 3, Jalan Hang Tuah, Melaka',
                'customer_state' => 'Malacca',
                'customer_post_code' => '75000',
                'customer_phone' => '016-9876543',
                'customer_email' => null,
                'currency_code' => 'MYR',
                'shipping_fee' => '5.00',
                'status' => OrderStatus::Paid,
                'shipping_method' => 'fixed',
                'shipping_provider' => null,
                'notes' => 'Demo guest order (paid via FPX).',
                'expired_at' => now()->subHour(),
                'lines' => [
                    ['slug' => 'kerudung-bergo-maryam', 'quantity' => 1],
                ],
            ],
            [
                'order_number' => 'BRK-DEMO-0003',
                'user_id' => $customerId,
                'customer_name' => 'Nurul Buyer',
                'customer_address' => 'No. 8, Jalan SS2, Petaling Jaya',
                'customer_state' => 'Selangor',
                'customer_post_code' => '47300',
                'customer_phone' => '017-1234567',
                'customer_email' => 'customer@barokah.local',
                'currency_code' => 'MYR',
                'shipping_fee' => '5.00',
                'status' => OrderStatus::Completed,
                'shipping_method' => 'fixed',
                'shipping_provider' => null,
                'notes' => 'Demo completed order (paid via DuitNow).',
                'expired_at' => now()->subDays(2),
                'lines' => [
                    ['slug' => 'pashmina-kaos-basic', 'quantity' => 1],
                ],
            ],
        ];
    }
}
