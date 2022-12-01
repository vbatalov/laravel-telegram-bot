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

    public function queryCheckBuyer($inn): array|string
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

    public function queryCheckSeller($inn): array|string
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
        DB::table("deal_queryCheckVolumeSeller")->truncate();
        DB::table("deal_queryCheckDealBuyer")->truncate();
        DB::table("deal_queryCheckDealSeller")->truncate();

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
                $query = $this->queryCheckBuyer("$inn");

                if ($curl = $this->get_contents_curl($query)) {
                    $this->insertCurlToDatabase("$cid", "$inn", "$curl", "$this->table_deal_queryCheckVolumeBuyer");
                } else {
                    $this->error("curlJob", "Пустой curl запрос", "cid: $cid, inn: $inn, type: $job->type");
                }

            } elseif ($job->type == $this->volume_seller) {
                $query = $this->queryCheckSeller("$inn");

                if ($curl = $this->get_contents_curl($query)) {
                    $this->insertCurlToDatabase("$cid", "$inn", "$curl", "$this->table_deal_queryCheckVolumeSeller");
                } else {
                    $this->error("curlJob", "Пустой curl запрос", "cid: $cid, inn: $inn, type: $job->type");
                }

            } elseif ($job->type == $this->deal_buyer) {
                $query = $this->queryCheckBuyer($inn);

                if ($curl = $this->get_contents_curl($query)) {
                    $this->insertCurlToDatabase("$cid", "$inn", "$curl", "$this->table_deal_queryCheckDealBuyer");
                } else {
                    $this->error("curlJob", "Пустой curl запрос", "cid: $cid, inn: $inn, type: $job->type");
                }

            } elseif ($job->type == $this->deal_seller) {
                $query = $this->queryCheckSeller("$inn");

                if ($curl = $this->get_contents_curl($query)) {
                    $this->insertCurlToDatabase("$cid", "$inn", "$curl", "$this->table_deal_queryCheckDealSeller");
                } else {
                    $this->error("curlJob", "Пустой curl запрос", "cid: $cid, inn: $inn, type: $job->type");
                }
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
                'old' => $curl,
                'checked' => 0,
            ]);
        } else {
            foreach ($table_data as $value) {

                // Если изменения ещё не проверены, будет ошибка.
                if ($value->checked == 0) {
                     $this->error("Insert Curl to DB", "Запущен curl без проверки изменений", "cid: $cid, inn: $inn");
                } else {
                    // Добавляю новый результат curl, а старый помещаю в old
                     DB::table($table_name)
                        ->where('cid', '=', "$value->cid")
                        ->where("inn", "=", "$value->inn")
                        ->update([
                            "new" => $curl,
                            "old" => "$value->new",
                            "checked" => 0,
                    ]);
                }
            }
        }
    }

    public function differentVolume() {

        // Проверка изменил ли отчет Продавец
        $table_data = DB::table("$this->table_deal_queryCheckVolumeBuyer")->get()->all();
        foreach ($table_data as $value) {
            if (isset($value->old)) {
                if ($value->checked == 0) {
                    if ($this->differentVolumeBuyer("$value->new", "$value->old", "$value->cid")) {
                        DB::table("$this->table_deal_queryCheckVolumeBuyer")
                            ->where("id", "=", "$value->id")
                            ->update(["checked" => 1]);
                    }
                }
            }
        }

        // Проверка изменил ли отчет Покупатель
        $table_data = DB::table("$this->table_deal_queryCheckVolumeSeller")->get()->all();
        foreach ($table_data as $value) {
            if (isset($value->old)) {
                if ($value->checked == 0) {
                    if ($this->differentVolumeSeller("$value->new", "$value->old", "$value->cid")) {
                        DB::table("$this->table_deal_queryCheckVolumeSeller")
                            ->where("id", "=", "$value->id")
                            ->update(["checked" => 1]);
                    }
                }
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
                        if ($newWoodVolumeBuyer != $oldWoodVolumeBuyer) {
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
                                "type" => $this->volume_seller,
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

    public function search($array, $key, $value): array
    {
        $results = array();

        if (is_array($array))
        {
            if (isset($array[$key]) && $array[$key] == $value)
                $results[] = $array;

            foreach ($array as $subarray)
                $results = array_merge($results, $this->search($subarray, $key, $value));
        }

        return $results;
    }

    /** ОБНОВИТЬ CHECKED = 1 ДЛЯ ТЕСТА ПОСТАВИЛ 0 */
    public function StartCheckNewDealBuyerAndSeller() {
        // Проверка на новые сделки у Продавца
        $table_data = DB::table("$this->table_deal_queryCheckDealSeller")->get()->all();
        foreach ($table_data as $value) {
            if (isset($value->old)) {
                if ($value->checked == 0) {
                    $this->searchNewDeal("$value->new", "$value->old", "$value->cid", "$this->deal_seller");

                    DB::table("$this->table_deal_queryCheckDealSeller")
                        ->where("id", "=", "$value->id")
                        ->update(["checked" => 1]);
                }
            }
        }
        // Проверяю у Покупателя
        $table_data = DB::table("$this->table_deal_queryCheckDealBuyer")->get()->all();
        foreach ($table_data as $value) {
            if (isset($value->old)) {
                if ($value->checked == 0) {
                    $this->searchNewDeal("$value->new", "$value->old", "$value->cid", "$this->deal_buyer");

                    DB::table("$this->table_deal_queryCheckDealBuyer")
                        ->where("id", "=", "$value->id")
                        ->update(["checked" => 1]);
                }
            }
        }
    }

    /**
     * Поиск новой сделки
     */
    public function searchNewDeal ($new_json, $old_json, $cid, $type) {
        $array = [];

        if (md5($new_json) != md5($old_json)) {
            $new = json_decode($new_json, true)['data']['searchReportWoodDeal']['content'];
            $old = json_decode($old_json, true)['data']['searchReportWoodDeal']['content'];

            $dealNewArray = [];
            foreach ($new as $key => $value) {
                //Данные о сделке
                $dealNumberNewFile = ($value['dealNumber']); // Номер декларации
                //Добавляю в массив номер сделки
                $dealNewArray[$key] = $dealNumberNewFile;
            }

            $dealOldArray = [];
            foreach ($old as $key => $value) {
                //Данные о сделке
                $dealNumberOldFile = ($value['dealNumber']); // Номер декларации
                //Добавляю в массив номер сделки
                $dealOldArray[$key] = $dealNumberOldFile;
            }

            $array_diff_dealClosed = array_diff($dealOldArray, $dealNewArray); // Сделка прекратила действие
            $array_diff_dealOpen = array_diff($dealNewArray, $dealOldArray); // Новая сделка

            // Если есть изменения в сделке, то выводим в ЛОГ (дебаг) и удаляем дубли
            if (!empty($array_diff_dealClosed or $array_diff_dealOpen)) {

                $array_diff_dealClosed = array_unique($array_diff_dealClosed); // Удаляю дубли

                /** array_diff_dealOpen → Работает стабильно. Сравнивая НОВЫЙ и СТАРЫЙ запрос */
                $array_diff_dealOpen = array_unique($array_diff_dealOpen); // Удаляю дубли

                # НАЧАЛО: Нахожу по ключу массив со всеми данными
                foreach ($array_diff_dealOpen as $key => $value) {
                    $searchKey = $this->search($new, 'dealNumber', $value);
                    $searchKey = array_unique($searchKey);

                    # Формируем ответ по каждой новой декларации
                    foreach ($searchKey as $key => $value) {
                        $sellerName = $value['sellerName'];
                        $sellerInn = $value['sellerInn'];
                        $buyerName = $value['buyerName'];
                        $buyerInn = $value['buyerInn'];
                        $dealNumber = $value['dealNumber'];
                        $dealDate = $value['dealDate'];
                        $dealDate = date("d.m.Y", strtotime($dealDate));

                        // Если массив пустой, мы не работаем дальше. Избегаем флуда
                        if (empty($old)) {
                            continue;
                        } elseif ($buyerName == "Физическое лицо") {
                            continue;
                        }

                        $array[] = [
                            "sellerInn" => $sellerInn,
                            "buyerInn" => $buyerInn,
                            "sellerName" =>$sellerName,
                            "buyerName" => $buyerName,
                            "dealNumberNew" => $dealNumber,
                            "dealDate" => $dealDate,
                            "type" => $type,
                            "cid" => $cid,
                        ];
                    }
                }
                # КОНЕЦ: Нахожу по ключу массив со всеми данными
                # return Данные без дублей
                # return Ответ пользователю по каждой новой сделке (где он Покупатель)
            }
        }

        if (!empty($array)) {
            $this->createNotificationUserJob($cid, $array);
            $this->createNotificationUserLog($cid, $array);
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
            $this->filterVolumeNotification($json);
            die;
            foreach ($json as $value) {
                $sellerInn = $value['sellerInn'] ?? null;
                $buyerInn = $value['buyerInn'] ?? null;
                $sellerName = $value['sellerName'] ?? null;
                $buyerName = $value['buyerName'] ?? null;
                $newWoodVolumeSeller = $value['newWoodVolumeSeller'] ?? null;
                $oldWoodVolumeSeller = $value['oldWoodVolumeSeller'] ?? null;
                $oldWoodVolumeBuyer = $value['oldWoodVolumeBuyer'] ?? null;
                $newWoodVolumeBuyer = $value['newWoodVolumeBuyer'] ?? null;
                $dealNumberNew = $value['dealNumberNew'] ?? null;
                $dealNumberOld = $value['dealNumberOld'] ?? null;
                $dealDate = $value['dealDate'] ?? null;
                $cid = $value['cid'] ?? null;
                $type = $value['type'] ?? null;

                if (!empty($type)) {
                    // Формирую текст для отправки
                    if ($type == $this->volume_buyer) {
                        $text = $this->textNotificationForBuyer (
                            "$sellerName",
                            "$sellerInn",
                            "$oldWoodVolumeSeller",
                            "$newWoodVolumeSeller",
                            "$newWoodVolumeBuyer",
                            "$dealNumberNew",
                        );
                    } elseif ($type == $this->volume_seller) {
                        $text = $this->textNotificationForSeller(
                            "$buyerName",
                            "$buyerInn",
                            "$oldWoodVolumeBuyer",
                            "$newWoodVolumeBuyer",
                            "$newWoodVolumeSeller",
                            "$dealNumberNew",
                        );
                    } elseif ($type == $this->deal_seller) {
                        $text = $this->textNotificationNewDeal(
                            "$buyerName",
                            "$buyerInn",
                            "$sellerName",
                            "$sellerInn",
                            "$dealNumberNew",
                            "$dealDate",
                        );
                    } elseif ($type == $this->deal_buyer) {
                        $text = $this->textNotificationNewDeal(
                            "$buyerName",
                            "$buyerInn",
                            "$sellerName",
                            "$sellerInn",
                            "$dealNumberNew",
                            "$dealDate",
                        );
                    }

                    // Отправляю уведомление
                    if (!empty($text)) {
                        try {
                            if ($bot->bot->sendMessage("112865662", $text, "HTML")) {
                                $this->sendNotificationLog("$cid", "$text", true, "$type");
                                $this->setStatusNotificationAtDoneOrError($allNotifications->id, true);
                            }
                        } catch (Exception $e) {
                            $error = ($e->getMessage());
                            $this->sendNotificationLog("$cid", "$text", false, "$type","$error");
                            $this->setStatusNotificationAtDoneOrError($allNotifications->id, false);
                        }
                    }
                }
            }
        }
    }

    public function filterVolumeNotification($json) {
        $first = $json;
        $second = $json;

        foreach ($first as $firstKey => $firstValue) {
            foreach ($second as $secondKey => $secondValue) {
                if (($firstValue['type'] == "$this->volume_seller") OR ($firstValue['type'] == "$this->volume_buyer")) {
                    if (($firstValue['dealNumberNew']) == ($secondValue['dealNumberNew'])) {
                        if ($firstValue['newWoodVolumeSeller'] == 0 ) {
                            unset($first[$firstKey]['newWoodVolumeSeller']);
                        }
                        if ($firstValue['oldWoodVolumeSeller'] == 0 ) {
                            unset($first[$firstKey]['oldWoodVolumeSeller']);
                        }
                        if ($firstValue['newWoodVolumeBuyer'] == 0 ) {
                            unset($first[$firstKey]['newWoodVolumeBuyer']);
                        }
                        if ($firstValue['oldWoodVolumeBuyer'] == 0 ) {
                            unset($first[$firstKey]['oldWoodVolumeBuyer']);
                        }
                    }
                }
            }
        }

        $third = $first;
        $fourth = $first;
        $arrayKeysForDelete = [];

        foreach ($third as $thirdKey => $thirdValue) {
            foreach ($fourth as $fourthKey => $fourthValue) {
                if ($thirdValue['dealNumberNew'] == $fourthValue['dealNumberNew']) {

                    if (!empty($fourthValue['newWoodVolumeSeller']) and (!empty($thirdValue['oldWoodVolumeSeller']))) {
                         if ($thirdValue['oldWoodVolumeSeller'] == $fourthValue['newWoodVolumeSeller']) {
                             $arrayKeysForDelete[] = [
                                 "key" => $thirdKey
                             ];
                         }
                     }

                     if (!empty($fourthValue['newWoodVolumeBuyer']) and (!empty($thirdValue['oldWoodVolumeBuyer']))) {
                         if ($thirdValue['oldWoodVolumeBuyer'] == $fourthValue['newWoodVolumeBuyer']) {
                             $arrayKeysForDelete[] = [
                                 "key" => $thirdKey
                             ];
                         }
                     }


                }
            }
        }

        // Удаляю сделки, в которых нет изменений
        foreach ($third as $key => $valueThird) {
            foreach ($arrayKeysForDelete as $keyForDelete => $valueForDelete) {
                if ($key == $valueForDelete['key']) {
                    unset($third[$key]);
                }
            }

        }

        // Образую единый массив
        $arrayOne = $third;
        $arrayTwo = $third;
        foreach ($arrayOne as $keyOne => $valueOne) {
            foreach ($arrayTwo as $keyTwo => $valueTwo) {
                if ($valueOne['dealNumberNew'] == $valueTwo['dealNumberNew']) {

                }
            }
        }

        dd($third);


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

    public function textNotificationForSeller(
        $buyerName,
        $buyerInn,
        $oldWoodVolumeBuyer,
        $newWoodVolumeBuyer,
        $newWoodVolumeSeller,
        $dealNumberNew
    ): string {

        $first = "<b>Обнаружены изменения в декларации:</b>\n<pre>$dealNumberNew</pre>";
        $second = "\n\n<b>Покупатель:</b> \n$buyerName, <b>ИНН:</b> $buyerInn \n\n<b>Изменил отчет:</b>";
        $third = "\n$oldWoodVolumeBuyer м³ → $newWoodVolumeBuyer м³";
        $fourth = "\n\n<b>Объем по сделке:</b> \nПр: $newWoodVolumeSeller / Пк: $newWoodVolumeBuyer"; // Общий объем по сделке


        return $first.$second.$third.$fourth;
    }

    public function textNotificationNewDeal(
        $buyerName,
        $buyerInn,
        $sellerName,
        $sellerInn,
        $dealNumberNew,
        $dealDate,
    ): string {

        $first = "<b>Обнаружена новая сделка с древесиной</b>";
        $second = "\n\n<b>Покупатель:</b> \n$buyerName, \n<b>ИНН:</b> $buyerInn";
        $third = "\n\n<b>Продавец:</b> \n$sellerName, \n<b>ИНН:</b> $sellerInn";
        $fourth = "\n\nДата сделки: $dealDate \n\n<b>Номер декларации:</b>\n<pre>$dealNumberNew</pre>";


        return $first.$second.$third.$fourth;
    }

}
