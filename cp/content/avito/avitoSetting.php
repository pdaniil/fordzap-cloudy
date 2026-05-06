<?php
defined('_ASTEXE_') or die('No access');
require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");

use League\Csv\Writer;
use League\Csv\CannotInsertRecord;

$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host=' . $DP_Config->host . ';dbname=' . $DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->query("SET NAMES utf8;");
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = 1;
    $contact_method = $_POST['contact_method'];
    $interne_calls = $_POST['interne_calls'];
    $delivery_array = isset($_POST['delivery']) ? $_POST['delivery'] : [];
    $delivery = json_encode(array_fill_keys($delivery_array, true), JSON_UNESCAPED_UNICODE);

    // Prepare and execute
    $stmt = $db_link->prepare("UPDATE avito_settings SET contact_method = ?, interne_calls = ?, delivery = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$contact_method, $interne_calls, $delivery, $id]);

    if ($stmt->rowCount()) {
        echo '<div class="col-md-12"><div class="alert alert-success" role="alert">
                Успешно обновлено!
              </div></div>';
    } else {
        echo '<div class="col-md-12"><div class="alert alert-danger" role="alert">
                Ошибка, не удалось обновить.
              </div></div>';
    }
}

// Получение текущих значений
$id = 1;
$stmt = $db_link->prepare("SELECT contact_method, interne_calls, delivery FROM avito_settings WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $contact_method = $row['contact_method'];
    $interne_calls = $row['interne_calls'];
    $delivery = $row['delivery'];
    $delivery_methods = json_decode($delivery, true);
} else {
    echo "No record found";
    exit;
}
?>


<div class="col-md-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            <h2>Основные настройки Авито</h2>
        </div>
        <div class="panel-body">
            <p><b>Внимание</b></p>
            <p>Здесь представлены основные настройки Авито, которые применятся ко все объявлениям</p>
            <form action="" method="POST">
                <div class="form-group">
                    <label for="contact_method">Способ связи</label>
                    <select class="form-control" id="contact_method" name="contact_method" required>
                        <option value="По телефону и в сообщениях" <?php if ($contact_method == 'По телефону и в сообщениях') echo 'selected'; ?>>
                            По телефону и в сообщениях
                        </option>
                        <option value="По телефону" <?php if ($contact_method == 'По телефону') echo 'selected'; ?>>По
                            телефону
                        </option>
                        <option value="В сообщениях" <?php if ($contact_method == 'В сообщениях') echo 'selected'; ?>>В
                            сообщениях
                        </option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="interne_calls">Возможность внутренних звонков</label>
                    <select class="form-control" id="interne_calls" name="interne_calls" required>
                        <option value="1" <?php if ($interne_calls == 1) echo 'selected'; ?>>Да</option>
                        <option value="0" <?php if ($interne_calls == 0) echo 'selected'; ?>>Нет</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="delivery">Способы доставки</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delivery[]" value="Выключена"
                               id="delivery1" <?php if (isset($delivery_methods['Выключена'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="delivery1">Выключена</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delivery[]" value="ПВЗ"
                               id="delivery2" <?php if (isset($delivery_methods['ПВЗ'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="delivery2">ПВЗ</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delivery[]" value="Курьер"
                               id="delivery3" <?php if (isset($delivery_methods['Курьер'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="delivery3">Курьер</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delivery[]" value="Постамат"
                               id="delivery4" <?php if (isset($delivery_methods['Постамат'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="delivery4">Постамат</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delivery[]" value="Свой курьер"
                               id="delivery5" <?php if (isset($delivery_methods['Свой курьер'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="delivery5">Свой курьер</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delivery[]" value="Свой партнер СДЭК"
                               id="delivery6" <?php if (isset($delivery_methods['Свой партнер СДЭК'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="delivery6">Свой партнер СДЭК</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delivery[]"
                               value="Свой партнер Деловые Линии"
                               id="delivery7" <?php if (isset($delivery_methods['Свой партнер Деловые Линии'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="delivery7">Свой партнер Деловые Линии</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delivery[]" value="Свой партнер DPD"
                               id="delivery8" <?php if (isset($delivery_methods['Свой партнер DPD'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="delivery8">Свой партнер DPD</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delivery[]" value="Свой партнер ПЭК"
                               id="delivery9" <?php if (isset($delivery_methods['Свой партнер ПЭК'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="delivery9">Свой партнер ПЭК</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delivery[]"
                               value="Свой партнер Почта России"
                               id="delivery10" <?php if (isset($delivery_methods['Свой партнер Почта России'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="delivery10">Свой партнер Почта России</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delivery[]" value="Свой партнер Boxberry"
                               id="delivery11" <?php if (isset($delivery_methods['Свой партнер Boxberry'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="delivery11">Свой партнер Boxberry</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delivery[]"
                               value="Свой партнер СДЭК курьер"
                               id="delivery12" <?php if (isset($delivery_methods['Свой партнер СДЭК курьер'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="delivery12">Свой партнер СДЭК курьер</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="/cp" class="btn btn-info">Отмена</a>
            </form>
        </div>
    </div>
</div>


