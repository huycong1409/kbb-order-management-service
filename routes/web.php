<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ─── Auth (guest only) ────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Toàn bộ app yêu cầu đăng nhập ──────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Quản lý Shop
    Route::resource('shops', ShopController::class)->except(['show']);

    // Quản lý Sản phẩm (nested trong Shop)
    Route::prefix('shops/{shopId}/products')->name('shops.products.')->group(function () {
        Route::get('/',                                        [ProductController::class, 'index'])->name('index');
        Route::get('/create',                                  [ProductController::class, 'create'])->name('create');
        Route::post('/',                                       [ProductController::class, 'store'])->name('store');
        Route::get('/{id}/edit',                               [ProductController::class, 'edit'])->name('edit');
        Route::put('/{id}',                                    [ProductController::class, 'update'])->name('update');
        Route::delete('/{id}',                                 [ProductController::class, 'destroy'])->name('destroy');
        Route::delete('/{productId}/variants/{variantId}',     [ProductController::class, 'destroyVariant'])->name('variants.destroy');
    });

    // Đơn hàng
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/',             [OrderController::class, 'index'])->name('index');
        Route::get('/export',       [OrderController::class, 'export'])->name('export');
        Route::get('/import/form',  [OrderController::class, 'importForm'])->name('import-form');
        Route::post('/import',      [OrderController::class, 'import'])->name('import');
        Route::get('/{id}',         [OrderController::class, 'show'])->name('show');
        Route::get('/{id}/preview', [OrderController::class, 'preview'])->name('preview');
    });

    // Báo cáo
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/monthly',       [ReportController::class, 'monthly'])->name('monthly');
        Route::post('/daily-ads',    [ReportController::class, 'updateDailyAds'])->name('daily-ads.update');
        Route::post('/monthly-kol',  [ReportController::class, 'updateMonthlyKol'])->name('monthly-kol.update');
    });

    // Quản lý tài khoản
    Route::resource('users', UserController::class)->except(['show']);
});
