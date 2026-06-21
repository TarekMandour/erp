<?php

namespace App\Events\Finance\Vouchers;

use App\Models\Finance\Voucher;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoucherEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Voucher $voucher,
        public string $action = 'create') {}
}