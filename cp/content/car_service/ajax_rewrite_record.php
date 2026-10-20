<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    exit(json_encode(['status' => false, 'message' => 'No DB connect']));
}
$db_link->query("SET NAMES utf8;");

require_once($_SERVER['DOCUMENT_ROOT']."/content/users/dp_user.php");
$user_session = DP_User::getAdminSession();

header('Content-Type: application/json');

$record_id  = isset($_POST['record_id'])  ? (int)$_POST['record_id']  : 0;
$service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
$post_id    = isset($_POST['post_id'])    ? (int)$_POST['post_id']    : 0;
$worker_id  = isset($_POST['worker_id'])  ? (int)$_POST['worker_id']  : 0;
$datetime   = isset($_POST['datetime'])   ? trim($_POST['datetime'])  : '';
$comment    = isset($_POST['comment'])    ? trim($_POST['comment'])   : '';

if ($record_id <= 0 || $service_id <= 0 || $datetime === '') {
    echo json_encode(['status' => false, 'message' => 'Некорректные данные']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}$/', $datetime)) {
    echo json_encode(['status' => false, 'message' => 'Неверный формат даты/времени']);
    exit;
}

try {
    // Получаем исходную запись
    $stmt = $db_link->prepare("SELECT * FROM car_service_records WHERE id = ? AND sto_id = ?");
    $stmt->execute([$record_id, $service_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        echo json_encode(['status' => false, 'message' => 'Запись не найдена']);
        exit;
    }

    // ВАЖНО:
    //  time       - datetime-строка "Y-m-d H:i:s"
    //  time_from  - UNIX timestamp
    //  time_to    - UNIX timestamp
    //
    // Длительность считаем по исходным timestamp-полям.
    $oldFromTs = (int)$record['time_from'];
    $oldToTs   = (int)$record['time_to'];
    $duration  = max(0, $oldToTs - $oldFromTs);

    // Новая метка времени
    $newTs       = strtotime($datetime);
    $newTimeFrom = $newTs;                 // UNIX timestamp
    $newTimeTo   = $newTs + $duration;     // UNIX timestamp
    $newTimeStr  = date('Y-m-d H:i:s', $newTs);

    // Пост / мастер — если не переданы, оставляем исходные
    $newPostId   = $post_id   > 0 ? $post_id   : (int)$record['post_id'];
    $newWorkerId = $worker_id > 0 ? $worker_id : (int)$record['worker_id'];

    // Аккумулируем комментарий (сохраняем историю перезаписей)
    $existingComment = (string)($record['rewrite_comment'] ?? '');
    $newComment = $existingComment;
    if ($comment !== '') {
        $stamp = date('d.m.Y H:i');
        $entry = "[$stamp] $comment";
        $newComment = $existingComment === '' ? $entry : ($existingComment . "\n\n" . $entry);
    }

    // Обновляем: time (строка), time_from (int), time_to (int)
    $update = $db_link->prepare("
        UPDATE car_service_records
        SET time = ?, time_from = ?, time_to = ?,
            post_id = ?, worker_id = ?,
            is_rewrite = 1, rewrite_comment = ?
        WHERE id = ? AND sto_id = ?
    ");
    $update->execute([
        $newTimeStr,
        $newTimeFrom,
        $newTimeTo,
        $newPostId,
        $newWorkerId,
        $newComment,
        $record_id,
        $service_id
    ]);

    echo json_encode([
        'status'    => true,
        'time'      => $newTimeStr,
        'time_from' => $newTimeFrom,
        'time_to'   => $newTimeTo,
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
}