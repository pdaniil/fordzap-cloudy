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
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image']; // Получение информации о загруженном файле
        $caption = $_POST['message']; // Получение сообщения из формы
        $query = "SELECT chat_id FROM telegram_users";
        $stmt = $db_link->prepare($query);
        $stmt->execute();
        $chatIds = $stmt->fetchAll(PDO::FETCH_COLUMN); // Извлечение результатов запроса в виде массива

        // Создание временного имени файла
        $tempFileName = tempnam(sys_get_temp_dir(), 'telegram_');

        //убираем повторяющиеся chatId
        $chatIds = array_unique($chatIds);

        // Сохранение загруженного файла в папке "uploads_tmp"
        $targetDir = $_SERVER["DOCUMENT_ROOT"] . '/uploads_tmp/';
        $tempFilePath = $targetDir . $image['name'];

        if (move_uploaded_file($image['tmp_name'], $tempFilePath)) {
            // Создание объекта CURLFile из сохраненного файла
            $photo = new \CURLFile($tempFilePath);

            // Отправка изображения с подписью в сообщении
            foreach ($chatIds as $chatId) {
                try {
                    $bot->sendPhoto($chatId, $photo, $caption);
                } catch (Exception $e) {}
            }

            // Удаление временного файла
            unlink($tempFilePath);

            // Сообщение об успешной отправке
            echo '
            <div class="alert alert-success alert-dismissable" id="success_div">
                <button type="button" class="close" onclick="$(\'#success_div\').fadeOut();">×</button>
                <p>Сообщение успешно отправлено.</p>
            </div>';

        } else {
            // Обработка ошибки перемещения файла
            echo '
            <div class="alert alert-danger alert-dismissable" id="danger_div">
                <button type="button" class="close" onclick="$(\'#danger_div\').fadeOut();">×</button>
                <p>Ошибка перемещения файла.</p>
            </div>';

        }
    }
    else {
        ?>
        <div class="alert alert-danger alert-dismissable" id="danger_div">
            <button type="button" class="close" onclick="clearAlert('danger_div');">×</button>
            <p>Произошла ошибка</p>
        </div>
        <?php
    }
}
?>
        <div class="panel-heading hbuilt">
            <p>Форма отправки Фото всем пользователям</p>
        </div>
        <div class="panel-body">
            <h3>Требования Telegram</h3>
            <ul class="list-unstyled">
                <li><i class="fa fa-check"></i> Максимальный размер фотографий - 10 МБ</li>
                <li><i class="fa fa-check"></i> Общая ширина и высота фотографии не должны превышать 10000px</li>
                <li><i class="fa fa-check"></i> Соотношение ширины и высоты должно быть не более 20</li>
            </ul>
            <form enctype="multipart/form-data" method="post" action="">

                <div class="col-md-6 input-group">
                    <input id="file-input" class=" form-control" type="text" readonly/>
                    <div class="input-group-btn">
                        <label for="image" class="btn btn-default">Добавить</label>
                        <input id="image" type="file" class="btn btn-default col-xs-1" name="image" style="visibility:hidden;" onchange="updateFileName(this)"/>
                    </div>
                </div>
                <div class="form-group">
                    <label for="message">Подпись к изображению:</label>
                    <textarea class="form-control" id="message" name="message"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" onclick="showSuccessMessage()">Отправить</button>
            </form>
        </div>
    </div>
</div>
<script>
    function clearAlert(alertId) {
        var alertElement = document.getElementById(alertId);
        if (alertElement) {
            alertElement.style.display = 'none';
        }
    }
</script>
<script>
    function updateFileName(input) {
        var fileName = input.files[0].name;
        document.getElementById('file-input').value = fileName;
    }
</script>