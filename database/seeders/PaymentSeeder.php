<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Seeder;

/**
 * Demo payments linked 1:1 to the demo orders from OrderSeeder.
 *
 * Idempotent: matched by order_id, safe to re-run. Amount always follows
 * the seeded order total so payment and order stay consistent.
 */
class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::query()->whereIn('order_number', array_keys($this->demoPayments()))->get()->keyBy('order_number');

        foreach ($this->demoPayments() as $orderNumber => $data) {
            $order = $orders->get($orderNumber);

            if ($order === null) {
                continue;
            }

            Payment::query()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'payment_gateway' => 'paynet',
                    'payment_method' => $data['payment_method'],
                    'amount' => $order->total,
                    'currency' => $order->currency_code ?? 'MYR',
                    'status' => $data['status'],
                    'transaction_id' => $data['transaction_id'],
                    'payload' => ['demo' => true, 'order_number' => $orderNumber],
                    'callback_payload' => $data['callback_payload'],
                    'paid_at' => $data['paid_at'],
                    'failed_at' => null,
                ]
            );
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function demoPayments(): array
    {
        return [
            'BRK-DEMO-0001' => [
                'payment_method' => PaymentMethod::Fpx,
                'status' => PaymentStatus::Pending,
                'transaction_id' => null,
                'callback_payload' => null,
                'paid_at' => null,
            ],
            'BRK-DEMO-0002' => [
                'payment_method' => PaymentMethod::Fpx,
                'status' => PaymentStatus::Paid,
                'transaction_id' => 'PAYNET-DEMO-0002',
                'callback_payload' => ['transaction_id' => 'PAYNET-DEMO-0002', 'status' => 'paid'],
                'paid_at' => now()->subHour(),
            ],
            'BRK-DEMO-0003' => [
                'payment_method' => PaymentMethod::DuitNow,
                'status' => PaymentStatus::Paid,
                'transaction_id' => 'PAYNET-DEMO-0003',
                'callback_payload' => ['transaction_id' => 'PAYNET-DEMO-0003', 'status' => 'paid'],
                'paid_at' => now()->subDays(2),
            ],
        ];
    }
}
