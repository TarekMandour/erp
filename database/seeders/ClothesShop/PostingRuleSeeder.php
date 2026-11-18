<?php

namespace Database\Seeders\ClothesShop;

use Illuminate\Database\Seeder;
use App\Models\Finance\PostingScenario;
use App\Models\Finance\PostingRule;
use App\Models\Finance\AccountTree;
use Illuminate\Support\Facades\DB;

class PostingRuleSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        PostingRule::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ─── حسابات الدليل المحاسبي ─────────────────────────────────────
        $cash          = AccountTree::where('code', '1101')->first(); // الصندوق
        $bank          = AccountTree::where('code', '1102')->first(); // البنك
        $inventory     = AccountTree::where('code', '1103')->first(); // المخزون
        $customers     = AccountTree::where('code', '1104')->first(); // العملاء
        $wallet        = AccountTree::where('code', '1106')->first(); // المحافظ الإلكترونية
        $bankOnline    = AccountTree::where('code', '1107')->first(); // البنك - أونلاين
        $bankDirect    = AccountTree::where('code', '1108')->first(); // البنك - مباشر

        // ملاحظة: هذا هو الحساب الموجود حالياً في الدليل باسم "شيكات مستحقة الدفع".
        // يستخدم في قواعد الشيكات الحالية كما في Seeder السابق.
        $checkPayable  = AccountTree::where('code', '2105')->first();
        $checkReceivable = AccountTree::where('code', '1109')->first(); // أوراق قبض – شيكات تحت التحصيل

        $suppliers     = AccountTree::where('code', '2101')->first(); // الموردون
        $vatPayable    = AccountTree::where('code', '2102')->first(); // ضريبة القيمة المضافة
        $salesRev      = AccountTree::where('code', '4001')->first(); // إيرادات المبيعات
        $otherRev      = AccountTree::where('code', '4002')->first(); // إيرادات أخرى
        $cogs          = AccountTree::where('code', '5001')->first(); // تكلفة البضاعة المباعة
        $miscExp       = AccountTree::where('code', '5007')->first(); // مصروفات متنوعة
        $discountGiven = AccountTree::where('code', '5008')->first(); // خصم ممنوح
        $shippingRev   = AccountTree::where('code', '4004')->first(); // إيرادات الشحن
        $couponExp     = AccountTree::where('code', '5009')->first(); // خصم كوبونات
        $offerExp      = AccountTree::where('code', '5010')->first(); // خصم عروض

        $rules = [];

        // ================================================================
        // المشتريات - الاحتفاظ بالقواعد الحالية
        // ================================================================

        // قواعد موحدة لكل أنواع الدفع (QA 12.4 / 12.5):
        //   مدين المخزون (subtotal صافٍ بعد الخصم) + مدين الضريبة
        //   دائن حساب الدفع بالمبلغ المدفوع (paid) + دائن الموردين بالباقي (total - paid)
        $purchasePaymentAccounts = [
            'PURCHASE_CASH'         => $cash?->id,
            'PURCHASE_CREDIT'       => $cash?->id,
            'PURCHASE_INSTALLMENTS' => $cash?->id,
            'PURCHASE_WALLET'       => $wallet?->id,
            'PURCHASE_BANK_ONLINE'  => $bankOnline?->id,
            'PURCHASE_BANK_DIRECT'  => $bankDirect?->id,
            'PURCHASE_CHECK'        => $checkPayable?->id,
        ];

        foreach ($purchasePaymentAccounts as $code => $payAccountId) {
            $scenario = PostingScenario::where('code', $code)->first();
            if (! $scenario) continue;

            $rules[] = ['scenario_id'=>$scenario->id,'rule_group'=>1,'debit_account_id'=>$inventory?->id,'credit_account_id'=>null,'amount_type'=>'subtotal','amount_field'=>'subtotal','sort_order'=>1,'is_required'=>1];
            $rules[] = ['scenario_id'=>$scenario->id,'rule_group'=>1,'debit_account_id'=>$vatPayable?->id,'credit_account_id'=>null,'amount_type'=>'tax','amount_field'=>'tax','sort_order'=>2,'is_required'=>1];
            $rules[] = ['scenario_id'=>$scenario->id,'rule_group'=>1,'debit_account_id'=>null,'credit_account_id'=>$payAccountId,'amount_type'=>'total','amount_field'=>'paid','sort_order'=>3,'is_required'=>1];
            $rules[] = ['scenario_id'=>$scenario->id,'rule_group'=>1,'debit_account_id'=>null,'credit_account_id'=>$suppliers?->id,'amount_type'=>'formula','formula'=>'total - paid','sort_order'=>4,'is_required'=>1];
        }

        // مرتجعات المشتريات
        $purchaseReturns = [
            'PURCHASE_RETURN_CASH' => ['debit'=>$cash?->id, 'paid_account'=>null],
            'PURCHASE_RETURN_CREDIT' => ['debit'=>$suppliers?->id, 'paid_account'=>null],
            'PURCHASE_RETURN_INSTALLMENTS' => ['debit'=>$suppliers?->id, 'paid_account'=>null],
            'PURCHASE_RETURN_WALLET' => ['debit'=>$wallet?->id, 'paid_account'=>$wallet?->id],
            'PURCHASE_RETURN_BANK_ONLINE' => ['debit'=>$bankOnline?->id, 'paid_account'=>$bankOnline?->id],
            'PURCHASE_RETURN_BANK_DIRECT' => ['debit'=>$bankDirect?->id, 'paid_account'=>$bankDirect?->id],
            'PURCHASE_RETURN_CHECK' => ['debit'=>$checkPayable?->id, 'paid_account'=>$checkPayable?->id],
        ];

        foreach ($purchaseReturns as $code => $map) {
            $scenario = PostingScenario::where('code', $code)->first();
            if (!$scenario) continue;

            if ($code === 'PURCHASE_RETURN_CASH' || $code === 'PURCHASE_RETURN_CREDIT' || $code === 'PURCHASE_RETURN_INSTALLMENTS') {
                $rules[] = ['scenario_id'=>$scenario->id,'rule_group'=>1,'debit_account_id'=>$map['debit'],'credit_account_id'=>null,'amount_type'=>'total','amount_field'=>'total','sort_order'=>1,'is_required'=>1];
                $rules[] = ['scenario_id'=>$scenario->id,'rule_group'=>1,'debit_account_id'=>null,'credit_account_id'=>$inventory?->id,'amount_type'=>'subtotal','amount_field'=>'subtotal','sort_order'=>2,'is_required'=>1];
                $rules[] = ['scenario_id'=>$scenario->id,'rule_group'=>1,'debit_account_id'=>null,'credit_account_id'=>$vatPayable?->id,'amount_type'=>'tax','amount_field'=>'tax','sort_order'=>3,'is_required'=>1];
            } else {
                $rules[] = ['scenario_id'=>$scenario->id,'rule_group'=>1,'debit_account_id'=>$map['paid_account'],'credit_account_id'=>null,'amount_type'=>'total','amount_field'=>'paid','sort_order'=>1,'is_required'=>1];
                $rules[] = ['scenario_id'=>$scenario->id,'rule_group'=>1,'debit_account_id'=>$suppliers?->id,'credit_account_id'=>null,'amount_type'=>'formula','formula'=>'total - paid','sort_order'=>2,'is_required'=>1];
                $rules[] = ['scenario_id'=>$scenario->id,'rule_group'=>1,'debit_account_id'=>null,'credit_account_id'=>$inventory?->id,'amount_type'=>'subtotal','amount_field'=>'subtotal','sort_order'=>3,'is_required'=>1];
                $rules[] = ['scenario_id'=>$scenario->id,'rule_group'=>1,'debit_account_id'=>null,'credit_account_id'=>$vatPayable?->id,'amount_type'=>'tax','amount_field'=>'tax','sort_order'=>4,'is_required'=>1];
            }
        }

        // ================================================================
        // طلبات المتجر الإلكتروني (Order)
        // المجموعة 1: الإيراد + الشحن + الخصومات (خصم/كوبون/عرض) + الضريبة
        //   ح/ وسيلة التحصيل (paid)              ح/ العملاء (total - paid عند الآجل/التقسيط)
        //   ح/ خصم ممنوح (discount)
        //   ح/ خصم كوبونات (coupon_discount)
        //   ح/ خصم عروض (offer_discount)
        //       ح/ إيرادات المبيعات (subtotal)
        //       ح/ إيرادات الشحن (shipping_cost)
        //       ح/ ضريبة القيمة المضافة (tax)
        // المجموعة 2: تكلفة البضاعة المباعة
        // ================================================================

        $orderPaymentAccounts = [
            // QA 6: كل الأنواع: المحصّل على حساب التحصيل والباقي على العملاء (الآجل: العربون نقداً)
            'ORDER_CASH' => $cash?->id,
            'ORDER_CREDIT' => $cash?->id,
            'ORDER_INSTALLMENTS' => $cash?->id,
            'ORDER_WALLET' => $wallet?->id,
            'ORDER_BANK_ONLINE' => $bankOnline?->id,
            'ORDER_BANK_DIRECT' => $bankDirect?->id,
            'ORDER_CHECK' => $checkReceivable?->id, // أوراق قبض (كان أوراق دفع)
        ];

        foreach ($orderPaymentAccounts as $code => $paymentAccountId) {
            $scenario = PostingScenario::where('code', $code)->first();
            if (!$scenario) continue;

            {
                $rules[] = [
                    'scenario_id'=>$scenario->id,'rule_group'=>1,
                    'debit_account_id'=>$paymentAccountId,'credit_account_id'=>null,
                    'amount_type'=>'total','amount_field'=>'paid',
                    'sort_order'=>1,'is_required'=>1
                ];
                $rules[] = [
                    'scenario_id'=>$scenario->id,'rule_group'=>1,
                    'debit_account_id'=>$customers?->id,'credit_account_id'=>null,
                    'amount_type'=>'formula','formula'=>'total - paid',
                    'sort_order'=>2,'is_required'=>0
                ];
            }

            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>1,
                'debit_account_id'=>$discountGiven?->id,'credit_account_id'=>null,
                'amount_type'=>'total','amount_field'=>'discount',
                'sort_order'=>3,'is_required'=>0
            ];
            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>1,
                'debit_account_id'=>$couponExp?->id,'credit_account_id'=>null,
                'amount_type'=>'total','amount_field'=>'coupon_discount',
                'sort_order'=>4,'is_required'=>0
            ];
            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>1,
                'debit_account_id'=>$offerExp?->id,'credit_account_id'=>null,
                'amount_type'=>'total','amount_field'=>'offer_discount',
                'sort_order'=>5,'is_required'=>0
            ];

            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>1,
                'debit_account_id'=>null,'credit_account_id'=>$salesRev?->id,
                'amount_type'=>'formula','amount_field'=>null,'formula'=>'subtotal + discount', // QA 6: الإيراد الإجمالي قبل خصم البنود
                'sort_order'=>6,'is_required'=>1
            ];
            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>1,
                'debit_account_id'=>null,'credit_account_id'=>$shippingRev?->id,
                'amount_type'=>'total','amount_field'=>'shipping_cost',
                'sort_order'=>7,'is_required'=>0
            ];
            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>1,
                'debit_account_id'=>null,'credit_account_id'=>$vatPayable?->id,
                'amount_type'=>'tax','amount_field'=>'tax',
                'sort_order'=>8,'is_required'=>1
            ];

            // تكلفة البضاعة المباعة
            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>2,
                'debit_account_id'=>$cogs?->id,'credit_account_id'=>null,
                'amount_type'=>'quantity_cost','amount_field'=>null,
                'sort_order'=>1,'is_required'=>1
            ];
            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>2,
                'debit_account_id'=>null,'credit_account_id'=>$inventory?->id,
                'amount_type'=>'quantity_cost','amount_field'=>null,
                'sort_order'=>2,'is_required'=>1
            ];
        }

        // ================================================================
        // مرتجعات طلبات المتجر الإلكتروني (Order)
        // عكس القيد الأصلي: عكس الإيراد + الشحن + الضريبة + الخصومات
        // ثم رد المبلغ للعميل/وسيلة الدفع + إعادة المخزون
        // ================================================================

        $orderReturnPaymentAccounts = [
            'ORDER_RETURN_CASH' => $cash?->id,
            'ORDER_RETURN_CREDIT' => $cash?->id,
            'ORDER_RETURN_INSTALLMENTS' => $cash?->id,
            'ORDER_RETURN_WALLET' => $wallet?->id,
            'ORDER_RETURN_BANK_ONLINE' => $bankOnline?->id,
            'ORDER_RETURN_BANK_DIRECT' => $bankDirect?->id,
            'ORDER_RETURN_CHECK' => $checkReceivable?->id,
        ];

        foreach ($orderReturnPaymentAccounts as $code => $paymentAccountId) {
            $scenario = PostingScenario::where('code', $code)->first();
            if (!$scenario) continue;

            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>1,
                'debit_account_id'=>$salesRev?->id,'credit_account_id'=>null,
                'amount_type'=>'formula','amount_field'=>null,'formula'=>'subtotal + discount',
                'sort_order'=>1,'is_required'=>1
            ];
            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>1,
                'debit_account_id'=>$shippingRev?->id,'credit_account_id'=>null,
                'amount_type'=>'total','amount_field'=>'shipping_cost',
                'sort_order'=>2,'is_required'=>0
            ];
            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>1,
                'debit_account_id'=>$vatPayable?->id,'credit_account_id'=>null,
                'amount_type'=>'tax','amount_field'=>'tax',
                'sort_order'=>3,'is_required'=>1
            ];

            // عكس الخصومات الأصلية (خصم/كوبون/عرض)
            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>1,
                'debit_account_id'=>null,'credit_account_id'=>$discountGiven?->id,
                'amount_type'=>'total','amount_field'=>'discount',
                'sort_order'=>4,'is_required'=>0
            ];
            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>1,
                'debit_account_id'=>null,'credit_account_id'=>$couponExp?->id,
                'amount_type'=>'total','amount_field'=>'coupon_discount',
                'sort_order'=>5,'is_required'=>0
            ];
            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>1,
                'debit_account_id'=>null,'credit_account_id'=>$offerExp?->id,
                'amount_type'=>'total','amount_field'=>'offer_discount',
                'sort_order'=>6,'is_required'=>0
            ];

            {
                $rules[] = [
                    'scenario_id'=>$scenario->id,'rule_group'=>1,
                    'debit_account_id'=>null,'credit_account_id'=>$paymentAccountId,
                    'amount_type'=>'total','amount_field'=>'paid',
                    'sort_order'=>7,'is_required'=>1
                ];
                $rules[] = [
                    'scenario_id'=>$scenario->id,'rule_group'=>1,
                    'debit_account_id'=>null,'credit_account_id'=>$customers?->id,
                    'amount_type'=>'formula','formula'=>'total - paid',
                    'sort_order'=>8,'is_required'=>0
                ];
            }

            // إعادة المخزون
            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>2,
                'debit_account_id'=>$inventory?->id,'credit_account_id'=>null,
                'amount_type'=>'quantity_cost','amount_field'=>null,
                'sort_order'=>1,'is_required'=>1
            ];
            $rules[] = [
                'scenario_id'=>$scenario->id,'rule_group'=>2,
                'debit_account_id'=>null,'credit_account_id'=>$cogs?->id,
                'amount_type'=>'quantity_cost','amount_field'=>null,
                'sort_order'=>2,'is_required'=>1
            ];
        }

        // ================================================================
        // السندات - يجب أن تطابق الأكواد التي يبنيها ScenarioResolver
        // VOUCHER_{RECEIPT|PAYMENT}_{PAYMENT_TYPE}_{PARTY_TYPE}
        // لا نستخدم account_id = NULL في أي قاعدة ثابتة.
        // للطرف الآخر نستخدم حساب الإيرادات الأخرى/المصروفات المتنوعة،
        // لأن TransactionsService الحالي لا يقرأ voucher_details من conditions.
        // ================================================================

        $voucherPaymentAccounts = [
            'CASH' => $cash?->id,
            'CREDIT' => $bank?->id,
            'INSTALLMENTS' => $cash?->id, // تحصيل/سداد قسط = حركة نقدية (قابلة للتغيير بالبند المالي)
            'WALLET' => $wallet?->id,
            'BANK_ONLINE' => $bankOnline?->id,
            'BANK_DIRECT' => $bankDirect?->id,
            'CHECK' => $checkPayable?->id,
        ];

        foreach ($voucherPaymentAccounts as $paymentCode => $paymentAccountId) {
            foreach (['CUSTOMER', 'SUPPLIER', 'OTHER'] as $partyCode) {
                // -------------------- سند قبض --------------------
                $scenario = PostingScenario::where('code', "VOUCHER_RECEIPT_{$paymentCode}_{$partyCode}")->first();
                if ($scenario) {
                    $creditAccountId = match ($partyCode) {
                        'CUSTOMER' => $customers?->id,
                        'SUPPLIER' => $suppliers?->id,
                        default    => $otherRev?->id,
                    };
                    $rules[] = [
                        'scenario_id'=>$scenario->id,'rule_group'=>1,'debit_account_id'=>($paymentCode === 'CHECK' ? $checkReceivable?->id : $paymentAccountId),'credit_account_id'=>null,'amount_type'=>'total','amount_field'=>'total_amount','sort_order'=>1,'is_required'=>1
                    ];
                    $rules[] = [
                        'scenario_id'=>$scenario->id,'rule_group'=>1,'debit_account_id'=>null,'credit_account_id'=>$creditAccountId,'amount_type'=>'total','amount_field'=>'total_amount','sort_order'=>2,'is_required'=>1
                    ];
                }

                // -------------------- سند صرف --------------------
                $scenario = PostingScenario::where('code', "VOUCHER_PAYMENT_{$paymentCode}_{$partyCode}")->first();
                if ($scenario) {
                    $debitAccountId = match ($partyCode) {
                        'CUSTOMER' => $customers?->id,
                        'SUPPLIER' => $suppliers?->id,
                        default    => $miscExp?->id,
                    };
                    $rules[] = [
                        'scenario_id'=>$scenario->id,'rule_group'=>1,'debit_account_id'=>$debitAccountId,'credit_account_id'=>null,'amount_type'=>'total','amount_field'=>'total_amount','sort_order'=>1,'is_required'=>1
                    ];
                    $rules[] = [
                        'scenario_id'=>$scenario->id,'rule_group'=>1,'debit_account_id'=>null,'credit_account_id'=>$paymentAccountId,'amount_type'=>'total','amount_field'=>'total_amount','sort_order'=>2,'is_required'=>1
                    ];
                }
            }
        }
        // ================================================================
        // الأرصدة الافتتاحية
        // OPENING_BALANCE يعمل ديناميكياً ولا يحتاج قواعد ثابتة.
        // ================================================================

        foreach ($rules as $rule) {
            PostingRule::create($rule);
        }

        $this->command->info(
            'PostingRule seeded successfully! Total: ' . count($rules)
        );
    }
}
