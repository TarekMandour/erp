<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Finance\CategoryController;
use App\Http\Controllers\Finance\{
    ProductController,
    ProductVariantController,
    InventoryController,
    UnitConversionController
};

/*
|--------------------------------------------------------------------------
| Finance Routes
|--------------------------------------------------------------------------
|
| Here is where you can register finance routes for your application.
| These routes are loaded by the RouteServiceProvider.
|
*/
Route::group(['middleware' => ['admin']], function () {
    Route::name('category.')->prefix('category')->group(function () {
        Route::get('/', 'CategoryController@index')->name('index');
        Route::get('/export', 'CategoryController@export')->name('export');
        Route::get('/show/{id}', 'CategoryController@show')->name('show');
        Route::post('/delete', 'CategoryController@destroy')->name('delete');
        Route::get('/create', 'CategoryController@create')->name('create');
        Route::post('/store', 'CategoryController@store')->name('store');
        Route::get('/edit/{id}', 'CategoryController@edit')->name('edit');
        Route::post('/update', 'CategoryController@update')->name('update');
        Route::get('/search', 'CategoryController@ajaxSearch')->name('ajax.search');
    });

    Route::name('brands.')->prefix('brands')->group(function () {
        Route::get('/', 'BrandsController@index')->name('index');
        Route::get('/export', 'BrandsController@export')->name('export');
        Route::get('/show/{id}', 'BrandsController@show')->name('show');
        Route::post('/delete', 'BrandsController@destroy')->name('delete');
        Route::get('/create', 'BrandsController@create')->name('create');
        Route::post('/store', 'BrandsController@store')->name('store');
        Route::get('/edit/{id}', 'BrandsController@edit')->name('edit');
        Route::post('/update', 'BrandsController@update')->name('update');
    });

    Route::name('units.')->prefix('units')->group(function () {
        Route::get('/', 'UnitsController@index')->name('index');
        Route::get('/export', 'UnitsController@export')->name('export');
        Route::get('/show/{id}', 'UnitsController@show')->name('show');
        Route::post('/delete', 'UnitsController@destroy')->name('delete');
        Route::get('/create', 'UnitsController@create')->name('create');
        Route::post('/store', 'UnitsController@store')->name('store');
        Route::get('/edit/{id}', 'UnitsController@edit')->name('edit');
        Route::post('/update', 'UnitsController@update')->name('update');
    });

    Route::name('attributes.')->prefix('attributes')->group(function () {
            Route::get('/', 'AttributesController@index')->name('index');
            Route::get('/export', 'AttributesController@export')->name('export');
            Route::get('/show/{id}', 'AttributesController@show')->name('show');
            Route::post('/delete', 'AttributesController@destroy')->name('delete');
            Route::get('/create', 'AttributesController@create')->name('create');
            Route::post('/store', 'AttributesController@store')->name('store');
            Route::get('/edit/{id}', 'AttributesController@edit')->name('edit');
            Route::post('/update', 'AttributesController@update')->name('update');
    });

    Route::name('products.')->prefix('products')->group(function () {
        Route::get('/', 'ProductsController@index')->name('index');
        Route::get('/export', 'ProductsController@export')->name('export');
        Route::get('/show/{id}', 'ProductsController@show')->name('show');
        Route::post('/delete', 'ProductsController@destroy')->name('delete');
        Route::get('/create', 'ProductsController@create')->name('create');
        Route::post('/store', 'ProductsController@store')->name('store');
        Route::get('/edit/{id}', 'ProductsController@edit')->name('edit');
        Route::post('/update', 'ProductsController@update')->name('update');

        // Variant AJAX endpoints
        Route::get('/{id}/generate-sku', 'ProductsController@generateSku')->name('generate-sku');
        Route::get('/search', 'ProductsController@ajaxSearch')->name('ajax.search');

        // Product Variants Routes
        Route::prefix('{productId}/variants')->name('variants.')->group(function () {
            Route::get('/', 'ProductVariantController@index')->name('index');
            Route::get('/create', 'ProductVariantController@create')->name('create');
            Route::post('/store', 'ProductVariantController@store')->name('store');
            Route::get('/edit/{id}', 'ProductVariantController@edit')->name('edit');
            Route::post('/update', 'ProductVariantController@update')->name('update');
            Route::post('/delete', 'ProductVariantController@destroy')->name('delete');

            // Variant AJAX endpoints 
            Route::get('/generate-sku', [ProductVariantController::class, 'generateSku'])->name('generate-sku');
        });

        Route::get('/list', [ProductVariantController::class, 'list'])->name('list');

        // Unit Conversions Routes
        Route::name('unitconversions.')->prefix('unitconversions')->group(function () {
            Route::get('/', 'UnitConversionController@index')->name('index');
            Route::get('/export', 'UnitConversionController@export')->name('export');
            Route::get('/show/{id}', 'UnitConversionController@show')->name('show');
            Route::post('/delete', 'UnitConversionController@destroy')->name('delete');
            Route::get('/create', 'UnitConversionController@create')->name('create');
            Route::post('/store', 'UnitConversionController@store')->name('store');
            Route::get('/edit/{id}', 'UnitConversionController@edit')->name('edit');
            Route::post('/update', 'UnitConversionController@update')->name('update');
        });

        Route::prefix('prices')->name('prices.')->group(function () {
            Route::get('/', 'ProductVariantPriceController@index')->name('index');
            Route::get('/create', 'ProductVariantPriceController@create')->name('create');
            Route::post('/store', 'ProductVariantPriceController@store')->name('store');
            Route::get('/edit/{id}', 'ProductVariantPriceController@edit')->name('edit');
            Route::post('/update', 'ProductVariantPriceController@update')->name('update');
            Route::post('/delete', 'ProductVariantPriceController@destroy')->name('delete');
        });

    });

    Route::name('customers.')->prefix('customers')->group(function () {
        Route::get('/', 'CustomersController@index')->name('index');
        Route::get('/export', 'CustomersController@export')->name('export');
        Route::get('/show/{id}', 'CustomersController@show')->name('show');
        Route::post('/delete', 'CustomersController@destroy')->name('delete');
        Route::get('/create', 'CustomersController@create')->name('create');
        Route::post('/store', 'CustomersController@store')->name('store');
        Route::get('/edit/{id}', 'CustomersController@edit')->name('edit');
        Route::post('/update', 'CustomersController@update')->name('update');

        // Customer Wallet Routes
        Route::prefix('{customerId}/wallet')->name('wallet.')->group(function () {
            Route::get('/', 'CustomerWalletController@index')->name('index');
            Route::get('/create', 'CustomerWalletController@create')->name('create');
            Route::post('/store', 'CustomerWalletController@store')->name('store');
            Route::get('/edit/{id}', 'CustomerWalletController@edit')->name('edit');
            Route::post('/update', 'CustomerWalletController@update')->name('update');
            Route::post('/delete', 'CustomerWalletController@destroy')->name('delete');
        });
    });

    Route::name('suppliers.')->prefix('suppliers')->group(function () {
        Route::get('/', 'SuppliersController@index')->name('index');
        Route::get('/export', 'SuppliersController@export')->name('export');
        Route::get('/show/{id}', 'SuppliersController@show')->name('show');
        Route::post('/delete', 'SuppliersController@destroy')->name('delete');
        Route::get('/create', 'SuppliersController@create')->name('create');
        Route::post('/store', 'SuppliersController@store')->name('store');
        Route::get('/edit/{id}', 'SuppliersController@edit')->name('edit');
        Route::post('/update', 'SuppliersController@update')->name('update');

        // Supplier Wallet Routes
        Route::prefix('{supplierId}/wallet')->name('wallet.')->group(function () {
            Route::get('/', 'SupplierWalletController@index')->name('index');
            Route::get('/create', 'SupplierWalletController@create')->name('create');
            Route::post('/store', 'SupplierWalletController@store')->name('store');
            Route::get('/edit/{id}', 'SupplierWalletController@edit')->name('edit');
            Route::post('/update', 'SupplierWalletController@update')->name('update');
            Route::post('/delete', 'SupplierWalletController@destroy')->name('delete');
        });
    });

    Route::name('account_trees.')->prefix('account-trees')->group(function () {
        Route::get('/', 'AccountTreeController@index')->name('index');
        Route::get('/export', 'AccountTreeController@export')->name('export');
        Route::get('/show/{id}', 'AccountTreeController@show')->name('show');
        Route::post('/delete', 'AccountTreeController@destroy')->name('delete');
        Route::get('/create', 'AccountTreeController@create')->name('create');
        Route::post('/store', 'AccountTreeController@store')->name('store');
        Route::get('/edit/{id}', 'AccountTreeController@edit')->name('edit');
        Route::post('/update', 'AccountTreeController@update')->name('update');
    });

    Route::name('trans_account_trees.')->prefix('trans-account-trees')->group(function () {
        Route::get('/', 'TransAccountTreeController@index')->name('index');
        Route::get('/export', 'TransAccountTreeController@export')->name('export');
        Route::get('/show/{id}', 'TransAccountTreeController@show')->name('show');
        Route::post('/delete', 'TransAccountTreeController@destroy')->name('delete');
        Route::get('/create', 'TransAccountTreeController@create')->name('create');
        Route::post('/store', 'TransAccountTreeController@store')->name('store');
        Route::get('/edit/{id}', 'TransAccountTreeController@edit')->name('edit');
        Route::post('/update', 'TransAccountTreeController@update')->name('update');
    });

    Route::name('banks.')->prefix('banks')->group(function () {
        Route::get('/', 'BanksController@index')->name('index');
        Route::get('/export', 'BanksController@export')->name('export');
        Route::get('/show/{id}', 'BanksController@show')->name('show');
        Route::post('/delete', 'BanksController@destroy')->name('delete');
        Route::get('/create', 'BanksController@create')->name('create');
        Route::post('/store', 'BanksController@store')->name('store');
        Route::get('/edit/{id}', 'BanksController@edit')->name('edit');
        Route::post('/update', 'BanksController@update')->name('update');
    });

    Route::name('bank_accounts.')->prefix('bank-accounts')->group(function () {
        Route::get('/', 'BankAccountsController@index')->name('index');
        Route::get('/export', 'BankAccountsController@export')->name('export');
        Route::get('/show/{id}', 'BankAccountsController@show')->name('show');
        Route::post('/delete', 'BankAccountsController@destroy')->name('delete');
        Route::get('/create', 'BankAccountsController@create')->name('create');
        Route::post('/store', 'BankAccountsController@store')->name('store');
        Route::get('/edit/{id}', 'BankAccountsController@edit')->name('edit');
        Route::post('/update', 'BankAccountsController@update')->name('update');
    });

    Route::name('bank_transactions.')->prefix('bank-transactions')->group(function () {
        Route::get('/', 'BankTransactionsController@index')->name('index');
        Route::get('/export', 'BankTransactionsController@export')->name('export');
        Route::get('/show/{id}', 'BankTransactionsController@show')->name('show');
        Route::post('/delete', 'BankTransactionsController@destroy')->name('delete');
        Route::get('/create', 'BankTransactionsController@create')->name('create');
        Route::post('/store', 'BankTransactionsController@store')->name('store');
        Route::get('/edit/{id}', 'BankTransactionsController@edit')->name('edit');
        Route::post('/update', 'BankTransactionsController@update')->name('update');
    });

    Route::name('treasuries.')->prefix('treasuries')->group(function () {
        Route::get('/', 'TreasuriesController@index')->name('index');
        Route::get('/export', 'TreasuriesController@export')->name('export');
        Route::get('/show/{id}', 'TreasuriesController@show')->name('show');
        Route::post('/delete', 'TreasuriesController@destroy')->name('delete');
        Route::get('/create', 'TreasuriesController@create')->name('create');
        Route::post('/store', 'TreasuriesController@store')->name('store');
        Route::get('/edit/{id}', 'TreasuriesController@edit')->name('edit');
        Route::post('/update', 'TreasuriesController@update')->name('update');
    });

    Route::name('treasury_transactions.')->prefix('treasury-transactions')->group(function () {
        Route::get('/', 'TreasuryTransactionsController@index')->name('index');
        Route::get('/export', 'TreasuryTransactionsController@export')->name('export');
        Route::get('/show/{id}', 'TreasuryTransactionsController@show')->name('show');
        Route::post('/delete', 'TreasuryTransactionsController@destroy')->name('delete');
        Route::get('/create', 'TreasuryTransactionsController@create')->name('create');
        Route::post('/store', 'TreasuryTransactionsController@store')->name('store');
        Route::get('/edit/{id}', 'TreasuryTransactionsController@edit')->name('edit');
        Route::post('/update', 'TreasuryTransactionsController@update')->name('update');
    });

    Route::name('opening_balances.')->prefix('opening-balances')->group(function () {
        Route::get('/', 'OpeningBalancesController@index')->name('index');
        Route::get('/export', 'OpeningBalancesController@export')->name('export');
        Route::get('/show/{id}', 'OpeningBalancesController@show')->name('show');
        Route::post('/delete', 'OpeningBalancesController@destroy')->name('delete');
        Route::get('/create', 'OpeningBalancesController@create')->name('create');
        Route::post('/store', 'OpeningBalancesController@store')->name('store');
        Route::get('/edit/{id}', 'OpeningBalancesController@edit')->name('edit');
        Route::post('/update', 'OpeningBalancesController@update')->name('update');
    });

    Route::name('vouchers.')->prefix('vouchers')->group(function () {
        Route::get('/', 'VouchersController@index')->name('index');
        Route::get('/export', 'VouchersController@export')->name('export');
        Route::get('/show/{id}', 'VouchersController@show')->name('show');
        Route::post('/delete', 'VouchersController@destroy')->name('delete');
        Route::get('/create', 'VouchersController@create')->name('create');
        Route::post('/store', 'VouchersController@store')->name('store');
        Route::get('/edit/{id}', 'VouchersController@edit')->name('edit');
        Route::post('/update', 'VouchersController@update')->name('update');
    });

    Route::name('warehouses.')->prefix('warehouses')->group(function () {
        Route::get('/', 'WarehousesController@index')->name('index');
        Route::get('/export', 'WarehousesController@export')->name('export');
        Route::get('/show/{id}', 'WarehousesController@show')->name('show');
        Route::post('/delete', 'WarehousesController@destroy')->name('delete');
        Route::get('/create', 'WarehousesController@create')->name('create');
        Route::post('/store', 'WarehousesController@store')->name('store');
        Route::get('/edit/{id}', 'WarehousesController@edit')->name('edit');
        Route::post('/update', 'WarehousesController@update')->name('update');
    });

    Route::name('inventory.')->prefix('inventory')->group(function () {
        Route::get('/', 'InventoryController@index')->name('index');
        Route::get('/export', 'InventoryController@export')->name('export');
        Route::get('/adjust', 'InventoryController@adjust')->name('adjust');
        Route::post('/store-adjust', 'InventoryController@storeAdjust')->name('store-adjust');
        Route::get('/adjust/{id}', 'InventoryController@adjust')->name('adjust-item');
        Route::get('/history/{id}', 'InventoryController@history')->name('history');
        Route::get('/transactions', 'InventoryController@transactions')->name('transactions');
        Route::get('/get-variants', 'InventoryController@getVariants')->name('get-variants');
        Route::get('/get-stock', 'InventoryController@getStock')->name('get-stock');

        Route::name('transfers.')->prefix('transfers')->group(function () {
            Route::get('/', 'InventoryTransferController@index')->name('index');
            Route::get('/export', 'InventoryTransferController@export')->name('export');
            Route::get('/create', 'InventoryTransferController@create')->name('create');
            Route::post('/store', 'InventoryTransferController@store')->name('store');
            Route::get('/show/{id}', 'InventoryTransferController@show')->name('show');
            Route::post('/cancel/{id}', 'InventoryTransferController@cancel')->name('cancel');
        });
    });

    Route::name('variant-prices.')->prefix('variant-prices')->group(function () {
        Route::get('/', 'ProductVariantPricesController@index')->name('index');
        Route::get('/create', 'ProductVariantPricesController@create')->name('create');
        Route::post('/store', 'ProductVariantPricesController@store')->name('store');
        Route::get('/edit/{id}', 'ProductVariantPricesController@edit')->name('edit');
        Route::post('/update', 'ProductVariantPricesController@update')->name('update');
        Route::post('/delete', 'ProductVariantPricesController@destroy')->name('delete');
        Route::get('/get-variants', 'ProductVariantPricesController@getVariants')->name('get-variants');
    });

    Route::name('coupons.')->prefix('coupons')->group(function () {
        Route::get('/', 'CouponsController@index')->name('index');
        Route::get('/create', 'CouponsController@create')->name('create');
        Route::post('/store', 'CouponsController@store')->name('store');
        Route::get('/edit/{id}', 'CouponsController@edit')->name('edit');
        Route::post('/update', 'CouponsController@update')->name('update');
        Route::post('/delete', 'CouponsController@destroy')->name('delete');
        Route::get('/generate-code', 'CouponsController@generateCode')->name('generate-code');
    });

    Route::name('coupon_usages.')->prefix('coupon-usages')->group(function () {
        Route::get('/', 'CouponUsagesController@index')->name('index');
        Route::get('/create', 'CouponUsagesController@create')->name('create');
        Route::post('/store', 'CouponUsagesController@store')->name('store');
        Route::post('/delete', 'CouponUsagesController@destroy')->name('delete');
    });

    Route::name('offers.')->prefix('offers')->group(function () {
        Route::get('/', 'OffersController@index')->name('index');
        Route::get('/create', 'OffersController@create')->name('create');
        Route::post('/store', 'OffersController@store')->name('store');
        Route::get('/edit/{id}', 'OffersController@edit')->name('edit');
        Route::post('/update', 'OffersController@update')->name('update');
        Route::post('/delete', 'OffersController@destroy')->name('delete');
        Route::get('/ajax-items', 'OffersController@ajaxItems')->name('ajax.items');
    });

    Route::name('purchases.')->prefix('purchases')->group(function () {
        Route::get('/', 'PurchasesController@index')->name('index');
        Route::get('/show/{id}', 'PurchasesController@show')->name('show');
        Route::get('/create', 'PurchasesController@create')->name('create');
        Route::post('/store', 'PurchasesController@store')->name('store');
        Route::get('/edit/{id}', 'PurchasesController@edit')->name('edit');
        Route::post('/update', 'PurchasesController@update')->name('update');
        Route::post('/delete', 'PurchasesController@destroy')->name('delete');
        Route::post('/import', 'PurchasesController@import')->name('import');
        Route::get('/template', 'PurchasesController@exportTemplate')->name('template');
        Route::get('/ajax-suppliers', 'PurchasesController@ajaxSuppliers')->name('ajax.suppliers');
        Route::get('/ajax-products', 'PurchasesController@ajaxProducts')->name('ajax.products');
        Route::get('/ajax-variants', 'PurchasesController@ajaxVariants')->name('ajax.variants');
    });

    Route::name('orders.')->prefix('orders')->group(function () {
        Route::get('/', 'OrdersController@index')->name('index');
        Route::get('/show/{id}', 'OrdersController@show')->name('show');
        Route::get('/create', 'OrdersController@create')->name('create');
        Route::post('/store', 'OrdersController@store')->name('store');
        Route::get('/edit/{id}', 'OrdersController@edit')->name('edit');
        Route::post('/update', 'OrdersController@update')->name('update');
        Route::post('/delete', 'OrdersController@destroy')->name('delete');
        Route::post('/import', 'OrdersController@import')->name('import');
        Route::get('/template', 'OrdersController@exportTemplate')->name('template');
        Route::get('/ajax-customers', 'OrdersController@ajaxCustomers')->name('ajax.customers');
        Route::get('/ajax-products', 'OrdersController@ajaxProducts')->name('ajax.products');
        Route::get('/ajax-variants', 'OrdersController@ajaxVariants')->name('ajax.variants');
        Route::get('/ajax-stock', 'OrdersController@ajaxStock')->name('ajax.stock');
        Route::get('/ajax-scan', 'OrdersController@ajaxScanProduct')->name('ajax.scan');
        Route::get('/ajax-units', 'OrdersController@ajaxUnitConversions')->name('ajax.units');
        Route::get('/ajax-coupon', 'OrdersController@ajaxValidateCoupon')->name('ajax.coupon');
        Route::get('/ajax-offers', 'OrdersController@ajaxSearchOffers')->name('ajax.offers');
        Route::get('/ajax-apply-offer', 'OrdersController@ajaxApplyOffer')->name('ajax.apply_offer');
    });

});

    


// Products Routes
// Route::prefix('products')->name('products.')->group(function () {
//     Route::get('/', [ProductController::class, 'index'])->name('index');
//     Route::get('/create', [ProductController::class, 'create'])->name('create');
//     Route::post('/', [ProductController::class, 'store'])->name('store');
//     Route::get('/{id}', [ProductController::class, 'show'])->name('show');
//     Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
//     Route::put('/{id}', [ProductController::class, 'update'])->name('update');
//     Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');

//     // Product Variants Routes
//     Route::prefix('{productId}/variants')->name('variants.')->group(function () {
//         Route::get('/create', [ProductVariantController::class, 'create'])->name('create');
//         Route::post('/', [ProductVariantController::class, 'store'])->name('store');
//         Route::get('/{variantId}/edit', [ProductVariantController::class, 'edit'])->name('edit');
//         Route::put('/{variantId}', [ProductVariantController::class, 'update'])->name('update');
//         Route::delete('/{variantId}', [ProductVariantController::class, 'destroy'])->name('destroy');

//         // Variant AJAX endpoints
//         Route::get('/{variantId}/show', [ProductVariantController::class, 'show'])->name('show');
//         Route::post('/{variantId}/stock', [ProductVariantController::class, 'updateStock'])->name('update-stock');
//         Route::get('/generate-sku', [ProductVariantController::class, 'generateSku'])->name('generate-sku');
//     });

//     // Product AJAX endpoints
//     Route::get('/{id}/generate-sku', [ProductController::class, 'generateSku'])->name('generate-sku');
//     Route::post('/{id}/update-prices', [ProductController::class, 'updatePrices'])->name('update-prices');
//     Route::post('/import', [ProductController::class, 'import'])->name('import');
//     Route::get('/export', [ProductController::class, 'export'])->name('export');
//     Route::get('/top-selling', [ProductController::class, 'topSelling'])->name('top-selling');
//     Route::get('/analytics', [ProductController::class, 'analytics'])->name('analytics');
//     Route::post('/{id}/restore', [ProductController::class, 'restore'])->name('restore');
// });

// Inventory Routes — moved inside middleware group above
// (old routes kept as comments for reference)
/*
Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::get('/transactions', [InventoryController::class, 'transactions'])->name('transactions');
    Route::get('/report', [InventoryController::class, 'report'])->name('report');
    Route::post('/stocktake', [InventoryController::class, 'stocktake'])->name('stocktake');
    Route::get('/low-stock-alerts', [InventoryController::class, 'lowStockAlerts'])->name('low-stock-alerts');
    Route::get('/out-of-stock', [InventoryController::class, 'outOfStock'])->name('out-of-stock');
});

// Unit Conversions Routes
Route::prefix('unit-conversions')->name('unit-conversions.')->group(function () {
    Route::get('/', [UnitConversionController::class, 'index'])->name('index');
    Route::post('/', [UnitConversionController::class, 'store'])->name('store');
    Route::post('/convert', [UnitConversionController::class, 'convert'])->name('convert');
    Route::post('/convert-to-base', [UnitConversionController::class, 'convertToBaseUnit'])->name('convert-to-base');
    Route::get('/conversions-for-unit', [UnitConversionController::class, 'getConversionsForUnit'])->name('conversions-for-unit');
    Route::post('/{id}/update', [UnitConversionController::class, 'update'])->name('update');
    Route::delete('/{id}', [UnitConversionController::class, 'destroy'])->name('destroy');
    Route::get('/{productId}/suggest', [UnitConversionController::class, 'suggestConversions'])->name('suggest');
});
*/
