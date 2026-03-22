<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientCodeController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\RefundRequestController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariantController;
use App\Http\Controllers\VariantGroupController;
use App\Http\Controllers\VariantOptionController;
use App\Http\Middleware\CustomSanctumAuth;
use App\Http\Middleware\EnsureUserIsApproved;
use Illuminate\Support\Facades\Route;


// Public auth endpoints (no authentication needed)

Route::prefix('auth')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('register', 'register');
        Route::post('login', 'login');

        Route::prefix('password')->group(function () {
            Route::post('forgot', 'password_forgot');
            Route::post('reset', 'password_reset');
        });

        Route::prefix('email')->group(function () {
            Route::post('info', 'email_info');
            Route::middleware(CustomSanctumAuth::class)->post('send-validation-code', 'send_validation_code');
            Route::middleware(CustomSanctumAuth::class)->post('verify', 'email_verify');
        });

        Route::prefix('user')
            ->group(function () {
                Route::middleware(CustomSanctumAuth::class)->post('update', 'update');
                Route::get('get', 'show')->middleware('auth:sanctum');
            });
    });

// Categories – read public, write requires full auth + approval
Route::prefix('category')
    ->controller(CategoryController::class)
    ->group(function () {
        Route::get('hierarchy', 'hierarchy');
        Route::get('all', 'index');
        Route::get('get/{id}', 'show');

        Route::middleware('api.auth.approved')->group(function () {
            Route::post('create', 'store');
            Route::post('update/{category}', 'update');
            Route::delete('delete', 'destroy');
        });
    });

// Products – read public, write requires full auth + approval
Route::prefix('product')
    ->controller(ProductController::class)
    ->group(function () {
        Route::get('all', 'index');
        Route::get('get/{slug}', 'show');
        Route::get('price-range', 'price_range');

        Route::middleware('api.auth.approved')->group(function () {
            Route::post('full-create', 'product_full_create');
            Route::post('create', 'store');
            Route::post('update/{product}', 'update');
            Route::post('full-update/{product}', 'product_full_update');
            Route::delete('delete', 'destroy');
        });
    });

// Variants – read public, write requires full auth + approval
Route::prefix('variant')
    ->controller(VariantController::class)
    ->group(function () {
        Route::get('get/{id}', 'show');
        Route::get('all', 'index');

        Route::middleware('api.auth.approved')->group(function () {
            Route::post('create', 'store');
            Route::put('update/{variant}', 'update');
            Route::delete('delete', 'destroy');
        });
    });

// Variant groups – read public, write requires full auth + approval
Route::prefix('variant-group')
    ->controller(VariantGroupController::class)
    ->group(function () {
        Route::get('all', 'index');
        Route::get('get/{variant_group_id}', 'show');

        Route::middleware('api.auth.approved')->group(function () {
            Route::post('create', 'store');
            Route::put('update/{variant_group}', 'update');
            Route::delete('delete', 'destroy');
        });
    });

// Variant options – read public, write requires full auth + approval
Route::prefix('variant-option')
    ->controller(VariantOptionController::class)
    ->group(function () {
        Route::get('all', 'index');
        Route::get('get/{variant_option_id}', 'show');

        Route::middleware('api.auth.approved')->group(function () {
            Route::post('create', 'store');
            Route::put('update/{variant_option}', 'update');
            Route::delete('delete', 'destroy');
        });
    });

// Coupons – read public, write requires full auth + approval
Route::prefix('coupon')
    ->controller(CouponController::class)
    ->group(function () {
        Route::get('get/{code}', 'show');
        Route::get('all', 'index');

        Route::middleware('api.auth.approved')->group(function () {
            Route::post('create', 'store');
            Route::put('update/{coupon}', 'update');
            Route::delete('delete', 'destroy');
        });
    });

// Client codes – read public, write requires auth (but not approval? actually approval is needed)
Route::prefix('client-code')
    ->controller(ClientCodeController::class)
    ->group(function () {
        Route::get('get/{code}', 'show');

        Route::middleware('api.auth.approved')->group(function () {
            Route::get('all', 'index');
            Route::post('create', 'store');
            Route::put('update/{client_code}', 'update');
            Route::delete('delete', 'destroy');
        });
    });

// Promotions – all actions require full auth + approval
Route::prefix('promotion')
    ->middleware('api.auth.approved')
    ->controller(PromotionController::class)
    ->group(function () {
        Route::get('all', 'index');
        Route::post('create', 'store');
        Route::put('update/{promotion}', 'update');
        Route::delete('delete', 'destroy');
        Route::get('get/{promotion_id}', 'show');
    });

// User management – all actions require full auth + approval
Route::prefix('user')
    ->middleware('api.auth.approved')
    ->controller(UserController::class)
    ->group(function () {
        Route::post('update/{user}', 'update');
        Route::get('get/{user_id}', 'show');
        Route::get('all', 'index');
        Route::post('{user}/status', 'storeStatus');
    });

// Address – requires authentication (email verified), but not approval (customers can manage addresses)
Route::prefix('address')
    ->middleware('api.auth')
    ->controller(AddressController::class)
    ->group(function () {
        Route::post('create', 'store');
        Route::post('update/{address}', 'update');
        Route::get('all', 'index');
        Route::delete('delete', 'destroy');
    });

// Cart – requires authentication (email verified), but not approval
Route::prefix('cart')
    ->middleware('api.auth')
    ->controller(CartItemController::class)
    ->name('cart.')
    ->group(function () {
        Route::get('get/{cart_item_id}', 'show');
        Route::get('all', 'index');
        Route::post('create/{variant}', 'store')->name('create');
        Route::put('update/{cart_item}', 'update');
        Route::delete('delete', 'destroy');
    });

// Orders – requires authentication (email verified), but not approval (customers can place orders)
Route::prefix('order')
    ->middleware('api.auth')
    ->controller(OrderController::class)
    ->group(function () {
        Route::get('get/{order_uuid}', 'show');
        Route::get('all', 'index');
        Route::post('create', 'store');
        Route::delete('delete', 'destroy');
        Route::post('create-from-variant', 'create_from_variant');
    });

// Shipments – requires authentication (email verified); write actions (create, update, delete) also require approval
Route::prefix('shipment')
    ->middleware('api.auth')
    ->controller(ShipmentController::class)
    ->group(function () {
        Route::get('get/{shipment_id}', 'show');
        Route::get('all', 'index');

        Route::middleware(EnsureUserIsApproved::class)->group(function () {
            Route::post('create', 'store');
            Route::put('update/{shipment}', 'update');
            Route::delete('delete', 'destroy');
            Route::post('bulk-update-shipment', 'bulkUpdateShipment');
        });
    });

// Notifications – requires authentication (email verified), not approval
Route::prefix('notifications')
    ->middleware('api.auth')
    ->controller(NotificationController::class)
    ->group(function () {
        Route::get('', 'index');
        Route::get('unread', 'unread');
        Route::patch('{id}/read', 'markAsRead');
        Route::post('mark-all-read', 'markAllAsRead');
        Route::delete('{id}', 'destroy');
        Route::delete('clear-read', 'clearRead');
    });

// Transactions – base auth for all; admin actions require approval
Route::prefix('transactions')
    ->middleware('api.auth')
    ->controller(TransactionController::class)
    ->group(function () {
        Route::get('',           'index');
        Route::post('',           'store');
        Route::get('{transaction}', 'show');
        Route::put('{transaction}', 'update');
        Route::delete('',           'destroy');

        Route::post('{transaction}/dispute', 'openDispute');
        Route::delete('{transaction}/dispute', 'cancelDispute');
        Route::post('{transaction}/refund-request', 'requestRefund');

        // Admin/finance actions – require approval
        Route::middleware(EnsureUserIsApproved::class)->group(function () {
            Route::get('export', 'export');
            Route::patch('{transaction}/override-status', 'overrideStatus');
            Route::post('{transaction}/refund', 'refund');
            Route::post('{transaction}/resend-notification', 'resendNotification');
            Route::post('bulk-review', 'bulkReview');
            Route::patch('{transaction}/dispute', 'resolveDispute');
            Route::get('{transaction}/audit-logs', 'auditLogs');
            Route::get('{transaction}/webhook-logs', 'webhookLogs');
        });
    });

// Refund requests – require full auth + approval (admin only)
Route::prefix('refund-requests')
    ->middleware('api.auth.approved')
    ->controller(RefundRequestController::class)
    ->group(function () {
        Route::get('', 'index');
        Route::post('{refund_request}/approve', 'approve');
        Route::post('{refund_request}/reject', 'reject');
    });
