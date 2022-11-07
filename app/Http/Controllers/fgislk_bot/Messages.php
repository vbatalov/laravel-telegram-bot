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

    /**
     * @param $cid
     * @param $city
     * @return \TelegramBot\Api\Types\Message
     * @throws \TelegramBot\Api\Exception
     * @throws \TelegramBot\Api\InvalidArgumentException
     */
    public function setupCity($cid, $city) {

        $model = new User($cid);
        $model->setupCity($city);

        $keyboard = new InlineKeyboardMarkup([
            [
                ['callback_data' => 'menu', 'text' => 'Да, верно'],
                ['callback_data' => 'cancel', 'text' => 'Нет, попробовать ещё раз'],
            ]
        ]);
        $text = "<b>Мы определили ваш город:</b> \n\n$city";
        return $this->bot->sendMessage("$cid", "$text", "HTML", "", "", $keyboard, "");
    }

    /**
     * @param $cid
     * @return \TelegramBot\Api\Types\Message
     * @throws \TelegramBot\Api\Exception
     * @throws \TelegramBot\Api\InvalidArgumentException
     */
    public function setupCityError($cid) {
        return $this->bot->sendMessage("$cid", "<b>Ошибка!</b> \n\nМы не смогли определить Ваш город, попробуйте снова.", "HTML", );
    }
}
