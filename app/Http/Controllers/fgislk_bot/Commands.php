<?php

namespace App\Http\Controllers\fgislk_bot;

use App\Models\fgislk_bot\User;
use App\Http\Controllers\fgislk_bot\Cookies;
use Dadata\CleanClient;

class Commands extends Main
{


    public function commandsList() {

        global $bot;

        /**
         * Команда /start
         */

        $this->client->command('start', function ($message) use ($bot) {
            /**
             * Сведения о полученном сообщении
             */
            $message = $this->messageInfo($message);

            /**
             * Проверка пользователя в БД
             * При отсутствии добавляет
             * $cid, $firstname, $lastname, $username
             */
            $checkUserInDatabase = new User($message->cid);
            $checkUserInDatabase->checkUser();

            /**
             * Первый этап (приветствие)
             */
            $firstText = "Здравствуйте, $message->firstname";
            $secondText = "\n\nМы создали бота чтобы упростить учет лесопользователям.";
            $thirdText = "\n\n<i>Официальный канал @fgislk \nОфициальный сайт ФГИСЛК.РФ</i>";
            $text = $firstText . $secondText . $thirdText;

            $this->bot->sendMessage("$message->cid", "$text", 'html'); // Отправляю первое сообщение

            /**
             * Второй этап (согласие)
             */
            $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup (
                [

                    [
                        ['callback_data' => 'setup_city', 'text' => 'Начать использовать бота']
                    ],
                    [
                        ['callback_data' => 'cancel', 'text' => 'Отказаться']
                    ]

                ]
            );
            $msg2 = "<b>Внимание!</b> \n\nПродолжая использовать бота вы даете согласие на обработку полученных Вами данных. \n\nМы не храним личные данные, не передаем информацию третьим лицам и никогда не запрашиваем пароли. \n\nБот разработан проектом @fgislk \n\n<i>Лучшая благодарность — если вы расскажете о нас всем, кто связан лесом и дорогами.</i>";
            $animation = "https://b2b.kedrach.com/fgislk_bot/images/gif/welcome.gif";
            $this->bot->sendAnimation($message->cid, $animation, null, "$msg2", null, $keyboard, "false", "HTML");
        });

        $this->client->run();

    }
}
