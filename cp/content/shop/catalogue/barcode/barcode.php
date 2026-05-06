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
            $article = $_POST['article'];
            $brand = $_POST['brand'];
            $name = $_POST['name'];
            $barcode = $_POST['barcode'];

            $query = "INSERT INTO barcode (article, brand, name, barcode) VALUES (?, ?, ?, ?)";
            $stmt = $db_link->prepare($query);
            $stmt->execute([$article, $brand, $name, $barcode]);

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
            <p>Управление данными штрихкодов</p>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <h3>Поиск в таблице штрихкодов</h3>
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
                            <label for="article">Артикул товара:</label>
                            <input type="text" class="form-control" id="article" name="article" required>
                        </div>
                        <div class="form-group">
                            <label for="brand">Бренд товара:</label>
                            <input type="text" class="form-control" id="brand" name="brand" required>
                        </div>
                        <div class="form-group">
                            <label for="name">Наименование товара:</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="barcode">Штрих-код:</label>
                            <input type="text" class="form-control" id="barcode" name="barcode" required>
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
                        <label for="articleInput">Артикул товара:</label>
                        <input type="text" class="form-control" id="articleInput" name="article" value="">
                    </div>
                    <div class="form-group">
                        <label for="brandInput">Бренд товара:</label>
                        <input type="text" class="form-control" id="brandInput" name="brand" value="">
                    </div>
                    <div class="form-group">
                        <label for="nameInput">Наименование товара:</label>
                        <input type="text" class="form-control" id="nameInput" name="name" value="">
                    </div>
                    <div class="form-group">
                        <label for="barcodeInput">Штрих-код:</label>
                        <input type="text" class="form-control" id="barcodeInput" name="barcode" value="">
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
                url: '../../content/shop/catalogue/barcode/search_barcode.php',
                data: {
                    searchQuery: searchQuery,
                    csrf_guard_key: '<?php echo $user_session["csrf_guard_key"]?>' // Добавление CSRF-токена
                },
                success: function(response) {
                    $('#searchResults').html(response);
                }
            });
        });
    });

    function updateRecord() {
        var id = $("#idInput").val();
        var article = $("#articleInput").val();
        var brand = $("#brandInput").val();
        var name = $("#nameInput").val();
        var barcode = $("#barcodeInput").val();

        // Отправка AJAX-запроса на сервер
        $.ajax({
            url: "../../content/shop/catalogue/barcode/ajax_edit_barcode.php",
            type: "POST",
            data: {
                id: id,
                article: article,
                brand: brand,
                name: name,
                barcode: barcode,
                csrf_guard_key: '<?php echo $user_session["csrf_guard_key"]?>' // Добавление CSRF-токена
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
        var article = button.getAttribute('data-article');
        var brand = button.getAttribute('data-brand');
        var name = button.getAttribute('data-name');
        var barcode = button.getAttribute('data-barcode');

        var idInput = document.querySelector('#idInput');
        var articleInput = document.querySelector('#articleInput');
        var brandInput = document.querySelector('#brandInput');
        var nameInput = document.querySelector('#nameInput');
        var barcodeInput = document.querySelector('#barcodeInput');

        idInput.value = id;
        articleInput.value = article;
        brandInput.value = brand;
        nameInput.value = name;
        barcodeInput.value = barcode;

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
