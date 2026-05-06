<?php
defined('_ASTEXE_') or die('No access');

//подключаем бота
require_once($_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php");

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");



use TelegramBot\Api\Client;

$DP_Config = new DP_Config;
$bot = new Client($DP_Config->telegram_bot_token);
?>

<div class="col-lg-12">
    <div class="hpanel">

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение сообщения из формы
    $message = $_POST['message'];
    //Извлекаем всех пользователей Бота
    $query = "SELECT chat_id FROM telegram_users";

    // Подготовка и выполнение запроса
    $stmt = $db_link->prepare($query);
    $stmt->execute();

    // Извлечение результатов запроса в виде массива
    $chatIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    //убираем повторяющиеся chatId
    $chatIds = array_unique($chatIds);
    // Вывод chat_id
    foreach ($chatIds as $chatId) {

        try {
            $bot->sendMessage($chatId, $message);
        }catch (Exception $e) {}
    }

    if (!empty($message)) {
        // Отправка сообщения
        echo '<div class="alert alert-success alert-dismissable" id="success_div">
    <button type="button" class="close" onclick="$(\'#success_div\').fadeOut();">×</button>
    <p><b>Сообщение отправлено</b></p>
    <p>' . $message . '</p>
</div>';
    }
}

?>


                <div class="panel-heading hbuilt">
                    <p>Форма отправки сообщения всем пользователям Телеграм Бота</p>
                </div>
                <div class="panel-body">
                    <h3>Требования Telegram</h3>
                    <ul class="list-unstyled">
                        <li><i class="fa fa-check"></i> Поддерживается только текст до 4096 символов</li>
                    </ul>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="message">Сообщение:</label>
                            <textarea class="form-control" id="message" name="message"
                                      oninput="countCharacters()"></textarea>
                        </div>
                        <p>Текущая длина текста: <span id="characterCount">0</span></p>
                        <button type="submit" class="btn btn-primary">Отправить</button>
                    </form>
                </div>
            </div>
        </div>


<script>
    function countCharacters() {
        var messageInput = document.getElementById('message');
        var characterCount = document.getElementById('characterCount');
        var messageLength = messageInput.value.length;
        characterCount.textContent = messageLength;
    }
</script>
</div>

