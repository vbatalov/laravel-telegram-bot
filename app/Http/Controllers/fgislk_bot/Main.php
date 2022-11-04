<?php

namespace App\Http\Controllers\fgislk_bot;

use App\Http\Controllers\Controller;

use App\Models\fgislk_bot\Error;


use App\Models\fgislk_bot\User;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\Exception;

class Main extends Controller
{


    public BotApi $bot;
    public Client $client;
    public Exception $exception;

    public function __construct () {
        $token = "5742517907:AAHlP8IsnJjHe7exYZkjmcK7ZQI4LJV5qjk";
        $this->bot = new BotApi($token, null);
        $this->client = new Client($token, null);
    }

    public function register() {
        $token = "5742517907:AAHlP8IsnJjHe7exYZkjmcK7ZQI4LJV5qjk";
        $bot = new Client($token, null);

            $page_url1 = "https://a6c0-37-21-235-73.eu.ngrok.io/";
            $page_url2 = "bot";
            $page_url = $page_url1.$page_url2;
            $bot->setWebhook($page_url);
    }

    public function index() {

        die;

        /**
         * Слушаем команды
         */
        try {
            $commands = new Commands();
            $commands->commandsList();
        } catch (Exception $e) {
            $this->errorsLog($e);
        }

        /**
         * Слушаем callback (нажатие на меню)
         */
//        try {
//            $callback_command = new Menu();
//            $callback_command->callback();
//        } catch (Exception $e) {
//            $this->errorsLog($e);
//        }

        /**
         * Слушаем сообщения
         */
        try {
            $messages = new Messages();
            $messages->messagesList();
        } catch (Exception $e) {
            $this->errorsLog($e);
        }

//        $this->client->run();
        $this->bot->sendMessage('112865662', "<b>Bot end</b>", 'html');
    }

    /**
     * @param $message
     * @return object
     */
    public function messageInfo ($message): object
    {
        try {
            return (object) [
                'cid' => $message->getChat()->getId() ?? null,
                'username' => $message->getChat()->getUsername(),
                'firstname' => $message->getChat()->getFirstname() ?? null,
                'lastname' => $message->getChat()->getLastname() ?? null,
                'text' => $message->getText() ?? null,
            ];
        } catch (Exception $e) {
            print_r($e->getMessage());
            return (object) [];
        }
    }

    /**
     * @param Exception $error
     */
    public function errorsLog (Exception $error) {

        /**
         * Ошибку помещаю в $error_message
         */
        $error_message = $error->getMessage();

        /**
         * Отправляю себе в чат
         */
        try {
            $this->bot->sendMessage('112865662', "<b>Error:</b> \n<pre>$error_message</pre>", 'html');
        } catch (Exception $e) {
            // nothing
        }

        /**
         * Добавляю в БД
         */
        try {
            $update = file_get_contents("php://input");
            $update_array = json_decode($update, true);
            $update_array += ['Error' => $error_message];
            $cid = $update_array['message']['from']['id'] ?? null;

            $insertToDb = new Error();
            $insertToDb->insertErrorToDb($cid, $update);

            // Вывожу на экран
            print_r($update_array);
        } catch (Exception $e) {

        }
        die;
}


}
