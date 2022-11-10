<?php

namespace App\Http\Controllers\fgislk_bot;

use App\Http\Controllers\Controller;
use App\Models\fgislk_bot\Deal;
use Illuminate\Http\Request;

class Deals extends Controller
{
    // Используется для вывода результатов
    public function index() {
        $model = new Deal();
//        $model->secondJob();
        $model->different();
    }
}
