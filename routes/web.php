<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\fgislk_bot\Main;
use App\Http\Controllers\fgislk_bot\Deals;
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
/** Регистрация бота */
Route::get('register', [Main::class, 'register']);


/**
 * Отладка изменения сделок с древесиной
 */
// Генерирует в БД один раз в день все компании, которые нужно проверить
Route::get('deals/firstJobGenerate', [Deals::class, 'generateJob']);

// Проверяет каждую компанию
Route::get('deals/index', [Deals::class, 'index']);
/** ВРЕМЕННО ПРОВЕРЯЕТ ТОЛЬКО ИЗМЕНЕНИЯ */
Route::get('deals/diff', [Deals::class, 'diff']);
// Отправка уведомлений
Route::get('deals/sendNotifications', [Deals::class, 'sendNotifications']);
