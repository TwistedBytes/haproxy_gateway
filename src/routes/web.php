<?php

use App\Http\Controllers\HaproxyMapController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Hello World';
});

//Route::resource('test', TestController::class);
Route::resource('test', TestController::class);
Route::get('loadState', [TestController::class, 'loadState']);


Route::prefix('map')->group(function () {
    Route::get('/add/{basename}/{ip}/{servername}', [HaproxyMapController::class, 'add']);
    Route::get('/del/{basename}/{ip}', [HaproxyMapController::class, 'del']);

    Route::get('/addthisip/{basename}/{servername}', [HaproxyMapController::class, 'addthisip']);
    Route::get('/delthisip/{basename}/{servername}', [HaproxyMapController::class, 'delthisip']);
});
