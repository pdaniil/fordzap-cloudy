<?php
defined('_ASTEXE_') or die('No access');

require_once($_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try
{
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e)
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");
?>

<div class="col-lg-12">
    <div class="hpanel">

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $part = $_POST['part'];
    $replacementInterval = $_POST['replacement_interval'];

    $query = "INSERT INTO telegram_car_to (part, replacement_interval) VALUES (?, ?)";
    $stmt = $db_link->prepare($query);
    $stmt->execute([$part, $replacementInterval]);

    // Проверка результата выполнения SQL-запроса и отправка соответствующего ответа
    if ($stmt) {
        // Успешное добавление записи
        echo '<div class="alert alert-success alert-dismissable" id="success_div">
    <button type="button" class="close" onclick="$(\'#success_div\').fadeOut();">×</button>
    <p>Запись добавлена.</p>
</div>';
    } else {
        // Ошибка при добавлении записи
        echo '<div class="alert alert-danger alert-dismissable" id="danger_div">
    <button type="button" class="close" onclick="$(\'#danger_div\').fadeOut();">×</button>
    <p>Ой, что-то пошло не так.</p>
</div>';

    }
}

?>


        <div id="success-notification" class="alert alert-success fade in" style="display:none;">
            <a href="#" class="close" data-dismiss="alert">&times;</a>
            <strong>Успех!</strong> Данные обновлены.
        </div>
        <div class="panel-heading hbuilt">
            <p>Управление данными ТО Бота</p>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <h3>Поиск в таблице с данными о ТО</h3>
                    <p><strong>Режим редактирования</strong> Для выполнения поиска, пожалуйста, введите ключевое слово в поле поиска. Если вы оставите поле пустым и нажмете кнопку "Найти", будут выведены все записи из таблицы. Затем нажмите на элемент, который необходимо отредактировать.</p>
                    <form id="searchForm">
                        <div class="input-group">
                            <input type="text" id="searchInput" class="form-control" placeholder="Поиск...">
                            <span class="input-group-btn">
                                <button id="search-button" class="btn btn-default">Найти</button>
                            </span>
                        </div>
                    </form>
                    <div id="searchResults"></div>
                </div>
                <div class="col-md-6">
                    <h3>Добавление новой записи</h3>
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="part">Позиция ТО:</label>
                            <input type="text" class="form-control" id="part" name="part" required>
                        </div>
                        <div class="form-group">
                            <label for="replacementInterval">Данные о ТО:</label>
                            <textarea class="form-control" id="replacementInterval" name="replacement_interval" required  style="resize: vertical; min-height: 100px;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Добавить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="editModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Содержимое модального окна -->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Редактирование элемента</h4>
            </div>
            <div class="modal-body">
                <!-- Форма для редактирования элемента -->
                <form id="editForm" action=""> <!--method="post" action="/cp/content/telegram-bot/search_to.php"-->
                    <input type="hidden" name="id" id="idInput" value="">
                    <div class="form-group">
                        <label for="partInput">Позиция ТО:</label>
                        <input type="text" class="form-control" id="partInput" name="part" value="">
                    </div>
                    <div class="form-group">
                        <label for="replacementIntervalInput">Данные о ТО:</label>
                        <textarea class="form-control" id="replacementIntervalInput" name="replacement_interval" value=""></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    $(document).ready(function() {
        $('#searchForm').submit(function(e) {
            e.preventDefault();

            var searchQuery = $('#searchInput').val();

            $.ajax({
                type: 'POST',
                url: '/cp/content/telegram-bot/search_to.php',
                data: { searchQuery: searchQuery },
                success: function(response) {
                    $('#searchResults').html(response);
                }
            });
        });
    });

    function updateRecord() {
        var id = $("#idInput").val();
        var part = $("#partInput").val();
        var replacementInterval = $("#replacementIntervalInput").val();

        // Отправка AJAX-запроса на сервер
        $.ajax({
            url: "/cp/content/telegram-bot/ajax_edit_to.php",
            type: "POST",
            data: {
                id: id,
                part: part,
                replacement_interval: replacementInterval
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    // Успешный AJAX-запрос
                    $("#success-notification").text(data.message).fadeIn().delay(3000).fadeOut();
                    $("#editModal").modal("hide");
                    $("#searchInput").val("");
                    $("#searchForm")[0].reset();
                    $("#searchResults").html("");
                } else {
                    // Обработка неуспешного ответа
                    alert(data.message); // Или какой-либо другой способ уведомления пользователя
                }
            },
            error: function(error) {
                // Обработка ошибки AJAX-запроса
                alert("Ошибка при выполнении AJAX-запроса");
            }

        });
    }

    function setParams(button) {
        var id = button.getAttribute('data-id');
        var part = button.getAttribute('data-part');
        var replacementInterval = button.getAttribute('data-replacement-interval');

        var idInput = document.querySelector('#idInput');
        var partInput = document.querySelector('#partInput');
        var replacementIntervalInput = document.querySelector('#replacementIntervalInput');

        idInput.value = id;
        partInput.value = part;
        replacementIntervalInput.value = replacementInterval;

        // Открытие модального окна
        setTimeout(function() {
            $('#editModal').modal('show');
        }, 200);
    }

    // Обработчик события отправки формы
    $("#editForm").submit(function(e) {
        e.preventDefault();

        // Вызов функции для сохранения данных
        updateRecord();
    });
</script>
