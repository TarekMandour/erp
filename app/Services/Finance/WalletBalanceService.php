<?php

namespace App\Services\Finance;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerWallet;
use App\Models\Finance\Supplier;
use App\Models\Finance\SupplierWallet;

/**
 * مصدر واحد لحساب أرصدة المحافظ من الحركات (QA 2.6 / 11.10)
 *
 * القاعدة الموحّدة:
 *  - محفظة العميل : الرصيد = مدين − دائن  (موجب = العميل مدين لنا) — تقابل 1104
 *  - محفظة المورد : الرصيد = دائن − مدين  (موجب = نحن مدينون للمورد) — تقابل 2101
 *    (فاتورة شراء = دائن، سند صرف للمورد = مدين، سند قبض من المورد = دائن)
 *
 * يعيد حساب رصيد كل حركة بالترتيب ثم wallet_balance في جدول الطرف،
 * فلا يُعتمد أبداً على رصيد مُدخل يدوياً.
 */
class WalletBalanceService
{
    public function recalculateCustomer(int $customerId): float
    {
        $balance = 0.0;

        foreach (CustomerWallet::where('customer_id', $customerId)->orderBy('date')->orderBy('id')->get() as $entry) {
            $balance += (float) $entry->debit - (float) $entry->credit;
            if ((float) $entry->balance !== round($balance, 2)) {
                $entry->balance = $balance;
                $entry->saveQuietly();
            }
        }

        Customer::where('id', $customerId)->update(['wallet_balance' => round($balance, 2)]);

        return round($balance, 2);
    }

    public function recalculateSupplier(int $supplierId): float
    {
        $prev = 0.0;

        foreach (SupplierWallet::where('supplier_id', $supplierId)->orderBy('date')->orderBy('id')->get() as $entry) {
            $balance = $prev + (float) $entry->credit - (float) $entry->debit;
            $entry->previous_balance = $prev;
            $entry->balance          = $balance;
            if ($entry->isDirty()) {
                $entry->saveQuietly();
            }
            $prev = $balance;
        }

        Supplier::where('id', $supplierId)->update(['wallet_balance' => round($prev, 2)]);

        return round($prev, 2);
    }
}
