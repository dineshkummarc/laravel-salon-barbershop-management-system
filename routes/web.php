<?php

use App\Domain\Availability;
use App\Http\Controllers\AuthController as Account;
use App\Http\Controllers\BackOfficeController as Office;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\StorefrontController as Store;
use App\Http\Middleware\PublicBusiness;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('site.platform', ['businesses' => Business::where('status', 'active')->get()]));
Route::post('/locale', function (Request $r) {
    $data = $r->validate(['locale' => 'required|in:en,bn']);
    session(['locale' => $data['locale']]);

    return back();
})->name('locale');
Route::get('/forgot-password', [Account::class, 'forgot'])->name('password.request');
Route::post('/forgot-password', [Account::class, 'emailReset'])->middleware('throttle:login')->name('password.email');
Route::get('/reset-password/{token}', [Account::class, 'resetForm'])->name('password.reset');
Route::post('/reset-password', [Account::class, 'reset'])->middleware('throttle:login')->name('password.update');
Route::post('/logout', [Account::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/receipts/{sale}', [Office::class, 'receipt'])->name('receipt');
    Route::get('/receipts/{sale}/pdf', [Office::class, 'pdf'])->name('receipt.pdf');
    Route::get('/reports/export', [Office::class, 'export'])->name('report.export');
});
Route::prefix('b/{business}')->middleware(PublicBusiness::class)->group(function () {
    Route::get('/', [Store::class, 'home'])->name('home');
    Route::get('/services', [Store::class, 'services'])->name('services');
    Route::get('/services/{slug}', [Store::class, 'service'])->name('service');
    Route::get('/shop', [Store::class, 'shop'])->name('shop');
    Route::get('/shop/{slug}', [Store::class, 'product'])->name('product');
    Route::get('/branches', [Store::class, 'branches'])->name('branches');
    Route::get('/journal', [Store::class, 'blog'])->name('blog');
    Route::get('/pages/{slug}', [Store::class, 'page'])->name('page');
    Route::get('/contact', [Store::class, 'contact'])->name('contact');
    Route::post('/contact', [Store::class, 'enquiry'])->middleware('throttle:public-write');
    Route::get('/availability', function (Request $request) {
        $data = $request->validate(['branch_id' => 'required|integer', 'service_id' => 'required|integer', 'employee_id' => 'required|integer', 'date' => 'required|date|after_or_equal:today|before_or_equal:'.now()->addMonths(3)->toDateString()]);

        return response()->json(app(Availability::class)->slots((int) $data['branch_id'], (int) $data['service_id'], (int) $data['employee_id'], $data['date']));
    })->middleware('throttle:60,1')->name('availability');
    Route::get('/book', [Store::class, 'booking'])->name('booking');
    Route::post('/book', [Store::class, 'book'])->middleware(['auth', 'throttle:public-write']);
    Route::get('/cart', [Store::class, 'cart'])->name('cart');
    Route::post('/cart', [Store::class, 'addCart'])->middleware('throttle:public-write')->name('cart.add');
    Route::post('/cart/remove', [Store::class, 'removeCart'])->name('cart.remove');
    Route::post('/checkout', [Store::class, 'checkout'])->middleware(['auth', 'throttle:public-write'])->name('checkout');
    Route::get('/account', [Store::class, 'account'])->middleware('auth')->name('account');
    Route::post('/bookings/{id}/cancel', [Store::class, 'cancelBooking'])->middleware('auth')->name('booking.cancel');
    Route::post('/orders/{sale}/review', [Store::class, 'review'])->middleware(['auth', 'throttle:public-write'])->name('review');
    Route::get('/orders/{token}', [Store::class, 'order'])->middleware('auth')->name('order');
    Route::get('/login', [Account::class, 'form'])->name('login');
    Route::post('/login', [Account::class, 'login'])->middleware('throttle:login');
    Route::get('/register', fn ($business) => view('site.auth', ['action' => 'register']))->name('register');
    Route::post('/register', [Account::class, 'register'])->middleware('throttle:login');
});
Route::match(['get', 'post'],'/unsubscribe/{token}',[ConsentController::class, 'unsubscribe'])->middleware('signed')->name('unsubscribe');
