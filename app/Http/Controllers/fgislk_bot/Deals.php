<?php

namespace App\Http\Controllers\fgislk_bot;

use App\Http\Controllers\Controller;
use App\Models\fgislk_bot\Deal;
use Illuminate\Http\Request;

class Deals extends Controller
{
    protected Deal $model;

    public function __construct()  {
        $this->model = new Deal();
    }

    /**
     * Генерирует в БД один раз в день все компании, которые нужно проверить
     */
    public function generateJob() {
        $this->model->firstJobGenerate();
    }

    /**
     * Используется для обработки результатов
     */
    public function index() {
        $this->model->curlJob(); // Создает запрос по каждому ИНН для 4х проверок
        $this->model->differentVolume();
    }

    public function sendNotifications() {
        $this->model->sendNotification();
    }

}
