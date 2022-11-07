<?php

namespace App\Http\Controllers\fgislk_bot;


use App\Models\fgislk_bot\User;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class Messages extends Main
{
    public function messagesList () {

        global $bot;
        global $client;

        /**
         * Начало обработки сообщений
         */

        $this->client->on(function(\TelegramBot\Api\Types\Update $update) use ($client) {

        $data = $update->getMessage() ?? null;

        if (!empty($data)) {
            $cid = $data->getChat()->getId();
            $text = $data->getText();
        }

        if (isset($cid)) {
            $cookie = new Cookies($cid);
            $data_cookie = $cookie->checkCookie();

            if ($data_cookie == "setup_city") {
                if ($city = $this->dadataSetupCity("$text")) {
                    $this->setupCity($cid, $city);
                } else {
                    $this->setupCityError($cid);
                }
            }
            if ($data_cookie == null) {
                $this->cookieIsEmpty($cid);
            }

        }

        }, function() {
            return true; // когда тут true - команда проходит
        });

        return $this->client->run();

    }

    /**
     * @param $message
     * @return false|mixed
     */
    public function dadataSetupCity ($message) {
        $token = "02eb6049f8f2a1a4a49561dda8aa38be17f0078a";
        $dadata = new \Dadata\DadataClient($token, null);
        $result = $dadata->suggest("address", "$message", "3",[
            'region_type_full' => 'город',
        ]);
        print_r($result);

        foreach ($result as $key => $value) {
            $city = $value['data']['city'];

            if (!empty($city)) {
                return $city;
            }
        }
    }

    public function setupCity($cid, $city) {

        $model = new User($cid);
        $model->setupCity($city);

        $keyboard = new InlineKeyboardMarkup([
            [
                ['callback_data' => 'menu', 'text' => 'Да, верно'],
            ]
        ]);
        $text = "<b>Настройка города</b> \n\nВы указали город: <b>$city</b>, верно? \n\n<i>Если я неправильно вас понял, попробуйте ещё раз.</i>";
        return $this->bot->sendMessage("$cid", "$text", "HTML", "", "", $keyboard, "");
    }

    public function setupCityError($cid) {
        return $this->bot->sendMessage("$cid", "<b>Ошибка!</b> \n\n<i>Мы не смогли определить Ваш город, попробуйте снова.</i>",
            "HTML", true, null, null, true);
    }

    /**
     * Если cookie пустые → прошу выбрать меню
     * @param $cid
     */
    public function cookieIsEmpty ($cid) {
        $keyboard = new InlineKeyboardMarkup([
            [
                ['callback_data' => 'menu', 'text' => 'Показать меню']
            ]
        ]);
        return $this->bot->sendMessage($cid, "<b>Внимание!</b> \n\nВы пытаетесь отправить боту сообщение, но не выбрали пункт меню. \n\nПожалуйста, воспользуйтесь меню и следуйте инструкциям.",
            "HTML", true, null, $keyboard, true);
    }
}
