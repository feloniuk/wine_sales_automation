<?php
// controllers/SalesController.php
// Контролер для роботи з продажами

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config.php';
}

require_once ROOT_PATH . '/config/database.php';

class SalesController {
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->db->getConnection();
    }

    // Отримання всіх замовлень
    public function getAllOrders($page = 1, $perPage = ITEMS_PER_PAGE) {
        $query = "SELECT o.*, 
                 u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
                 COUNT(oi.id) as items_count, 
                 SUM(oi.quantity) as total_items
                 FROM orders o
                 LEFT JOIN users u ON o.customer_id = u.id
                 LEFT JOIN order_items oi ON o.id = oi.order_id
                 GROUP BY o.id, o.customer_id, o.sales_manager_id, o.total_amount,
                         o.status, o.payment_status, o.payment_method, o.shipping_address,
                         o.shipping_cost, o.notes, o.created_at, o.updated_at,
                         u.name, u.email, u.phone
                 ORDER BY o.created_at DESC";
        
        return $this->db->paginate($query, [], $page, $perPage);
    }

    // Отримання замовлень за статусом
    public function getOrdersByStatus($status, $page = 1, $perPage = ITEMS_PER_PAGE) {
        $query = "SELECT o.*, 
                 u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
                 COUNT(oi.id) as items_count, 
                 SUM(oi.quantity) as total_items
                 FROM orders o
                 LEFT JOIN users u ON o.customer_id = u.id
                 LEFT JOIN order_items oi ON o.id = oi.order_id
                 WHERE o.status = ?
                 GROUP BY o.id, o.customer_id, o.sales_manager_id, o.total_amount,
                         o.status, o.payment_status, o.payment_method, o.shipping_address,
                         o.shipping_cost, o.notes, o.created_at, o.updated_at,
                         u.name, u.email, u.phone
                 ORDER BY o.created_at DESC";
        
        return $this->db->paginate($query, [$status], $page, $perPage);
    }

    // Отримання замовлень для менеджера
    public function getManagerOrders($managerId, $page = 1, $perPage = ITEMS_PER_PAGE) {
        $query = "SELECT o.*, 
                 u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
                 COUNT(oi.id) as items_count, 
                 SUM(oi.quantity) as total_items
                 FROM orders o
                 LEFT JOIN users u ON o.customer_id = u.id
                 LEFT JOIN order_items oi ON o.id = oi.order_id
                 WHERE o.sales_manager_id = ?
                 GROUP BY o.id, o.customer_id, o.sales_manager_id, o.total_amount,
                         o.status, o.payment_status, o.payment_method, o.shipping_address,
                         o.shipping_cost, o.notes, o.created_at, o.updated_at,
                         u.name, u.email, u.phone
                 ORDER BY o.created_at DESC";
        
        return $this->db->paginate($query, [$managerId], $page, $perPage);
    }

    // Отримання детальної інформації про замовлення
    public function getOrderDetails($orderId) {
        // Загальна інформація про замовлення
        $orderQuery = "SELECT o.*, 
                      u.name as customer_name, u.email as customer_email, 
                      u.phone as customer_phone, u.address as customer_address,
                      u.city as customer_city, u.region as customer_region,
                      u.postal_code as customer_postal_code,
                      m.name as manager_name
                      FROM orders o
                      LEFT JOIN users u ON o.customer_id = u.id
                      LEFT JOIN users m ON o.sales_manager_id = m.id
                      WHERE o.id = ?";
        
        $order = $this->db->selectOne($orderQuery, [$orderId]);
        
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Замовлення не знайдено'
            ];
        }
        
        // Товари в замовленні
        $itemsQuery = "SELECT oi.*, p.name as product_name, p.image, p.price as current_price,
                      pc.name as category_name
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.id
                      JOIN product_categories pc ON p.category_id = pc.id
                      WHERE oi.order_id = ?";
        
        $items = $this->db->select($itemsQuery, [$orderId]);
        
        // Історія змін статусу замовлення (можна додати таблицю order_status_history)
        // Тут спрощений варіант - використовуємо notes для відображення історії
        
        return [
            'success' => true,
            'order' => $order,
            'items' => $items
        ];
    }

    // Оновлення статусу замовлення
    public function updateOrderStatus($orderId, $status, $notes = '') {
        // Перевіряємо існування замовлення
        $orderQuery = "SELECT * FROM orders WHERE id = ?";
        $order = $this->db->selectOne($orderQuery, [$orderId]);
        
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Замовлення не знайдено'
            ];
        }
        
        // Валідуємо статус
        $validStatuses = ['pending', 'processing', 'ready_for_pickup', 'shipped', 'delivered', 'cancelled', 'completed'];
        if (!in_array($status, $validStatuses)) {
            return [
                'success' => false,
                'message' => 'Некоректний статус замовлення'
            ];
        }
        
        // Формуємо примітку зі зміною статусу
        $statusNote = date('Y-m-d H:i:s') . " - Статус змінено на '$status'";
        if (!empty($notes)) {
            $statusNote .= ": $notes";
        }
        
        // Оновлюємо статус та додаємо примітку
        $updateQuery = "UPDATE orders SET 
                      status = ?, 
                      notes = CONCAT(IFNULL(notes, ''), '\n', ?)
                      WHERE id = ?";
                      
        $updateResult = $this->db->execute($updateQuery, [$status, $statusNote, $orderId]);
        
        if (!$updateResult) {
            return [
                'success' => false,
                'message' => 'Помилка при оновленні статусу замовлення'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Статус замовлення успішно оновлено'
        ];
    }

    // Оновлення статусу оплати
    public function updatePaymentStatus($orderId, $paymentStatus, $notes = '') {
        // Перевіряємо існування замовлення
        $orderQuery = "SELECT * FROM orders WHERE id = ?";
        $order = $this->db->selectOne($orderQuery, [$orderId]);
        
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Замовлення не знайдено'
            ];
        }
        
        // Валідуємо статус оплати
        $validStatuses = ['pending', 'paid', 'refunded'];
        if (!in_array($paymentStatus, $validStatuses)) {
            return [
                'success' => false,
                'message' => 'Некоректний статус оплати'
            ];
        }
        
        // Формуємо примітку зі зміною статусу оплати
        $statusNote = date('Y-m-d H:i:s') . " - Статус оплати змінено на '$paymentStatus'";
        if (!empty($notes)) {
            $statusNote .= ": $notes";
        }
        
        // Оновлюємо статус оплати та додаємо примітку
        $updateQuery = "UPDATE orders SET 
                      payment_status = ?, 
                      notes = CONCAT(IFNULL(notes, ''), '\n', ?)
                      WHERE id = ?";
                      
        $updateResult = $this->db->execute($updateQuery, [$paymentStatus, $statusNote, $orderId]);
        
        if (!$updateResult) {
            return [
                'success' => false,
                'message' => 'Помилка при оновленні статусу оплати'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Статус оплати успішно оновлено'
        ];
    }

    // Призначення менеджера для замовлення
    public function assignManager($orderId, $managerId) {
        // Перевіряємо існування замовлення
        $orderQuery = "SELECT * FROM orders WHERE id = ?";
        $order = $this->db->selectOne($orderQuery, [$orderId]);
        
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Замовлення не знайдено'
            ];
        }
        
        // Перевіряємо існування менеджера
        $managerQuery = "SELECT * FROM users WHERE id = ? AND role = 'sales'";
        $manager = $this->db->selectOne($managerQuery, [$managerId]);
        
        if (!$manager) {
            return [
                'success' => false,
                'message' => 'Менеджер не знайдений'
            ];
        }
        
        // Оновлюємо замовлення
        $updateQuery = "UPDATE orders SET sales_manager_id = ? WHERE id = ?";
        $updateResult = $this->db->execute($updateQuery, [$managerId, $orderId]);
        
        if (!$updateResult) {
            return [
                'success' => false,
                'message' => 'Помилка при призначенні менеджера'
            ];
        }
        
        // Додаємо примітку про призначення менеджера
        $notes = date('Y-m-d H:i:s') . " - Призначено менеджера: " . $manager['name'];
        $notesQuery = "UPDATE orders SET notes = CONCAT(IFNULL(notes, ''), '\n', ?) WHERE id = ?";
        $this->db->execute($notesQuery, [$notes, $orderId]);
        
        return [
            'success' => true,
            'message' => 'Менеджера успішно призначено для замовлення'
        ];
    }

    // Отримання всіх повідомлень для менеджера
    public function getManagerMessages($managerId) {
        $query = "SELECT m.*, 
                 s.name as sender_name, s.role as sender_role,
                 r.name as receiver_name, r.role as receiver_role
                 FROM messages m
                 LEFT JOIN users s ON m.sender_id = s.id
                 LEFT JOIN users r ON m.receiver_id = r.id
                 WHERE m.sender_id = ? OR m.receiver_id = ?
                 ORDER BY m.created_at DESC";
        
        return $this->db->select($query, [$managerId, $managerId]);
    }

    // Відправлення повідомлення
    public function sendMessage($senderId, $receiverId, $subject, $message) {
        // Перевіряємо існування відправника і отримувача
        $senderQuery = "SELECT * FROM users WHERE id = ?";
        $sender = $this->db->selectOne($senderQuery, [$senderId]);
        
        $receiverQuery = "SELECT * FROM users WHERE id = ?";
        $receiver = $this->db->selectOne($receiverQuery, [$receiverId]);
        
        if (!$sender || !$receiver) {
            return [
                'success' => false,
                'message' => 'Користувач не знайдений'
            ];
        }
        
        // Створюємо повідомлення
        $insertQuery = "INSERT INTO messages (sender_id, receiver_id, subject, message) 
                       VALUES (?, ?, ?, ?)";
        
        $insertResult = $this->db->execute($insertQuery, [$senderId, $receiverId, $subject, $message]);
        
        if (!$insertResult) {
            return [
                'success' => false,
                'message' => 'Помилка при відправленні повідомлення'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Повідомлення успішно відправлено',
            'message_id' => $this->db->lastInsertId()
        ];
    }

    // Позначення повідомлення як прочитаного
    public function markMessageAsRead($messageId, $userId) {
        // Перевіряємо, що користувач є отримувачем повідомлення
        $messageQuery = "SELECT * FROM messages WHERE id = ? AND receiver_id = ?";
        $message = $this->db->selectOne($messageQuery, [$messageId, $userId]);
        
        if (!$message) {
            return [
                'success' => false,
                'message' => 'Повідомлення не знайдено або ви не є його отримувачем'
            ];
        }
        
        // Позначаємо як прочитане
        $updateQuery = "UPDATE messages SET is_read = 1 WHERE id = ?";
        $updateResult = $this->db->execute($updateQuery, [$messageId]);
        
        if (!$updateResult) {
            return [
                'success' => false,
                'message' => 'Помилка при оновленні статусу повідомлення'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Повідомлення позначено як прочитане'
        ];
    }


// Метод для поиска заказов по тексту (номеру или клиенту)
public function searchOrders($searchText, $page = 1, $perPage = ITEMS_PER_PAGE) {
    $query = "SELECT o.*, 
             u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
             COUNT(oi.id) as items_count, 
             SUM(oi.quantity) as total_items
             FROM orders o
             LEFT JOIN users u ON o.customer_id = u.id
             LEFT JOIN order_items oi ON o.id = oi.order_id
             WHERE (o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)
             GROUP BY o.id, o.customer_id, o.sales_manager_id, o.total_amount,
                     o.status, o.payment_status, o.payment_method, o.shipping_address,
                     o.shipping_cost, o.notes, o.created_at, o.updated_at,
                     u.name, u.email, u.phone
             ORDER BY o.created_at DESC";
    
    $searchParam = "%$searchText%";
    return $this->db->paginate($query, [$searchParam, $searchParam, $searchParam, $searchParam], $page, $perPage);
}

// Метод для получения статистики для дашборда менеджера
public function getDashboardData($managerId) {
    // Количество заказов менеджера по статусам
    $ordersQuery = "SELECT 
                  COUNT(*) as total_orders,
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                  SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_count,
                  SUM(CASE WHEN status = 'ready_for_pickup' THEN 1 ELSE 0 END) as ready_count,
                  SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_count,
                  SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
                  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                  SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                  SUM(total_amount) as total_sales
               FROM orders
               WHERE sales_manager_id = ?";
    
    $ordersStats = $this->db->selectOne($ordersQuery, [$managerId]) ?: [
        'total_orders' => 0,
        'pending_count' => 0,
        'processing_count' => 0,
        'ready_count' => 0,
        'shipped_count' => 0,
        'delivered_count' => 0,
        'completed_count' => 0,
        'cancelled_count' => 0,
        'total_sales' => 0
    ];
    
    // Заказы, требующие действия менеджера
    $pendingOrdersQuery = "SELECT o.*, 
                         u.name as customer_name, u.phone as customer_phone,
                         COUNT(oi.id) as items_count
                         FROM orders o
                         LEFT JOIN users u ON o.customer_id = u.id
                         LEFT JOIN order_items oi ON o.id = oi.order_id
                         WHERE o.sales_manager_id = ? AND (o.status = 'pending' OR o.status = 'processing')
                         GROUP BY o.id, o.customer_id, o.sales_manager_id, o.total_amount,
                                 o.status, o.payment_status, o.payment_method, o.shipping_address,
                                 o.shipping_cost, o.notes, o.created_at, o.updated_at,
                                 u.name, u.phone
                         ORDER BY o.created_at ASC
                         LIMIT 10";
    
    $pendingOrders = $this->db->select($pendingOrdersQuery, [$managerId]);
    
    // Непрочитанные сообщения
    $messagesQuery = "SELECT m.*, u.name as sender_name, u.role as sender_role
                     FROM messages m
                     JOIN users u ON m.sender_id = u.id
                     WHERE m.receiver_id = ? AND m.is_read = 0
                     ORDER BY m.created_at DESC";
    
    $unreadMessages = $this->db->select($messagesQuery, [$managerId]);
    
    // Статистика продаж за последний месяц (по дням)
    $monthAgo = date('Y-m-d', strtotime('-30 days'));
    
    $salesStatsQuery = "SELECT 
                       DATE(created_at) as date,
                       COUNT(*) as order_count,
                       SUM(total_amount) as total_sales
                    FROM orders
                    WHERE sales_manager_id = ? AND created_at >= ?
                    GROUP BY DATE(created_at)
                    ORDER BY DATE(created_at)";
    
    $salesByDay = $this->db->select($salesStatsQuery, [$managerId, $monthAgo]);
    
    // Топ клиентов менеджера
    $topCustomersQuery = "SELECT u.id, u.name, u.email, u.phone,
                        COUNT(o.id) as order_count,
                        SUM(o.total_amount) as total_spent,
                        MAX(o.created_at) as last_order_date
                     FROM users u
                     JOIN orders o ON u.id = o.customer_id
                     WHERE o.sales_manager_id = ?
                     GROUP BY u.id, u.name, u.email, u.phone
                     ORDER BY total_spent DESC
                     LIMIT 5";
    
    $topCustomers = $this->db->select($topCustomersQuery, [$managerId]);
    
    // Топ товаров, проданных менеджером
    $topProductsQuery = "SELECT p.id, p.name, p.image, pc.name as category_name,
                       SUM(oi.quantity) as total_quantity,
                       COUNT(DISTINCT o.id) as order_count,
                       SUM(oi.quantity * oi.price) as total_sales
                    FROM products p
                    JOIN product_categories pc ON p.category_id = pc.id
                    JOIN order_items oi ON p.id = oi.product_id
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.sales_manager_id = ?
                    GROUP BY p.id, p.name, p.image, pc.name
                    ORDER BY total_sales DESC
                    LIMIT 5";
    
    $topProducts = $this->db->select($topProductsQuery, [$managerId]);
    
    return [
        'orders_stats' => $ordersStats,
        'pending_orders' => $pendingOrders,
        'unread_messages' => $unreadMessages,
        'sales_by_day' => $salesByDay,
        'top_customers' => $topCustomers,
        'top_products' => $topProducts
    ];
}

    // Отримання клієнтів для менеджера
    public function getCustomers($page = 1, $perPage = ITEMS_PER_PAGE) {
        $query = "SELECT u.*, 
                (SELECT COUNT(*) FROM orders WHERE customer_id = u.id) as order_count,
                (SELECT SUM(total_amount) FROM orders WHERE customer_id = u.id) as total_spent,
                (SELECT MAX(created_at) FROM orders WHERE customer_id = u.id) as last_order_date
                FROM users u
                WHERE u.role = 'customer'
                ORDER BY order_count DESC, u.name";
        
        return $this->db->paginate($query, [], $page, $perPage);
    }

    // Отримання повної інформації про клієнта
    public function getCustomerDetails($customerId) {
        // Основна інформація про клієнта
        $customerQuery = "SELECT u.*, 
                        (SELECT COUNT(*) FROM orders WHERE customer_id = u.id) as order_count,
                        (SELECT SUM(total_amount) FROM orders WHERE customer_id = u.id) as total_spent,
                        (SELECT MAX(created_at) FROM orders WHERE customer_id = u.id) as last_order_date
                        FROM users u
                        WHERE u.id = ? AND u.role = 'customer'";
        
        $customer = $this->db->selectOne($customerQuery, [$customerId]);
        
        if (!$customer) {
            return [
                'success' => false,
                'message' => 'Клієнт не знайдений'
            ];
        }
        
        // Замовлення клієнта
        $ordersQuery = "SELECT o.*, 
                      COUNT(oi.id) as items_count, 
                      SUM(oi.quantity) as total_items,
                      m.name as manager_name
                      FROM orders o
                      LEFT JOIN order_items oi ON o.id = oi.order_id
                      LEFT JOIN users m ON o.sales_manager_id = m.id
                      WHERE o.customer_id = ?
                      GROUP BY o.id, o.customer_id, o.sales_manager_id, o.total_amount,
                              o.status, o.payment_status, o.payment_method, o.shipping_address,
                              o.shipping_cost, o.notes, o.created_at, o.updated_at,
                              m.name
                      ORDER BY o.created_at DESC";
        
        $orders = $this->db->select($ordersQuery, [$customerId]);
        
        // Улюблені товари (найчастіше замовлені)
        $favoriteProductsQuery = "SELECT p.id, p.name, p.image, pc.name as category_name,
                                COUNT(oi.id) as order_count,
                                SUM(oi.quantity) as total_quantity
                                FROM products p
                                JOIN product_categories pc ON p.category_id = pc.id
                                JOIN order_items oi ON p.id = oi.product_id
                                JOIN orders o ON oi.order_id = o.id
                                WHERE o.customer_id = ?
                                GROUP BY p.id, p.name, p.image, pc.name
                                ORDER BY total_quantity DESC
                                LIMIT 5";
        
        $favoriteProducts = $this->db->select($favoriteProductsQuery, [$customerId]);
        
        // Історія спілкування
        $messagesQuery = "SELECT m.*, 
                         CASE 
                            WHEN m.sender_id = ? THEN 'outgoing' 
                            ELSE 'incoming' 
                         END as direction
                         FROM messages m
                         WHERE m.sender_id = ? OR m.receiver_id = ?
                         ORDER BY m.created_at DESC";
        
        $messages = $this->db->select($messagesQuery, [$customerId, $customerId, $customerId]);
        
        return [
            'success' => true,
            'customer' => $customer,
            'orders' => $orders,
            'favorite_products' => $favoriteProducts,
            'messages' => $messages
        ];
    }

    // Створення нового замовлення менеджером від імені клієнта
    public function createOrder($data) {
        // Перевіряємо наявність обов'язкових полів
        if (empty($data['customer_id']) || empty($data['items'])) {
            return [
                'success' => false,
                'message' => 'Не вказано клієнта або товари'
            ];
        }
        
        // Перевіряємо існування клієнта
        $customerQuery = "SELECT * FROM users WHERE id = ? AND role = 'customer'";
        $customer = $this->db->selectOne($customerQuery, [$data['customer_id']]);
        
        if (!$customer) {
            return [
                'success' => false,
                'message' => 'Клієнт не знайдений'
            ];
        }
        
        // Перевіряємо наявність товарів на складі
        foreach ($data['items'] as $item) {
            $productQuery = "SELECT * FROM products WHERE id = ?";
            $product = $this->db->selectOne($productQuery, [$item['product_id']]);
            
            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Товар з ID ' . $item['product_id'] . ' не знайдений'
                ];
            }
            
            if ($product['stock_quantity'] < $item['quantity']) {
                return [
                    'success' => false,
                    'message' => 'Недостатня кількість товару "' . $product['name'] . '" на складі. Доступно: ' . $product['stock_quantity']
                ];
            }
        }
        
        // Розраховуємо загальну суму замовлення
        $totalAmount = 0;
        foreach ($data['items'] as $item) {
            $productQuery = "SELECT price FROM products WHERE id = ?";
            $product = $this->db->selectOne($productQuery, [$item['product_id']]);
            $totalAmount += $product['price'] * $item['quantity'];
        }
        
        // Додаємо вартість доставки, якщо вказана
        if (!empty($data['shipping_cost'])) {
            $totalAmount += $data['shipping_cost'];
        }
        
        // Створюємо замовлення
        $this->db->beginTransaction();
        
        try {
            // Вставляємо запис замовлення
            $insertOrderQuery = "INSERT INTO orders (
                                customer_id, sales_manager_id, total_amount, 
                                status, payment_status, payment_method, 
                                shipping_address, shipping_cost, notes
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $orderParams = [
                $data['customer_id'],
                $data['manager_id'] ?? null,
                $totalAmount,
                $data['status'] ?? 'pending',
                $data['payment_status'] ?? 'pending',
                $data['payment_method'] ?? null,
                $data['shipping_address'] ?? $customer['address'] . ', ' . $customer['city'] . ', ' . $customer['region'] . ', ' . $customer['postal_code'],
                $data['shipping_cost'] ?? 0,
                $data['notes'] ?? 'Замовлення створено менеджером'
            ];
            
            $insertOrderResult = $this->db->execute($insertOrderQuery, $orderParams);
            
            if (!$insertOrderResult) {
                throw new Exception('Помилка при створенні замовлення');
            }
            
            $orderId = $this->db->lastInsertId();
            
            // Вставляємо товари замовлення
            foreach ($data['items'] as $item) {
                $productQuery = "SELECT price FROM products WHERE id = ?";
                $product = $this->db->selectOne($productQuery, [$item['product_id']]);
                
                $insertItemQuery = "INSERT INTO order_items (
                                    order_id, product_id, quantity, price, discount
                                ) VALUES (?, ?, ?, ?, ?)";
                
                $itemParams = [
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $product['price'],
                    $item['discount'] ?? 0
                ];
                
                $insertItemResult = $this->db->execute($insertItemQuery, $itemParams);
                
                if (!$insertItemResult) {
                    throw new Exception('Помилка при додаванні товару до замовлення');
                }
            }
            
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
}