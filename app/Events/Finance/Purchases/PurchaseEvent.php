<?php

namespace App\Events\Finance\Purchases;

use App\Models\Finance\Purchase;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Purchase $purchase,
        public string $action = 'create') {}
}
