<?php
/**
 * Класс HttpFetcher
 * 
 * ОТВЕТСТВЕННОСТЬ:
 * Только загрузка веб-страниц через CURL.
 * Не анализирует и не обрабатывает содержимое.
 * 
 * ЗАЧЕМ НУЖЕН:
 * - Инкапсулирует всю работу с CURL
 * - Управляет user-agent, таймаутами, редиректами
 * - Возвращает сырой HTML или false при ошибке
 * - Предоставляет информацию о последней ошибке
 */
class HttpFetcher {
    /**
     * @var string Последняя ошибка
     */
    private $lastError;
    
    /**
     * @var int Последний HTTP код ответа
     */
    private $lastHttpCode;
    
    /**
     * @var array Массив user-agent'ов для ротации
     * Используются разные, чтобы сайты не блокировали как бота
     */
    private $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1'
    ];
    
    /**
     * Загрузить страницу по URL
     * 
     * @param string $url URL для загрузки
     * @param array $options Опции загрузки
     *        - timeout: int таймаут в секундах (default: 10)
     *        - max_redirects: int макс. редиректов (default: 3)
     *        - user_agent: string свой user-agent
     * @return string|false HTML страницы или false при ошибке
     */
    public function fetch($url, $options = []) {
        // Устанавливаем параметры по умолчанию
        $defaults = [
            'timeout' => 10,
            'max_redirects' => 3,
            'user_agent' => $this->getRandomUserAgent()
        ];
        $options = array_merge($defaults, $options);
        
        // Инициализируем CURL
        $ch = curl_init($url);
        
        // Настраиваем параметры CURL
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,           // Возвращать результат, а не выводить
            CURLOPT_FOLLOWLOCATION => true,           // Следовать за редиректами
            CURLOPT_MAXREDIRS => $options['max_redirects'], // Максимум редиректов
            CURLOPT_TIMEOUT => $options['timeout'],    // Таймаут
            CURLOPT_USERAGENT => $options['user_agent'], // User-agent
            CURLOPT_SSL_VERIFYPEER => false,          // Не проверять SSL (для тестов)
            CURLOPT_SSL_VERIFYHOST => false,          // Не проверять SSL
            CURLOPT_HTTPHEADER => [                    // Заголовки как у браузера
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1'
            ],
            CURLOPT_ENCODING => ''                      // Поддержка сжатия
        ]);
        
        // Выполняем запрос
        $html = curl_exec($ch);
        $this->lastHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Проверяем на ошибки CURL
        if (curl_error($ch)) {
            $this->lastError = 'CURL Error: ' . curl_error($ch);
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        // Проверяем HTTP код ответа
        if ($this->lastHttpCode !== 200) {
            $this->lastError = "HTTP Error: {$this->lastHttpCode}";
            return false;
        }
        
        // Успех - возвращаем HTML
        return $html;
    }
    
    /**
     * Получить последнюю ошибку
     * 
     * @return string|null
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Получить последний HTTP код ответа
     * 
     * @return int|null
     */
    public function getLastHttpCode() {
        return $this->lastHttpCode;
    }
    
    /**
     * Получить случайный user-agent из списка
     * Используется для ротации, чтобы не палиться как бот
     * 
     * @return string
     */
    private function getRandomUserAgent() {
        return $this->userAgents[array_rand($this->userAgents)];
    }
}
?>