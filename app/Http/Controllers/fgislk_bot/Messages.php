<?php

namespace App\Http\Controllers\fgislk_bot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use TelegramBot\Api\Exception;

class Messages extends Main
{
    public Exception $exception;

    public function messagesList () {

        global $bot;
        $cid = '112865662';

        $cookie = new Cookies($cid);
        $data_cookie = $cookie->checkCookie();
        $this->bot->sendMessage('112865662', "Cookie: $data_cookie");


            $this->client->on(function($update) use ($bot){

//            $message = $this->messageInfo($update->getMessage());

            $this->bot->sendMessage('112865662', 'Function Message Work');

            }, function() {
                return true; // когда тут true - команда проходит
            });

            return $this->client->run();

    }
}
