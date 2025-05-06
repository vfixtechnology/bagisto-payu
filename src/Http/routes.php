<?php

use Illuminate\Support\Facades\Route;
use Vfixtechnology\Payu\Http\Controllers\PayuController;

Route::group([
    //   'prefix'     => 'payu',
    'middleware' => ['web', 'theme', 'locale', 'currency']
], function () {

    Route::get('payu-redirect', [PayuController::class, 'redirect'])->name('payu.redirect');
    Route::post('payu/verify', [PayuController::class, 'verify'])->name('payu.verify');
    Route::post('payu-failure', [PayuController::class, 'failure'])->name('payu.failure');
});
