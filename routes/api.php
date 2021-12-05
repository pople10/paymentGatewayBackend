<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\Http\Controllers\UserController;

Route::post('/account/signup', [UserController::class,"signUp"]);

Route::post('/account/login', [UserController::class,"login"]);

Route::post('/account/login/otp', [UserController::class,"OTP"]);

Route::middleware('auth:sanctum')->get('/account/send', [UserController::class,"sendWebNotification"]);

Route::middleware('auth:sanctum')->delete('/account/delete', [UserController::class,"delete"]);

Route::middleware('auth:sanctum')->get('/account/logout', [UserController::class,"logout"]);

Route::middleware('auth:sanctum')->post('/account/verify', [UserController::class,"verify"]);

Route::middleware('auth:sanctum')->put('/account/profile/update', [UserController::class,"editInfo"]);

Route::middleware('auth:sanctum')->post('/account/profile/update/photo', [UserController::class,"updatePhoto"]);

Route::middleware('auth:sanctum')->put('/account/change/password', [UserController::class,"edit"]);

Route::middleware('auth:sanctum')->get('/account/profile', [UserController::class,"getProfile"]);

Route::post('/account/reset', [UserController::class,"sendResetRequest"]);

Route::put('/account/reset/change', [UserController::class,"resetPassword"]);

Route::middleware('auth:sanctum')->put('/account/firebase/token', [UserController::class,"fireBaseTokenPush"]);

Route::middleware('auth:sanctum')->put('/account/firebase/token/unsubscribe', [UserController::class,"fireBaseTokenPop"]);

use App\Http\Controllers\EmailController;

Route::middleware('auth:sanctum')->post('/email/message/private', [EmailController::class,"contact_us"]);

Route::post('/email/message/public', [EmailController::class,"contact_us"]);

use App\Http\Controllers\BalanceController;

Route::middleware('auth:sanctum')->post('/balance/open', [BalanceController::class,"store"]);

Route::middleware('auth:sanctum')->delete('/balance/close/{id}', [BalanceController::class,"delete"]);

Route::middleware('auth:sanctum')->put('/balance/edit', [BalanceController::class,"modify"]);

Route::middleware('auth:sanctum')->get('/balance/currency/available', [BalanceController::class,"getCurrencyAvailable"]);

Route::middleware('auth:sanctum')->get('/balance/currency/used', [BalanceController::class,"getCurrencyUsed"]);

Route::middleware('auth:sanctum')->get('/balance/currency/used/{curr}', [BalanceController::class,"getCurrencyUsedExcept"]);

Route::middleware('auth:sanctum')->get('/balance', [BalanceController::class,"findAll"]);

Route::middleware('auth:sanctum')->get('/balance/{currency}', [BalanceController::class,"findById"]);

Route::get('/getRate/{base}/{to}', [BalanceController::class,"getRate"]);

Route::middleware('auth:sanctum')->post('/balance/convert', [BalanceController::class,"convert"]);

use App\Http\Controllers\VCCController;

Route::middleware('auth:sanctum')->post('/vcc/create', [VCCController::class,"store"]);

Route::middleware('auth:sanctum')->get('/vcc', [VCCController::class,"findAll"]);

Route::middleware('auth:sanctum')->delete('/vcc/delete/{number}', [VCCController::class,"destroy"]);

Route::post('/vcc/serve', [VCCController::class,"serve"]);

use App\Http\Controllers\PaymentController;

Route::middleware('auth:sanctum')->post('/payment/send', [PaymentController::class,"sendPayment"]);

Route::middleware('auth:sanctum')->post('/payment/send/check', [PaymentController::class,"checkReceiver"]);

Route::middleware('auth:sanctum')->get('/payment', [PaymentController::class,"findAll"]);

Route::middleware('auth:sanctum')->get('/payment/{id}', [PaymentController::class,"findById"]);

Route::middleware('auth:sanctum')->post('/payment/refund/full', [PaymentController::class,"fullRefund"]);

use App\Http\Controllers\CurrencyController;

Route::get('/currency', [CurrencyController::class,"findAll"]);

use App\Http\Controllers\CryptoController;

Route::get('/crypto/BTC/rate', [CryptoController::class,"getExchangeToBTC"]);

Route::middleware('auth:sanctum')->post('/crypto/deposit', [CryptoController::class,"deposit"]);

Route::middleware('auth:sanctum')->post('/crypto/withdraw', [CryptoController::class,"withdraw"]);

Route::middleware('auth:sanctum')->get('/crypto/wallet', [CryptoController::class,"getWallet"]);

Route::middleware('auth:sanctum')->delete('/crypto/wallet/close', [CryptoController::class,"close"]);

Route::middleware('auth:sanctum')->post('/crypto/wallet/open', [CryptoController::class,"open"]);

Route::middleware('auth:sanctum')->get('/static', [BalanceController::class,"getStatisticInfo"]);

Route::middleware('auth:sanctum')->get('/homeTransactions', [BalanceController::class,"getPayments"]);

Route::middleware('auth:sanctum')->post('/Transaction', [BalanceController::class,"getTransaction"]);


