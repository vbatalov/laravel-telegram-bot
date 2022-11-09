<?php

namespace App\Models\fgislk_bot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades;

class Deal extends Model
{
    use HasFactory;

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

        curl_close($curl);
        return curl_exec($curl);
    }

    public function queryLes($inn) {
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

        $data_string = str_replace("{inn}", $inn, $data_string);
        return $this->get_contents_curl($data_string);
    }

    public function getCompanies() {
        return Facades\DB::table('deal_companies')->get()->all();
    }

    public function updateDeal() {

    }

}
