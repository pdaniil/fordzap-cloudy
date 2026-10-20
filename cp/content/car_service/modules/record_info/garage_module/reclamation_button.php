<?php
/**
 * Кнопка «Рекламация» + инициализация модуля.
 * Требует: на странице должен быть <select id="user_car"> с выбранным авто.
 */
$rec_record_id  = isset($record_id)  ? (int)$record_id  : 0;
?>
<!-- Кнопка «Рекламация» -->
<button type="button" class="btn btn-warning" id="btn_reclamation" onclick="cwReclamationOpen();" disabled>
    📄 Рекламация
</button>
<!-- Подключение модуля -->
<script src="/cp/content/car_service/reclamation.js"></script>
<script>
(function () {
    var REC_RECORD_ID = <?= (int)$rec_record_id ?>;
    function cwReclamationRefreshButton() {
        var btn = document.getElementById('btn_reclamation');
        if (!btn) return;
        var carId = parseInt(jQuery('#user_car').val()) || 0;
        btn.disabled = !carId;
    }
    window.cwReclamationOpen = function () {
        var carId = parseInt(jQuery('#user_car').val()) || 0;
        if (!carId) { alert('Сначала выберите автомобиль'); return; }
        if (typeof window.CwReclamation === 'undefined') { alert('Модуль рекламации не загружен'); return; }
        window.CwReclamation.open({ carId: carId, recordId: REC_RECORD_ID });
    };
    jQuery(document).ready(function () {
        cwReclamationRefreshButton();
        jQuery('#user_car').on('change', cwReclamationRefreshButton);
        var sel = document.getElementById('user_car');
        if (sel && window.MutationObserver) {
            new MutationObserver(cwReclamationRefreshButton).observe(sel, { childList: true });
        }
    });
})();
</script>