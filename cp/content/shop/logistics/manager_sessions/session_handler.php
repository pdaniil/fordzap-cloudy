<?php
require_once $_SERVER['DOCUMENT_ROOT']."/lib/PHPMailer/PHPMailerAutoload.php";
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
} catch (PDOException $e) {
    $result["status"] = false;
    $result["message"] = "DB connect error";
    $result["code"] = 502;
    exit(json_encode($result));
}
$db_link->query("SET NAMES utf8;");

function openSession() {
    global $db_link;
    $manager_id = json_decode(file_get_contents("php://input"))->manager_id;
		$office_id = json_decode(file_get_contents("php://input"))->office_id;
    $stmt = $db_link->prepare("INSERT INTO managers_cash_session (is_active, time_start, manager_id, office_id) VALUES (1, UNIX_TIMESTAMP(), ?, ?)");
    if ($stmt->execute([$manager_id, $office_id])) {
        $result["status"] = "session_opened";
        $result["session_id"] = $db_link->lastInsertId();
        echo json_encode($result);
        exit;
    }
}

function closeSession() {
    global $db_link;
    $session_id = json_decode(file_get_contents("php://input"))->session_id;
    $stmt = $db_link->prepare("UPDATE managers_cash_session SET is_active = 0, time_end = UNIX_TIMESTAMP() WHERE id = ?");
    if ($stmt->execute([$session_id])) {
        $result["status"] = "session_closed";
        echo json_encode($result);
        exit;
    }
}

function checkSessionStatus() {
    global $db_link;
    $manager_id = json_decode(file_get_contents("php://input"))->manager_id;
    $stmt = $db_link->prepare("SELECT * FROM managers_cash_session WHERE manager_id = ? AND is_active = 1");
    $stmt->execute([$manager_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($session) {
        $result["status"] = "session_active";
        $result["time_start"] = $session['time_start'];
        $result["session_id"] = $session['id'];
        $result["manager_id"] = $manager_id;
        echo json_encode($result);
    } else {
        $result["status"] = "no_active_session";
        echo json_encode($result);
    }
}

function notifyAdmin() {
    // Получаем данные
    $res_select = getFinancialOperations();

    // Если данные отсутствуют, отправляем письмо с текстом о том, что операций не было
    if (!$res_select) {
        $html = createHtmlHeader('Офис не найден') . "<p>Финансовых операций зафиксировано не было</p>" . createHtmlFooter(getManagerStrBySessionId());
    } else {
        // Формируем письмо с финансовыми операциями
        $html = createHtmlHeader($res_select[0]['office']);
        $html .= createHtmlTable($res_select);
        $html .= createHtmlFooter(getManagerStrBySessionId());
    }

    // Отправляем уведомление
    sendEmail('m71-auto@mail.ru', 'Отчет по финансовым операциям', $html);
}

function getFinancialOperations() {
    global $db_link;

    // Получаем ID сессии из входящих данных
    $session_id = json_decode(file_get_contents("php://input"))->session_id;
	
    // SQL-запрос для получения данных о финансовых операциях
    $sql = "
        SELECT 
            (SELECT CONCAT('ID: ', `id`, ', ', `caption`) FROM `shop_offices` WHERE `shop_offices_cash`.`office_id` = `id`) as `office`, 
            (SELECT `name` FROM `shop_offices_cash_codes` WHERE `shop_offices_cash`.`operation_code` = `id`) as `code_caption`,
            `amount`, `time`, `income`
        FROM `shop_offices_cash`
        WHERE 
            `time` >= (SELECT `time_start` FROM `managers_cash_session` WHERE `id` = ?) 
            AND 
            `time` <= (SELECT `time_end` FROM `managers_cash_session` WHERE `id` = ?);
    ";

    $select = $db_link->prepare($sql);
    $select->execute([$session_id, $session_id]);

    // Получаем результаты
    $res_select = $select->fetchAll(PDO::FETCH_ASSOC);

    // Возвращаем данные, если они есть, иначе false
    return $res_select ?: false;
}


function createHtmlHeader($office) {
    return "<h3>Отчет по офису: " . $office . "</h3>";
}


function createHtmlTable($rows) {
    $html = "
        <table border='1' cellpadding='5' cellspacing='0' style='width: 100%; border-collapse: collapse;'>
            <thead>
                <tr style='background-color: #f2f2f2;'>
                    <th style='padding: 8px; border: 1px solid #ddd;'>Дата</th>
                    <th style='padding: 8px; border: 1px solid #ddd;'>Операция</th>
                    <th style='padding: 8px; border: 1px solid #ddd;'>Сумма</th>
                    <th style='padding: 8px; border: 1px solid #ddd;'>Направление</th>
                </tr>
            </thead>
            <tbody>";

    $total_sum = 0;
	
	$curent_balance = json_decode(file_get_contents("php://input"))->curent_balance;
	
    foreach ($rows as $row) {
        $direction = $row['income'] == 1 ? "Приход" : "Расход";
        $row_color = $row['income'] == 1 ? "#d4edda" : "#f8d7da";  // Зеленый для прихода, красный для расхода
        $html .= "
            <tr style='background-color: $row_color;'>
                <td style='padding: 8px; border: 1px solid #ddd;'>" . date("d.m.Y H:i", $row['time']) . "</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>" . $row['code_caption'] . "</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>" . number_format($row['amount'], 2, ',', ' ') . "</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>" . $direction . "</td>
            </tr>";
			
		if ($row['income'] == 1)
			$total_sum += $row['amount'];
		else
			$total_sum -= $row['amount'];
    }

    $html .= "
            </tbody>
        </table>
        <p style='padding-top: 15px;'>Общая сумма операций: <b>" . number_format($total_sum, 2, ',', ' ') . "</b></p>
        <p style='padding-top: 15px;'>Сумма в кассе до смены: <b>" . number_format($curent_balance - $total_sum, 2, ',', ' ') . "</b></p>
        <p style='padding-top: 15px;'>Сумма в кассе после смены: <b>" . number_format($curent_balance, 2, ',', ' ') . "</b></p>
	";

    return $html;
}

function createHtmlFooter($manager_str) {
    return "<p>Менеджер: <b>" . $manager_str . "</b></p>";
}

function getManagerStrBySessionId() {
	global $db_link;
	
	$session_id = json_decode(file_get_contents("php://input"))->session_id;
	
	$select_id = $db_link->prepare("SELECT `manager_id` FROM `managers_cash_session` WHERE `id` = ?;");
	$select_id->execute([$session_id]);;
	$select_id_res = $select_id->fetch(PDO::FETCH_ASSOC);
	
	$manager_id = $select_id_res["manager_id"];
	
	$select_name = $db_link->prepare("SELECT `data_value` FROM `users_profiles` WHERE `user_id` = ? AND `data_key` = 'name'");
	$select_sunrame = $db_link->prepare("SELECT `data_value` FROM `users_profiles` WHERE `user_id` = ? AND `data_key` = 'surname'");
	$select_patronymic = $db_link->prepare("SELECT `data_value` FROM `users_profiles` WHERE `user_id` = ? AND `data_key` = 'patronymic'");
	
	$select_name->execute([$manager_id]);
	$select_sunrame->execute([$manager_id]);
	$select_patronymic->execute([$manager_id]);
	
	$name_res = $select_name->fetch(PDO::FETCH_ASSOC);
	$sunrame_res = $select_sunrame->fetch(PDO::FETCH_ASSOC);
	$patronymic_res = $select_patronymic->fetch(PDO::FETCH_ASSOC);
	
	$name = $name_res["data_value"] ? $name_res["data_value"] : "";
	$sunrame = $sunrame_res["data_value"] ? $sunrame_res["data_value"] : "";
	$patronymic = $patronymic_res["data_value"] ? $patronymic_res["data_value"] : "";
	
	$select_group = $db_link->prepare("SELECT `value` FROM `groups` WHERE `id` = (SELECT `group_id` FROM `users_groups_bind` WHERE `user_id` = ? LIMIT 1)");
	$select_group->execute([$manager_id]);
	$group_res = $select_group->fetch(PDO::FETCH_ASSOC);
	
	
	
	return $manager_id . ',  ' . $name . ' ' . $sunrame . ' ' . $patronymic . ', ' . $group_res["value"];
	
}

function sendEmail($to, $subject, $html) {
    global $DP_Config;

    // Кодируем заголовок письма для корректного отображения кириллических символов
    $encoded_subject = mb_encode_mimeheader($subject, "UTF-8", "B", "\r\n");

    $headers = "From: " . $DP_Config->from_name . " <" . $DP_Config->from_email . ">\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    if ($DP_Config->smtp_mode == '1') {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = $DP_Config->smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $DP_Config->smtp_username;
        $mail->Password = $DP_Config->smtp_password;
        $mail->SMTPSecure = $DP_Config->smtp_encryption;
        $mail->Port = $DP_Config->smtp_port;

        $mail->CharSet = 'UTF-8';  // Устанавливаем кодировку для тела письма
        $mail->setFrom($DP_Config->from_email, $DP_Config->from_name);
        $mail->addAddress($to);
        $mail->Subject = $encoded_subject;
        $mail->Body = $html;
        $mail->isHTML(true);

        if (!$mail->send()) {
            echo json_encode(["message" => 'Mailer Error: ' . $mail->ErrorInfo]);
        } else {
            echo json_encode(["message" => 'Message sent!']);
        }
    } else {
        mail($to, $encoded_subject, $html, $headers);
        echo json_encode(["message" => 'Message sent!']);
    }
}





$action = json_decode(file_get_contents("php://input"))->action ?? '';

if (function_exists($action)) {
    $action();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
}



?>
