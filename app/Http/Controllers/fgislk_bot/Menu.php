<?php

namespace App\Http\Controllers\fgislk_bot;

use App\Http\Controllers\Controller;
use App\Http\Controllers\fgislk_bot\Cookies;
use App\Models\fgislk_bot\User;
use TelegramBot\Api\Exception;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\InputMedia\InputMedia;

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
            $messageId = $callback->getMessage()->getMessageId();

            $message = $callback->getMessage();
            $cid = $message->getChat()->getId();

            $username = $message->getChat()->getUsername();
            $firstname = $message->getChat()->getFirstname();
            $lastname = $message->getChat()->getLastname();

            $data = $callback->getData();
            $cbid = $callback->getId();

            $cookie = new Cookies($cid);

            //Задать вопрос
            if ($data == "menu") {

                $cookie->setCoookie(null);
                $this->mainMenu($cid, $cbid, $messageId);

            } elseif ($data == 'cancel') {

                $this->bot->answerCallbackQuery($cbid, "Мы не можем допустить использование бота, пока вы не согласитесь с условиями.",true);

            } elseif ($data == 'setup_city') {

                $cookie->setCoookie("setup_city");
                $this->setupCity($cid, $messageId);

            } else {
                $this->bot->answerCallbackQuery($cbid, "Кажется, ещё не работает",true);
            }

            $this->bot->answerCallbackQuery($cbid);

        }, function($update) {
            $callback = $update->getCallbackQuery();
            if (is_null($callback) || !strlen($callback->getData()))
                return false;
            return true;
        });

        return $this->client->run();
    }


    public function setupCity ($cid, $messageId)
    {
        $first_text = "<b>Пожалуйста, укажите город</b> \n\n";
        $second_text = "Напишите из какого Вы города, бот обработает сообщение и будет ждать Вашего подвтверждения.";
        $text = $first_text . $second_text;

        try {
            return $this->bot->editMessageCaption("$cid", $messageId, "$text", null, "", "HTML");
        } catch (\TelegramBot\Api\Exception $e) {
            print_r($e->getMessage());
        }
    }

    public function mainMenu ($cid, $cbid, $messageId) {
        $keyboard = new InlineKeyboardMarkup (
            [
                [
                    ['callback_data' => 'lesegais_report_menu', 'text' => 'Отследить сделки ЕГАИС Лес'],
                ],

                [
                    ['callback_data' => 'start_question', 'text' => 'Задать вопрос'],
                    ['callback_data' => 'doc_menu', 'text' => 'Документация'],
                ],
                [
                    ['callback_data' => 'func_menu', 'text' => 'Дополнительные функции'],
                ],
                [
                    ['callback_data' => 'cabinet', 'text' => 'Личный кабинет'],
                    ['callback_data' => 'contacts', 'text' => 'Контакты']
                ]
            ]
        );

        $caption = "<b>Главное меню</b> \n\nЭто Ваш личный помощник — Buddy. \n\nДля начала работы, выберите соответствующий пункт.";
        $media = 'https://b2b.kedrach.com/fgislk_bot/images/gif/Main_menu.gif';

        $photo =  [
            'type'=> 'animation',
            'media' => $media,
            'caption' => $caption,
            'parse_mode' => 'html'
        ];

        try {
//            $this->bot->editMessageMedia($cid, $cbid, json_encode($photo));
            $this->bot->editMessageReplyMarkup($cid, $cbid, $keyboard);
        }
        catch (Exception $e) {
            $this->bot->deleteMessage($cid, $messageId);
            $this->bot->sendAnimation($cid, $media, null, "$caption", null, $keyboard, "true", "HTML");
        }
    }
}
