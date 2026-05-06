<?php
// /fordzap.com/cp/content/yandex-ai/generator-module/generator_api.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


function generateLeafNodesArray($connection, $tree_list_id = 1) {
    // Получаем все элементы дерева
    $query = "SELECT id, value, parent FROM shop_tree_lists_items 
              WHERE tree_list_id = ? 
              ORDER BY id";
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $tree_list_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Создаем массив узлов для быстрого доступа
    $nodes = [];
    while ($row = $result->fetch_assoc()) {
        $nodes[$row['id']] = [
            'id' => $row['id'],
            'value' => $row['value'],
            'parent' => $row['parent']
        ];
    }
    $stmt->close();
    
    // Если нет данных, возвращаем пустой массив
    if (empty($nodes)) {
        return [];
    }
    
    // Находим все родительские ID (узлы, у которых есть дети)
    $parentIds = [];
    foreach ($nodes as $node) {
        if ($node['parent'] !== null && $node['parent'] != 0) {
            $parentIds[$node['parent']] = true;
        }
    }
    
    // Формируем массив конечных узлов (тех, которых нет в parentIds)
    $resultArray = [];
    foreach ($nodes as $node) {
        // Если узел не является родителем (нет детей), то это конечный узел
        if (!isset($parentIds[$node['id']])) {
            // Собираем все значения по цепочке от корня до текущего узла
            $caption = collectPathValues($node['id'], $nodes);
            $resultArray[] = [$node['id'], $caption];
        }
    }
    
    return $resultArray;
}

function generateLeafNodesJson($connection, $tree_list_id = 1, $pretty = false) {
    // Получаем все элементы дерева
    $query = "SELECT `url` FROM shop_tree_lists_items 
              WHERE tree_list_id = ? AND level = 3
              ORDER BY id";
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $tree_list_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Создаем массив узлов для быстрого доступа
	$nodes = [];
	while ($row = $result->fetch_assoc()) {
		$nodes[] = $row['url']; // Добавляем только url в массив
	}
	$stmt->close();

	// Исправляем array_join на implode
	$strNodes = implode(",", $nodes);
	
	return $strNodes;
	
    // Если нет данных, возвращаем пустой JSON массив
    if (empty($nodes)) {
        return $pretty ? json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : json_encode([]);
    }
    
    // Находим все родительские ID (узлы, у которых есть дети)
    $parentIds = [];
    foreach ($nodes as $node) {
        if ($node['parent'] !== null && $node['parent'] != 0) {
            $parentIds[$node['parent']] = true;
        }
    }
    
    // Формируем массив конечных узлов (тех, которых нет в parentIds)
    $resultArray = [];
    foreach ($nodes as $node) {
        // Если узел не является родителем (нет детей), то это конечный узел
        if (!isset($parentIds[$node['id']])) {
            // Собираем все значения по цепочке от корня до текущего узла
            $caption = collectPathValues($node['id'], $nodes);
            $resultArray[] = [$node['id'], $caption];
        }
    }
    
    // Возвращаем JSON
    if ($pretty) {
        return json_encode($resultArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        return json_encode($resultArray, JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Собирает все значения по пути от корня до указанного узла
 * 
 * @param int $nodeId ID узла
 * @param array $nodes Все узлы дерева
 * @return string Строка значений через пробел
 */
function collectPathValues($nodeId, $nodes) {
    $values = [];
    $currentId = $nodeId;
    
    // Идем от текущего узла до корня
    while ($currentId !== null && $currentId != 0 && isset($nodes[$currentId])) {
        // Добавляем значение текущего узла в начало массива
        array_unshift($values, $nodes[$currentId]['value']);
        // Переходим к родителю
        $currentId = $nodes[$currentId]['parent'];
    }
    
    return implode(' ', $values);
}

// Альтернативная версия с рекурсивным обходом (более наглядная)
function generateLeafNodesArrayRecursive($connection, $tree_list_id = 1) {
    // Получаем все элементы дерева
    $query = "SELECT id, value, parent FROM shop_tree_lists_items 
              WHERE tree_list_id = ? 
              ORDER BY id";
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $tree_list_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Создаем структуру дерева
    $tree = [];
    $nodes = [];
    
    while ($row = $result->fetch_assoc()) {
        $nodes[$row['id']] = [
            'id' => $row['id'],
            'value' => $row['value'],
            'parent' => $row['parent'],
            'children' => []
        ];
    }
    $stmt->close();
    
    // Если нет данных, возвращаем пустой массив
    if (empty($nodes)) {
        return [];
    }
    
    // Формируем дерево
    $roots = [];
    foreach ($nodes as $id => &$node) {
        if ($node['parent'] === null || $node['parent'] == 0) {
            $roots[] = &$node;
        } else if (isset($nodes[$node['parent']])) {
            $nodes[$node['parent']]['children'][] = &$node;
        }
    }
    
    // Собираем конечные узлы
    $leafNodes = [];
    collectLeafNodes($roots, [], $leafNodes);
    
    return $leafNodes;
}

/**
 * Рекурсивно собирает конечные узлы и их пути
 * 
 * @param array $nodes Массив узлов
 * @param array $currentPath Текущий путь значений
 * @param array &$leafNodes Ссылка на массив для сбора результатов
 */
function collectLeafNodes($nodes, $currentPath, &$leafNodes) {
    foreach ($nodes as $node) {
        $newPath = array_merge($currentPath, [$node['value']]);
        
        if (empty($node['children'])) {
            // Это конечный узел
            $leafNodes[] = [$node['id'], implode(' ', $newPath)];
        } else {
            // Рекурсивно обрабатываем дочерние узлы
            collectLeafNodes($node['children'], $newPath, $leafNodes);
        }
    }
}

// Пример использования:

// Подключение к базе данных
$connection = new mysqli('localhost', 'm71automai', 'xJZi7KTTJK2BPkM_', 'm71automai');

// Проверка подключения
if ($connection->connect_error) {
    die("Ошибка подключения: " . $connection->connect_error);
}

// Устанавливаем кодировку
$connection->set_charset("utf8");

// Генерация массива (используйте любую из функций)
$strData = generateLeafNodesJson($connection, 1);

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON');
    }
    
    $text = $input['text'] ?? '';
    if (empty($text)) {
        throw new Exception('Text parameter is required');
    }
    
    require_once dirname(__DIR__) . '/config.php';
    require_once __DIR__ . '/classes/ProductGenerator.php';
    
    $generator = new ProductGenerator();
    $result = $generator->generate($text, $strData);
    $result['success'] = true;
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>