<?php

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try {
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
    $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $result = ["status" => false, "message" => "No DB connect"];
    exit(json_encode($result));
}
$db_link->query("SET NAMES utf8;");

// Получение данных
$surname = isset($_POST['surname']) ? trim($_POST['surname']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$patronymic = isset($_POST['patronymic']) ? trim($_POST['patronymic']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : ''; // отформатированный
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Базовая валидация
if (empty($surname) || empty($name) || empty($email)) {
    $result = ["status" => false, "message" => "Фамилия, имя и email обязательны"];
    exit(json_encode($result));
}

// Очистка телефона от всех нецифровых символов для проверки
$phone_clean = preg_replace('/\D/', '', $phone);

// Проверка корректности телефона (если передан)
if (!empty($phone_clean) && strlen($phone_clean) !== 11) {
    $result = ["status" => false, "message" => "Некорректный номер телефона (должен содержать 11 цифр)"];
    exit(json_encode($result));
}

// Проверка уникальности email и телефона
try {
    // Проверка email
    $check = $db_link->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetchColumn()) {
        $result = ["status" => false, "message" => "Пользователь с таким email уже существует"];
        exit(json_encode($result));
    }

    // Проверка телефона по очищенному значению (учитываем существующие записи)
    if (!empty($phone_clean)) {
        // Получаем все телефоны из БД и сравниваем очищенные
        $stmt = $db_link->query("SELECT phone FROM users WHERE phone IS NOT NULL AND phone != ''");
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($existing as $existing_phone) {
            $existing_clean = preg_replace('/\D/', '', $existing_phone);
            if ($existing_clean === $phone_clean) {
                $result = ["status" => false, "message" => "Пользователь с таким телефоном уже существует"];
                exit(json_encode($result));
            }
        }
    }
} catch (PDOException $e) {
    $result = ["status" => false, "message" => "Ошибка проверки уникальности: " . $e->getMessage()];
    exit(json_encode($result));
}

// Начинаем транзакцию
try {
    $db_link->beginTransaction();

    // 1. Создание записи в users
    $reg_variant = 1; // физическое лицо
    $email_confirmed = 1;
    $phone_confirmed = (!empty($phone)) ? 1 : 0;
    $password = md5($email . $DP_Config->secret_succession);
    $unlocked = 1;
    $admin_created = 1;
    $auto_sao_user = 0;
    $allow_minus_quantity = 0;
    $time_registered = time();

    $stmt = $db_link->prepare("INSERT INTO `users` 
        (`reg_variant`, `email`, `email_confirmed`, `phone`, `phone_confirmed`, `password`, `unlocked`, `time_registered`, `admin_created`, `auto_sao_user`, `allow_minus_quantity`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $reg_variant,
        $email,
        $email_confirmed,
        !empty($phone) ? $phone : null, // сохраняем отформатированную строку
        $phone_confirmed,
        $password,
        $unlocked,
        $time_registered,
        $admin_created,
        $auto_sao_user,
        $allow_minus_quantity
    ]);
    $user_id = $db_link->lastInsertId();

    // 2. Заполнение профиля (users_profiles)
    $profile_fields = [
        'surname' => $surname,
        'name' => $name,
        'patronymic' => $patronymic,
        // при необходимости можно добавить 'phone' сюда, но он уже есть в users
    ];
    foreach ($profile_fields as $key => $value) {
        if (!empty($value)) {
            $stmt = $db_link->prepare("INSERT INTO `users_profiles` (`user_id`, `data_key`, `data_value`) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $key, htmlentities($value, ENT_QUOTES, "UTF-8", false)]);
        }
    }

    // 3. Привязка к группе (по умолчанию группа 2)
    $group_id = 2;
    $stmt = $db_link->prepare("INSERT INTO `users_groups_bind` (`user_id`, `group_id`) VALUES (?, ?)");
    $stmt->execute([$user_id, $group_id]);

    // Подтверждение транзакции
    $db_link->commit();

    // Формируем строку для отображения в селекте
    $display_text = "ФЛ " . $surname . " " . $name . " " . $patronymic . " " . $email . " " . (!empty($phone) ? preg_replace('/[^0-9]/', '', $phone) : "") . " (id-" . $user_id . ")";
    $display_text = trim($display_text);

    $result = [
        "status" => true,
        "user_id" => $user_id,
        "display_text" => $display_text,
        "email" => $email,
        "phone" => $phone, // возвращаем отформатированный для отображения
        "message" => "Пользователь успешно создан"
    ];
    exit(json_encode($result));

} catch (PDOException $e) {
    $db_link->rollBack();
    $result = ["status" => false, "message" => "Ошибка при создании пользователя: " . $e->getMessage()];
    exit(json_encode($result));
} catch (Exception $e) {
    $db_link->rollBack();
    $result = ["status" => false, "message" => $e->getMessage()];
    exit(json_encode($result));
}