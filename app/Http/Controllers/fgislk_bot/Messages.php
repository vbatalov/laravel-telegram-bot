<?php

namespace App\Http\Controllers\fgislk_bot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Messages extends Main
{
    public function messagesList () {

        global $bot;
        $cid = '112865662';

        $this->bot->sendMessage('112865662', 'Message Controller');
        $cookie = new Cookies($cid);
        $data_cookie = $cookie->checkCookie();
        $this->bot->sendMessage('112865662', "Cookie: $data_cookie");
        die;

        $bot->on(function($Update) use ($bot){
            $message = $Update->getMessage();
            $mtext = $message->getText();
            $mid = $message->getMessageId();
            $cid = $message->getChat()->getId();

            // Если есть фото, запрашивает ИД фото
            $photoId = $Update->getMessage()->getPhoto();

            //Проверим, есть ли пользователь в новой БД обновленного бота?
            //Если нет, мы просто предложим заного пройти регистрацию
            if (checkUserAfterUpdate($cid) == false) {
                $bot->sendMessage($cid, "<b>Мы обновили функионал бота</b> \n\nДля начала работы нажмите /start", "html");
                die;
            }

            //Пользователь написал сообщение. Нужно проверить, куда он обращается?
            require_once("include/cookie.php");

        }, function($message) use ($name){
            return true; // когда тут true - команда проходит
        });

        // запускаем обработку
        $bot->run();
    }
}
