<?php

namespace App\Http\Controllers\fgislk_bot;

use App\Http\Controllers\fgislk_bot\Main;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Cookies extends Main
{
    public $cid;
    public $param;


    public function __construct($cid) {
        $this->cid = $cid;
    }

    public function setCoookie ($param) {
        try {
            DB::table('users')->where('cid', '=', "$this->cid")->update([
                'cookie' => "$param",
            ]);
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }
    }

    public function checkCookie () {
        try {
            $cookies = DB::table('users')->where('cid', '=', "$this->cid")->get();
            foreach ($cookies as $cookie) {
                return $cookie->cookie;
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }
    }
}
