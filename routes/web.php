<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminCommentsController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminRepliesController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\LiqPayController;
use App\Http\Controllers\PineconeSearchController;
use App\Http\Controllers\User\AdressesController;
use App\Http\Controllers\User\CartController;
use App\Http\Controllers\User\ChatController;
use App\Http\Controllers\User\ChatSessionsController;
use App\Http\Controllers\User\CommentsController;
use App\Http\Controllers\User\FavoriteProductsController;
use App\Http\Controllers\User\OrdersController;
use App\Http\Controllers\User\ProductListController;
use App\Http\Controllers\User\ReplyController;
use App\Http\Controllers\User\UserController;
use App\Models\FavoriteProducts;
use Inertia\Inertia;


// user
Route::get('/', [UserController::class, 'index'])->name('user.home');

Route::get('/dashboard', [UserController::class, 'index'])->name('dashboard');

//routes for liqpay
    Route::post('/liqpay/getPaymentForm', [LiqPayController::class, 'getPaymentForm']);
    Route::post('/liqpay/callback', [LiqPayController::class, 'callback'])->name('liqpay.callback');
    Route::get('/payment/success', function () {
        return view('payment.success');
    })->name('payment.success');
    //end

    Route::get('/orders', [OrdersController::class, 'index']);
    Route::get('/orders/{id}', [OrdersController::class, 'show']);
    Route::post('/orders/store', [OrdersController::class, 'store']);
    Route::post('/adresses/store', [AdressesController::class, 'store']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    

    Route::get('/user/{id}/favorites', [FavoriteProductsController::class, 'index']);
    Route::post('/user/favorites/store', [FavoriteProductsController::class, 'store']);
    Route::post('/user/favorites/delete', [FavoriteProductsController::class, 'delete']);

    //routes for chat
    Route::post('/initSession', [ChatSessionsController::class, 'initSession']);

    //end
});

//end

//add to cart

Route::prefix('cart')->controller(CartController::class)->group(function () {
    Route::get('view', 'view')->name('cart.view');
    Route::post('store/{product}', 'store')->name('cart.store');
    Route::patch('update/{product}', 'update')->name('cart.update');
    Route::delete('delete/{product}', 'delete')->name('cart.delete');
});
//end

Route::middleware(['auth', 'verified'])->post('/comment/store', [CommentsController::class, 'store'])->name('comments.store');
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/comments', [AdminCommentsController::class, 'index'])->name('admin.comments.index');
    Route::patch('/comments/update/{id}', [AdminCommentsController::class, 'update'])->name('admin.comments.update');
});
Route::middleware(['auth', 'admin'])->post('/reply/store', [ReplyController::class, 'store'])->name('reply.store');
//admin

Route::group(['prefix' => 'admin', 'middleware' => 'redirectAdmin'], function () {
    Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('admin.login.post');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/orders', [OrdersController::class, 'index'])->name('admin.orders.index');

    //product routes
    Route::get('/products', [ProductController::class, 'index'])->name('admin.products.index');
    Route::post('/products/store', [ProductController::class, 'store'])->name('admin.products.store');
    Route::put('/products/update/{id}', [ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/products/image/{id}', [ProductController::class, 'deleteImage'])->name('admin.products.image.delete');
    Route::delete('/products/destroy/{id}', [ProductController::class, 'destroy'])->name('admin.products.destroy');
    
});

//end

//routes for product list and filters
Route::prefix('products')->controller(ProductListController::class)->group(function () {
    Route::get('/', 'index')->name('products.index');
    Route::get('/all_products', 'indexAll')->name('allProducts.index');
    Route::get('/{id}', 'showAndIndex')->name('product.show');
    Route::get('/category/{category}', 'indexByCategory')->name('productByCategory.index');
});
//end

//routes for nova poshta
Route::get('/warehouses', [CartController::class, 'getWarehouses']);
Route::get('/getCities/{findBy}', [CartController::class, 'getCities']);
Route::get('/getWarehouses/{City}/{findBy}', [CartController::class, 'getWarehouses']);
//end

//routes for pinecone
Route::post('/search', [PineconeSearchController::class, 'search']);
Route::post('/search/tos', [PineconeSearchController::class, 'search_tos']);
//end

//routes for openrouter
Route::post('/ask', [ChatController::class, 'ask']);
Route::post('/ask/product', [ChatController::class, 'ask']);
//end


require __DIR__ . '/auth.php';