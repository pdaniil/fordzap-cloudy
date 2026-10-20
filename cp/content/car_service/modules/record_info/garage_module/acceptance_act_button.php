<?php
/**
 * Кнопка «Акт приёма» + инициализация модуля.
 * Требует: на странице должен быть <select id="user_car"> с выбранным авто.
 *
 * Параметры (необязательные), которые можно задать ПЕРЕД require:
 *   $aa_record_id  — ID записи (для передачи в печать)
 *   $aa_service_id — ID СТО
 */
$sql = $db_link->prepare("SELECT `id` FROM `car_service_records` WHERE `orders_ids` = ?;");
$sql->execute([$_GET["order_id"]]);
$res_select_record_id = $sql->fetch(PDO::FETCH_ASSOC);
$record_id = $res_select_record_id["id"];
$aa_record_id  = isset($record_id)  ? (int)$record_id  : 0;
$aa_service_id = isset($service_id) ? (int)$service_id : 0;
?>

<!-- Кнопка «Акт приёма» -->
<button type="button"
        class="btn btn-warning"
        id="btn_acceptance_act"
        onclick="cwAcceptanceActOpen();"
        disabled>
    📋 Акт приёма
</button>

<!-- Подключение модуля — только один раз на странице -->
<script src="/cp/content/car_service/acceptance_act.js"></script>

<script>
(function () {
    var AA_RECORD_ID  = <?= (int)$aa_record_id ?>;
    var AA_SERVICE_ID = <?= (int)$aa_service_id ?>;

    // Обновляем состояние кнопки в зависимости от выбранного авто
    function cwAcceptanceActRefreshButton() {
        var btn = document.getElementById('btn_acceptance_act');
        if (!btn) return;
        var carId = parseInt(jQuery('#user_car').val()) || 0;
        btn.disabled = !carId;
    }

    // Открытие модуля
    window.cwAcceptanceActOpen = function () {
        var carId = parseInt(jQuery('#user_car').val()) || 0;
        if (!carId) {
            alert('Сначала выберите автомобиль');
            return;
        }
        if (typeof window.CwAcceptanceAct === 'undefined') {
            alert('Модуль акта приёма не загружен');
            return;
        }
        window.CwAcceptanceAct.open({
            carId:     carId,
            recordId:  AA_RECORD_ID,
            serviceId: AA_SERVICE_ID
        });
    };

    // Следим за сменой авто
    jQuery(document).ready(function () {
        cwAcceptanceActRefreshButton();

        jQuery('#user_car').on('change', cwAcceptanceActRefreshButton);

        // Если селект заполняется асинхронно — держим кнопку в актуальном состоянии
        // через MutationObserver
        var sel = document.getElementById('user_car');
        if (sel && window.MutationObserver) {
            new MutationObserver(cwAcceptanceActRefreshButton)
                .observe(sel, { childList: true });
        }
    });
})();
</script>