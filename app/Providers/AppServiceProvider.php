<?php

namespace App\Providers;

use App\Events\Finance\Orders\OrderEvent;
use App\Events\Finance\Purchases\PurchaseEvent;
use App\Listeners\Orders\PostOrderJournalEntry;
use App\Listeners\Purchases\PostPurchaseJournalEntry;
use App\Models\Finance\JournalEntryItem;
use App\Models\Finance\Order;
use App\Models\Finance\Purchase;
use App\Models\Finance\Voucher;
use App\Observers\JournalEntryItemObserver;
use App\Observers\OrderObserver;
use App\Observers\PurchaseObserver;
use App\Observers\VoucherObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (!session()->get('locale')) {
            session()->put('locale', 'ar');
        }

        // ── Observers ────────────────────────────────────────────────────────
        Voucher::observe(VoucherObserver::class);
        Order::observe(OrderObserver::class);
        Purchase::observe(PurchaseObserver::class);
        JournalEntryItem::observe(JournalEntryItemObserver::class);

        // ── Events → Listeners ───────────────────────────────────────────────
        Event::listen(OrderEvent::class,    PostOrderJournalEntry::class);
        Event::listen(PurchaseEvent::class, PostPurchaseJournalEntry::class);
    }
}

