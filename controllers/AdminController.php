<?php
// controllers/AdminController.php
// Контролер для адміністратора системи

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config.php';
}

require_once ROOT_PATH . '/config/database.php';

class AdminController {
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->db->getConnection();
    }

    // Отримання статистики по користувачах
    public function getUserStatistics() {
        $query = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
                    SUM(CASE WHEN role = 'warehouse' THEN 1 ELSE 0 END) as warehouse_count,
                    SUM(CASE WHEN role = 'sales' THEN 1 ELSE 0 END) as sales_count,
                    SUM(CASE WHEN role = 'customer' THEN 1 ELSE 0 END) as customer_count
                 FROM users";
        
        $result = $this->db->selectOne($query);
        return $result ?: [
            'total_users' => 0,
            'admin_count' => 0,
            'warehouse_count' => 0,
            'sales_count' => 0,
            'customer_count' => 0
        ];
    }

    // Отримання статистики по продуктах
    public function getProductStatistics() {
        $query = "SELECT 
                    COUNT(*) as total_products,
                    SUM(CASE WHEN category_id = 1 THEN 1 ELSE 0 END) as red_wine_count,
                    SUM(CASE WHEN category_id = 2 THEN 1 ELSE 0 END) as white_wine_count,
                    SUM(CASE WHEN category_id IN (3, 4, 5) THEN 1 ELSE 0 END) as other_wine_count,
                    SUM(CASE WHEN stock_quantity <= min_stock THEN 1 ELSE 0 END) as low_stock_count,
                    SUM(CASE WHEN featured = 1 THEN 1 ELSE 0 END) as featured_count
                 FROM products";
        
        $result = $this->db->selectOne($query);
        return $result ?: [
            'total_products' => 0,
            'red_wine_count' => 0,
            'white_wine_count' => 0,
            'other_wine_count' => 0,
            'low_stock_count' => 0,
            'featured_count' => 0
        ];
    }
    
    // Отримання статистики по замовленнях
    public function getOrderStatistics() {
        $query = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_count,
                    SUM(CASE WHEN status = 'ready_for_pickup' THEN 1 ELSE 0 END) as ready_count,
                    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_count,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                    SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as payment_pending_count,
                    SUM(total_amount) as total_sales
                 FROM orders";
        
        $result = $this->db->selectOne($query);
        return $result ?: [
            'total_orders' => 0,
            'pending_count' => 0,
            'processing_count' => 0,
            'ready_count' => 0,
            'shipped_count' => 0,
            'delivered_count' => 0,
            'cancelled_count' => 0,
            'completed_count' => 0,
            'paid_count' => 0,
            'payment_pending_count' => 0,
            'total_sales' => 0
        ];
    }

    // Отримання списку всіх користувачів
    public function getAllUsers() {
        $query = "SELECT u.*, 
                 (SELECT COUNT(*) FROM orders WHERE customer_id = u.id) as order_count
                 FROM users u
                 ORDER BY u.role, u.name";
        
        return $this->db->select($query);
    }

    // Отримання інформації про конкретного користувача
    public function getUserById($userId) {
        $query = "SELECT u.*,
                 (SELECT COUNT(*) FROM orders WHERE customer_id = u.id) as order_count,
                 (SELECT MAX(created_at) FROM orders WHERE customer_id = u.id) as last_order_date
                 FROM users u
                 WHERE u.id = ?";
        
        return $this->db->selectOne($query, [$userId]);
    }

    // Створення нового користувача
    public function createUser($userData) {
        // Перевіряємо, чи існує вже користувач з таким логіном або email
        $checkQuery = "SELECT * FROM users WHERE username = ? OR email = ?";
        $existingUser = $this->db->selectOne($checkQuery, [$userData['username'], $userData['email']]);
        
        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'Користувач з таким логіном або email вже існує'
            ];
        }
        
        // Хешуємо пароль
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Вставляємо дані користувача
        $insertQuery = "INSERT INTO users (username, password, role, name, email, phone, address, city, region, postal_code) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $result = $this->db->execute($insertQuery, [
            $userData['username'],
            $hashedPassword,
            $userData['role'],
            $userData['name'],
            $userData['email'],
            $userData['phone'] ?? null,
            $userData['address'] ?? null,
            $userData['city'] ?? null,
            $userData['region'] ?? null,
            $userData['postal_code'] ?? null
        ]);
        
        if ($result) {
            $userId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Користувач успішно створений',
                'user_id' => $userId
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Помилка при створенні користувача'
            ];
        }
    }

    // Оновлення даних користувача
    public function updateUser($userId, $userData) {
        // Перевіряємо існування користувача
        $userQuery = "SELECT * FROM users WHERE id = ?";
        $user = $this->db->selectOne($userQuery, [$userId]);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Користувач не знайдений'
            ];
        }
        
        // Перевіряємо унікальність логіна і email
        $checkQuery = "SELECT * FROM users WHERE (username = ? OR email = ?) AND id != ?";
        $existingUser = $this->db->selectOne($checkQuery, [$userData['username'], $userData['email'], $userId]);
        
        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'Користувач з таким логіном або email вже існує'
            ];
        }
        
        // Формуємо базовий запит оновлення
        $updateFields = [];
        $updateParams = [];
        
        $allowedFields = ['username', 'name', 'email', 'role', 'phone', 'address', 'city', 'region', 'postal_code', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($userData[$field])) {
                $updateFields[] = "$field = ?";
                $updateParams[] = $userData[$field];
            }
        }
        
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
                'message' => 'Дані користувача успішно оновлені'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Помилка при оновленні даних користувача'
            ];
        }
    }

    // Блокування/активація користувача
    public function toggleUserStatus($userId, $isActive) {
        $status = $isActive ? 'active' : 'inactive';
        
        $query = "UPDATE users SET status = ? WHERE id = ?";
        $result = $this->db->execute($query, [$status, $userId]);
        
        return [
            'success' => $result,
            'message' => $result ? 'Статус користувача успішно змінено' : 'Помилка при зміні статусу користувача'
        ];
    }

    // Отримання списку камер
    public function getCameras() {
        $query = "SELECT * FROM cameras ORDER BY name";
        return $this->db->select($query);
    }

    // Додавання нової камери
    public function addCamera($name, $location, $streamUrl) {
        $query = "INSERT INTO cameras (name, location, stream_url) VALUES (?, ?, ?)";
        $result = $this->db->execute($query, [$name, $location, $streamUrl]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Камера успішно додана',
                'camera_id' => $this->db->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Помилка при додаванні камери'
            ];
        }
    }

    // Оновлення даних камери
    public function updateCamera($cameraId, $name, $location, $streamUrl, $status) {
        $query = "UPDATE cameras SET name = ?, location = ?, stream_url = ?, status = ? WHERE id = ?";
        $result = $this->db->execute($query, [$name, $location, $streamUrl, $status, $cameraId]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Дані камери успішно оновлені'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Помилка при оновленні даних камери'
            ];
        }
    }

    // Видалення камери
    public function deleteCamera($cameraId) {
        $query = "DELETE FROM cameras WHERE id = ?";
        $result = $this->db->execute($query, [$cameraId]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Камера успішно видалена'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Помилка при видаленні камери'
            ];
        }
    }

    // Отримання недавніх замовлень
    public function getRecentOrders($limit = 10) {
        $query = "SELECT o.*, u.name as customer_name, sm.name as sales_manager_name
                 FROM orders o
                 LEFT JOIN users u ON o.customer_id = u.id
                 LEFT JOIN users sm ON o.sales_manager_id = sm.id
                 ORDER BY o.created_at DESC
                 LIMIT ?";
        
        return $this->db->select($query, [$limit]);
    }

    // Отримання найбільш активних клієнтів
    public function getMostActiveCustomers($limit = 5) {
        $query = "SELECT u.id, u.name, u.email, u.phone,
                COUNT(o.id) as order_count,
                SUM(o.total_amount) as total_spent,
                MAX(o.created_at) as last_order_date
                FROM users u
                JOIN orders o ON u.id = o.customer_id
                WHERE u.role = 'customer'
                GROUP BY u.id, u.name, u.email, u.phone
                ORDER BY order_count DESC
                LIMIT ?";
        
        return $this->db->select($query, [$limit]);
    }

    // Отримання найпопулярніших продуктів
    public function getTopSellingProducts($limit = 5) {
        $query = "SELECT p.id, p.name, p.image, p.price, pc.name as category_name,
                SUM(oi.quantity) as total_sold,
                COUNT(DISTINCT o.id) as order_count
                FROM products p
                JOIN order_items oi ON p.id = oi.product_id
                JOIN orders o ON oi.order_id = o.id
                JOIN product_categories pc ON p.category_id = pc.id
                GROUP BY p.id, p.name, p.image, p.price, pc.name
                ORDER BY total_sold DESC
                LIMIT ?";
        
        return $this->db->select($query, [$limit]);
    }

    // Отримання системних повідомлень
    public function getSystemAlerts() {
        // Тут ми генеруємо системні повідомлення на основі різних умов
        $alerts = [];
        
        // 1. Товари з критично низьким запасом (менше min_stock)
        $lowStockQuery = "SELECT p.*, pc.name as category_name 
                        FROM products p 
                        JOIN product_categories pc ON p.category_id = pc.id 
                        WHERE p.stock_quantity < p.min_stock 
                        ORDER BY (p.min_stock - p.stock_quantity) DESC";
        $lowStockItems = $this->db->select($lowStockQuery);
        
        if (!empty($lowStockItems)) {
            foreach ($lowStockItems as $item) {
                $alerts[] = [
                    'id' => 'low_stock_' . $item['id'],
                    'title' => 'Критично низький запас товару',
                    'message' => 'Товар "' . $item['name'] . '" (' . $item['category_name'] . ') має критично низький запас: ' . 
                               $item['stock_quantity'] . ' шт. (мінімальний запас: ' . $item['min_stock'] . ' шт.)',
                    'created_at' => date('Y-m-d H:i:s'),
                    'type' => 'warning'
                ];
            }
        }
        
        // 2. Неактивні камери
        $inactiveCamerasQuery = "SELECT * FROM cameras WHERE status = 'inactive'";
        $inactiveCameras = $this->db->select($inactiveCamerasQuery);
        
        if (!empty($inactiveCameras)) {
            foreach ($inactiveCameras as $camera) {
                $alerts[] = [
                    'id' => 'inactive_camera_' . $camera['id'],
                    'title' => 'Неактивна камера','message' => 'Камера "' . $camera['name'] . '" (' . $camera['location'] . ') неактивна',
                    'created_at' => date('Y-m-d H:i:s'),
                    'type' => 'danger'
                ];
            }
        }
        
        // 3. Замовлення, які очікують на обробку більш ніж 24 години
        $pendingOrdersQuery = "SELECT o.*, u.name as customer_name 
                              FROM orders o 
                              JOIN users u ON o.customer_id = u.id 
                              WHERE o.status = 'pending' 
                              AND o.created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $pendingOrders = $this->db->select($pendingOrdersQuery);
        
        if (!empty($pendingOrders)) {
            foreach ($pendingOrders as $order) {
                $alerts[] = [
                    'id' => 'pending_order_' . $order['id'],
                    'title' => 'Необроблене замовлення',
                    'message' => 'Замовлення #' . $order['id'] . ' від ' . $order['customer_name'] . 
                               ' очікує на обробку більше 24 годин (створено: ' . date('d.m.Y H:i', strtotime($order['created_at'])) . ')',
                    'created_at' => date('Y-m-d H:i:s'),
                    'type' => 'warning'
                ];
            }
        }
        
        // 4. Непрочитані повідомлення від клієнтів для адміністратора
        $unreadMessagesQuery = "SELECT m.*, u.name as sender_name 
                               FROM messages m 
                               JOIN users u ON m.sender_id = u.id 
                               WHERE m.receiver_id = 1 
                               AND m.is_read = 0";
        $unreadMessages = $this->db->select($unreadMessagesQuery);
        
        if (!empty($unreadMessages)) {
            foreach ($unreadMessages as $message) {
                $alerts[] = [
                    'id' => 'unread_message_' . $message['id'],
                    'title' => 'Непрочитане повідомлення',
                    'message' => 'У вас є непрочитане повідомлення від ' . $message['sender_name'] . 
                               ' (тема: ' . $message['subject'] . ')',
                    'created_at' => date('Y-m-d H:i:s'),
                    'type' => 'info'
                ];
            }
        }
        
        // Сортуємо за часом створення (найновіші спочатку)
        usort($alerts, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $alerts;
    }

    // Отримання категорій товарів
    public function getProductCategories() {
        $query = "SELECT * FROM product_categories ORDER BY name";
        return $this->db->select($query);
    }

    // Додавання нової категорії
    public function addProductCategory($name, $description, $image) {
        $query = "INSERT INTO product_categories (name, description, image) VALUES (?, ?, ?)";
        $result = $this->db->execute($query, [$name, $description, $image]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Категорія успішно додана',
                'category_id' => $this->db->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Помилка при додаванні категорії'
            ];
        }
    }

    // Оновлення категорії
    public function updateProductCategory($categoryId, $name, $description, $image) {
        // Перевіряємо, чи передано зображення
        if (!empty($image)) {
            $query = "UPDATE product_categories SET name = ?, description = ?, image = ? WHERE id = ?";
            $params = [$name, $description, $image, $categoryId];
        } else {
            $query = "UPDATE product_categories SET name = ?, description = ? WHERE id = ?";
            $params = [$name, $description, $categoryId];
        }
        
        $result = $this->db->execute($query, $params);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Категорія успішно оновлена'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Помилка при оновленні категорії'
            ];
        }
    }

    // Видалення категорії
    public function deleteProductCategory($categoryId) {
        // Перевіряємо, чи є товари в цій категорії
        $checkQuery = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
        $result = $this->db->selectOne($checkQuery, [$categoryId]);
        
        if ($result && $result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Неможливо видалити категорію, оскільки в ній є товари'
            ];
        }
        
        // Видаляємо категорію
        $deleteQuery = "DELETE FROM product_categories WHERE id = ?";
        $result = $this->db->execute($deleteQuery, [$categoryId]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Категорія успішно видалена'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Помилка при видаленні категорії'
            ];
        }
    }

    // Отримання списку всіх повідомлень
    public function getAllMessages() {
        $query = "SELECT m.*, 
                 s.name as sender_name, s.role as sender_role,
                 r.name as receiver_name, r.role as receiver_role
                 FROM messages m
                 LEFT JOIN users s ON m.sender_id = s.id
                 LEFT JOIN users r ON m.receiver_id = r.id
                 ORDER BY m.created_at DESC";
        
        return $this->db->select($query);
    }

    // Отримання повідомлень для конкретного користувача
    public function getUserMessages($userId) {
        $query = "SELECT m.*, 
                 s.name as sender_name, s.role as sender_role,
                 r.name as receiver_name, r.role as receiver_role
                 FROM messages m
                 LEFT JOIN users s ON m.sender_id = s.id
                 LEFT JOIN users r ON m.receiver_id = r.id
                 WHERE m.sender_id = ? OR m.receiver_id = ?
                 ORDER BY m.created_at DESC";
        
        return $this->db->select($query, [$userId, $userId]);
    }

    // Додавання нової акції/промокоду
    public function addPromotion($name, $description, $discountPercent, $discountAmount, $code, $minOrderAmount, $startDate, $endDate) {
        $query = "INSERT INTO promotions (name, description, discount_percent, discount_amount, code, min_order_amount, start_date, end_date) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $result = $this->db->execute($query, [
            $name, 
            $description, 
            $discountPercent ?: null, 
            $discountAmount ?: null, 
            $code, 
            $minOrderAmount ?: null, 
            $startDate, 
            $endDate
        ]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Акція успішно додана',
                'promotion_id' => $this->db->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Помилка при додаванні акції'
            ];
        }
    }

    // Отримання всіх акцій/промокодів
    public function getAllPromotions() {
        $query = "SELECT * FROM promotions ORDER BY start_date DESC";
        return $this->db->select($query);
    }

    // Активація/деактивація акції
    public function togglePromotionStatus($promotionId, $isActive) {
        $status = $isActive ? 'active' : 'inactive';
        
        $query = "UPDATE promotions SET status = ? WHERE id = ?";
        $result = $this->db->execute($query, [$status, $promotionId]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Статус акції успішно змінено'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Помилка при зміні статусу акції'
            ];
        }
    }

    // Отримання статистики по продажам за період
    public function getSalesStatisticsByPeriod($startDate, $endDate) {
        $query = "SELECT 
                    DATE(o.created_at) as date,
                    COUNT(DISTINCT o.id) as order_count,
                    SUM(o.total_amount) as total_sales,
                    COUNT(DISTINCT o.customer_id) as unique_customers
                 FROM orders o
                 WHERE o.created_at BETWEEN ? AND ?
                 AND o.status != 'cancelled'
                 GROUP BY DATE(o.created_at)
                 ORDER BY DATE(o.created_at)";
        
        return $this->db->select($query, [$startDate, $endDate]);
    }

    // Отримання статистики по популярним категоріям
    public function getPopularCategories($limit = 5) {
        $query = "SELECT 
                    pc.id, 
                    pc.name, 
                    COUNT(DISTINCT o.id) as order_count,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price) as total_sales
                 FROM product_categories pc
                 JOIN products p ON pc.id = p.category_id
                 JOIN order_items oi ON p.id = oi.product_id
                 JOIN orders o ON oi.order_id = o.id
                 WHERE o.status != 'cancelled'
                 GROUP BY pc.id, pc.name
                 ORDER BY total_sales DESC
                 LIMIT ?";
        
        return $this->db->select($query, [$limit]);
    }

    // Отримання даних для дашборда
    public function getDashboardData() {
        // Базова статистика
        $userStats = $this->getUserStatistics();
        $productStats = $this->getProductStatistics();
        $orderStats = $this->getOrderStatistics();
        
        // Недавні замовлення
        $recentOrders = $this->getRecentOrders(5);
        
        // Найпопулярніші продукти
        $topProducts = $this->getTopSellingProducts(5);
        
        // Найактивніші клієнти
        $topCustomers = $this->getMostActiveCustomers(5);
        
        // Системні повідомлення
        $alerts = $this->getSystemAlerts();
        
        // Статистика за останній тиждень
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        $today = date('Y-m-d');
        $weeklyStats = $this->getSalesStatisticsByPeriod($weekAgo, $today);
        
        // Популярні категорії
        $popularCategories = $this->getPopularCategories(5);
        
        return [
            'user_stats' => $userStats,
            'product_stats' => $productStats,
            'order_stats' => $orderStats,
            'recent_orders' => $recentOrders,
            'top_products' => $topProducts,
            'top_customers' => $topCustomers,
            'alerts' => $alerts,
            'weekly_stats' => $weeklyStats,
            'popular_categories' => $popularCategories
        ];
    }
}