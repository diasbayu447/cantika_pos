<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\OperationalExpenseController;
use App\Http\Controllers\AnalyticsController;


/*
|--------------------------------------------------------------------------
| 1. RUTE PUBLIK (GUEST)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| 2. RUTE TERPROTEKSI (AUTH)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // --- Rute Global untuk Semua Role yang Login ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::post('/unlock-screen', [UserController::class, 'unlockScreen'])->name('screen.unlock');

    // --- Khusus Owner & Supervisor (Akses Audit, Laporan & User) ---
    Route::middleware('role:owner,supervisor')->group(function () {
        //edit profil toko
        Route::post('/store-settings/update', [UserController::class, 'updateStoreSetting'])->name('store_settings.update');

        // Kelola Staf
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/{id}/update', [UserController::class, 'update'])->name('users.update');
        // RUTE LENGKAP MANAJEMEN USER & STAF
        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::post('/users/{id}/kick', [\App\Http\Controllers\UserController::class, 'kickSession'])->name('users.kick');

        // Audit Trail & Laporan Finansial
        Route::get('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity_logs.index');
        Route::post('/activity-logs/{id}/rollback', [\App\Http\Controllers\ActivityLogController::class, 'rollback'])->name('activity_logs.rollback');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

        // Rute Biaya Operasional Toko
        Route::get('/operational-expenses', [OperationalExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/operational-expenses', [OperationalExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('/operational-expenses/{id}', [OperationalExpenseController::class, 'destroy'])->name('expenses.destroy');

        // Analitik Toko (Barang Mati & Jam Sibuk)
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    });

    // --- Khusus Owner, Admin, dan Supervisor (Manajemen Operasional & Stok) ---
    Route::middleware('role:owner,admin,supervisor')->group(function () {
        // Backup Database
        Route::get('/backup-database', [BackupController::class, 'download'])->name('backup.download');

        // Kelola Customer
        Route::resource('customers', \App\Http\Controllers\CustomerController::class);

        // Master Barang & Kategori
        // 1. Rute Khusus Produk (Wajib ditaruh DI ATAS Route::resource)
        Route::get('/products/generate-barcode', [\App\Http\Controllers\ProductController::class, 'generateBarcode'])->name('products.generate_barcode');
        Route::get('/products/export', [\App\Http\Controllers\ProductController::class, 'exportCsv'])->name('products.export');
        Route::get('/products/template', [\App\Http\Controllers\ProductController::class, 'downloadTemplate'])->name('products.template');
        Route::post('/products/import', [\App\Http\Controllers\ProductController::class, 'importCsv'])->name('products.import');
        Route::post('/products/{id}/toggle', [\App\Http\Controllers\ProductController::class, 'toggleStatus'])->name('products.toggle');
        Route::get('/products/{id}/history', [\App\Http\Controllers\ProductController::class, 'history'])->name('products.history');
        Route::post('/products/quick-store', [\App\Http\Controllers\ProductController::class, 'quickStore'])->name('products.quick_store');
        Route::post('/products/bulk-update-prices', [\App\Http\Controllers\ProductController::class, 'bulkUpdatePrices'])->name('products.bulk_update_prices');

        // 2. Resource Produk
        Route::resource('products', \App\Http\Controllers\ProductController::class)->except(['show', 'create']);

        // 3. Resource Kategori (Untuk Modal Kelola Kategori AJAX)
        Route::resource('categories', \App\Http\Controllers\CategoryController::class)->except(['create', 'show', 'edit']);

        //barcode
        Route::get('/barcodes', [BarcodeController::class, 'index'])->name('barcodes.index');
        Route::post('/barcodes/print', [BarcodeController::class, 'print'])->name('barcodes.print');

        // Supplier & Kulakan
        Route::resource('suppliers', SupplierController::class);
        Route::post('/suppliers/{id}/toggle', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle');
        
        // Rute Khusus Pembelian (Wajib ditaruh DI ATAS Route::resource)
        Route::get('/purchases/debts', [PurchaseController::class, 'debts'])->name('purchases.debts');
        Route::post('/purchases/{id}/pay-debt', [PurchaseController::class, 'payDebt'])->name('purchases.pay_debt');
        Route::post('/purchases/quick-supplier', [PurchaseController::class, 'quickSupplier'])->name('purchases.quick_supplier');
        Route::post('/purchases/quick-product', [PurchaseController::class, 'quickProduct'])->name('purchases.quick_product');
        Route::get('/purchases/search-product', [PurchaseController::class, 'searchProduct'])->name('purchases.search_product');
        
        // Resource Pembelian (Sudah otomatis menangani index, create, store, show, edit, update, destroy)
        Route::resource('purchases', PurchaseController::class);

        // Stock Opname
        Route::resource('stock-adjustments', StockAdjustmentController::class);

        // Manajemen Diskon & Voucher
        Route::resource('discounts', DiscountController::class);
        Route::post('/discounts/{id}/toggle', [DiscountController::class, 'toggleStatus'])->name('discounts.toggle');
        Route::get('/discounts/{id}/codes', [DiscountController::class, 'showCodes'])->name('discounts.codes');
        Route::get('/discounts/{id}/print', [\App\Http\Controllers\DiscountController::class, 'printVouchers'])->name('discounts.print');

        // Impor & Ekspor Barang
        Route::get('/products-export', [ProductController::class, 'exportCsv'])->name('products.export');
        Route::get('/products-template', [ProductController::class, 'downloadTemplate'])->name('products.template');
        Route::post('/products-import', [ProductController::class, 'importCsv'])->name('products.import');

        // Modul Retur Barang
        Route::get('/returns', [ReturnController::class, 'index'])->name('returns.index');
        Route::get('/returns/sale/create', [ReturnController::class, 'createSaleReturn'])->name('returns.sale.create');
        Route::post('/returns/sale/search-trx', [ReturnController::class, 'searchTransaction'])->name('returns.sale.search_trx');
        Route::post('/returns/sale', [ReturnController::class, 'storeSaleReturn'])->name('returns.sale.store');

        Route::get('/returns/purchase/create', [ReturnController::class, 'createPurchaseReturn'])->name('returns.purchase.create');
        Route::post('/returns/purchase', [ReturnController::class, 'storePurchaseReturn'])->name('returns.purchase.store');
    });

    // --- Khusus Kasir, Supervisor, dan Owner (Area Mesin POS) ---
    Route::middleware('role:owner,supervisor,cashier')->group(function () {
        
        // Buka Shift Kasir (Bebas diakses sebelum masuk POS)
        Route::get('/shift/open', [ShiftController::class, 'create'])->name('shift.create');
        Route::post('/shift/open', [ShiftController::class, 'store'])->name('shift.store');

        // Rute Layar POS (Wajib Lolos Buka Shift)
        Route::middleware('shift.open')->group(function () {
            Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
            Route::get('/pos/search', [PosController::class, 'searchProduct'])->name('pos.search');
            Route::post('/pos/add-to-cart', [PosController::class, 'addToCart'])->name('pos.add_to_cart');
            Route::post('/pos/remove-from-cart', [PosController::class, 'removeFromCart'])->name('pos.remove_from_cart');
            Route::post('/pos/update-qty', [PosController::class, 'updateQty'])->name('pos.update_qty');
            Route::post('/pos/calculate', [PosController::class, 'calculateCart'])->name('pos.calculate');
            Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
            Route::get('/pos/receipt/{id}', [PosController::class, 'receipt'])->name('pos.receipt');
            Route::post('/pos/verify-pin', [PosController::class, 'verifyPin'])->name('pos.verify_pin');
            Route::post('/pos/apply-discount', [PosController::class, 'applyDiscount'])->name('pos.apply_discount');
            Route::post('/pos/remove-discount', [PosController::class, 'removeDiscount'])->name('pos.remove_discount');
            Route::post('/pos/hold', [PosController::class, 'holdCart'])->name('pos.hold_cart');
            Route::get('/pos/held', [PosController::class, 'getHeldCarts'])->name('pos.get_held_carts');
            Route::post('/pos/recall', [PosController::class, 'recallCart'])->name('pos.recall_cart');
            Route::post('/pos/receipt/{id}/wa', [PosController::class, 'generateWaReceipt'])->name('pos.receipt_wa');
            Route::post('/pos/add-customer', [PosController::class, 'storeCustomer'])->name('pos.store_customer');
            Route::post('/pos/update-target-number', [\App\Http\Controllers\PosController::class, 'updateTargetNumber'])->name('pos.update_target_number');
            Route::post('/pos/update-price', [\App\Http\Controllers\PosController::class, 'updatePrice'])->name('pos.update_price');
            Route::post('/pos/update-pascabayar', [\App\Http\Controllers\PosController::class, 'updatePascabayar'])->name('pos.update_pascabayar');
            Route::post('/pos/clear-cart', [App\Http\Controllers\PosController::class, 'clearCart'])->name('pos.clear_cart');
            Route::get('/shift/summary', [ShiftController::class, 'summary'])->name('shift.summary');

            // Kas Keluar (Petty Cash) & Tutup Shift Kasir
            Route::post('/shift/petty-cash', [ShiftController::class, 'storePettyCash'])->name('shift.petty_cash');
            Route::post('/shift/close', [ShiftController::class, 'closeShift'])->name('shift.close');
        });
    });
});