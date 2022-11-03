<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\fgislk_bot\Main;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//Route::post('bot', [Main::class, 'config']);

/** Бот всегда стартует с функции index */
Route::post('bot', [Main::class, 'index']);

Route::get('register', [Main::class, 'register']);
