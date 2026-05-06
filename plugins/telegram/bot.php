<?php
//подключаем бота
require_once ($_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");


use TelegramBot\Api\Client;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use \TelegramBot\Api\Types\ReplyKeyboardMarkup;


$DP_Config = new DP_Config;//Конфигурация CMS
$db_Link = new mysqli($DP_Config->host, $DP_Config->user, $DP_Config->password, $DP_Config->db);
$db_Link->set_charset("utf8mb4");


try {
    $bot = new Client($DP_Config->telegram_bot_token);


    // Обработка команды /start
    $bot->command('start', function ($message) use ($bot, $db_Link) {
        $chatId = $message->getChat()->getId();
        $bot->sendChatAction($chatId, 'typing');

        $buttonRequestContact = [
            'text' => 'Отправить номер телефона',
            'request_contact' => true,
        ];

        $keyboard = new ReplyKeyboardMarkup(array(array($buttonRequestContact)), true, true);

        $text = "🌟 Приветствуем вас! 🌟 Для обеспечения вашего комфорта и максимально удобного взаимодействия с нами, мы просим вас предоставить свой номер телефона. ☎️Для этого нажмите кнопку \"Отправить номер телефона\" внизу экрана. Это позволит нам отправлять вам сообщения о статусе ваших заказов и обеспечить более эффективную коммуникацию. 📲 Будем рады быть на связи с вами! 😊";

        $bot->sendMessage($message->getChat()->getId(), $text, null, false, null, $keyboard);
    });

    // Обработка сообщения с контактом
    $bot->on(function (Update $update) use ($bot, $db_Link) {
        $message = $update->getMessage();
        $contact = $message->getContact();
        $chatId = $message->getChat()->getId();
        $bot->sendChatAction($chatId, 'typing');

        if ($contact) {
            // Получен контакт, обработка данных
            $phoneNumber = $contact->getPhoneNumber();
            $firstName = $contact->getFirstName();
            $chatId = $message->getChat()->getId();

            // Поиск совпадений номера телефона в таблице users_profiles

            $phoneNumber = str_replace(['+', '-', '(', ')'], '', $phoneNumber);
            $phoneNumber = substr($phoneNumber, 1);
            $query = "SELECT user_id FROM users WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), '-', ''), '(', ''), ')', ''), ' ', '') LIKE '%$phoneNumber'";



            $result = $db_Link->query($query);

            // Если найдено совпадение, извлечь user_id
            $user_id = null;
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $user_id = $row['user_id'];
            }
            // Запись chat_id и номера телефона в таблицу telegram_users

            // Проверка наличия записи с совпадением по номеру телефона
            $query = "SELECT * FROM telegram_users WHERE number_phone = '$phoneNumber'";
            $result = $db_Link->query($query);

            if ($result && $result->num_rows > 0) {
                // Если запись уже существует, выполняем обновление поля `chat_id`
                $updateQuery = "UPDATE telegram_users SET chat_id = '$chatId', user_id = '$user_id' WHERE number_phone = '$phoneNumber'";
                $db_Link->query($updateQuery);

            } else {
                // Если записи с таким номером телефона не существует, выполняем вставку новой записи
                $insertQuery = "INSERT INTO telegram_users (user_id, chat_id, number_phone) VALUES ('$user_id', '$chatId', '$phoneNumber')";
                $db_Link->query($insertQuery);
            }

            /*$keyboard = new InlineKeyboardMarkup(
                [
                    [
                        ['text' => 'Запрос на запчасти', 'callback_data' => 'vin'],
                        ['text' => 'Контакты', 'callback_data' => 'contact']
                    ]
                ]
            );*/

            // Отправляем сообщение с благодарностью пользователю
            $text = "Спасибо, $firstName, за предоставленный контакт: $phoneNumber! Выбери необходимые пункты меню.";

            $bot->sendMessage($chatId, $text/*, null, false, null, $keyboard*/);
        }

    }, function (Update $update) {
        // Проверяем, является ли сообщение контактом
//        $message = $update->getMessage();
//        $contact = $message->getContact();
        return $update !== null;
    });


    /* /adress ---Обработка команды adress выводит данные о точках продаж*/
    $bot->command('address', function ($message) use ($bot, $db_Link) {
        $chatId = $message->getChat()->getId();
        $bot->sendChatAction($chatId, 'typing');
        $query = "SELECT * FROM shop_offices";
        $result = $db_Link->query($query);
        $message = '';
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $message .= $row['caption'] . "\n" .
                    $row['city'] . "\n" .
                    $row['address'] . "\n" .
                    $row['phone'] . "\n" .
                    $row['email'] . "\n" .
                    $row['timetable'] . "\n\n";
            }
        }
        $keyboard = new InlineKeyboardMarkup(
            [
                [
                    ['text' => 'Ссылка на контакты', 'url' => 'https://fordzap.com/kontakty']
                ]
            ]
        );

        $bot->sendMessage($chatId, $message, 'html', false, null, $keyboard);
    });
    /* end /adress ---*/

    /* /refresh ---Обработка команды refresh обновляет chat_id*/
    $bot->command('refresh', function ($message) use ($bot) {
        $chatId = $message->getChat()->getId();
        $bot->sendChatAction($chatId, 'typing');

        $buttonRequestContact = [
            'text' => 'Обновить контакт',
            'request_contact' => true,
        ];

        $keyboard = new ReplyKeyboardMarkup(array(array($buttonRequestContact)), true, true);

        $text = "Чтобы обновить ваши данные, отправьте контакт по кнопке ниже";

        $bot->sendMessage($message->getChat()->getId(), $text, null, false, null, $keyboard);

    });
    /* end /refresh ---*/


    $bot->command('about', function ($message) use ($bot) {
        $chatId = $message->getChat()->getId();
        $bot->sendChatAction($chatId, 'typing');
        $message = "Привет! 👋 Я рад приветствовать тебя. Я - 🤖 бот, и вот что я умею:\n✅Автоматически уведомлять о состояниях позиций заказов и самих заказов. 📦🚚 \n✅Рассказывать примерную информацию по техническому обслуживанию (ТО) автомобиля. 🚗💡 Если тебе нужна информация о ТО автомобиля, просто напиши первое слово \"то\" и затем укажи, например, \"свечи\". \n✅Присылать интересную информацию о наших акциях. 📰";


        $bot->sendMessage($chatId, $message);

    });

    $bot->command('price', function ($message) use ($bot) {
        $chatId = $message->getChat()->getId();
        $bot->sendChatAction($chatId, 'typing');
        $message = 'Чтобы посмотреть стоимость интересующей детали введите "цена АРТИКУЛ".';

        $bot->sendMessage($chatId, $message);

    });

    // Обработка команды /zapros
    $bot->command('zapros', function ($message) use ($bot, $db_Link) {
        $chatId = $message->getChat()->getId();
        $bot->sendChatAction($chatId, 'typing');

        $keyboard = new InlineKeyboardMarkup(
            [
                [
                    ['text' => 'Ссылка на страницу запроса', 'url' => 'https://fordzap.com/zapros-prodavczu']
                ]
            ]
        );

        $text = "🔍 У вас возникли вопросы о запчастях для вашего автомобиля? Не беспокойтесь, мы здесь, чтобы помочь! Нажмите на кнопку ниже и оставьте свой запрос на детали. 🚗💡";

        $bot->sendMessage($message->getChat()->getId(), $text, null, false, null, $keyboard);
    });


    /*Ответ по ТО*/
    $bot->on(function (Update $update) use ($bot, $db_Link) {
        $message = $update->getMessage();
        $chatId = $message->getChat()->getId();
        //$bot->sendChatAction($chatId, 'typing');

        $text = $message->getText();
        $words = explode(' ', trim($text)); // Разделяем текст на отдельные слова
        $firstWord = mb_strtolower($words[0]);
        $part = mb_strtolower($words[1]);


        if ($firstWord == 'то') {

            $message = '';
            $query = "SELECT * FROM telegram_car_to WHERE part LIKE '%" . $words[1] . "%'";
            $stmt = $db_Link->query($query);



            if ($stmt && $stmt->num_rows > 0) {
                $message .= "Вот что нашлось: \n";
                while ($row = $stmt->fetch_assoc()) {

                    $message .= $row['part'] . ": " . $row['replacement_interval']."\n\n";
                }

                $bot->sendChatAction($chatId, 'typing');
                $bot->sendMessage($chatId, $message);

            } else {

                $message .= "Я не распознал - \"".$words[1]."\", попробуйте изменить запрос, например, вместо \"масло\" напишите \"масл\"";
                $bot->sendChatAction($chatId, 'typing');
                $bot->sendMessage($chatId, $message);
            }
        }
        elseif ($firstWord == 'цена'){

            $keyboard = new InlineKeyboardMarkup(
                [
                    [
                        ['text' => 'Цены и стоимость '. $part, 'url' => 'https://fordzap.com/parts/brands/'.$part]
                    ]
                ]
            );

            $message = "Для просмотра стоимости ".$part." - перейдите по ссылке ниже." ;
            $bot->sendChatAction($chatId, 'typing');
            $bot->sendMessage($chatId, $message, null, false, null, $keyboard);
        }
        elseif($firstWord == ""){

            return false;/*$message = "Я не распознал - \"".$firstWord."\".";
            $bot->sendChatAction($chatId, 'typing');
            $bot->sendMessage($chatId, $message);*/

        }else{
            $message = "Я не распознал - \"".$firstWord."\".";
            $bot->sendChatAction($chatId, 'typing');
            $bot->sendMessage($chatId, $message);
        }

    }, function () {
        return true;
    });

    $bot->run();
} catch (\TelegramBot\Api\Exception $e) {
    echo $e->getMessage();
}
function writeToLog($value) {
    $logFile = 'log.txt';

    // Преобразование значения переменной в строку
    if (is_object($value) || is_array($value)) {
        $logValue = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        $logValue = $value;
    }

    // Получение текущей даты и времени
    $timestamp = date("Y-m-d H:i:s");

    // Форматирование записи для лога
    $logEntry = "[$timestamp] $logValue" . PHP_EOL;

    // Запись в лог файл
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}