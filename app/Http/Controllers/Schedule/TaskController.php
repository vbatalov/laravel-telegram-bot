<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Console\Commands\TestStage1;

class TaskController extends Controller
{
    public function generateFirstJob() {
        $task = new TestStage1();
        $task->handle();
    }
}
