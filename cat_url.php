<?php 
	require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

	$DP_Config = new DP_Config;

	try {
		$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
	} catch (PDOException $e) {
		exit("No DB connect");
	}

	$db_link->query("SET NAMES utf8;");
	
	
	function getProductUrl($productId, $db_link) {
		try {
			// Получаем информацию о товаре
			$stmt = $db_link->prepare("
				SELECT 
					p.id,
					p.alias as product_alias,
					p.category_id,
					c.alias as category_alias,
					c.parent as category_parent
				FROM 
					shop_catalogue_products p
				LEFT JOIN 
					shop_catalogue_categories c ON p.category_id = c.id
				WHERE 
					p.id = :product_id
			");
			
			$stmt->execute([':product_id' => $productId]);
			$product = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if (!$product) {
				return false; // Товар не найден
			}
			
			// Строим путь категории
			$categoryPath = getCategoryPath($product['category_id'], $db_link);
			
			// Формируем URL
			$url = [];
			
			// Добавляем путь категорий, если он есть
			if (!empty($categoryPath)) {
				// Категории в пути идут от родителя к потомку (слева направо)
				$url = array_merge($url, $categoryPath);
			}
			
			// Добавляем алиас товара
			if (!empty($product['product_alias'])) {
				$url[] = $product['product_alias'];
			}
			
			// Собираем URL
			if (empty($url)) {
				return false;
			}
			
			return implode('/', $url);
			
		} catch (PDOException $e) {
			// Логирование ошибки
			error_log("Ошибка при построении URL: " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Рекурсивная функция для получения пути категорий
	 */
	function getCategoryPath($categoryId, $db_link, $path = []) {
		try {
			if (empty($categoryId) || $categoryId == 0) {
				return $path;
			}
			
			// Получаем информацию о категории
			$stmt = $db_link->prepare("
				SELECT 
					id,
					alias,
					parent
				FROM 
					shop_catalogue_categories
				WHERE 
					id = :category_id
			");
			
			$stmt->execute([':category_id' => $categoryId]);
			$category = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if (!$category) {
				return $path;
			}
			
			// Добавляем алиас категории в начало массива (так как мы идем от родителя к потомку)
			array_unshift($path, $category['alias']);
			
			// Если есть родитель, идем рекурсивно вверх
			if (!empty($category['parent']) && $category['parent'] != 0) {
				return getCategoryPath($category['parent'], $db_link, $path);
			}
			
			return $path;
			
		} catch (PDOException $e) {
			error_log("Ошибка при построении пути категории: " . $e->getMessage());
			return $path;
		}
	}
	
	$url = getProductUrl(5699, $db_link);
	var_dump ($url);
	
?>