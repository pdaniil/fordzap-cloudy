<?php
defined('_ASTEXE_') or die('No access');
require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");

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
<style>
    body.modal-open {
        position: relative !important;
    }
</style>
<div class="col-md-12">
    <div id="alertContainer" class="alert alert-success alert-dismissible" role="alert" style="display: none;">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <span id="alertMessage"></span>
    </div>
</div>

<div class="col-md-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            <h2>Связка категорий Авито с категориями Docpart</h2>
        </div>
        <div class="panel-body">
            <p>Для правильной связки категорий Авито с категориями сайта, необходимо связать данные категории. Все что не связано в выгрузку авито не попадет.</p>
            <button type="button" style="margin-bottom: 10px" class="btn btn-success" id="createMappingBtn">Создать связку категорий</button>
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Авито категория</th>
                    <th>Категория сайта</th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody id="mappingTable">
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно для создания -->
<div class="modal fade" id="createMappingModal" tabindex="-1" role="dialog" aria-labelledby="createMappingModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="color: red">&times;</span>
                </button>
                <p id="createMappingModalLabel">Создание связки категорий Авито с категориями Docpart</p>
            </div>
            <div class="modal-body">
                <form id="createMappingForm">
                    <div class="form-group">
                        <label for="create_avito_category_name">Авито Категория</label>
                        <select class="form-control" id="create_avito_category_name" name="avito_category_name" required>
                            <!--option загруженные из БД-->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="create_shop_catalogue_category_name">Категория товаров сайта</label>
                        <select class="form-control" id="create_shop_catalogue_category_name" name="shop_catalogue_category_name" required>
                            <!--option загруженные из БД-->
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                    <button type="button" class="btn btn-info" data-dismiss="modal" aria-label="Close">
                        Отмена
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для редактирования -->
<div class="modal fade" id="editMappingModal" tabindex="-1" role="dialog" aria-labelledby="editMappingModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="color: red">&times;</span>
                </button>
                <p id="editMappingModalLabel">Редактирование связки категорий Авито с категориями Docpart</p>
            </div>
            <div class="modal-body">
                <form id="editMappingForm">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="form-group">
                        <label for="edit_avito_category_name">Авито Категория</label>
                        <select class="form-control" id="edit_avito_category_name" name="avito_category_name" required>
                            <!--option загруженные из БД-->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_shop_catalogue_category_name">Категория товаров сайта</label>
                        <select class="form-control" id="edit_shop_catalogue_category_name" name="shop_catalogue_category_name" required>
                            <!--option загруженные из БД-->
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                    <button type="button" class="btn btn-info" data-dismiss="modal" aria-label="Close">
                        Отмена
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function () {
        loadCategories();
        loadMappings();

        $('#createMappingBtn').on('click', function () {
            $('#createMappingModal').modal('show');
        });

        $('#createMappingForm').on('submit', function (e) {
            e.preventDefault();
            saveMapping('create');
        });

        $('#editMappingForm').on('submit', function (e) {
            e.preventDefault();
            saveMapping('edit');
        });

        $('#mappingTable').on('click', '.edit-btn', function () {
            const id = $(this).data('id');
            editMapping(id);
        });
    });

    function loadCategories() {
        $.ajax({
            url: '/cp/content/avito/getCategories.php',
            method: 'GET',
            data: 'csrf_guard_key=<?php echo $user_session["csrf_guard_key"]?>',
            success: function (data) {
                const categories = JSON.parse(data);
                let avitoOptions = '';
                let shopOptions = '';
                categories.avito.forEach(category => {
                    avitoOptions += `<option value="${category.id}">${category.name}</option>`;
                });
                categories.shop.forEach(category => {
                    shopOptions += `<option value="${category.id}">${category.name}</option>`;
                });
                $('#create_avito_category_name').html(avitoOptions);
                $('#create_shop_catalogue_category_name').html(shopOptions);
                $('#edit_avito_category_name').html(avitoOptions);
                $('#edit_shop_catalogue_category_name').html(shopOptions);
            }
        });
    }

    function loadMappings() {
        $.ajax({
            url: '/cp/content/avito/getMappings.php',
            method: 'GET',
            data: 'csrf_guard_key=<?php echo $user_session["csrf_guard_key"]?>',
            success: function (data) {
                const mappings = JSON.parse(data);
                let rows = '';
                mappings.forEach(mapping => {
                    rows += `<tr>
                        <td>${mapping.id}</td>
                        <td>${mapping.avito_category_name} (${mapping.avito_category_id})</td>
                        <td>${mapping.shop_catalogue_category_name} (${mapping.shop_catalogue_categorie_id})</td>
                        <td>
                            <button class="btn btn-sm btn-primary edit-btn" data-id="${mapping.id}">Редактировать</button>
                        </td>
                    </tr>`;
                });
                $('#mappingTable').html(rows);
            }
        });
    }

    function saveMapping(type) {
        const form = type === 'create' ? $('#createMappingForm') : $('#editMappingForm');
        const modal = type === 'create' ? $('#createMappingModal') : $('#editMappingModal');
        const mapping = {
            id: form.find('input[name="id"]').val() || '',
            avito_category_id: form.find('select[name="avito_category_name"]').val(),
            shop_catalogue_categorie_id: form.find('select[name="shop_catalogue_category_name"]').val(),
            csrf_guard_key: '<?php echo $user_session["csrf_guard_key"]; ?>'
        };

        $.ajax({
            url: '/cp/content/avito/saveMapping.php',
            method: 'POST',
            data: mapping,

            success: function (response) {
                modal.modal('hide');
                loadMappings();
                form[0].reset();
                const res = JSON.parse(response);
                showAlert(res.message, res.status);
            }
        });
    }

    function editMapping(id) {
        $.ajax({
            url: `/cp/content/avito/getMapping.php?id=${id}`,
            method: 'GET',
            data: 'csrf_guard_key=<?php echo $user_session["csrf_guard_key"]?>',
            success: function (data) {
                const mapping = JSON.parse(data);
                $('#edit_id').val(mapping.id);
                $('#edit_avito_category_name').val(mapping.avito_category_id);
                $('#edit_shop_catalogue_category_name').val(mapping.shop_catalogue_categorie_id);
                $('#editMappingModal').modal('show');
            }
        });
    }

    function showAlert(message, success) {
        const alertContainer = $('#alertContainer');
        const alertMessage = $('#alertMessage');

        alertMessage.text(message);
        alertContainer.removeClass('alert-success alert-danger');

        if (success) {
            alertContainer.addClass('alert-success');
        } else {
            alertContainer.addClass('alert-danger');
        }

        alertContainer.show();
        setTimeout(() => {
            alertContainer.hide();
        }, 3000);
    }
</script>