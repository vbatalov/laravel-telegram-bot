<?php

namespace App\Http\Controllers;

use Doctrine\DBAL\Driver\OCI8\Exception\Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InsertAllCompanies {

    public function index() {
        /**
         * @return array|false
         */
        function scanFile () {
            $directory = scandir("D:\Баталов\phpstorm\BOT-TELEGRAM-FGISLK\laravel\app\Http\Controllers\cid");
            unset($directory[0], $directory[1]); //Убрал точки (которые всегда появляются)

            return $directory;
        }

        $scan = scanFile (); //Функция сканирует файлы в папке actions

        foreach ($scan as $FileName) {
            $open = (file_get_contents("D:\Баталов\phpstorm\BOT-TELEGRAM-FGISLK\laravel\app\Http\Controllers\cid/" . $FileName));
            $decode = json_decode($open, true);
            $cid = $FileName;
            $cid = str_replace(".txt", "", $cid);

            foreach ($decode as $inn) {
                        DB::table('deal_companies')->insert([
                            "cid" => $cid,
                            "inn" => $inn,
                        ]);
            }
        }
    }

}


