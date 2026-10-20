<?php
/**
 * API графика смен СТО
 * Совместимо с PHP 5.4+
 */

// Чтобы фатальные ошибки не превращались в голый 500 без тела
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8', true, 500);
        }
        echo json_encode(array(
            'status'  => false,
            'message' => 'Fatal: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']
        ), JSON_UNESCAPED_UNICODE);
    }
});

require_once($_SERVER["DOCUMENT_ROOT"] . "/config.php");
$DP_Config = new DP_Config;

header('Content-Type: application/json; charset=utf-8');

try {
    $db = new PDO(
        'mysql:host=' . $DP_Config->host . ';dbname=' . $DP_Config->db,
        $DP_Config->user,
        $DP_Config->password
    );
    $db->query("SET NAMES utf8mb4;");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    cwJson(array('status' => false, 'message' => 'Ошибка подключения к БД: ' . $e->getMessage()));
}

function cwJson($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function cwFail($msg) { cwJson(array('status' => false, 'message' => $msg)); }
function cwOk($extra = array()) {
    if (!is_array($extra)) $extra = array();
    cwJson(array_merge(array('status' => true), $extra));
}
function cwShiftTypes() {
    static $types = null;
    if ($types === null) $types = array('work', 'off', 'vacation', 'sick', 'holiday');
    return $types;
}
function cwVal($arr, $key, $default = null) {
    return isset($arr[$key]) ? $arr[$key] : $default;
}
function cwParseWorkerIds($raw) {
    if (!is_array($raw)) $raw = array($raw);
    $out = array();
    foreach ($raw as $v) {
        $v = (int)$v;
        if ($v > 0) $out[] = $v;
    }
    return array_values(array_unique($out));
}
function cwDateRange($from, $to) {
    $start = new DateTime($from);
    $end   = new DateTime($to);
    if ($start > $end) throw new Exception('Дата начала позже даты конца');
    $dates = array();
    $cur = clone $start;
    while ($cur <= $end) {
        $dates[] = $cur->format('Y-m-d');
        $cur->modify('+1 day');
    }
    return $dates;
}

$action = cwVal($_REQUEST, 'action', '');

try {
    switch ($action) {

        case 'get_workers': {
            $stmt = $db->query("SELECT id, fio, user_id FROM car_service_personal ORDER BY fio");
            cwOk(array('workers' => $stmt->fetchAll(PDO::FETCH_ASSOC)));
        }

        case 'get_templates': {
            $stmt = $db->query("
                SELECT id, name, work_days, rest_days, is_system, color
                FROM car_service_shift_templates
                ORDER BY is_system DESC, id ASC
            ");
            cwOk(array('templates' => $stmt->fetchAll(PDO::FETCH_ASSOC)));
        }

        case 'save_template': {
            $id    = (int) cwVal($_POST, 'id', 0);
            $name  = trim((string) cwVal($_POST, 'name', ''));
            $work  = (int) cwVal($_POST, 'work_days', 0);
            $rest  = (int) cwVal($_POST, 'rest_days', 0);
            $color = trim((string) cwVal($_POST, 'color', ''));
            if ($color === '') $color = null;

            if ($name === '') cwFail('Укажите название шаблона');
            if ($work < 1)    cwFail('Рабочих дней должно быть не меньше 1');
            if ($rest < 0)    cwFail('Выходных не может быть меньше 0');

            if ($id > 0) {
                $stmt = $db->prepare("SELECT is_system FROM car_service_shift_templates WHERE id = ?");
                $stmt->execute(array($id));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row)                  cwFail('Шаблон не найден');
                if ((int)$row['is_system']) cwFail('Системный шаблон нельзя изменять');

                $db->prepare("UPDATE car_service_shift_templates SET name=?, work_days=?, rest_days=?, color=? WHERE id=?")
                   ->execute(array($name, $work, $rest, $color, $id));
            } else {
                $db->prepare("INSERT INTO car_service_shift_templates (name, work_days, rest_days, is_system, color) VALUES (?,?,?,0,?)")
                   ->execute(array($name, $work, $rest, $color));
                $id = (int)$db->lastInsertId();
            }

            $stmt = $db->prepare("SELECT id, name, work_days, rest_days, is_system, color FROM car_service_shift_templates WHERE id = ?");
            $stmt->execute(array($id));
            cwOk(array('template' => $stmt->fetch(PDO::FETCH_ASSOC)));
        }

        case 'delete_template': {
            $id = (int) cwVal($_POST, 'id', 0);
            if ($id <= 0) cwFail('Неверный ID');

            $stmt = $db->prepare("SELECT is_system FROM car_service_shift_templates WHERE id = ?");
            $stmt->execute(array($id));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row)                  cwFail('Шаблон не найден');
            if ((int)$row['is_system']) cwFail('Системный шаблон нельзя удалить');

            $db->prepare("DELETE FROM car_service_shift_templates WHERE id = ?")->execute(array($id));
            cwOk();
        }

        case 'get_schedule': {
            $serviceId = (int)    cwVal($_REQUEST, 'service_id', 0);
            $dateFrom  = (string) cwVal($_REQUEST, 'date_from', '');
            $dateTo    = (string) cwVal($_REQUEST, 'date_to', '');
            $workerIds = cwParseWorkerIds(cwVal($_REQUEST, 'worker_ids', array()));

            if (!$serviceId || !$dateFrom || !$dateTo) cwFail('Не хватает параметров');

            $sql = "SELECT worker_id, date, shift_type, template_id, comment
                    FROM car_service_shift_schedule
                    WHERE service_id = ? AND date BETWEEN ? AND ?";
            $params = array($serviceId, $dateFrom, $dateTo);

            if (!empty($workerIds)) {
                $ph = implode(',', array_fill(0, count($workerIds), '?'));
                $sql .= " AND worker_id IN ($ph)";
                $params = array_merge($params, $workerIds);
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            cwOk(array('schedule' => $stmt->fetchAll(PDO::FETCH_ASSOC)));
        }

        case 'set_day': {
            $serviceId  = (int)    cwVal($_POST, 'service_id', 0);
            $workerId   = (int)    cwVal($_POST, 'worker_id', 0);
            $date       = (string) cwVal($_POST, 'date', '');
            $type       = (string) cwVal($_POST, 'shift_type', '');
            $comment    = trim((string) cwVal($_POST, 'comment', ''));
            $templateId = (int)    cwVal($_POST, 'template_id', 0);

            if (!$serviceId || !$workerId || !$date) cwFail('Не хватает параметров');
            if (!in_array($type, cwShiftTypes(), true)) cwFail('Недопустимый тип смены');

            if ($type === 'off') {
                $db->prepare("DELETE FROM car_service_shift_schedule WHERE service_id=? AND worker_id=? AND date=?")
                   ->execute(array($serviceId, $workerId, $date));
            } else {
                $db->prepare("
                    INSERT INTO car_service_shift_schedule (service_id, worker_id, date, shift_type, template_id, comment)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        shift_type  = VALUES(shift_type),
                        template_id = VALUES(template_id),
                        comment     = VALUES(comment)
                ")->execute(array(
                    $serviceId,
                    $workerId,
                    $date,
                    $type,
                    $templateId ? $templateId : null,
                    $comment !== '' ? $comment : null
                ));
            }
            cwOk();
        }

        case 'set_range': {
            $serviceId = (int)    cwVal($_POST, 'service_id', 0);
            $workerIds = cwParseWorkerIds(cwVal($_POST, 'worker_ids', array()));
            $dateFrom  = (string) cwVal($_POST, 'date_from', '');
            $dateTo    = (string) cwVal($_POST, 'date_to', '');
            $type      = (string) cwVal($_POST, 'shift_type', '');

            if (!$serviceId || empty($workerIds) || !$dateFrom || !$dateTo) cwFail('Не хватает параметров');
            if (!in_array($type, cwShiftTypes(), true)) cwFail('Недопустимый тип смены');

            $dates = cwDateRange($dateFrom, $dateTo);
            $db->beginTransaction();
            try {
                $del = $db->prepare("DELETE FROM car_service_shift_schedule WHERE service_id=? AND worker_id=? AND date=?");
                $ins = $db->prepare("
                    INSERT INTO car_service_shift_schedule (service_id, worker_id, date, shift_type)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE shift_type = VALUES(shift_type)
                ");
                foreach ($workerIds as $wid) {
                    foreach ($dates as $d) {
                        if ($type === 'off') {
                            $del->execute(array($serviceId, $wid, $d));
                        } else {
                            $ins->execute(array($serviceId, $wid, $d, $type));
                        }
                    }
                }
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            cwOk(array('affected' => count($dates) * count($workerIds)));
        }

        case 'apply_template': {
            $serviceId  = (int)    cwVal($_POST, 'service_id', 0);
            $workerIds  = cwParseWorkerIds(cwVal($_POST, 'worker_ids', array()));
            $templateId = (int)    cwVal($_POST, 'template_id', 0);
            $workDays   = (int)    cwVal($_POST, 'work_days', 0);
            $restDays   = (int)    cwVal($_POST, 'rest_days', 0);
            $anchor     = (string) cwVal($_POST, 'anchor_date', '');
            $dateFrom   = (string) cwVal($_POST, 'date_from', '');
            $dateTo     = (string) cwVal($_POST, 'date_to', '');
            $overwrite  = (int)    cwVal($_POST, 'overwrite', 1);

            if (!$serviceId || empty($workerIds)) cwFail('Не выбраны сотрудники');
            if (!$anchor || !$dateFrom || !$dateTo) cwFail('Не хватает дат');

            if ($templateId > 0) {
                $stmt = $db->prepare("SELECT work_days, rest_days FROM car_service_shift_templates WHERE id = ?");
                $stmt->execute(array($templateId));
                $tpl = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$tpl) cwFail('Шаблон не найден');
                $workDays = (int)$tpl['work_days'];
                $restDays = (int)$tpl['rest_days'];
            }

            if ($workDays < 1) cwFail('Рабочих дней должно быть не меньше 1');
            if ($restDays < 0) cwFail('Выходных не может быть меньше 0');
            $cycle = $workDays + $restDays;
            if ($cycle < 1) cwFail('Некорректный цикл');

            $dates    = cwDateRange($dateFrom, $dateTo);
            $anchorDt = new DateTime($anchor);

            $db->beginTransaction();
            try {
                $del = $db->prepare("DELETE FROM car_service_shift_schedule WHERE service_id=? AND worker_id=? AND date=?");
                $ins = $db->prepare("
                    INSERT INTO car_service_shift_schedule (service_id, worker_id, date, shift_type, template_id)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        shift_type  = VALUES(shift_type),
                        template_id = VALUES(template_id)
                ");
                $chk = $db->prepare("SELECT id FROM car_service_shift_schedule WHERE service_id=? AND worker_id=? AND date=? LIMIT 1");

                $applied = 0;
                foreach ($workerIds as $wid) {
                    foreach ($dates as $dStr) {
                        if (!$overwrite) {
                            $chk->execute(array($serviceId, $wid, $dStr));
                            if ($chk->fetch()) continue;
                        }
                        $d    = new DateTime($dStr);
                        $diff = (int)$anchorDt->diff($d)->format('%r%a');
                        $pos  = (($diff % $cycle) + $cycle) % $cycle;
                        $isWork = ($pos < $workDays);
                        $type   = $isWork ? 'work' : 'off';

                        if ($type === 'off') {
                            $del->execute(array($serviceId, $wid, $dStr));
                        } else {
                            $ins->execute(array(
                                $serviceId,
                                $wid,
                                $dStr,
                                $type,
                                $templateId ? $templateId : null
                            ));
                        }
                        $applied++;
                    }
                }
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            cwOk(array('applied' => $applied));
        }

        case 'clear_period': {
            $serviceId = (int)    cwVal($_POST, 'service_id', 0);
            $workerIds = cwParseWorkerIds(cwVal($_POST, 'worker_ids', array()));
            $dateFrom  = (string) cwVal($_POST, 'date_from', '');
            $dateTo    = (string) cwVal($_POST, 'date_to', '');

            if (!$serviceId || empty($workerIds) || !$dateFrom || !$dateTo) cwFail('Не хватает параметров');

            $ph = implode(',', array_fill(0, count($workerIds), '?'));
            $params = array_merge(array($serviceId, $dateFrom, $dateTo), $workerIds);
            $db->prepare("DELETE FROM car_service_shift_schedule
                          WHERE service_id = ? AND date BETWEEN ? AND ? AND worker_id IN ($ph)")
               ->execute($params);
            cwOk();
        }

        default:
            cwFail('Неизвестное действие: ' . $action);
    }
} catch (Exception $e) {
    cwFail($e->getMessage());
}