<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\{
    Login,
    SignUp,
    ForgotPassword,
    ResetPasswords
};
use App\Livewire\User\{
    Dashboard,
    Orders,
    OrderDetails,
    RechargeOrder,
    Balance,
    Profile,
    Password
};
use App\Livewire\{
    Home,
    About,
    Business,
    Faq,
    Guide,
    Contact,
    PlansDetails,
    Cart,
    Checkout,
    EsimCompatible,
    CellularOptimization,
    Terms,
    FairUse
};

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/', Home::class)->name('home');

/*
|--------------------------------------------------------------------------
| GUEST AUTH ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {

    Route::get('/login', Login::class)->name('login');

    // OAuth callbacks (handled inside Livewire Login)
    Route::get('/login/google/callback', Login::class)->name('google.callback');
    Route::match(['get','post'],'/login/apple/callback', Login::class)
    ->name('apple.callback');

    Route::get('/signup', SignUp::class)->name('sign-up');
});

Route::get('/forgot-password', ForgotPassword::class)->middleware('throttle:5,10')->name('forgot-password');
Route::get('/reset-password', ResetPasswords::class)->name('password.reset');
Route::get('/reset-password/setpassword/{uid}/{token}', ResetPasswords::class)->name('password.reset.form');


/*
|--------------------------------------------------------------------------
| AUTHENTICATED USER ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('user')->group(function () {

    Route::get('/dashboard', Dashboard::class)->name('user.dashboard');

    Route::get('/orders', Orders::class)->name('user.orders');
    Route::get('/orders/data', [Orders::class, 'datatable'])->name('orders.data');
    Route::get('/orders/{order}/detail', OrderDetails::class)->name('orders.detail');
    Route::get('/orders/{order}/recharge', RechargeOrder::class)->name('orders.recharge');
    Route::get('/orders/{order}/balance', Balance::class)->name('orders.balance');

    Route::get('/profile', Profile::class)->name('user.profile');
    Route::get('/password', Password::class)->name('user.password');
});


/*
|--------------------------------------------------------------------------
| CONTENT ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/about', About::class)->name('about');
Route::get('/business', Business::class)->name('business');
Route::get('/faq', Faq::class)->name('faq');
Route::get('/guide', Guide::class)->name('guide');
Route::get('/contact', Contact::class)->name('contact');
Route::get('/plans-details/{zone}', PlansDetails::class)->name('plans-details');
Route::get('/esim-compatible', EsimCompatible::class)->name('esim-compatible');
Route::get('/cellular-optimization', CellularOptimization::class)->name('cellular-optimization');
Route::get('/terms', Terms::class)->name('terms');
Route::get('/fair-use', FairUse::class)->name('fair-use');
Route::get('/cart', Cart::class)->name('cart');
Route::get('/checkout', Checkout::class)->name('checkout');



Route::post('/webhooks/airwallex', function (Request $request) {

    Log::info('Webhook Received', $request->all());

    return response()->json(['success' => true]);
});
/*
|--------------------------------------------------------------------------
| LANGUAGE SWITCH
|--------------------------------------------------------------------------
*/
Route::get('/lang/{lang}', function ($lang) {
    if (in_array($lang, ['en', 'he', 'ar'])) {
        session(['locale' => $lang]);
        app()->setLocale($lang);
    }
    return back();
})->name('lang.switch');

/*
|--------------------------------------------------------------------------
| DEBUG / CLEAR SESSION
|--------------------------------------------------------------------------
*/
Route::get('/clear-session', function () {
    session()->flush();
    return 'Session cleared';
});
