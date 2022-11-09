<?php

namespace App\Http\Controllers\fgislk_bot;


use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\InputMedia\ArrayOfInputMedia;
use TelegramBot\Api\Types\InputMedia\InputMedia;
use TelegramBot\Api\Types\InputMedia\InputMediaAnimation;


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
                $this->menuMain($cid, $cbid, $messageId);
            }
            if  ($data == "functions") {
                $this->menuFunctions($cid, $messageId);
            }
            if ($data == 'cancel') {
                $this->bot->answerCallbackQuery($cbid, "Мы не можем допустить использование бота, пока вы не согласитесь с условиями.",true);
            }
            if ($data == 'setup_city') {
                $cookie->setCoookie("setup_city");
                $this->setupCity($cid, $messageId);
            }

            // Убираю прогрузку
            $this->bot->answerCallbackQuery($cbid);

        }, function($update) {
            $callback = $update->getCallbackQuery();
            if (is_null($callback) || !strlen($callback->getData()))
                return false;
            return true;
        });

        // Запуск
        return $this->client->run();
    }

    public function setupCity ($cid, $messageId) {
        $first_text = "<b>Пожалуйста, укажите город</b> \n\n";
        $second_text = "Напишите из какого Вы города, бот обработает сообщение и будет ждать Вашего подвтверждения.";
        $text = $first_text . $second_text;

        try {
            return $this->bot->editMessageCaption("$cid", $messageId, "$text", null, "", "HTML");
        } catch (\TelegramBot\Api\Exception $e) {
            return print_r($e->getMessage());
        }
    }

    /**
     * Главное меню
     */
    public function menuMain ($cid, $cbid, $messageId) {
        $keyboard = new InlineKeyboardMarkup (
            [
                [
                    ['callback_data' => 'functionLesegaisReportMenu', 'text' => 'Отследить сделки ЕГАИС Лес'],
                ],
                [
                    ['callback_data' => 'start_question', 'text' => 'Задать вопрос'],
                    ['callback_data' => 'doc_menu', 'text' => 'Документация'],
                ],
                [
                    ['callback_data' => 'functions', 'text' => 'Дополнительные функции'],
                ],
                [
                    ['callback_data' => 'cabinet', 'text' => 'Личный кабинет'],
                    ['callback_data' => 'contacts', 'text' => 'Контакты']
                ]
            ]
        );

        $caption = "<b>Главное меню</b> \n\nЭто Ваш личный помощник — Buddy. \n\nДля начала работы, выберите соответствующий пункт.";
        $media =  "https://b2b.kedrach.com/fgislk_bot/images/gif/Main_menu.gif";

        $animation = new InputMediaAnimation($media, $caption, "html");


        try {
            $a = $this->bot->editMessageMedia($cid, $messageId, $animation);
            $b = $this->bot->editMessageReplyMarkup($cid, $messageId, $keyboard);
            if (!$a and !$b) {
                throw new \Exception();
            }
        }
        catch (\Exception $e) {
            print_r($e->getMessage());
            $this->bot->sendAnimation($cid, $media, null, "$caption", null, $keyboard, "true", "HTML");
        } catch (\Error $e) {
            print_r($e->getMessage());
        }
    }

    public function menuFunctions ($cid, $messageId) {
        $keyboard = new InlineKeyboardMarkup (
            [
                [
                    ['callback_data' => 'menu', 'text' => 'Меню'],
                ],
            ]
        );

        $caption = "<b>Функции</b> \n\nРаздел функций1";
        $media =  "https://b2b.kedrach.com/fgislk_bot/images/gif/func_menu.gif";

        $animation = new InputMediaAnimation($media, $caption, "html");


        try {
            $a = $this->bot->editMessageMedia($cid, $messageId, $animation);
            $b = $this->bot->editMessageReplyMarkup($cid, $messageId, $keyboard);
            if (!$a and !$b) {
                throw new \Exception();
            }
        }
        catch (\Exception $e) {
            print_r($e->getMessage());
            $this->bot->sendAnimation($cid, $media, null, "$caption", null, $keyboard, "true", "HTML");
        } catch (\Error $e) {
            print_r($e->getMessage());
        }
    }
}
