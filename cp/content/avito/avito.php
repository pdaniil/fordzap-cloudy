<?php
defined('_ASTEXE_') or die('No access');
require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");

$DP_Config = new DP_Config;

?>


<div class="col-md-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            <h4>Автозагрузка Авито</h4>
        </div>
        <div class="panel-body">
            <h4>Загрузка объявлений на авито возможна только 1 раз в 60 минут</h4>
            <p>Перед отправкой файла обновите файл с товарами по кнопке "Генерация файла".</p>
            <p>В выгрузку попадут только те товары, категории которых привязаны к категориям Авито <a href="https://fordzap.com/cp/avito-categories" class="btn btn-xs btn-info">связка категорий</a></p>
            <div id="successMessage" class="alert alert-success" style="display: none; margin-bottom: 10px; margin-top: 10px;"></div>
            <button id="sendToAvitoBtn" class="btn btn-success">Генерация файла</button>
            <img id="loadingImage" src="/cp/content/avito/Spinner-3.gif" alt="Загрузка..." style="display: none; margin-bottom: 10px; width: 30px;"/>
            <button id="loadToAvitoBtn" class="btn btn-primary">Запросить считать файл Авито</button>
            <img id="loadingImage1" src="/cp/content/avito/Spinner-3.gif" alt="Загрузка..." style="display: none; margin-bottom: 10px; width: 30px;"/>

            <button id="updateStocksBtn" class="btn btn-warning">Обновить количество</button>
            <img id="loadingImageStocks" src="/cp/content/avito/Spinner-3.gif" alt="Загрузка..." style="display: none; margin-bottom: 10px; width: 30px;"/>

            <div id="stockSuccess" class="alert alert-success" style="display: none; margin-top: 10px;"></div>
            <div id="stockError" class="alert alert-danger" style="display: none; margin-top: 10px;"></div>

        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#sendToAvitoBtn').on('click', function() {
            $('#loadingImage').show();

            $.ajax({
                url: '/cp/content/avito/AjaxMakeCSV.php',
                method: 'GET',
                data: 'csrf_guard_key=<?php echo $user_session["csrf_guard_key"]?>',
                success: function(response) {
                    console.log('Успешно отправлено:', response);
                    $('#successMessage').html('Успешно создан файл, далее его можно отправить в Авито вручную по кнопке или он автоматически загрузится по расписанию').show();
                },
                error: function(xhr, status, error) {
                    console.error('Произошла ошибка:', error);
                },
                complete: function() {
                    $('#loadingImage').hide();
                }
            });
        });

        $('#loadToAvitoBtn').on('click', function() {
            $('#loadingImage1').show();

            $.ajax({
                url: '/cp/content/avito/runAutoloadAvito.php',
                method: 'POST',
                data: {
                    csrf_guard_key: '<?php echo $user_session["csrf_guard_key"]; ?>'
                },
                success: function(response) {
                    console.log('Ответ от сервера:', response);

                    if (response.error) {
                        console.error('Произошла ошибка на сервере:', response.message);
                        $('#errorMessage').html('Произошла ошибка: ' + response.message).show();
                    } else {
                        console.log('Успешно выполнен запрос на автозагрузку:', response);
                        $('#successMessage').html('Запрос на автозагрузку успешно выполнен.').show();
                    }
                },
                error: function(xhr, status, error, response) {
                    console.log('Произошла ошибка при выполнении AJAX-запроса:', response.message);
                    $('#errorMessage').html('Произошла ошибка: ' + error).show();
                },
                complete: function() {
                    $('#loadingImage1').hide();
                }
            });
        });

        $('#updateStocksBtn').on('click', function () {
            $('#loadingImageStocks').show();
            $('#stockSuccess, #stockError').hide().html('');

            $.ajax({
                url: '/cp/content/avito/updateStocks.php',
                method: 'POST',
                data: {
                    csrf_guard_key: '<?php echo $user_session["csrf_guard_key"]; ?>'
                },
                success: function (responseText) {
                    var response;
                    try {
                        response = typeof responseText === 'string' ? JSON.parse(responseText) : responseText;
                    } catch (e) {
                        $('#stockError').html('Ошибка: некорректный ответ сервера').show();
                        console.error('JSON parse error', e, responseText);
                        return;
                    }

                    if (response.error) {
                        console.error('Ошибка при обновлении остатков:', response.message || response.error);
                        $('#stockError').html('Ошибка: ' + (response.message || response.error)).show();
                        return;
                    }

                    var ok = response.total_success || 0;
                    var fail = response.total_failed || 0;
                    var sent = response.total_sent || 0;

                    var msg = 'Отправлено позиций: ' + sent + '. Успешно: ' + ok + '. Ошибок: ' + fail + '.';
                    $('#stockSuccess').html(msg).show();

                    if (response.details) {
                        console.log('Детали по позициям:', response.details);
                    }
                },
                error: function (xhr, status, error) {
                    var msg = xhr && xhr.responseText ? xhr.responseText : ('Произошла ошибка запроса: ' + (error || status));
                    console.error(msg);
                    $('#stockError').html(msg).show();
                },
                complete: function () {
                    $('#loadingImageStocks').hide();
                }
            });
        });

    });
</script>
