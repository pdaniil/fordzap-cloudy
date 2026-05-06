<?php
// /fordzap.com/cp/content/yandex-ai/generator-module/classes/ProductGenerator.php

require_once __DIR__ . '/Database.php';

class ProductGenerator {
    private $db;
    private $apiKey;
    
    public function __construct() {
        $this->db = new Database();
        $this->apiKey = API_KEY; // используем API_KEY из config.php
    }
    
    /**
     * Генерация на основе текста
     * @param string $text Объединённый текст со всех страниц
     * @return array Результат с description, specifications, applicability_ids
     */
    public function generate($text, $strData) {
        $prompt = $this->buildPrompt($text, $strData);
        $gptResult = $this->callGpt($prompt);
        
        // Парсим JSON из ответа GPT
        $data = $this->parseGptResponse($gptResult);
        
        // Если нет применимости – возвращаем как есть
        if (empty($data['applicability'])) {
            return $gptResult;
        }
        
        // Ищем ID узлов в БД
        $ids = $this->db->findApplicabilityIds($data['applicability']);
        $data['applicability_ids'] = $ids;
        $data['applicability_raw'] = $data['applicability'];
        
        return $data;
    }
    
private function buildPrompt($text, $strData) {
    return "Ты — эксперт по автозапчастям с 10-летним опытом. Твоя задача: извлечь из текста структурированную информацию о товаре и вернуть её ТОЛЬКО в формате JSON (без пояснений, комментариев или дополнительного текста).

	Требования к ответу:
	1. Формат JSON должен строго соответствовать образцу ниже. Используй двойные кавычки, не добавляй комментарии.
	2. Для отсутствующих числовых полей указывай 0, строковых — пустую строку \"\", массивов — [].
	3. Конвертируй все единицы измерения:
	   - Вес — в граммы (например, 1.5 кг → 1500, 200 г → 200).
	   - Размеры — в миллиметры (например, 15 см → 150, 0.3 м → 300). Если единица не указана, считай, что это мм.
	4. Поле description: информативное продающее описание на 80–140 слов. Акцент на преимуществах для клиента и ключевых характеристиках. Не используй маркетинговые штампы («лучший», «уникальный»).
	5. Поле applicability: массив строк в формате «Марка Модель (годы выпуска, двигатель)». Если данных нет — пустой массив. Примеры корректных записей:
	   - \"Toyota Camry (2015-2020, 2.5L)\"
	   - \"BMW X5 (2018-2023)\"
	   - \"Ford Focus (2010-2015, 1.6L EcoBoost)\"
	6. Поле applicability_uris: строка url, соответствующих элементам в applicability. Если соответствие не найдено, не включай. Порядок url должен соответствовать порядку строк в applicability. Нужно сопоставить те url, что я прислал.

	Образец JSON:
	{
	  \"description\": \"Качественная тормозная колодка для легковых автомобилей. Обеспечивает эффективное торможение и долгий срок службы. Изготовлена из износостойкого композитного материала, снижающего шум при работе. Подходит для городской езды и трассы. Простая установка без специальных инструментов.\",
	  \"specifications\": {
		\"weight\": 450,
		\"length\": 120,
		\"width\": 65,
		\"height\": 25
	  },
	  \"applicability\": [
		\"Toyota Camry (2015-2020, 2.5L)\",
		\"Honda Accord (2017-2022, 1.5T)\"
	  ],
	  \"applicability_uris\": [
		\"acura/mdx-2000---2006/35-4wd-benzinovyj-j35a3-265-ls-2001---2002\",
		\"acura/mdx-2000---2006/35-4wd-benzinovyj-j35a3-265-ls-2003---2004\"
	  ]
	}

	Правила обработки данных:
	- Если в тексте несколько значений для одного параметра (например, вес для разных модификаций), бери первое упомянутое.
	- Если применимость указана фрагментами (марка в одном месте, модель в другом), собери полную строку по шаблону.
	- Игнорируй упоминания аналогов и совместимых запчастей, если они не относятся напрямую к описываемому товару.
	- Для сопоставления с БД: сравнивай каждую строку из applicability с полем. При точном совпадении или совпадении по марке/модели бери соответствующий id.
	- Если для какой‑то строки из applicability нет соответствия в присланных мной данных, не добавляй url в applicability_uris.

	Строка url для сопоставления (формат: url через запятую):
	{$strData}
	Текст для обработки:
	{$text}";
}
   

    
    private function callGpt($prompt) {
        $url = 'https://llm.api.cloud.yandex.net/foundationModels/v1/completion';
        $data = [
            'modelUri' => "gpt://" . FOLDER_ID . "/aliceai-llm/latest",
            'completionOptions' => [
                'stream' => false,
                'temperature' => 0.3,
                'maxTokens' => 2000
            ],
            'messages' => [
                ['role' => 'user', 'text' => $prompt]
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Api-Key ' . $this->apiKey,
                'x-folder-id: ' . FOLDER_ID
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpCodes = curl_getinfo($ch);
        
		$f = fopen('log.txt', 'w');
		fwrite($f, $httpCodes);
		fclose($f);
		
		curl_close($ch);
        
		
		
        if ($httpCode !== 200) {
            writeLog("GPT error: HTTP $httpCode, response: $response", 'error');
            return null;
        }
        return $response;
    }
    
    private function parseGptResponse($response) {
        $data = json_decode($response, true);
        $text = $data['result']['alternatives'][0]['message']['text'] ?? '';
        
        // Ищем JSON в тексте
        preg_match('/\{.*\}/s', $text, $matches);
        if (empty($matches)) {
            writeLog("No JSON found in GPT response", 'error');
            return [
                'description' => '',
                'specifications' => ['weight' => 0, 'length' => 0, 'width' => 0, 'height' => 0],
                'applicability' => []
            ];
        }
        
        $result = json_decode($matches[0], true);
        if (!$result) {
            writeLog("Invalid JSON from GPT", 'error');
            return [
                'description' => '',
                'specifications' => ['weight' => 0, 'length' => 0, 'width' => 0, 'height' => 0],
                'applicability' => []
            ];
        }
        
        // Нормализуем поля
        $defaultSpecs = ['weight' => 0, 'length' => 0, 'width' => 0, 'height' => 0];
        return [
            'description' => $result['description'] ?? '',
            'specifications' => array_merge($defaultSpecs, $result['specifications'] ?? []),
            'applicability' => $result['applicability'] ?? []
        ];
    }
}
?>