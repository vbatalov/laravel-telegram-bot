<?php

namespace App\Models\fgislk_bot;

use App\Http\Controllers\fgislk_bot\Main;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Error extends Main
{
    use HasFactory;

    public function insertErrorToDb($cid, $update) {

        try {
            DB::table('error_logs')->insert([
                'cid' => "$cid",
                'error_array' => "$update",
            ]);
            $this->bot->sendMessage('112865662', "<pre>New Error in DB</pre>", 'html');
        } catch (\Exception $e) {
             $db_error = $e->getMessage();
             $this->bot->sendMessage('112865662', "<b>Database Error:</b> \n<pre>$db_error</pre>", 'html');
        }
    }
}
