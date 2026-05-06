<?php
//Конфигурация CMS
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

//Подключение к БД
try
{
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $answer = array();
    $answer["status"] = false;
    $answer["message"] = "No DB connect";
    exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");

// -----------------------------------------------------------------
// Функция для извлечения диапазона лет из строки (например "2005-2011" или "2008 - настоящее время")
// Возвращает [год_от, год_до] и очищенную строку без этого диапазона
function parseYearRange($str, &$cleaned) {
    // Шаблон: четыре цифры, тире, затем либо четыре цифры, либо русские буквы/пробелы (настоящее время)
    $pattern = '/(\d{4})\s*-\s*(\d{4}|[а-яё\s]+)/ui';
    if (preg_match($pattern, $str, $matches)) {
        $year_from = (int)$matches[1];
        $year_to_str = trim($matches[2]);
        if (preg_match('/^\d{4}$/', $year_to_str)) {
            $year_to = (int)$year_to_str;
        } else {
            $year_to = null; // фраза вроде "настоящее время"
        }
        $cleaned = trim(preg_replace($pattern, '', $str));
        return [$year_from, $year_to];
    }
    // Если нет тире, пробуем один год
    $pattern2 = '/(\d{4})/u';
    if (preg_match($pattern2, $str, $matches)) {
        $year_from = (int)$matches[1];
        $year_to = null;
        $cleaned = trim(preg_replace($pattern2, '', $str));
        return [$year_from, $year_to];
    }
    $cleaned = trim($str);
    return [null, null];
}

// Получаем все элементы дерева с tree_list_id = 1
$stmt = $db_link->prepare("SELECT id, parent, level, value FROM shop_tree_lists_items WHERE tree_list_id = 1 ORDER BY id");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$items = [];
$children = [];
foreach ($rows as $row) {
    $id = $row['id'];
    $items[$id] = $row;
    $children[$row['parent']][] = $id;
}

$result = [];

// Рекурсивный обход дерева
function processNode($node_id, $make_value, $model_data, $items, $children, &$result) {
    $node = $items[$node_id];
    $level = $node['level'];
    $value = $node['value'] ?? '';

    if ($level == 1) { // Уровень Make (производитель)
        $make_value = trim($value);
        if (isset($children[$node_id])) {
            foreach ($children[$node_id] as $child_id) {
                processNode($child_id, $make_value, null, $items, $children, $result);
            }
        }
    } 
    elseif ($level == 2) { // Уровень Model (модель + диапазон лет)
        $cleaned_model = '';
        list($model_year_from, $model_year_to) = parseYearRange($value, $cleaned_model);
        $model_data = [
            'cleaned'  => $cleaned_model,
            'year_from' => $model_year_from,
            'year_to'   => $model_year_to
        ];
        if (isset($children[$node_id])) {
            foreach ($children[$node_id] as $child_id) {
                processNode($child_id, $make_value, $model_data, $items, $children, $result);
            }
        }
    } 
    elseif ($level == 3) { // Уровень Engine (двигатель)
        $cleaned_engine = '';
        list($engine_year_from, $engine_year_to) = parseYearRange($value, $cleaned_engine);

        // Годы: если указаны у двигателя – берём их, иначе – у модели
        if ($engine_year_from !== null) {
            $year_from = $engine_year_from;
            $year_to   = $engine_year_to;
        } else {
            $year_from = $model_data['year_from'];
            $year_to   = $model_data['year_to'];
        }

        // Формируем строку диапазона для full_text
        $year_range_str = '';
        if ($year_from !== null) {
            if ($year_to !== null) {
                $year_range_str = $year_from . ' - ' . $year_to;
            } else {
                $year_range_str = $year_from . ' - наст. время';
            }
        }

        $full_text = trim($make_value . ' ' . $model_data['cleaned'] . ' ' . $year_range_str . ' ' . $cleaned_engine);
        $full_text = preg_replace('/\s+/', ' ', $full_text); // убираем лишние пробелы

        $result[] = [
            'id'        => (int)$node_id,
            'make'      => $make_value,
            'model'     => $model_data['cleaned'],
            'year_from' => $year_from,
            'year_to'   => $year_to,
            'engine'    => $cleaned_engine,
            'full_text' => $full_text
        ];
    }
}

// Находим корневые элементы (parent = 0 или NULL)
foreach ($items as $id => $item) {
    if (empty($item['parent']) || $item['parent'] == 0) {
        processNode($id, '', null, $items, $children, $result);
    }
}

// Сохраняем результат в JSON-файл
$json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/car_dump.json', $json);

echo "Файл cars_dump.json успешно создан.";