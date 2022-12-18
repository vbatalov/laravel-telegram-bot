<?php

namespace App\Console\Commands;

use App\Models\fgislk_bot\Deal;
use Illuminate\Console\Command;

class DealsCheckVolume extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:CheckVolume';

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
        \Log::info("$this->signature Start.");


        $model = new Deal();
        $model->differentVolume();

        \Log::info("$this->signature Success End.");

        return Command::SUCCESS;
    }
}
