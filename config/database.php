<?php
// config/database.php
// Конфігурація підключення до бази даних

// Підключаємо конфігураційний файл, якщо ще не підключений
if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config.php';
}

// Підключаємо клас логування
require_once ROOT_PATH . '/utils/Logger.php';

class Database {
    private $host = 'localhost';
    private $db_name = 'wine_sales_automation';
    private $username = 'root';
    private $password = '';
    private $conn;
    
    // Режим відладки - логувати всі SQL запити
    private $debug = false;

    // Метод підключення до бази даних
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
            $this->debug = defined('SQL_DEBUG') ? SQL_DEBUG : false;
        } catch(PDOException $e) {
            Logger::logSqlError("Connection attempt", [], $e, 'database_errors');
            echo "Помилка підключення до бази даних: " . $e->getMessage();
        }

        return $this->conn;
    }

    // Метод для виконання запиту SELECT і отримання результатів
    public function select($query, $params = []) {
        try {
            // У режимі відладки логуємо запит
            if ($this->debug) {
                Logger::logSqlQuery($query, $params);
            }
            
            $stmt = $this->conn->prepare($query);
            $this->bindParams($stmt, $params);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // Логуємо помилку SQL
            Logger::logSqlError($query, $params, $e);
            echo "Помилка виконання запиту: " . $e->getMessage();
            return false;
        }
    }

    // Метод для виконання запиту і отримання однієї строки
    public function selectOne($query, $params = []) {
        try {
            // У режимі відладки логуємо запит
            if ($this->debug) {
                Logger::logSqlQuery($query, $params);
            }
            
            $stmt = $this->conn->prepare($query);
            $this->bindParams($stmt, $params);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // Логуємо помилку SQL
            Logger::logSqlError($query, $params, $e);
            echo "Помилка виконання запиту: " . $e->getMessage();
            return false;
        }
    }

    // Метод для виконання запитів INSERT, UPDATE, DELETE
    public function execute($query, $params = []) {
        try {
            // У режимі відладки логуємо запит
            if ($this->debug) {
                Logger::logSqlQuery($query, $params);
            }
            
            $stmt = $this->conn->prepare($query);
            $this->bindParams($stmt, $params);
            return $stmt->execute();
        } catch(PDOException $e) {
            // Логуємо помилку SQL
            Logger::logSqlError($query, $params, $e);
            echo "Помилка виконання запиту: " . $e->getMessage();
            return false;
        }
    }

    // Отримати ID останнього вставленого запису
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    // Увімкнути/вимкнути режим відладки
    public function setDebug($debug) {
        $this->debug = $debug;
    }
    
    // Метод для правильного зв'язування параметрів з урахуванням їх типів
    private function bindParams($stmt, $params) {
        if (is_array($params)) {
            // Якщо $params - це звичайний масив (не асоціативний), зв'язуємо параметри по порядку
            if (array_keys($params) === range(0, count($params) - 1)) {
                foreach ($params as $i => $param) {
                    $position = $i + 1; // PDO використовує позиції з 1
                    if (is_null($param)) {
                        $stmt->bindValue($position, $param, PDO::PARAM_NULL);
                    } elseif (is_int($param)) {
                        $stmt->bindValue($position, $param, PDO::PARAM_INT);
                    } elseif (is_bool($param)) {
                        $stmt->bindValue($position, $param, PDO::PARAM_BOOL);
                    } else {
                        $stmt->bindValue($position, $param, PDO::PARAM_STR);
                    }
                }
            } else {
                // Якщо $params - асоціативний масив, зв'язуємо за іменами параметрів
                foreach ($params as $key => $param) {
                    if (is_null($param)) {
                        $stmt->bindValue(':' . $key, $param, PDO::PARAM_NULL);
                    } elseif (is_int($param)) {
                        $stmt->bindValue(':' . $key, $param, PDO::PARAM_INT);
                    } elseif (is_bool($param)) {
                        $stmt->bindValue(':' . $key, $param, PDO::PARAM_BOOL);
                    } else {
                        $stmt->bindValue(':' . $key, $param, PDO::PARAM_STR);
                    }
                }
            }
        }
    }
    
    // Метод для пагінації результатів
    public function paginate($query, $params = [], $page = 1, $perPage = 10) {
        // Підраховуємо загальну кількість записів
        $countQuery = "SELECT COUNT(*) as total FROM (" . $query . ") as count_query";
        $countResult = $this->selectOne($countQuery, $params);
        $total = $countResult ? $countResult['total'] : 0;
        
        // Обчислюємо загальну кількість сторінок
        $totalPages = ceil($total / $perPage);
        
        // Валідуємо номер поточної сторінки
        $page = max(1, min($page, $totalPages));
        
        // Обчислюємо зміщення для запиту
        $offset = ($page - 1) * $perPage;
        
        // Додаємо LIMIT до запиту
        $limitedQuery = $query . " LIMIT " . $offset . ", " . $perPage;
        
        // Виконуємо запит з обмеженням
        $results = $this->select($limitedQuery, $params);
        
        // Повертаємо результати з інформацією про пагінацію
        return [
            'data' => $results,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => ($page < $totalPages)
            ]
        ];
    }
    
    // Метод транзакції
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollBack();
    }
}