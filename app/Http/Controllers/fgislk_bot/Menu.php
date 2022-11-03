<?php

namespace App\Http\Controllers\fgislk_bot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use TelegramBot\Api\Client;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidJsonException;
use TelegramBot\Api\Types\CallbackQuery;
use TelegramBot\Api\Types\Update;

class Menu extends Main
{
    /**
     * @throws \TelegramBot\Api\InvalidArgumentException
     * @throws \TelegramBot\Api\InvalidJsonException
     * @throws \TelegramBot\Api\Exception
     */
    public function callback() {

        global $bot;

        $this->client->on(function($update) use ($bot) {

            $callback = $update->getCallbackQuery();
            $message = $callback->getMessage();
            $messageId = $callback->getMessage()->getMessageId();
            $data = $callback->getData();
            $cbid = $callback->getId();

            $cid = $message->getChat()->getId();
            $cun = $message->getChat()->getUsername();
            $cfn = $message->getChat()->getFirstname();
            $cln = $message->getChat()->getLastname();

            //Задать вопрос
            if ($data == "menu") {
                $this->bot->sendMessage("112865662", "hello $cbid");
            } else if ($data == 'cancel') {
                $this->bot->answerCallbackQuery($cbid, "Мы не можем допустить использование бота, пока вы не согласитесь с условиями.",true);
            }

        }, function($update){
            $callback = $update->getCallbackQuery();
            if (is_null($callback) || !strlen($callback->getData()))
                return false;
            return true;
        });

        $this->client->run();
    }

}
