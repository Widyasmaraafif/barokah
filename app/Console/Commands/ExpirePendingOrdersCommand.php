<?php

namespace App\Console\Commands;

use App\Jobs\ExpirePendingOrders;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:expire-pending')]
#[Description('Expire pending_payment orders past expired_at and restore reserved stock (spec §14.5).')]
class ExpirePendingOrdersCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ExpirePendingOrders $job): int
    {
        $expired = $job->handle();

        $this->info("Expired {$expired} pending order(s).");

        return self::SUCCESS;
    }
}
