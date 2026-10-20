<?php
require_once("include.php"); //Необходимые библиотеки
// TerminalKey	String(20)	Да	Идентификатор терминала, выдается Продавцу Банком
// Amount	Number(10)	Да	Сумма в копейках
// OrderId	String(50)	Да	Номер заказа в 

$user_id  = DP_User::getUserId();
$admin_id = (int) DP_User::getAdminId();
$is_admin = ($admin_id > 0);

// -------------------------------------------------------------------------------
// Защита от CSRF-атак.
// Для админа — пропускаем: у него своя сессия и свои проверки на уровне /cp/.
// Для клиента — всё как раньше.
// -------------------------------------------------------------------------------
if (!$is_admin) {
    require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/stop_csrf.php");
}
// -------------------------------------------------------------------------------

//Получаем данные операции
$operation_id = (int)$_GET["operation"];

// 1. Сначала всегда ищем как клиент
$operation_query = $db_link->prepare(
    'SELECT * FROM `shop_users_accounting` WHERE `id` = ? AND `active` = 0 AND `user_id` = ?;'
);
$operation_query->execute([ $operation_id, $user_id ]);
$operation = $operation_query->fetch();

// Флаг: операция найдена как «своя»
$is_own_operation = (bool)$operation;

// 2. Если не нашли и это админ — фолбэк на админский доступ
if (!$operation && $is_admin) {
    $operation_query = $db_link->prepare(
        'SELECT * FROM `shop_users_accounting` WHERE `id` = ? AND `active` = 0;'
    );
    $operation_query->execute([ $operation_id ]);
    $operation = $operation_query->fetch();
}

// 3. Ничего не нашли — отказ
if (!$operation) {
    exit(json_encode([
        "result" => false,
        "code"   => 2,
    ]));
}

// 4. Доп. проверки — ТОЛЬКО когда админ зашёл как админ (не по своей операции)
if ($is_admin && !$is_own_operation) {
    $pay_order_id = (int)$operation['pay_orders'];
    if ($pay_order_id > 0) {
        $chk = $db_link->prepare('SELECT id FROM car_service_records WHERE orders_ids = ? LIMIT 1');
        $chk->execute([ $pay_order_id ]);
        if (!$chk->fetchColumn()) {
            exit(json_encode([
                "result"  => false,
                "code"    => 3,
                "message" => 'Операция не привязана к записи автосервиса',
            ]));
        }
    }
}

// --- Данные клиента для автозаполнения формы ---
$client_email = '';
$client_name  = '';

$client_id = (int)$operation['user_id'];
if ($client_id > 0) {
    // email из users
    $uStmt = $db_link->prepare("SELECT email FROM users WHERE user_id = ?");
    $uStmt->execute([$client_id]);
    $client_email = (string)$uStmt->fetchColumn();

    // ФИО из users_profiles
    $pStmt = $db_link->prepare("
        SELECT data_key, data_value
        FROM users_profiles
        WHERE user_id = ? AND data_key IN ('surname','name','patronymic')
    ");
    $pStmt->execute([$client_id]);
    $profile = [];
    while ($row = $pStmt->fetch(PDO::FETCH_ASSOC)) {
        $profile[$row['data_key']] = $row['data_value'];
    }
    $client_name = trim(
        ($profile['surname']    ?? '') . ' ' .
        ($profile['name']       ?? '') . ' ' .
        ($profile['patronymic'] ?? '')
    );
}

$operation_description = "Пополнение баланса";
if($operation["pay_orders"] != "" && $operation["pay_orders"] != NULL)
{
	$operation_description = "Оплата заказа";
}
//Общий скрипт получения настроек платежной системы.
require_once( $_SERVER['DOCUMENT_ROOT'].'/content/shop/finance/get_pay_system_parameters.php' );
$terminal_id = $paysystem_parameters["terminal_id"];
?>
<head>
	<meta charset="utf-8">
	<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous" />
	<script src="https://securepay.tinkoff.ru/html/payForm/js/tinkoff.js"></script>
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="col-lg-6"  style="margin: 10% 0 0 10%">
			<div class="hpanel">
				<div class="panel-heading hbuilt">
				</div>
				<div class="panel-body">
					<form class="form" name="TinkoffPayForm" onsubmit="pay(this); return false;">
						<input class="" type="hidden" name="terminalkey" value="<?=$terminal_id?>">
						<input class="" type="hidden" name="frame" value="false">
						<input class="" type="hidden" name="language" value="ru">
						<input class="" type="hidden" placeholder="Номер заказа" name="order" value="<?=$operation["id"];?>">
						<input class="" type="hidden" placeholder="Контактный телефон" name="phone">
						<div class="form-group">
							<label class="control-label" for="amount">Сумма закза</label>
							<input class="form-control" disabled type="text" placeholder="Сумма заказа" id="amount" name="amount" value="<?=$operation["amount"];?>" requred>	
						</div>
						<div class="form-group">
							<label class="contol-label" for="description" >Описание заказа</label>
							<input class="form-control" type="text" placeholder="Описание заказа" id="description" name="description" value="<?=$operation_description;?>">
						</div>
						<div class="form-group">
							<label for="name" class="control-label">ФИО плательщика</label>
							<input class="form-control" type="text" placeholder="ФИО плательщика"
								   id="name" name="name"
								   value="<?= htmlspecialchars($client_name, ENT_QUOTES, 'UTF-8'); ?>">
						</div>
						<div class="form-group">
							<label for="email" class="conrol-label">E-mail</label>
							<input class="form-control" type="text" id="email" placeholder="E-mail"
								   name="email"
								   value="<?= htmlspecialchars($client_email, ENT_QUOTES, 'UTF-8'); ?>">
						</div>
						<input class="btn btn-success" type="submit" value="Перейти на страницу оплаты"
							   id="cw_pay_submit_btn">
					   <div id="cw_pay_auto_hint"
							 style="margin-top:10px;color:#888;font-size:13px;display:none;">
							⏳ Переход на страницу оплаты...
						</div>
					</form>
				</div>
				<div class="panel-body"></div>
			</div>
			</div>
		</div>
	</div>
	<script>
(function() {
    function cwAutoSubmitPayForm() {
        var form = document.forms['TinkoffPayForm'];
        if (!form) return;

        var nameEl  = document.getElementById('name');
        var emailEl = document.getElementById('email');

        // Заполняем "по факту" ещё раз — на случай если PHP не отдал значение
        // (например, у незарегистрированного клиента)
        if (nameEl  && !nameEl.value)  nameEl.value  = '';
        if (emailEl && !emailEl.value) emailEl.value = '';

        var hint = document.getElementById('cw_pay_auto_hint');
        if (hint) hint.style.display = 'block';

        var btn = document.getElementById('cw_pay_submit_btn');
        if (btn) {
            btn.disabled = true;
            btn.value = 'Переход на страницу оплаты...';
        }

        // Небольшая задержка, чтобы tinkoff.js успел инициализироваться
        setTimeout(function() {
            try {
                // Вызываем тот же путь, что и при ручной отправке
                if (typeof form.onsubmit === 'function') {
                    form.onsubmit.call(form, new Event('submit'));
                } else {
                    form.submit();
                }
            } catch (e) {
                console.error('Ошибка автоотправки формы:', e);
                // Фолбэк — обычный submit
                try { form.submit(); } catch (e2) {}
            }
        }, 400);
    }

    if (document.readyState === 'complete' ||
        document.readyState === 'interactive') {
        setTimeout(cwAutoSubmitPayForm, 100);
    } else {
        document.addEventListener('DOMContentLoaded', cwAutoSubmitPayForm);
    }
})();
</script>
</body>