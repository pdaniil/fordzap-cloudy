<?php
// /fordzap.com/cp/content/yandex-ai/generator-module/classes/Database.php

class Database {
    private $connection;
    
    public function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->connection->connect_error) {
            throw new Exception("DB connection failed: " . $this->connection->connect_error);
        }
        $this->connection->set_charset("utf8");
    }
    
    /**
     * Поиск ID узлов по строкам применимости, возвращает массив ID последних узлов
     * @param array $applicabilityStrings Строки от GPT
     * @return array Массив ID
     */
    public function findApplicabilityIds($applicabilityStrings) {
        $ids = [];
        foreach ($applicabilityStrings as $applicability) {
            $id = $this->findBestMatch($applicability);
            if ($id) {
                $ids[] = $id;
            } else {
                // Логируем, что не найдено
                writeLog("Applicability not found: $applicability", 'generator');
            }
        }
        return $ids;
    }
    
    /**
     * Найти лучший узел, соответствующий строке применимости
     * @param string $applicability
     * @return int|null ID узла или null
     */
    private function findBestMatch($applicability) {
        $applicability = trim($applicability);
        if (empty($applicability)) return null;
        
        // 1. Пытаемся найти точное совпадение
        $stmt = $this->connection->prepare("SELECT id, value, parent FROM shop_tree_lists_items WHERE tree_list_id = 1 AND value = ? LIMIT 1");
        $stmt->bind_param("s", $applicability);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row['id'];
        }
        $stmt->close();
        
        // 2. Если точного нет, ищем по LIKE (частичное совпадение)
        $like = "%{$applicability}%";
        $stmt = $this->connection->prepare("SELECT id, value, parent FROM shop_tree_lists_items WHERE tree_list_id = 1 AND value LIKE ? LIMIT 1");
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row['id'];
        }
        $stmt->close();
        
        // 3. Пытаемся разобрать строку (марка, модель, годы/двигатель) и найти по частям
        $parts = $this->parseApplicability($applicability);
        if (!empty($parts['brand']) && !empty($parts['model'])) {
            $id = $this->findByBrandModel($parts['brand'], $parts['model'], $parts['engine'] ?? null, $parts['years'] ?? null);
            if ($id) return $id;
        }
        
        return null;
    }
    
    /**
     * Парсит строку применимости на составляющие (марка, модель, двигатель, годы)
     * @param string $applicability
     * @return array
     */
    private function parseApplicability($applicability) {
        $result = [
            'brand' => null,
            'model' => null,
            'engine' => null,
            'years' => null
        ];
        
        // Простейший парсинг: предполагаем, что строка имеет вид "Марка Модель ..."
        $words = explode(' ', $applicability);
        if (count($words) >= 2) {
            $result['brand'] = $words[0];
            $result['model'] = $words[1];
        }
        
        // Поиск годов (например, 2002-2006)
        if (preg_match('/(\d{4})\s*-\s*(\d{4})/', $applicability, $matches)) {
            $result['years'] = [$matches[1], $matches[2]];
        }
        
        // Поиск двигателя (например, "2.0", "1.6", "V6")
        if (preg_match('/(\d\.\d|V\d|L\d)/i', $applicability, $matches)) {
            $result['engine'] = $matches[0];
        }
        
        return $result;
    }
    
    /**
     * Поиск ID по марке, модели, двигателю и годам
     */
    private function findByBrandModel($brand, $model, $engine = null, $years = null) {
        // Находим ID марки
        $stmt = $this->connection->prepare("SELECT id FROM shop_tree_lists_items WHERE tree_list_id = 1 AND value = ? AND parent IS NULL");
        $stmt->bind_param("s", $brand);
        $stmt->execute();
        $result = $stmt->get_result();
        $brandRow = $result->fetch_assoc();
        $stmt->close();
        if (!$brandRow) return null;
        
        $brandId = $brandRow['id'];
        
        // Ищем модель, где parent = brandId
        $stmt = $this->connection->prepare("SELECT id FROM shop_tree_lists_items WHERE tree_list_id = 1 AND value = ? AND parent = ?");
        $stmt->bind_param("si", $model, $brandId);
        $stmt->execute();
        $result = $stmt->get_result();
        $modelRow = $result->fetch_assoc();
        $stmt->close();
        if (!$modelRow) return null;
        
        $modelId = $modelRow['id'];
        
        // Если есть двигатель или годы – ищем среди потомков модели
        if ($engine || $years) {
            $stmt = $this->connection->prepare("SELECT id FROM shop_tree_lists_items WHERE tree_list_id = 1 AND parent = ? AND (value LIKE ? OR value LIKE ?)");
            $likeEngine = "%{$engine}%";
            $likeYears = "%{$years[0]}%";
            $stmt->bind_param("iss", $modelId, $likeEngine, $likeYears);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $stmt->close();
                return $row['id'];
            }
            $stmt->close();
        }
        
        // Если нет точной модификации, возвращаем модель
        return $modelId;
    }
}
?>