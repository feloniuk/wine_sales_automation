<?php
// utils/Logger.php
// Клас для логування помилок і подій

// Підключаємо конфігураційний файл, якщо ще не підключений
if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config.php';
}

class Logger {
    // Типи логів
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const INFO = 'INFO';
    const DEBUG = 'DEBUG';
    
    // Директорія для зберігання логів
    private static $logDir;
    
    // Ініціалізація класу
    public static function init() {
        // Встановлюємо директорію для логів
        self::$logDir = ROOT_PATH . '/logs';
        
        // Створюємо директорію, якщо її немає
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }
    
    /**
     * Запис повідомлення в лог
     * 
     * @param string $message Текст повідомлення
     * @param string $type Тип логу
     * @param string $file Ім'я файлу логу (без розширення)
     * @return bool Результат запису
     */
    public static function log($message, $type = self::INFO, $file = 'app') {
        // Ініціалізуємо, якщо ще не було
        if (!isset(self::$logDir)) {
            self::init();
        }
        
        // Формуємо ім'я файлу логу
        $logFile = self::$logDir . '/' . $file . '_' . date('Y-m-d') . '.log';
        
        // Формуємо рядок логу
        $logMessage = '[' . date('Y-m-d H:i:s') . '] [' . $type . '] ' . $message . PHP_EOL;
        
        // Записуємо в файл
        return file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Запис SQL-помилки в лог
     * 
     * @param string $query SQL-запит
     * @param array $params Параметри запиту
     * @param \PDOException $exception Об'єкт винятку
     * @param string $file Ім'я файлу логу (без розширення)
     * @return bool Результат запису
     */
    public static function logSqlError($query, $params, $exception, $file = 'sql_errors') {
        // Ініціалізуємо, якщо ще не було
        if (!isset(self::$logDir)) {
            self::init();
        }
        
        // Формуємо повідомлення про помилку
        $message = "SQL Error: " . $exception->getMessage() . PHP_EOL;
        $message .= "Query: " . $query . PHP_EOL;
        $message .= "Params: " . json_encode($params) . PHP_EOL;
        $message .= "Stack trace: " . PHP_EOL . $exception->getTraceAsString();
        
        // Записуємо в лог
        return self::log($message, self::ERROR, $file);
    }
    
    /**
     * Запис запиту SQL в лог (для відладки)
     * 
     * @param string $query SQL-запит
     * @param array $params Параметри запиту
     * @param string $file Ім'я файлу логу (без розширення)
     * @return bool Результат запису
     */
    public static function logSqlQuery($query, $params, $file = 'sql_queries') {
        // Формуємо повідомлення
        $message = "Query: " . $query . PHP_EOL;
        $message .= "Params: " . json_encode($params);
        
        // Записуємо в лог
        return self::log($message, self::DEBUG, $file);
    }
}