<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('check-otp',[AuthController::class,'checkopt']);

Route::post('set-info',[AuthController::class,'setinfo']);

Route::post('check-auth',[AuthController::class,'checkauth']);

Route::post('check-user',[AuthController::class,'checkUser']);
