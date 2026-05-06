<?php
defined('_ASTEXE_') or die('No access');

// подключаем бота
require_once($_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");

use TelegramBot\Api\Client;

$DP_Config = new DP_Config;
$bot = new Client($DP_Config->telegram_bot_token);
?>

<div class="col-lg-12">
    <div class="hpanel">

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $image = $_FILES['file']; // Получение информации о загруженном файле
                $caption = $_POST['message']; // Получение сообщения из формы
                $chatId = $_POST['chat_id'];

                // Создание временного имени файла
                $tempFileName = tempnam(sys_get_temp_dir(), 'telegram_');

                // Сохранение загруженного файла в папке "uploads_tmp"
                $targetDir = $_SERVER["DOCUMENT_ROOT"] . '/uploads_tmp/';
                $tempFilePath = $targetDir . $image['name'];

                if (move_uploaded_file($image['tmp_name'], $tempFilePath)) {

                    // Создание объекта CURLFile из сохраненного файла
                    $doc = new \CURLFile($tempFilePath);

                    // Отправка файла с подписью в сообщении
                    try {
                        $bot->sendDocument($chatId, $doc, $caption);
                    } catch (Exception $e) {
                        echo '
        <div class="alert alert-danger alert-dismissable" id="danger_div">
            <button type="button" class="close" onclick="$(\'#danger_div\').fadeOut();">×</button>
            <p>Произошла ошибка</p>
        </div>';
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
            } else {
                echo '
        <div class="alert alert-danger alert-dismissable" id="danger_div">
            <button type="button" class="close" onclick="$(\'#danger_div\').fadeOut();">×</button>
            <p>Произошла ошибка</p>
        </div>';
            }
        }
        ?>

        <div class="panel-heading hbuilt">
            <p>Форма отправки документа</p>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <h3>Выбор получателя</h3>
                    <p>Поиск только среди пользователей, которые предоставили Боту свои контактные данные. Выбрать можно
                        только одного пользователя для отправки. Поиск осуществляется по всем полям указанным в
                        регистрации, а так же ID клиента.</p>
                    <div class="input-group">
                        <input type="text" id="search-input" class="form-control" placeholder="Поиск...">
                        <span class="input-group-btn">
                        <button id="search-button" class="btn btn-default">Найти</button>
                        </span>
                    </div>
                    <div id="search-results"></div>
                </div>
                <div class="col-md-6">
                    <h3>Требования Telegram</h3>
                    <ul class="list-unstyled">
                        <li><i class="fa fa-check"></i> Максимальный размер фотографий - 10 МБ</li>
                        <li><i class="fa fa-check"></i> Максимальный размер других файлов - 50 МБ</li>
                    </ul>
                    <form enctype="multipart/form-data" method="post" action="">
                        <input type="hidden" value="" id="hidden-chatid" name="chat_id">
                        <div class="col-md-12 col-xs-12 input-group">
                            <input id="file-input" class=" form-control" type="text" readonly/>
                            <div class="input-group-btn">
                                <label for="file" class="btn btn-default">Добавить</label>
                                <input id="file" type="file" class="btn btn-default col-xs-1" name="file" style="visibility:hidden;" onchange="updateFileName(this)"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="message">Подпись к документу:</label>
                            <textarea class="form-control" id="message" name="message"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" onclick="showSuccessMessage()">Отправить</button>
                    </form>
                </div>
            </div>
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
    $(document).ready(function () {
        // Функция для выполнения поиска
        function performSearch() {
            var searchQuery = $('#search-input').val().trim();

            // Отправка AJAX-запроса на сервер для поиска
            $.ajax({
                url: '/cp/content/telegram-bot/search.php',
                method: 'POST',
                data: { searchQuery: searchQuery },
                success: function (response) {
                    $('#search-results').html(response);
                }
            });
        }

        // Обработка события клика на кнопку "Найти"
        $('#search-button').on('click', function () {
            performSearch();
        });

        // Обработка события нажатия клавиши Enter в поле поиска
        $('#search-input').on('keypress', function (e) {
            if (e.which === 13) { // Код клавиши Enter
                performSearch();
            }
        });

        // Обработка события клика на результат поиска
        $('#search-results').on('click', '.search-result', function () {
            var selectedResult = $(this);
            var chatId = selectedResult.data('chatid');

            // Скрытие всех остальных результатов поиска
            $('.search-result').not(selectedResult).hide();

            // Отображение выбранного результата поиска
            selectedResult.show();

            // Записываем значение chatId в скрытое поле input
            $('#hidden-chatid').val(chatId);
        });
    });
</script>
<script>
    function updateFileName(input) {
        var fileName = input.files[0].name;
        document.getElementById('file-input').value = fileName;
    }
</script>



