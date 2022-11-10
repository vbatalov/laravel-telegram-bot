<?php

namespace App\Models\fgislk_bot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\fgislk_bot\Main;

class Deal extends Model
{
    use HasFactory;
    private string $volume_buyer;
    private string $volume_seller;
    private string $deal_buyer;
    private string $deal_seller;

    private string $table_deal_queryCheckVolumeBuyer;

    public function __construct()
    {
        $this->volume_buyer = "volume_buyer";
        $this->volume_seller = "volume_seller";
        $this->deal_buyer = "deal_buyer";
        $this->deal_seller = "deal_seller";

        $this->table_deal_queryCheckVolumeBuyer = "deal_queryCheckVolumeBuyer";
    }


    public function get_contents_curl($data_string) {
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

    public function queryCheckVolumeBuyer($inn) {
        $data_string = '
		{
		  "query": "query SearchReportWoodDeal($size: Int!, $number: Int!, $filter: Filter, $orders: [Order!]) {\n  searchReportWoodDeal(filter: $filter, pageable: {number: $number, size: $size}, orders: $orders) {\n    content {\n      sellerName\n      sellerInn\n      buyerName\n      buyerInn\n      woodVolumeBuyer\n      woodVolumeSeller\n      dealDate\n      dealNumber\n      __typename\n    }\n    __typename\n  }\n}\n",
		  "variables": {
			"size": 20,
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

    /**
     * Создает первую работу в БД. И всегда опирается на неё
     * Все ИНН, которые нужно проверить
     * Для каждого ИНН создаю отдельные 4 записи (Проверка отчета (2), проверка новой сделки (2)
     */
    public function firstJob() {
        $companies = $this->getCompanies();

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
    public function getFirstJob(): array
    {
        return DB::table('deals_first_job')->get()->all();
    }

    /**
     * Для каждой записи делаю curl запрос
     */
    public function secondJob() {

        $jobs = $this->getFirstJob();

        foreach ($jobs as $job) {

            $cid = $job->cid;
            $inn = $job->inn;
            $success = false;

            // Если ещё не проверили
            if ($job->checked == 0) {
                if ($job->type == $this->volume_buyer) {
                    $query = $this->queryCheckVolumeBuyer("$inn");
                    if ($curl = $this->get_contents_curl($query)) {
                        $success = true;
                        $this->insertCurlToDatabase("$cid", "$inn", "$curl", "$this->table_deal_queryCheckVolumeBuyer");
                    }

                } elseif ($job->type == $this->volume_seller) {

                } elseif ($job->type == $this->deal_buyer) {

                } elseif ($job->type == $this->deal_seller) {

                }
                // ИНН на которые запрос прошел, ставлю проверенным.
                if ($success == true) {
                    DB::table('deals_first_job')
                        ->where('cid', '=', "$cid")
                        ->where('inn', '=', "$inn")
                        ->where('type', '=', "$job->type")
                        ->update(['checked' => 1]);
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

    public function different() {
        $table_data = DB::table('deal_queryCheckVolumeBuyer')->get()->all();
        foreach ($table_data as $value) {
            if (empty($value->old)) {
                // Используется один раз для новых ИНН. Если ещё нет значения @old,
                // то с новым циклом всех задач, этот оператор if будет пропущен
                DB::table('deals_first_job')
                    ->where('inn', '=', "$value->inn")
                    ->where("cid", "=", "$value->cid")
                    ->update(["checked" => 0]);
            } else {
                $new = $value->new;
                $old = $value->old;
                $this->differentVolume("$new", "$old", "$value->cid");
            }
        }
    }

    /** Фукнция осуществляет поиск различий в двух запросах ($new, $old) */
    public function differentVolume($new_json, $old_json, $cid) {
        $array = [];

        if (md5($new_json) != md5($old_json)) {
            $new = json_decode($new_json, true)['data']['searchReportWoodDeal']['content'];
            $old = json_decode($old_json, true)['data']['searchReportWoodDeal']['content'];
//            dd($new_json);

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
                                "dealNumberNew" => $dealNumberNew,
                                "dealNumberOld" => $dealNumberOld,
                                "type" => $this->volume_seller,
                                "cid" => $cid,
                            ];
                        }
                    }

                 }
            }
        }
        dd($array);
    }

    public function getCompanies(): array
    {
        return DB::table('deal_companies')->get()->all();
    }




}
