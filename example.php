<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

/**
 * revcom_bot
 *
 * @author - Александр Штокман
 */
header('Content-Type: text/html; charset=utf-8');
// подрубаем API
require_once("vendor/autoload.php");
// подрубаем базу данных
require_once("db_connect.php");
//Функции
require_once("include/userfunction.php");

// дебаг
if(true){
	error_reporting(E_ALL & ~(E_NOTICE | E_USER_NOTICE | E_DEPRECATED));
	ini_set('display_errors', 1);
}

// создаем переменную бота
$token = "5199631496:AAFaKpaRDgbI_2VZcitaGpO_6SC613j7LLg";
$bot = new \TelegramBot\Api\Client($token,null);

// если бот еще не зарегистрирован - регистируем
if(!file_exists("registered.trigger")){
	/**
	 * файл registered.trigger будет создаваться после регистрации бота.
	 * если этого файла нет значит бот не зарегистрирован
	 */

	// URl текущей страницы
	$page_url = "https://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	$result = $bot->setWebhook($page_url);
	if($result){
		file_put_contents("registered.trigger",time()); // создаем файл дабы прекратить повторные регистрации
	} else die("Ошибка регистрации");
}


//Запуск бота
$bot->command('start', function ($message) use ($bot) {
    require_once("include/start.php");
});
//Dadata
$bot->command('test', function ($message) use ($bot) {
    require_once("include/checkBuyer/uzb/index.php");
});
$bot->command('ref', function ($message) use ($bot) {
    require_once("include/referal/index.php");
});
//Меню
$bot->command('deal', function ($message) use ($bot) {
    require_once("include/menu/lesegais_deal_menu.php");
});

// Обработка кнопок у сообщений
$bot->on(function($update) use ($bot, $callback_loc, $find_command) {

	$callback = $update->getCallbackQuery();
	$message = $callback->getMessage();
	$messageId = $callback->getMessage()->getMessageId();
	$data = $callback->getData();
	$cbid = $callback->getId();

	$cid = $message->getChat()->getId();
	$cun = $message->getChat()->getUsername();
	$cfn = $message->getChat()->getFirstname();
	$cln = $message->getChat()->getLastname();

	// Обновление бота
	if ($data == "reload") {
		$action = "reload";
		sendAction($cid, $action);
        require_once("include/reload.php");
    }

	// Проверяем пользователя, есть ли он после обновления Бота (если нет, просим нажать кнопку Обновить)
	if (checkUserAfterUpdate($cid) == false) {
		$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup (
        		[

        			[
        				['callback_data' => 'reload', 'text' => 'Обновить']
        			]
        		]
        	);
		$bot->sendMessage($cid, "<b>Мы обновили функионал бота</b> \n\nДля начала работы нажмите <b>Обновить</b>", "html", null, null, $keyboard);
		die;
	}

	//Технические работы
	/*
	if ($cid !== 112865662) {
		$bot->sendMessage($cid, 'В данный момент проводятся технические работы. Ждем вас позже!');
		die;
	}
	*/

	//Проверяем подписан ли пользователь на группу @fgislk
	/**
	try {
		$status = $bot->getChatMember("@fgislk", $cid)->getStatus();
		if ($status == "left") {
			throw new Exception();
		}
		if ($status == "left" or $status == "kicked") {
			throw new Exception();
		}
	}
	catch (Exception $ex) {
		//Выводим сообщение об исключении.
		$bot->sendMessage($cid, "<b>Внимание!</b> Чтобы использовать Бота, вы должны быть подписаны на основной канал <b>@fgislk</b> \n\nПожалуйста, подпишитесь на канал, а затем нажмите /start \n\n<pre>Error: $status</pre>", 'html');
		die;
	}
	*/


    //Здесь используются всплывающие подсказки, а затем сбрасывается Прогрузка
    if ($data == "gowebsite") {
		$action = "gowebsite";
		sendAction($cid, $action);
        $bot->answerCallbackQuery($cbid, "Сайт в разработке",true);
	} else if ($data == "cancel") {
        $bot->answerCallbackQuery($cbid, "Мы не можем допустить использование бота, пока вы не согласитесь с условиями.",true);
    } else if ($data == "inwork") {
        $bot->answerCallbackQuery($cbid, "Функция (раздел) в разработке",true);
    } else if ($data == "beVoted") {
        $bot->answerCallbackQuery($cbid, "Голос уже учтен",true);
    } else if ($data == "likeOptionDealReport") {
		$bot->answerCallbackQuery($cbid, "Спасибо за оценку!",true);
		$likesFile = file_get_contents("include/lesegais_report/likes/like.txt");
		$dislikeFile = file_get_contents("include/lesegais_report/likes/dislike.txt");
		$likesFile++;
		file_put_contents("include/lesegais_report/likes/like.txt", $likesFile);
		$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup (
			[

				[
					['callback_data' => 'beVoted', 'text' => "👍🏽 → $likesFile"],
					['callback_data' => 'beVoted', 'text' => "👎🏽 → $dislikeFile"]
				],
				[
					['callback_data' => 'lesegais_report_setup', 'text' => 'Настроить уведомления']
				],
				[
					['callback_data' => 'menu', 'text' => 'Главное меню']
				]
			]
		);
		$bot->editMessageReplyMarkup($cid, $messageId, $keyboard);

	} else if ($data == "DislikeOptionDealReport") {
		$bot->answerCallbackQuery($cbid, "Спасибо за оценку!",true);
		$likesFile = file_get_contents("include/lesegais_report/likes/like.txt");
		$dislikeFile = file_get_contents("include/lesegais_report/likes/dislike.txt");
		$dislikeFile++;
		file_put_contents("include/lesegais_report/likes/dislike.txt", $dislikeFile);
		$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup (
			[

				[
					['callback_data' => 'beVoted', 'text' => "👍🏽 → $likesFile"],
					['callback_data' => 'beVoted', 'text' => "👎🏽 → $dislikeFile"]
				],
				[
					['callback_data' => 'lesegais_report_setup', 'text' => 'Настроить уведомления']
				],
				[
					['callback_data' => 'menu', 'text' => 'Главное меню']
				]
			]
		);
		$bot->editMessageReplyMarkup($cid, $messageId, $keyboard);
	} else {
        // Убираем прогрузку
    $bot->answerCallbackQuery($cbid);
    }

	//Задать вопрос
	if ($data == "start_question") {
		$action = "startQuestion";
		sendAction($cid, $action);
	    require_once("include/start_question.php");
    	}
	// Меню
    if ($data == "menu") {
		$action = "menu";
		sendAction($cid, $action);
	    require_once("include/menu/main_menu.php");
    	}
	// Меню функций
    if ($data == "func_menu") {
		$action = "func_menu";
		sendAction($cid, $action);
	    require_once("include/menu/func_menu.php");
    	}
	// Меню Контакты
    if ($data == "contacts") {
		$action = "contacts";
		sendAction($cid, $action);
	    require_once("include/menu/contacts.php");
    	}
	// Меню Документы
    if ($data == "doc_menu") {
		$action = "doc_menu";
		sendAction($cid, $action);
	    require_once("include/menu/doc_menu.php");
    	}
	// Меню Кодекс
    if ($data == "doc_code") {
		$action = "doc_code";
		sendAction($cid, $action);
	    require_once("include/menu/documents/code.php");
    	}
	// Меню Инструкции
    if ($data == "doc_manual") {
		$action = "doc_manual";
		sendAction($cid, $action);
	    require_once("include/menu/documents/manual.php");
    	}
	// Меню НПА
    if ($data == "doc_npa") {
		$action = "doc_npa";
		sendAction($cid, $action);
	    require_once("include/menu/documents/npa.php");
    	}
	// Проверка координат
    if ($data == "check_coordinate") {
		$action = "checkCoordinate";
		sendAction($cid, $action);
	    require_once("include/check_coordinate.php");
    	}
	// Отправить вопрос
    if ($data == "send_question") {
		$action = "sendQuestion";
		sendAction($cid, $action);
        require_once("include/send_question.php");
    }
	// Расшифровка QR
    if ($data == "qrcode") {
		$action = "DecodeQR";
		sendAction($cid, $action);
        require_once("include/qrcode.php");
    }
	// Услуги Декларирования
	if ($data == "declaration") {
		$action = "declaration";
		sendAction($cid, $action);
        require_once("include/declaration.php");
    }
	// Отслеживание отчетов ЛесЕГАИС
	if ($data == "lesegais_report_menu") {
		$action = "lesegais_report_menu";
		sendAction($cid, $action);
		require_once("include/menu/lesegais_report_menu.php");
    }
	// Настройка для ЛесЕГАИС: Проверка отчетов
	if ($data == "lesegais_report_setup") {
		$action = "lesegais_report_setup";
		sendAction($cid, $action);
        require_once("include/lesegais_report/lesegais_report_setup.php");
    }
	// Добавить (предварительно, меню) компанию для ЛесЕГАИС: Проверка отчетов
	if ($data == "lesegais_report_add") {
		$action = "lesegais_report_add";
		sendAction($cid, $action);
        require_once("include/lesegais_report/lesegais_report_add.php");
    }
	// Добавить окончательно в базу компанию для ЛесЕГАИС: Проверка отчетов
	if ($data == "lesegais_report_setup_inn") {
		$action = "lesegais_report_setup_inn";
		sendAction($cid, $action);
        require_once("include/lesegais_report/lesegais_report_setup_inn.php");
    }
	// Удалить компанию
	if ($data == "lesegais_report_del") {
		$action = "lesegais_report_del";
		sendAction($cid, $action);
        $InfoMsgDelete = $bot->sendMessage($cid, "Удаление ИНН сейчас не работает. \n\nЕсли нужно удалить, напишите в раздел «Задать вопрос».", "html");
		$InfoMsgDeleteID = $InfoMsgDelete->getMessageId();
		sleep(4);
		$bot->deleteMessage($cid, $InfoMsgDeleteID);
    }
	// Личный кабинет
	if ($data == "cabinet") {
		$action = "cabinet";
		require_once("include/menu/cabinet.php");
    }
	// Личный кабинет → Получить ссылку для приглашения
	if ($data == "get_reff_link") {
		$action = "get_reff_link";
		$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup (
    		[
    			[
    				['callback_data' => 'menu', 'text' => 'Главное меню']
    			]
    		]
    	);
        $bot->sendMessage($cid, "Поделитесь со всеми, кто связан лесом и дорогами! \n\n<b>Ссылка:</b> t.me/fgislk_bot?start=$cid", "html", null, null, $keyboard);
    }
	// Выход после рассылки
	if ($data == "back_from_adv") {
		$action = "back_from_adv";
		sendAction($cid, $action);
		$keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup (
    		[
    			[
    				['callback_data' => 'menu', 'text' => 'Главное меню']
    			]
    		]
    	);
        $bot->sendMessage($cid, "Ваша пригласительная ссылка всегда будет доступна в личном кабинете 😉", "html", null, null, $keyboard);
    }

	if ($data == "likeAnswer") {
		$bot->sendMessage(112865662, "Пользователю $cid - понравился ответ! :)");
		require_once("include/menu.php");
	} else if ($data == "dislikeAnswer") {
		$bot->sendMessage(112865662, "Пользователю $cid - <b>не понравился ответ :(</b>", 'html');
		require_once("include/menu.php");
	}
	if ($data == "likeQR") {
		$bot->sendMessage(112865662, "Пользователю $cid - понравилась расшифровка QR! :)");
		require_once("include/menu.php");
	} else if ($data == "dislikeQR") {
		$bot->sendMessage(112865662, "Пользователю $cid - <b>не понравилась расшифровка QR :(</b>", 'html');
		require_once("include/menu.php");
	}

}, function($update){
	$callback = $update->getCallbackQuery();
	if (is_null($callback) || !strlen($callback->getData()))
		return false;
	return true;
});

// Отлов любых сообщений
$bot->on(function($Update) use ($bot){
    $message = $Update->getMessage();
	$mtext = $message->getText();
	$mid = $message->getMessageId();
	$cid = $message->getChat()->getId();

	// Если есть фото, запрашивает ИД фото
	$photoId = $Update->getMessage()->getPhoto();

    //Проверим, есть ли пользователь в новой БД обновленного бота?
	//Если нет, мы просто предложим заного пройти регистрацию
	if (checkUserAfterUpdate($cid) == false) {
		$bot->sendMessage($cid, "<b>Мы обновили функионал бота</b> \n\nДля начала работы нажмите /start", "html");
		die;
	}

    //Пользователь написал сообщение. Нужно проверить, куда он обращается?
    require_once("include/cookie.php");

}, function($message) use ($name){
	return true; // когда тут true - команда проходит
});
// запускаем обработку
$bot->run();


//$t = $bot->sendMessage(112865662, "Hello");

$do = $_GET["do"];
if ($do == "admin") {
	$DIR = posix_getcwd();
	require_once("admin/index.php");
}
if ($do == "lesegais_report_buyer")  {
	require_once("include/lesegais_report/check_buyer.php");
}
if ($do == "lesegais_report_seller")  {
	require_once("include/lesegais_report/check_seller.php");
}
if ($do == "deal_check_seller")  {
	require_once("include/lesegais_report/deal_checkSeller.php");
}
if ($do == "deal_check_buyer")  {
	require_once("include/lesegais_report/deal_checkBuyer.php");
}

################################# TEST
/*
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

// Выполняем запрос
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
	if ($result == true) {
		$resultSuccess++;
	}

	curl_close($curl);

	$newResult = json_decode($result, true);
	$newResult = $newResult['data']['searchReportWoodDeal']['content'];
	$countArray = count($newResult); // Количество массивов

	print_r ("Res:$newResult");
	var_dump $result;

*/

################################# END
print_r ("<pre>Work.</pre>");
?>
