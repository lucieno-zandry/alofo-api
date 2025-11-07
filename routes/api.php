<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientCodeController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariantController;
use App\Http\Controllers\VariantGroupController;
use App\Http\Controllers\VariantOptionController;
use App\Http\Middleware\CustomSanctumAuth;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureUserIsApproved;
use Illuminate\Support\Facades\Route;

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

Route::prefix('category')
    ->controller(CategoryController::class)
    ->group(function () {
        Route::get('hierarchy', 'hierarchy');
        Route::get('all', 'index');
        Route::get('get/{id}', 'show');
    });

Route::prefix('product')
    ->controller(ProductController::class)
    ->group(function () {
        Route::get('all', 'index');
        Route::get('get/{slug}', 'show');
        Route::get('search/{keywords}', 'search');
    });

Route::prefix('variant')
    ->controller(VariantController::class)
    ->group(function () {
        Route::get('get/{id}', 'show');
        Route::get('all', 'index');
    });

Route::prefix('variant-group')
    ->controller(VariantGroupController::class)
    ->group(function () {
        Route::get('all', 'index');
        Route::get('get/{variant_group_id}', 'show');
    });

Route::prefix('variant-option')
    ->controller(VariantOptionController::class)
    ->group(function () {
        Route::get('all', 'index');
        Route::get('get/{variant_option_id}', 'show');
    });

Route::prefix('coupon')
    ->controller(CouponController::class)
    ->group(function () {
        Route::get('get/{coupon_id}', 'show');
        Route::get('all', 'index');
    });

Route::middleware([CustomSanctumAuth::class, EnsureEmailIsVerified::class])
    ->group(function () {
        Route::prefix('address')
            ->controller(AddressController::class)
            ->group(function () {
                Route::post('create', 'store');
                Route::put('update/{address}', 'update');
                Route::get('all', 'index');
                Route::delete('delete', 'destroy');
            });

        Route::prefix('cart')
            ->controller(CartItemController::class)
            ->name('cart.')
            ->group(function () {
                Route::get('get/{cart_item_id}', 'show');
                Route::get('all', 'index');
                Route::post('create/{variant}', 'store')->name('create');
                Route::put('update/{cart_item}', 'update');
                Route::delete('delete', 'destroy');
            });

        Route::middleware([EnsureUserIsApproved::class])->group(function () {
            Route::prefix('user')
                ->controller(UserController::class)
                ->group(function () {
                    Route::post('update/{user}', 'update');
                    Route::get('get/{user_id}', 'show');
                    Route::get('all', 'index');
                });

            Route::prefix('client-code')
                ->controller(ClientCodeController::class)
                ->group(function () {
                    Route::get('all', 'index');
                    Route::post('create', 'store');
                    Route::put('update/{client_code}', 'update');
                    Route::delete('delete', 'destroy');
                });

            Route::prefix('category')
                ->controller(CategoryController::class)
                ->group(function () {
                    Route::post('create', 'store');
                    Route::put('update/{category}', 'update');
                    Route::delete('delete', 'destroy');
                });

            Route::prefix('product')
                ->controller(ProductController::class)
                ->group(function () {
                    Route::post('create', 'store');
                    Route::post('update/{product}', 'update');
                    Route::delete('delete', 'destroy');
                });

            Route::prefix('variant')
                ->controller(VariantController::class)
                ->group(function () {
                    Route::post('create', 'store');
                    Route::put('update/{variant}', 'update');
                    Route::delete('delete', 'destroy');
                });

            Route::prefix('variant-group')
                ->controller(VariantGroupController::class)
                ->group(function () {
                    Route::post('create', 'store');
                    Route::put('update/{variant_group}', 'update');
                    Route::delete('delete', 'destroy');
                });

            Route::prefix('variant-option')
                ->controller(VariantOptionController::class)
                ->group(function () {
                    Route::post('create', 'store');
                    Route::put('update/{variant_option}', 'update');
                    Route::delete('delete', 'destroy');
                });

            Route::prefix('promotion')
                ->controller(PromotionController::class)
                ->group(function () {
                    Route::get('all', 'index');
                    Route::post('create', 'store');
                    Route::put('update/{promotion}', 'update');
                    Route::delete('delete', 'destroy');
                    Route::get('get/{promotion_id}', 'show');
                });

            Route::prefix('order')
                ->controller(OrderController::class)
                ->group(function () {
                    Route::get('get/{order_uuid}', 'show');
                    Route::get('all', 'index');
                    Route::post('create', 'store');
                    Route::delete('delete', 'destroy');
                    Route::post('create-from-variant', 'create_from_variant');
                });

            Route::prefix('coupon')
                ->controller(CouponController::class)
                ->group(function () {
                    Route::post('create', 'store');
                    Route::put('update/{coupon}', 'update');
                    Route::delete('delete', 'destroy');
                });

            Route::prefix('transaction')
                ->controller(TransactionController::class)
                ->group(function () {
                    Route::get('get/{transaction_id}', 'show');
                    Route::get('all', 'index');
                    Route::post('create', 'store');
                    Route::put('update/{transaction}', 'update');
                    Route::delete('delete', 'destroy');
                });

            Route::prefix('shipment')
                ->controller(ShipmentController::class)
                ->group(function () {
                    Route::get('get/{shipment_id}', 'show');
                    Route::get('all', 'index');
                    Route::post('create', 'store');
                    Route::put('update/{shipment}', 'update');
                    Route::delete('delete', 'destroy');
                });
        });
    });
