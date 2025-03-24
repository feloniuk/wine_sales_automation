<?php
// controllers/CustomerController.php
// Контролер для роботи з клієнтами та каталогом товарів

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config.php';
}

require_once ROOT_PATH . '/config/database.php';

class CustomerController {
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->db->getConnection();
        
        // Запуск сесії, якщо ще не запущена
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Отримання всіх категорій
    public function getAllCategories() {
        $query = "SELECT * FROM product_categories ORDER BY name";
        return $this->db->select($query);
    }

   
    // Отримання детальної інформації про товар
    public function getProductDetails($productId) {
        // Основна інформація про товар
        $productQuery = "SELECT p.*, pc.name as category_name 
                       FROM products p 
                       JOIN product_categories pc ON p.category_id = pc.id 
                       WHERE p.id = ? AND p.status = 'active'";
        
        $product = $this->db->selectOne($productQuery, [$productId]);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Товар не знайдено'
            ];
        }
        
        // Відгуки про товар
        $reviewsQuery = "SELECT pr.*, u.name as customer_name
                        FROM product_reviews pr
                        JOIN users u ON pr.customer_id = u.id
                        WHERE pr.product_id = ? AND pr.status = 'approved'
                        ORDER BY pr.created_at DESC";
        
        $reviews = $this->db->select($reviewsQuery, [$productId]);
        
        // Рекомендовані товари (з тієї ж категорії)
        $recommendedQuery = "SELECT p.id, p.name, p.image, p.price
                           FROM products p
                           WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
                           LIMIT 4";
        
        $recommended = $this->db->select($recommendedQuery, [$product['category_id'], $productId]);
        
        return [
            'success' => true,
            'product' => $product,
            'reviews' => $reviews,
            'recommended' => $recommended
        ];
    }

    // Додавання відгуку про товар
    public function addProductReview($productId, $customerId, $rating, $review) {
        // Перевіряємо, чи існує товар
        $productQuery = "SELECT * FROM products WHERE id = ? AND status = 'active'";
        $product = $this->db->selectOne($productQuery, [$productId]);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Товар не знайдено'
            ];
        }
        
        // Перевіряємо, чи клієнт вже залишав відгук
        $existingQuery = "SELECT * FROM product_reviews WHERE product_id = ? AND customer_id = ?";
        $existing = $this->db->selectOne($existingQuery, [$productId, $customerId]);
        
        if ($existing) {
            return [
                'success' => false,
                'message' => 'Ви вже залишали відгук про цей товар'
            ];
        }
        
        // Додаємо відгук
        $insertQuery = "INSERT INTO product_reviews (product_id, customer_id, rating, review) 
                       VALUES (?, ?, ?, ?)";
        
        $result = $this->db->execute($insertQuery, [$productId, $customerId, $rating, $review]);
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Помилка при додаванні відгуку'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Дякуємо за ваш відгук! Він буде опублікований після перевірки.'
        ];
    }

    // Отримання вмісту кошика
    public function getCart($sessionId) {
        $query = "SELECT ci.*, p.name, p.image, p.price, p.stock_quantity
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.session_id = ?";
        
        return $this->db->select($query, [$sessionId]);
    }

    // Додавання товару до кошика
    public function addToCart($sessionId, $productId, $quantity = 1) {
        // Перевіряємо наявність товару
        $productQuery = "SELECT * FROM products WHERE id = ? AND status = 'active'";
        $product = $this->db->selectOne($productQuery, [$productId]);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Товар не знайдено'
            ];
        }
        
        // Перевіряємо наявність на складі
        if ($product['stock_quantity'] < $quantity) {
            return [
                'success' => false,
                'message' => 'Недостатня кількість товару на складі. Доступно: ' . $product['stock_quantity']
            ];
        }
        
        // Перевіряємо, чи є товар вже в кошику
        $existingQuery = "SELECT * FROM cart_items WHERE session_id = ? AND product_id = ?";
        $existing = $this->db->selectOne($existingQuery, [$sessionId, $productId]);
        
        if ($existing) {
            // Оновлюємо кількість
            $newQuantity = $existing['quantity'] + $quantity;
            
            if ($newQuantity > $product['stock_quantity']) {
                return [
                    'success' => false,
                    'message' => 'Недостатня кількість товару на складі. Доступно: ' . $product['stock_quantity']
                ];
            }
            
            $updateQuery = "UPDATE cart_items SET quantity = ? WHERE id = ?";
            $result = $this->db->execute($updateQuery, [$newQuantity, $existing['id']]);
        } else {
            // Додаємо новий запис
            $insertQuery = "INSERT INTO cart_items (session_id, product_id, quantity) VALUES (?, ?, ?)";
            $result = $this->db->execute($insertQuery, [$sessionId, $productId, $quantity]);
        }
        
        if (!isset($result) || !$result) {
            return [
                'success' => false,
                'message' => 'Помилка при додаванні товару до кошика'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Товар успішно додано до кошика'
        ];
    }

    // Оновлення кількості товару в кошику
    public function updateCartQuantity($cartItemId, $quantity) {
        // Перевіряємо наявність запису
        $cartItemQuery = "SELECT ci.*, p.stock_quantity 
                         FROM cart_items ci
                         JOIN products p ON ci.product_id = p.id
                         WHERE ci.id = ?";
        $cartItem = $this->db->selectOne($cartItemQuery, [$cartItemId]);
        
        if (!$cartItem) {
            return [
                'success' => false,
                'message' => 'Товар не знайдено в кошику'
            ];
        }
        
        // Перевіряємо наявність на складі
        if ($quantity > $cartItem['stock_quantity']) {
            return [
                'success' => false,
                'message' => 'Недостатня кількість товару на складі. Доступно: ' . $cartItem['stock_quantity']
            ];
        }
        
        // Оновлюємо кількість
        $updateQuery = "UPDATE cart_items SET quantity = ? WHERE id = ?";
        $result = $this->db->execute($updateQuery, [$quantity, $cartItemId]);
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Помилка при оновленні кількості товару'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Кількість товару успішно оновлено'
        ];
    }

    // Видалення товару з кошика
    public function removeFromCart($cartItemId) {
        $query = "DELETE FROM cart_items WHERE id = ?";
        $result = $this->db->execute($query, [$cartItemId]);
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Помилка при видаленні товару з кошика'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Товар успішно видалено з кошика'
        ];
    }

    // Очищення кошика
    public function clearCart($sessionId) {
        $query = "DELETE FROM cart_items WHERE session_id = ?";
        $result = $this->db->execute($query, [$sessionId]);
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Помилка при очищенні кошика'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Кошик успішно очищено'
        ];
    }

    // Створення замовлення
    public function createOrder($customerId, $sessionId, $data) {
        // Отримуємо товари з кошика
        $cartQuery = "SELECT ci.*, p.name, p.price, p.stock_quantity
                     FROM cart_items ci
                     JOIN products p ON ci.product_id = p.id
                     WHERE ci.session_id = ?";
        
        $cartItems = $this->db->select($cartQuery, [$sessionId]);
        
        if (empty($cartItems)) {
            return [
                'success' => false,
                'message' => 'Кошик порожній'
            ];
        }
        
        // Перевіряємо наявність на складі
        foreach ($cartItems as $item) {
            if ($item['quantity'] > $item['stock_quantity']) {
                return [
                    'success' => false,
                    'message' => 'Недостатня кількість товару "' . $item['name'] . '" на складі. Доступно: ' . $item['stock_quantity']
                ];
            }
        }
        
        // Розраховуємо загальну суму
        $totalAmount = 0;
        foreach ($cartItems as $item) {
            $totalAmount += $item['price'] * $item['quantity'];
        }
        
        // Додаємо вартість доставки
        if (!empty($data['shipping_cost'])) {
            $totalAmount += $data['shipping_cost'];
        }
        
        // Отримуємо дані клієнта
        $customerQuery = "SELECT * FROM users WHERE id = ?";
        $customer = $this->db->selectOne($customerQuery, [$customerId]);
        
        if (!$customer) {
            return [
                'success' => false,
                'message' => 'Клієнт не знайдений'
            ];
        }
        
        // Створюємо замовлення
        $this->db->beginTransaction();
        
        try {
            // Додаємо запис замовлення
            $orderQuery = "INSERT INTO orders (
                          customer_id, total_amount, status, payment_status, 
                          payment_method, shipping_address, shipping_cost, notes
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $shippingAddress = $data['shipping_address'] ?? 
                               ($customer['address'] . ', ' . $customer['city'] . ', ' . 
                                $customer['region'] . ', ' . $customer['postal_code']);
            
            $orderParams = [
                $customerId,
                $totalAmount,
                'pending',
                'pending',
                $data['payment_method'] ?? 'card',
                $shippingAddress,
                $data['shipping_cost'] ?? 0,
                $data['notes'] ?? ''
            ];
            
            $orderResult = $this->db->execute($orderQuery, $orderParams);
            
            if (!$orderResult) {
                throw new Exception('Помилка при створенні замовлення');
            }
            
            $orderId = $this->db->lastInsertId();
            
            // Додаємо товари до замовлення
            foreach ($cartItems as $item) {
                $orderItemQuery = "INSERT INTO order_items (
                                  order_id, product_id, quantity, price, discount
                              ) VALUES (?, ?, ?, ?, ?)";
                
                $orderItemParams = [
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price'],
                    0 // Знижка поки не реалізована
                ];
                
                $orderItemResult = $this->db->execute($orderItemQuery, $orderItemParams);
                
                if (!$orderItemResult) {
                    throw new Exception('Помилка при додаванні товару до замовлення');
                }
            }
            
            // Очищаємо кошик
            $clearCartQuery = "DELETE FROM cart_items WHERE session_id = ?";
            $this->db->execute($clearCartQuery, [$sessionId]);
            
            // Підтверджуємо транзакцію
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Замовлення успішно створено',
                'order_id' => $orderId
            ];
        } catch (Exception $e) {
            // Відкочуємо транзакцію у разі помилки
            $this->db->rollback();
            
            return [
                'success' => false,
                'message' => 'Помилка при створенні замовлення: ' . $e->getMessage()
            ];
        }
    }

    // Отримання замовлень клієнта
    public function getCustomerOrders($customerId) {
        $query = "SELECT o.*, 
                 (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
                 FROM orders o
                 WHERE o.customer_id = ?
                 ORDER BY o.created_at DESC";
        
        return $this->db->select($query, [$customerId]);
    }

    // Methods to add to the CustomerController class

/**
 * Отримання рекомендованих товарів (featured products)
 * 
 * @param int $limit Кількість товарів для отримання
 * @return array Масив товарів із позначкою "featured"
 */
public function getFeaturedProducts($limit = 4) {
    $query = "SELECT p.*, pc.name as category_name 
             FROM products p 
             JOIN product_categories pc ON p.category_id = pc.id 
             WHERE p.featured = 1 AND p.status = 'active'
             ORDER BY p.name
             LIMIT ?";
    
    return $this->db->select($query, [$limit]);
}

/**
 * Сортування товарів за різними критеріями
 *
 * @param string $sort Критерій сортування
 * @return string SQL частина для ORDER BY
 */
private function getSortingOrder($sort) {
    switch ($sort) {
        case 'price_asc':
            return "ORDER BY p.price ASC";
        case 'price_desc':
            return "ORDER BY p.price DESC";
        case 'name':
            return "ORDER BY p.name ASC";
        case 'newest':
            return "ORDER BY p.created_at DESC";
        case 'popularity':
            return "ORDER BY p.featured DESC, p.id DESC";
        default:
            return "ORDER BY p.name ASC";
    }
}

/**
 * Отримання всіх товарів з пагінацією та сортуванням
 * 
 * @param int $page Номер сторінки
 * @param int $perPage Кількість товарів на сторінці
 * @param string $sort Критерій сортування
 * @return array Результат з даними та інформацією про пагінацію
 */
public function getAllProducts($page = 1, $perPage = ITEMS_PER_PAGE, $sort = '') {
    $orderBy = $this->getSortingOrder($sort);
    
    $query = "SELECT p.*, pc.name as category_name 
             FROM products p 
             JOIN product_categories pc ON p.category_id = pc.id 
             WHERE p.status = 'active'
             $orderBy";
    
    return $this->db->paginate($query, [], $page, $perPage);
}

/**
 * Отримання товарів за категорією з пагінацією та сортуванням
 * 
 * @param int $categoryId ID категорії
 * @param int $page Номер сторінки
 * @param int $perPage Кількість товарів на сторінці
 * @param string $sort Критерій сортування
 * @return array Результат з даними та інформацією про пагінацію
 */
public function getProductsByCategory($categoryId, $page = 1, $perPage = ITEMS_PER_PAGE, $sort = '') {
    $orderBy = $this->getSortingOrder($sort);
    
    $query = "SELECT p.*, pc.name as category_name 
             FROM products p 
             JOIN product_categories pc ON p.category_id = pc.id 
             WHERE p.category_id = ? AND p.status = 'active'
             $orderBy";
    
    return $this->db->paginate($query, [$categoryId], $page, $perPage);
}

/**
 * Пошук товарів з пагінацією та сортуванням
 * 
 * @param string $keyword Ключове слово для пошуку
 * @param int $page Номер сторінки
 * @param int $perPage Кількість товарів на сторінці
 * @param string $sort Критерій сортування
 * @return array Результат з даними та інформацією про пагінацію
 */
public function searchProducts($keyword, $page = 1, $perPage = ITEMS_PER_PAGE, $sort = '') {
    $orderBy = $this->getSortingOrder($sort);
    
    $query = "SELECT p.*, pc.name as category_name 
             FROM products p 
             JOIN product_categories pc ON p.category_id = pc.id 
             WHERE (p.name LIKE ? OR p.description LIKE ? OR p.details LIKE ?) 
             AND p.status = 'active'
             $orderBy";
    
    $searchParam = "%$keyword%";
    return $this->db->paginate($query, [$searchParam, $searchParam, $searchParam], $page, $perPage);
}

    // Отримання детальної інформації про замовлення клієнта
    public function getCustomerOrderDetails($orderId, $customerId) {
        // Перевіряємо, що замовлення належить цьому клієнту
        $orderQuery = "SELECT o.*, u.name as manager_name
                      FROM orders o
                      LEFT JOIN users u ON o.sales_manager_id = u.id
                      WHERE o.id = ? AND o.customer_id = ?";
        
        $order = $this->db->selectOne($orderQuery, [$orderId, $customerId]);
        
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Замовлення не знайдено'
            ];
        }
        
        // Товари замовлення
        $itemsQuery = "SELECT oi.*, p.name as product_name, p.image
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.id
                      WHERE oi.order_id = ?";
        
        $items = $this->db->select($itemsQuery, [$orderId]);
        
        return [
            'success' => true,
            'order' => $order,
            'items' => $items
        ];
    }

    // Відправлення повідомлення менеджеру
    public function sendMessageToManager($customerId, $subject, $message) {
        // Перевіряємо існування клієнта
        $customerQuery = "SELECT * FROM users WHERE id = ?";
        $customer = $this->db->selectOne($customerQuery, [$customerId]);
        
        if (!$customer) {
            return [
                'success' => false,
                'message' => 'Клієнт не знайдений'
            ];
        }
        
        // Знаходимо активного менеджера з продажу (в реальному проекті може бути складніша логіка)
        $managerQuery = "SELECT * FROM users WHERE role = 'sales' AND status = 'active' LIMIT 1";
        $manager = $this->db->selectOne($managerQuery);
        
        if (!$manager) {
            return [
                'success' => false,
                'message' => 'Не вдалося знайти менеджера'
            ];
        }
        
        // Відправляємо повідомлення
        $messageQuery = "INSERT INTO messages (sender_id, receiver_id, subject, message) 
                        VALUES (?, ?, ?, ?)";
        
        $result = $this->db->execute($messageQuery, [$customerId, $manager['id'], $subject, $message]);
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Помилка при відправленні повідомлення'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Повідомлення успішно відправлено'
        ];
    }

    // Отримання повідомлень клієнта
    public function getCustomerMessages($customerId) {
        $query = "SELECT m.*, 
                 CASE 
                    WHEN m.sender_id = ? THEN 'outgoing' 
                    ELSE 'incoming' 
                 END as direction,
                 u.name as user_name, u.role as user_role
                 FROM messages m
                 LEFT JOIN users u ON (
                    CASE 
                        WHEN m.sender_id = ? THEN m.receiver_id 
                        ELSE m.sender_id 
                    END = u.id
                 )
                 WHERE m.sender_id = ? OR m.receiver_id = ?
                 ORDER BY m.created_at DESC";
        
        return $this->db->select($query, [$customerId, $customerId, $customerId, $customerId]);
    }

    // Отримання рекомендованих товарів для клієнта
    public function getRecommendedProducts($customerId, $limit = 6) {
        // На основі попередніх замовлень та категорій
        $query = "SELECT DISTINCT p.id, p.name, p.image, p.price, pc.name as category_name
                FROM products p
                JOIN product_categories pc ON p.category_id = pc.id
                WHERE p.category_id IN (
                    SELECT DISTINCT p2.category_id
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    JOIN products p2 ON oi.product_id = p2.id
                    WHERE o.customer_id = ?
                )
                AND p.id NOT IN (
                    SELECT oi.product_id
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    WHERE o.customer_id = ?
                )
                AND p.status = 'active'
                LIMIT ?";
        
        $recommendedByHistory = $this->db->select($query, [$customerId, $customerId, $limit]);
        
        // Якщо нема історії замовлень або мало рекомендацій, додаємо популярні товари
        if (count($recommendedByHistory) < $limit) {
            $remainingLimit = $limit - count($recommendedByHistory);
            
            $popularQuery = "SELECT DISTINCT p.id, p.name, p.image, p.price, pc.name as category_name
                          FROM products p
                          JOIN product_categories pc ON p.category_id = pc.id
                          LEFT JOIN order_items oi ON p.id = oi.product_id
                          WHERE p.status = 'active'
                          AND (p.id NOT IN (". implode(',', array_column($recommendedByHistory, 'id')) ."))
                          GROUP BY p.id, p.name, p.image, p.price, pc.name
                          ORDER BY COUNT(oi.id) DESC
                          LIMIT ?";
            
            $popularProducts = $this->db->select($popularQuery, [$remainingLimit]);
            
            return array_merge($recommendedByHistory, $popularProducts);
        }
        
        return $recommendedByHistory;
    }

    // Отримання статистики для профілю клієнта
    public function getCustomerDashboard($customerId) {
        // Основна інформація про клієнта
        $customerQuery = "SELECT u.*, 
                         (SELECT COUNT(*) FROM orders WHERE customer_id = u.id) as order_count,
                         (SELECT SUM(total_amount) FROM orders WHERE customer_id = u.id) as total_spent,
                         (SELECT MAX(created_at) FROM orders WHERE customer_id = u.id) as last_order_date
                         FROM users u
                         WHERE u.id = ?";
        
        $customer = $this->db->selectOne($customerQuery, [$customerId]);
        
        if (!$customer) {
            return [
                'success' => false,
                'message' => 'Клієнт не знайдений'
            ];
        }
        
        // Останні замовлення клієнта
        $ordersQuery = "SELECT o.*, 
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
                       FROM orders o
                       WHERE o.customer_id = ?
                       ORDER BY o.created_at DESC
                       LIMIT 5";
        
        $recentOrders = $this->db->select($ordersQuery, [$customerId]);
        
        // Рекомендовані товари
        $recommendedProducts = $this->getRecommendedProducts($customerId, 4);
        
        // Непрочитані повідомлення
        $messagesQuery = "SELECT m.*, u.name as sender_name, u.role as sender_role
                         FROM messages m
                         JOIN users u ON m.sender_id = u.id
                         WHERE m.receiver_id = ? AND m.is_read = 0
                         ORDER BY m.created_at DESC";
        
        $unreadMessages = $this->db->select($messagesQuery, [$customerId]);
        
        return [
            'success' => true,
            'customer' => $customer,
            'recent_orders' => $recentOrders,
            'recommended_products' => $recommendedProducts,
            'unread_messages' => $unreadMessages
        ];
    }
    
    // Генерація ID сесії для кошика
    public function generateCartSessionId() {
        $sessionId = bin2hex(random_bytes(16));
        setcookie('cart_session_id', $sessionId, time() + 86400 * 30, '/'); // 30 днів
        return $sessionId;
    }
    
    // Отримання активних промокодів
    public function getActivePromotions() {
        $query = "SELECT * FROM promotions 
                 WHERE status = 'active' 
                 AND (NOW() BETWEEN start_date AND end_date)
                 ORDER BY min_order_amount DESC";
        
        return $this->db->select($query);
    }
    
    // Застосування знижки за промокодом
    public function applyDiscount($subtotal, $promoCode) {
        // Перевіряємо наявність та валідність промокоду
        $query = "SELECT * FROM promotions 
                 WHERE code = ? 
                 AND status = 'active' 
                 AND (NOW() BETWEEN start_date AND end_date)";
        
        $promotion = $this->db->selectOne($query, [$promoCode]);
        
        if (!$promotion) {
            return [
                'success' => false,
                'message' => 'Промокод не знайдено або він неактивний'
            ];
        }
        
        // Перевіряємо мінімальну суму замовлення
        if ($promotion['min_order_amount'] && $subtotal < $promotion['min_order_amount']) {
            return [
                'success' => false,
                'message' => 'Цей промокод діє лише для замовлень від ' . number_format($promotion['min_order_amount'], 2) . ' ₴'
            ];
        }
        
        // Розраховуємо знижку
        $discount = 0;
        if ($promotion['discount_percent']) {
            $discount = $subtotal * ($promotion['discount_percent'] / 100);
        } elseif ($promotion['discount_amount']) {
            $discount = $promotion['discount_amount'];
            if ($discount > $subtotal) {
                $discount = $subtotal; // Знижка не може перевищувати суму замовлення
            }
        }
        
        // Розраховуємо нову суму
        $newTotal = $subtotal - $discount;
        
        return [
            'success' => true,
            'message' => 'Промокод успішно застосований',
            'discount' => $discount,
            'new_total' => $newTotal
        ];
    }
}