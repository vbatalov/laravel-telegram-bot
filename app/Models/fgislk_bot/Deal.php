<?php

namespace App\Models\fgislk_bot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\fgislk_bot\Main;
use TelegramBot\Api\Exception;

class Deal extends Model
{
    use HasFactory;

    private string $volume_buyer;
    private string $volume_seller;
    private string $deal_buyer;
    private string $deal_seller;

    private string $table_deal_queryCheckVolumeBuyer;
    private string $table_deal_queryCheckVolumeSeller;
    private string $table_deal_queryCheckDealSeller;
    private string $table_deal_queryCheckDealBuyer;

    public function __construct()
    {
        $this->volume_buyer = "volume_buyer";
        $this->volume_seller = "volume_seller";
        $this->deal_buyer = "deal_buyer";
        $this->deal_seller = "deal_seller";

        $this->table_deal_queryCheckVolumeBuyer = "deal_queryCheckVolumeBuyer";
        $this->table_deal_queryCheckVolumeSeller = "deal_queryCheckVolumeSeller";
        $this->table_deal_queryCheckDealSeller = "deal_queryCheckDealSeller";
        $this->table_deal_queryCheckDealBuyer = "deal_queryCheckDealBuyer";
    }

    /** Добавляю ошибку в БД */
    public function error(string $error, string $text, string $other = null) {
        DB::table('deal_errors')->insert([
            "error" => $error,
            "text" => $text,
            "other" => $other,
        ]);
    }

    public function get_contents_curl($data_string): bool|string
    {
        $curl = curl_init('https://lesegais.ru/open-area/graphql');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        // Принимаем в виде массива. (false - в виде объекта)
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.75 Safari/537.36');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );

        $result = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($result, true);
        if (!empty($json['data']['searchReportWoodDeal']['content'])) {
            return $result;
        } else {
            return false;
        }
    }

    public function queryCheckVolumeBuyer($inn): array|string
    {
        $data_string = '
		{
		  "query": "query SearchReportWoodDeal($size: Int!, $number: Int!, $filter: Filter, $orders: [Order!]) {\n  searchReportWoodDeal(filter: $filter, pageable: {number: $number, size: $size}, orders: $orders) {\n    content {\n      sellerName\n      sellerInn\n      buyerName\n      buyerInn\n      woodVolumeBuyer\n      woodVolumeSeller\n      dealDate\n      dealNumber\n      __typename\n    }\n    __typename\n  }\n}\n",
		  "variables": {
			"size": 100,
			"number": 0,
			"filter": {
			  "items": [
				{
				  "property": "buyerInn",
				  "value": "{inn}",
				  "operator": "ILIKE"
				}
			  ]
			},
			"orders": null
		  },
		  "operationName": "SearchReportWoodDeal"
		}
		';
        return str_replace("{inn}", $inn, $data_string);
    }

    public function queryCheckVolumeSeller($inn): array|string
    {
        $data_string = '
		{
		  "query": "query SearchReportWoodDeal($size: Int!, $number: Int!, $filter: Filter, $orders: [Order!]) {\n  searchReportWoodDeal(filter: $filter, pageable: {number: $number, size: $size}, orders: $orders) {\n    content {\n      sellerName\n      sellerInn\n      buyerName\n      buyerInn\n      woodVolumeBuyer\n      woodVolumeSeller\n      dealDate\n      dealNumber\n      __typename\n    }\n    __typename\n  }\n}\n",
		  "variables": {
			"size": 100,
			"number": 0,
			"filter": {
			  "items": [
				{
				  "property": "sellerInn",
				  "value": "{inn}",
				  "operator": "ILIKE"
				}
			  ]
			},
			"orders": null
		  },
		  "operationName": "SearchReportWoodDeal"
		}
		';
        return str_replace("{inn}", $inn, $data_string);
    }

    public function getCompanies(): array
    {
        return DB::table('deal_companies')->get()->all();
    }

    /**
     * Создает первую работу в БД. И всегда опирается на неё
     * Все ИНН, которые нужно проверить
     * Для каждого ИНН создаю отдельные 4 записи (Проверка отчета (2), проверка новой сделки (2)
     */
    public function firstJobGenerate() {
        $companies = $this->getCompanies();
        DB::table('deals_first_job')->truncate();
        DB::table('deal_queryCheckVolumeBuyer')->truncate();

        foreach ($companies as $value) {
            DB::table('deals_first_job')->insert([
                'cid' => $value->cid,
                'inn' => $value->inn,
                'type' => $this->volume_seller,
            ]);
            DB::table('deals_first_job')->insert([
                'cid' => $value->cid,
                'inn' => $value->inn,
                'type' => $this->volume_buyer
            ]);
            DB::table('deals_first_job')->insert([
                'cid' => $value->cid,
                'inn' => $value->inn,
                'type' => $this->deal_seller
            ]);
            DB::table('deals_first_job')->insert([
                'cid' => $value->cid,
                'inn' => $value->inn,
                'type' => $this->deal_buyer
            ]);
        }
    }

    // Получает массив всех последующих задач
    public function getFirstJobGenerate(): array
    {
        return DB::table('deals_first_job')->get()->all();
    }

    /**
     * Для каждой записи делаю curl запрос
     */
    public function curlJob() {

        $jobs = $this->getFirstJobGenerate();

        foreach ($jobs as $job) {

            $cid = $job->cid;
            $inn = $job->inn;

            if ($job->type == $this->volume_buyer) {
                $query = $this->queryCheckVolumeBuyer("$inn");

                if ($curl = $this->get_contents_curl($query)) {
                    $this->insertCurlToDatabase("$cid", "$inn", "$curl", "$this->table_deal_queryCheckVolumeBuyer");
                } else {
                    $this->error("curlJob", "Пустой curl запрос", "cid: $cid, inn: $inn, type: $job->type");
                }

            } elseif ($job->type == $this->volume_seller) {
                $query = $this->queryCheckVolumeSeller("$inn");

                if ($curl = $this->get_contents_curl($query)) {
                    $this->insertCurlToDatabase("$cid", "$inn", "$curl", "$this->table_deal_queryCheckVolumeSeller");
                } else {
                    $this->error("curlJob", "Пустой curl запрос", "cid: $cid, inn: $inn, type: $job->type");
                }

            } elseif ($job->type == $this->deal_buyer) {
            } elseif ($job->type == $this->deal_seller) {
            }

        }
    }

    /**
     * Помещаю в БД запись о новом запросе с ЛесЕГАИС
     */
    public function insertCurlToDatabase($cid, $inn, $curl, string $table_name) {
        $table_data = DB::table("$table_name")
            ->where('cid', '=', "$cid")
            ->where('inn', '=', "$inn")
            ->get()->all();

        // Если это новый ИНН, создаю начальную конфигурацию
        if (empty($table_data)) {
            DB::table($table_name)->insert([
                'cid' => "$cid",
                'inn' => "$inn",
                'new' => $curl,
            ]);
        } else {
            foreach ($table_data as $value) {
                // Добавляю новый результат curl, а старый помещаю в old
                DB::table($table_name)
                    ->where('cid', '=', "$value->cid")
                    ->where("inn", "=", "$value->inn")
                    ->update([
                        "new" => $curl,
                        "old" => "$value->new"
                ]);
            }
        }
    }

    public function differentVolume() {
        // Проверка изменил ли отчет Продавец
        $table_data = DB::table("$this->table_deal_queryCheckVolumeBuyer")->get()->all();
        foreach ($table_data as $value) {
            if (isset($value->old)) {
                $this->differentVolumeBuyer("$value->new", "$value->old", "$value->cid");
            }
        }

        // Проверка изменил ли отчет Покупатель
        $table_data = DB::table("$this->table_deal_queryCheckVolumeSeller");
        foreach ($table_data as $value) {
            if (isset($value->old)) {
                $this->differentVolumeSeller("$value->new", "$value->old", "$value->cid");
            }
        }
    }

    /** Фукнция осуществляет поиск различий в двух запросах ($new, $old)
     * Возвращает ТОЛЬКО если Продавец изменил отчет
     */
    public function differentVolumeBuyer($new_json, $old_json, $cid) {
        $array = [];

        if (md5($new_json) != md5($old_json)) {
            $new = json_decode($new_json, true)['data']['searchReportWoodDeal']['content'];
            $old = json_decode($old_json, true)['data']['searchReportWoodDeal']['content'];

            foreach ($new as $value_new) {
                // Начало: Данные о сделке
                $sellerName = ($value_new['sellerName']); // Продавец (наименование)
                $sellerInn = ($value_new['sellerInn']); // Продавец (ИНН)
                $buyerName = ($value_new['buyerName']); // Покупатель (наименование)
                $buyerInn = ($value_new['buyerInn']); // Продавец (ИНН)
                $dealNumberNew = ($value_new['dealNumber']); // Номер декларации
                // Конец: Данные о сделке

                //Данные отчета (новый запрос lesegais)
                $newWoodVolumeBuyer = ($value_new['woodVolumeBuyer']); // Покупатель (данные отчета) новый файл
                $newWoodVolumeSeller = ($value_new['woodVolumeSeller']); // Продавец (данные отчета) новый файл

                foreach ($old as $value_old) {
                    $dealNumberOld = ($value_old['dealNumber']);
                    $oldWoodVolumeBuyer = ($value_old['woodVolumeBuyer']);	// Покупатель (данные отчета) старый файл
                    $oldWoodVolumeSeller = ($value_old['woodVolumeSeller']); // Продавец (данные отчета) старый файл

                    /** Нашли одинаковую сделку */
                    if ($dealNumberNew == $dealNumberOld) {
                        /** Если продавец изменил отчет */
                        if ($newWoodVolumeSeller != $oldWoodVolumeSeller) {
                            $array[] = [
                                "sellerInn" => $sellerInn,
                                "buyerInn" => $buyerInn,
                                "sellerName" =>$sellerName,
                                "buyerName" => $buyerName,
                                "newWoodVolumeSeller" => $newWoodVolumeSeller,
                                "oldWoodVolumeSeller" => $oldWoodVolumeSeller,
                                "newWoodVolumeBuyer" => $newWoodVolumeBuyer,
                                "oldWoodVolumeBuyer" => $oldWoodVolumeBuyer,
                                "dealNumberNew" => $dealNumberNew,
                                "dealNumberOld" => $dealNumberOld,
                                "type" => $this->volume_buyer,
                                "cid" => $cid,
                            ];
                        }
                    }

                 }
            }

            if (!empty($array)) {
                $this->createNotificationUserJob($cid, $array);
                $this->createNotificationUserLog($cid, $array);
            }
        }
    }

    /** Фукнция осуществляет поиск различий в двух запросах ($new, $old)
     * Возвращает ТОЛЬКО если Покупатель изменил отчет
     */
    public function differentVolumeSeller($new_json, $old_json, $cid) {
        $array = [];

        if (md5($new_json) != md5($old_json)) {
            $new = json_decode($new_json, true)['data']['searchReportWoodDeal']['content'];
            $old = json_decode($old_json, true)['data']['searchReportWoodDeal']['content'];

            foreach ($new as $value_new) {
                // Начало: Данные о сделке
                $sellerName = ($value_new['sellerName']); // Продавец (наименование)
                $sellerInn = ($value_new['sellerInn']); // Продавец (ИНН)
                $buyerName = ($value_new['buyerName']); // Покупатель (наименование)
                $buyerInn = ($value_new['buyerInn']); // Продавец (ИНН)
                $dealNumberNew = ($value_new['dealNumber']); // Номер декларации
                // Конец: Данные о сделке

                //Данные отчета (новый запрос lesegais)
                $newWoodVolumeBuyer = ($value_new['woodVolumeBuyer']); // Покупатель (данные отчета) новый файл
                $newWoodVolumeSeller = ($value_new['woodVolumeSeller']); // Продавец (данные отчета) новый файл

                foreach ($old as $value_old) {
                    $dealNumberOld = ($value_old['dealNumber']);
                    $oldWoodVolumeBuyer = ($value_old['woodVolumeBuyer']);	// Покупатель (данные отчета) старый файл
                    $oldWoodVolumeSeller = ($value_old['woodVolumeSeller']); // Продавец (данные отчета) старый файл

                    /** Нашли одинаковую сделку */
                    if ($dealNumberNew == $dealNumberOld) {
                        /** Если продавец изменил отчет */
                        if ($newWoodVolumeSeller != $oldWoodVolumeSeller) {
                            $array[] = [
                                "sellerInn" => $sellerInn,
                                "buyerInn" => $buyerInn,
                                "sellerName" =>$sellerName,
                                "buyerName" => $buyerName,
                                "newWoodVolumeSeller" => $newWoodVolumeSeller,
                                "oldWoodVolumeSeller" => $oldWoodVolumeSeller,
                                "newWoodVolumeBuyer" => $newWoodVolumeBuyer,
                                "oldWoodVolumeBuyer" => $oldWoodVolumeBuyer,
                                "dealNumberNew" => $dealNumberNew,
                                "dealNumberOld" => $dealNumberOld,
                                "type" => $this->volume_buyer,
                                "cid" => $cid,
                            ];
                        }
                    }

                }
            }

            if (!empty($array)) {
                $this->createNotificationUserJob($cid, $array);
                $this->createNotificationUserLog($cid, $array);
            }
        }
    }

    /**
     * Создаю список для дальнейей отправки уведомлений
     */

    public function createNotificationUserJob($cid, array $array) {
        DB::table('deal_NotificationUserJob')->insert([
            "cid" => "$cid",
            "json" => json_encode($array, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /** Логирование нотификация для админстрирования */
    public function createNotificationUserLog($cid, array $array) {

    }

    /** Отправка уведомлений */
    public function sendNotification() {
        $bot = new Main();
        $notifications = DB::table('deal_NotificationUserJob')->where('status', '=', '0')->get()->all();
        foreach ($notifications as $allNotifications) {
            $json = json_decode($allNotifications->json, true);
            foreach ($json as $value) {
                $sellerInn = $value['sellerInn'];
                $buyerInn = $value['buyerInn'];
                $sellerName = $value['sellerName'];
                $buyerName = $value['buyerName'];
                $newWoodVolumeSeller = $value['newWoodVolumeSeller'];
                $oldWoodVolumeSeller = $value['oldWoodVolumeSeller'];
                $newWoodVolumeBuyer = $value['newWoodVolumeBuyer'] ?? null;
                $dealNumberNew = $value['dealNumberNew'];
                $dealNumberOld = $value['dealNumberOld'];
                $cid = $value['cid'];
                $type = $value['type'];

                if ($type == $this->volume_buyer) {
                    // Формирую текст для отправки Покупателю, что его Продавец изменил отчет
                    $text = $this->textNotificationForBuyer (
                        "$sellerName",
                        "$sellerInn",
                        "$oldWoodVolumeSeller",
                        "$newWoodVolumeSeller",
                        "$newWoodVolumeBuyer",
                        "$dealNumberNew",
                    );
                    try {
                        if ($bot->bot->sendMessage($cid, $text, "HTML")) {
                            $this->sendNotificationLog("$cid", "$text", true, "$this->volume_buyer");
                            $this->setStatusNotificationAtDoneOrError($allNotifications->id, true);
                        }
                    } catch (Exception $e) {
                        $error = ($e->getMessage());
                        $this->sendNotificationLog("$cid", "$text", false, "$this->volume_buyer","$error");
                        $this->setStatusNotificationAtDoneOrError($allNotifications->id, false);
                    }
                }
            }
        }
    }

    public function sendNotificationLog ($cid, $text, bool $success, $type, $error = null) {
        if ($success) {
            DB::table('deal_sendNotificationLog')->insert([
                "cid" => "$cid",
                "text" => "$text",
                "status" => "1",
                "type" => "$type",
            ]);
        } else {
            DB::table('deal_sendNotificationLog')->insert([
                "cid" => "$cid",
                "text" => "$text",
                "status" => "-1",
                "error" => "$error",
                "type" => "$type",
            ]);
        }
    }

    public function setStatusNotificationAtDoneOrError ($id, bool $success) {
        if ($success) {
            DB::table('deal_NotificationUserJob')
                ->where("id", "=", $id)
                ->update([
                    "status" => 1
                ]);
        } else {
            DB::table('deal_NotificationUserJob')
                ->where("id", "=", $id)
                ->update([
                    "status" => -1
                ]);
        }

    }

    public function textNotificationForBuyer(
        $sellerName,
        $sellerInn,
        $oldWoodVolumeSeller,
        $newWoodVolumeSeller,
        $newWoodVolumeBuyer,
        $dealNumberNew
    ): string {

        $first = "<b>Обнаружены изменения в декларации:</b>\n<pre>$dealNumberNew</pre>";
        $second = "\n\n<b>Продавец:</b> \n$sellerName, <b>ИНН:</b> $sellerInn \n\n<b>Изменил отчет:</b>";
        $third = "\n$oldWoodVolumeSeller м³ → $newWoodVolumeSeller м³";
        $fourth = "\n\n<b>Объем по сделке:</b> \nПр: $newWoodVolumeSeller / Пк: $newWoodVolumeBuyer"; // Общий объем по сделке


        return $first.$second.$third.$fourth;
    }

}
