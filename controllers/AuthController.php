<?php
// controllers/AuthController.php
// Контролер для авторизації та реєстрації користувачів

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config.php';
}

require_once ROOT_PATH . '/config/database.php';

class AuthController {
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->db->getConnection();
        
        // Запуск сесії, якщо ще не запущена
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Метод для авторизації користувача
    public function login($username, $password) {
        // Валідація вхідних даних
        if (empty($username) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Будь ласка, введіть логін та пароль'
            ];
        }

        // SQL запит для перевірки користувача
        $query = "SELECT * FROM users WHERE username = ? AND status = 'active'";
        $user = $this->db->selectOne($query, [$username]);

        // Перевіряємо існування користувача та правильність пароля
        if ($user && password_verify($password, $user['password'])) {
            // Записуємо дані користувача в сесію
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            // Перенаправляємо на відповідну сторінку в залежності від ролі
            return [
                'success' => true,
                'role' => $user['role'],
                'message' => 'Ви успішно увійшли в систему'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Невірний логін або пароль'
            ];
        }
    }

    // Метод для реєстрації нового клієнта
    public function registerCustomer($userData) {
        // Валідація вхідних даних
        if (empty($userData['username']) || empty($userData['password']) || 
            empty($userData['name']) || empty($userData['email'])) {
            return [
                'success' => false,
                'message' => 'Будь ласка, заповніть всі обов\'язкові поля'
            ];
        }

        // Перевіряємо, чи існує вже користувач з таким username або email
        $query = "SELECT * FROM users WHERE username = ? OR email = ?";
        $existingUser = $this->db->selectOne($query, [$userData['username'], $userData['email']]);

        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'Користувач з таким логіном або email вже існує'
            ];
        }

        // Хешуємо пароль
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);

        // Вставляємо запис в таблицю users
        $insertUserQuery = "INSERT INTO users (username, password, role, name, email, phone, address, city, region, postal_code) 
                          VALUES (?, ?, 'customer', ?, ?, ?, ?, ?, ?, ?)";
        
        $userInserted = $this->db->execute($insertUserQuery, [
            $userData['username'],
            $hashedPassword,
            $userData['name'],
            $userData['email'],
            $userData['phone'] ?? null,
            $userData['address'] ?? null,
            $userData['city'] ?? null,
            $userData['region'] ?? null,
            $userData['postal_code'] ?? null
        ]);

        if ($userInserted) {
            $userId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Реєстрація успішно завершена. Тепер ви можете увійти в систему.',
                'user_id' => $userId
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Помилка при створенні користувача'
            ];
        }
    }

    // Метод для виходу з системи
    public function logout() {
        // Знищуємо всі дані сесії
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'Ви успішно вийшли з системи'
        ];
    }

    // Перевірка авторизації
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Перевірка ролі користувача
    public function checkRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        if (is_array($role)) {
            return in_array($_SESSION['role'], $role);
        } else {
            return $_SESSION['role'] === $role;
        }
    }

    // Метод для отримання інформації про поточного користувача
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $query = "SELECT * FROM users WHERE id = ?";
        return $this->db->selectOne($query, [$_SESSION['user_id']]);
    }
    
    // Метод для оновлення профілю користувача
    public function updateUserProfile($userId, $userData) {
        // Перевіряємо існування користувача
        $userQuery = "SELECT * FROM users WHERE id = ?";
        $user = $this->db->selectOne($userQuery, [$userId]);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Користувач не знайдений'
            ];
        }
        
        // Перевіряємо унікальність email
        $checkQuery = "SELECT * FROM users WHERE email = ? AND id != ?";
        $existingUser = $this->db->selectOne($checkQuery, [$userData['email'], $userId]);
        
        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'Користувач з таким email вже існує'
            ];
        }
        
        // Формуємо базовий запит оновлення
        $updateFields = [];
        $updateParams = [];
        
        // Оновлюємо тільки ті поля, які передані
        $allowedFields = ['name', 'email', 'phone', 'address', 'city', 'region', 'postal_code'];
        
        foreach ($allowedFields as $field) {
            if (isset($userData[$field])) {
                $updateFields[] = "$field = ?";
                $updateParams[] = $userData[$field];
            }
        }
        
        // Якщо передано новий пароль, оновлюємо його
        if (!empty($userData['password'])) {
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            $updateFields[] = "password = ?";
            $updateParams[] = $hashedPassword;
        }
        
        if (empty($updateFields)) {
            return [
                'success' => false,
                'message' => 'Немає даних для оновлення'
            ];
        }
        
        // Додаємо ID в параметри
        $updateParams[] = $userId;
        
        // Виконуємо оновлення
        $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $result = $this->db->execute($updateQuery, $updateParams);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Профіль успішно оновлено'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Помилка при оновленні профілю'
            ];
        }
    }
}