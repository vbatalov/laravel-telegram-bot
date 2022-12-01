<?php

namespace App\Console\Commands;

use App\Http\Controllers\fgislk_bot\Deals;
use App\Http\Controllers\fgislk_bot\Main;
use Illuminate\Console\Command;

class TestStage1 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $notification = new Deals();
        $notification->generateJob();

        \Log::info("Working fine! Command from web");
        return Command::SUCCESS;
    }
}
