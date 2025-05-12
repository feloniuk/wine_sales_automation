<?php
// controllers/WarehouseController.php
// Контролер для роботи з складом

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config.php';
}

require_once ROOT_PATH . '/config/database.php';

class WarehouseController {
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->db->getConnection();
    }

    // Отримання всіх товарів на складі
    public function getAllProducts() {
        $query = "SELECT p.*, pc.name as category_name 
                FROM products p 
                JOIN product_categories pc ON p.category_id = pc.id 
                ORDER BY p.name";
        
        return $this->db->select($query);
    }

    // Отримання товарів з низьким запасом
    public function getLowStockProducts() {
        $query = "SELECT p.*, pc.name as category_name 
                FROM products p 
                JOIN product_categories pc ON p.category_id = pc.id 
                WHERE p.stock_quantity <= p.min_stock 
                ORDER BY (p.min_stock - p.stock_quantity) DESC";
        
        return $this->db->select($query);
    }

    // Отримання детальної інформації про товар
    public function getProductById($productId) {
        $query = "SELECT p.*, pc.name as category_name 
                FROM products p 
                JOIN product_categories pc ON p.category_id = pc.id 
                WHERE p.id = ?";
        
        return $this->db->selectOne($query, [$productId]);
    }

    // Оновлення інформації про товар
    public function updateProduct($productId, $data) {
        // Перевірка існування товару
        $productQuery = "SELECT * FROM products WHERE id = ?";
        $product = $this->db->selectOne($productQuery, [$productId]);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Товар не знайдено'
            ];
        }
        
        // Формуємо запит для оновлення
        $updateFields = [];
        $updateParams = [];
        
        $allowedFields = [
            'category_id', 'name', 'description', 'details', 'price', 
            'min_stock', 'year', 'alcohol', 'volume', 'image', 'featured', 'status'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $updateParams[] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            return [
                'success' => false,
                'message' => 'Немає даних для оновлення'
            ];
        }
        
        // Додаємо ID в параметри
        $updateParams[] = $productId;
        
        // Виконуємо оновлення
        $updateQuery = "UPDATE products SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $result = $this->db->execute($updateQuery, $updateParams);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Дані товару успішно оновлені'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Помилка при оновленні даних товару'
            ];
        }
    }

    // Додавання нового товару
    public function addProduct($data) {
        // Перевіряємо обов'язкові поля
        if (empty($data['name']) || empty($data['category_id']) || empty($data['price'])) {
            return [
                'success' => false,
                'message' => 'Будь ласка, заповніть всі обов\'язкові поля'
            ];
        }
        
        // Формуємо запит для вставки
        $query = "INSERT INTO products (
                    category_id, name, description, details, price, stock_quantity, 
                    min_stock, year, alcohol, volume, image, featured, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['category_id'],
            $data['name'],
            $data['description'] ?? '',
            $data['details'] ?? '',
            $data['price'],
            $data['stock_quantity'] ?? 0,
            $data['min_stock'] ?? 10,
            $data['year'] ?? null,
            $data['alcohol'] ?? null,
            $data['volume'] ?? 750,
            $data['image'] ?? '',
            $data['featured'] ?? 0,
            $data['status'] ?? 'active'
        ];
        
        $result = $this->db->execute($query, $params);
        
        if ($result) {
            $productId = $this->db->lastInsertId();
            
            // Записуємо в лог транзакції, якщо є початковий запас
            if (!empty($data['stock_quantity']) && $data['stock_quantity'] > 0) {
                $this->addInventoryTransaction(
                    $productId, 
                    $data['stock_quantity'], 
                    'in', 
                    null, 
                    'production', 
                    'Початковий запас товару'
                );
            }
            
            return [
                'success' => true,
                'message' => 'Товар успішно доданий',
                'product_id' => $productId
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Помилка при додаванні товару'
            ];
        }
    }

    // Оновлення кількості товару на складі (надходження/списання)
    public function updateStock($productId, $quantity, $transactionType, $referenceType, $referenceId = null, $notes = '') {
        // Перевіряємо існування товару
        $productQuery = "SELECT * FROM products WHERE id = ?";
        $product = $this->db->selectOne($productQuery, [$productId]);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Товар не знайдено'
            ];
        }
        
        // Перевіряємо коректність транзакції
        if ($transactionType === 'out' && $product['stock_quantity'] < $quantity) {
            return [
                'success' => false,
                'message' => 'Недостатня кількість товару на складі'
            ];
        }
        
        // Оновлюємо кількість товару
        $newQuantity = ($transactionType === 'in') 
            ? $product['stock_quantity'] + $quantity 
            : $product['stock_quantity'] - $quantity;
        
        $updateQuery = "UPDATE products SET stock_quantity = ? WHERE id = ?";
        $updateResult = $this->db->execute($updateQuery, [$newQuantity, $productId]);
        
        if (!$updateResult) {
            return [
                'success' => false,
                'message' => 'Помилка при оновленні кількості товару'
            ];
        }
        
        // Записуємо транзакцію в історію
        $transactionResult = $this->addInventoryTransaction(
            $productId, 
            $quantity, 
            $transactionType, 
            $referenceId, 
            $referenceType, 
            $notes
        );
        
        if (!$transactionResult['success']) {
            return [
                'success' => false,
                'message' => 'Помилка при записі транзакції: ' . $transactionResult['message']
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Кількість товару успішно оновлена',
            'new_quantity' => $newQuantity,
            'transaction_id' => $transactionResult['transaction_id']
        ];
    }

    // Додавання запису про транзакцію інвентаря
    private function addInventoryTransaction($productId, $quantity, $transactionType, $referenceId, $referenceType, $notes) {
        // Отримуємо ID користувача з сесії
        $userId = $_SESSION['user_id'] ?? null;
        
        $query = "INSERT INTO inventory_transactions (
                    product_id, quantity, transaction_type, reference_id,
                    reference_type, notes, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $productId,
            $quantity,
            $transactionType,
            $referenceId,
            $referenceType,
            $notes,
            $userId
        ];
        
        $result = $this->db->execute($query, $params);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Транзакція успішно записана',
                'transaction_id' => $this->db->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Помилка при записі транзакції'
            ];
        }
    }

    // Отримання історії транзакцій для товару
    public function getProductTransactionHistory($productId) {
        $query = "SELECT it.*, p.name as product_name, u.name as user_name
                FROM inventory_transactions it
                LEFT JOIN products p ON it.product_id = p.id
                LEFT JOIN users u ON it.created_by = u.id
                WHERE it.product_id = ?
                ORDER BY it.created_at DESC";
        
        return $this->db->select($query, [$productId]);
    }

    // Отримання замовлень, які очікують на комплектацію
    public function getPendingOrders() {
        $query = "SELECT o.*, u.name as customer_name, sm.name as sales_manager_name,
                COUNT(oi.id) as items_count,
                SUM(oi.quantity) as total_items
                FROM orders o
                LEFT JOIN users u ON o.customer_id = u.id
                LEFT JOIN users sm ON o.sales_manager_id = sm.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.status = 'pending' OR o.status = 'processing'
                GROUP BY o.id, o.customer_id, o.sales_manager_id, o.total_amount, 
                         o.status, o.payment_status, o.payment_method, o.shipping_address,
                         o.shipping_cost, o.notes, o.created_at, o.updated_at,
                         u.name, sm.name
                ORDER BY o.created_at ASC";
        
        return $this->db->select($query);
    }

    // Отримання детальної інформації про замовлення
    public function getOrderDetails($orderId) {
        // Загальна інформація про замовлення
        $orderQuery = "SELECT o.*, u.name as customer_name, u.phone as customer_phone,
                      sm.name as sales_manager_name
                      FROM orders o
                      LEFT JOIN users u ON o.customer_id = u.id
                      LEFT JOIN users sm ON o.sales_manager_id = sm.id
                      WHERE o.id = ?";
        
        $order = $this->db->selectOne($orderQuery, [$orderId]);
        
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Замовлення не знайдено'
            ];
        }
        
        // Товари в замовленні
        $itemsQuery = "SELECT oi.*, p.name as product_name, p.image, p.stock_quantity
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
        
        // Оновлюємо статус
        $updateQuery = "UPDATE orders SET status = ?, notes = CONCAT(notes, '\n', ?) WHERE id = ?";
        $updateResult = $this->db->execute($updateQuery, [$status, $notes, $orderId]);
        
        if (!$updateResult) {
            return [
                'success' => false,
                'message' => 'Помилка при оновленні статусу замовлення'
            ];
        }
        
        // Обробка специфічних дій при зміні статусу
        if ($status === 'ready_for_pickup' && $order['status'] === 'processing') {
            // Якщо замовлення готове до відправки, оновлюємо запаси на складі
            $this->processOrderInventory($orderId);
        } elseif ($status === 'cancelled' && in_array($order['status'], ['ready_for_pickup', 'shipped', 'delivered'])) {
            // Якщо замовлення скасовано після підготовки, повертаємо товари на склад
            $this->returnOrderInventory($orderId);
        }
        
        return [
            'success' => true,
            'message' => 'Статус замовлення успішно оновлено'
        ];
    }

    // Списання товарів зі складу для замовлення
    private function processOrderInventory($orderId) {
        // Отримуємо товари з замовлення
        $itemsQuery = "SELECT oi.product_id, oi.quantity, p.name as product_name
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.id
                      WHERE oi.order_id = ?";
        
        $items = $this->db->select($itemsQuery, [$orderId]);
        
        // Списуємо товари зі складу
        foreach ($items as $item) {
            $this->updateStock(
                $item['product_id'],
                $item['quantity'],
                'out',
                'order',
                $orderId,
                'Списання для замовлення #' . $orderId . ' - ' . $item['product_name']
            );
        }
    }

    // Повернення товарів на склад при скасуванні замовлення
    private function returnOrderInventory($orderId) {
        // Перевіряємо, чи були товари списані
        $transactionsQuery = "SELECT * FROM inventory_transactions 
                             WHERE reference_id = ? AND reference_type = 'order' AND transaction_type = 'out'";
        
        $transactions = $this->db->select($transactionsQuery, [$orderId]);
        
        if (empty($transactions)) {
            return; // Товари не були списані
        }
        
        // Повертаємо товари на склад
        foreach ($transactions as $transaction) {
            $this->updateStock(
                $transaction['product_id'],
                $transaction['quantity'],
                'in',
                'return',
                $orderId,
                'Повернення товару при скасуванні замовлення #' . $orderId
            );
        }
    }


// Метод для получения статистики по складу для дашборда
public function getDashboardData() {
    // Статистика по товарам
    $productsStatsQuery = "SELECT 
                COUNT(*) as total_products,
                SUM(CASE WHEN category_id = 1 THEN 1 ELSE 0 END) as red_wine_count,
                SUM(CASE WHEN category_id = 2 THEN 1 ELSE 0 END) as white_wine_count,
                SUM(CASE WHEN category_id IN (3, 4, 5) THEN 1 ELSE 0 END) as other_wine_count,
                SUM(CASE WHEN stock_quantity <= min_stock THEN 1 ELSE 0 END) as low_stock_count,
                SUM(stock_quantity) as total_stock_items,
                SUM(stock_quantity * price) as total_stock_value
             FROM products";
    
    $productsStats = $this->db->selectOne($productsStatsQuery) ?: [
        'total_products' => 0,
        'red_wine_count' => 0,
        'white_wine_count' => 0,
        'other_wine_count' => 0,
        'low_stock_count' => 0,
        'total_stock_items' => 0,
        'total_stock_value' => 0
    ];
    
    // Получение заказов, которые ожидают обработки
    $pendingOrdersQuery = "SELECT o.*, u.name as customer_name 
                        FROM orders o
                        JOIN users u ON o.customer_id = u.id
                        WHERE o.status IN ('pending', 'processing')
                        ORDER BY o.created_at ASC";
    
    $pendingOrders = $this->db->select($pendingOrdersQuery);
    
    // Получение товаров с низким запасом
    $lowStockProductsQuery = "SELECT p.*, pc.name as category_name 
                            FROM products p
                            JOIN product_categories pc ON p.category_id = pc.id
                            WHERE p.stock_quantity <= p.min_stock
                            ORDER BY ((p.min_stock - p.stock_quantity) / p.min_stock) DESC";
    
    $lowStockProducts = $this->db->select($lowStockProductsQuery);
    
    // Получение статистики по транзакциям за неделю
    $weekAgo = date('Y-m-d', strtotime('-7 days'));
    $today = date('Y-m-d');
    
    $transactionStatsQuery = "SELECT 
                            DATE(it.created_at) as date,
                            SUM(CASE WHEN it.transaction_type = 'in' THEN it.quantity ELSE 0 END) as in_quantity,
                            SUM(CASE WHEN it.transaction_type = 'out' THEN it.quantity ELSE 0 END) as out_quantity,
                            COUNT(CASE WHEN it.transaction_type = 'in' THEN 1 ELSE NULL END) as in_count,
                            COUNT(CASE WHEN it.transaction_type = 'out' THEN 1 ELSE NULL END) as out_count
                        FROM inventory_transactions it
                        WHERE it.created_at BETWEEN ? AND ?
                        GROUP BY DATE(it.created_at)
                        ORDER BY DATE(it.created_at)";
    
    $transactionStats = $this->db->select($transactionStatsQuery, [$weekAgo, $today]);
    
    // Получение топ-продуктов по количеству отправлений
    $topProductsQuery = "SELECT p.id, p.name, p.image, pc.name as category_name,
                        SUM(CASE WHEN it.transaction_type = 'out' THEN it.quantity ELSE 0 END) as total_out,
                        COUNT(DISTINCT CASE WHEN it.reference_type = 'order' THEN it.reference_id ELSE NULL END) as order_count
                     FROM products p
                     JOIN product_categories pc ON p.category_id = pc.id
                     JOIN inventory_transactions it ON p.id = it.product_id
                     WHERE it.transaction_type = 'out'
                     GROUP BY p.id, p.name, p.image, pc.name
                     ORDER BY total_out DESC
                     LIMIT 5";
    
    $topProducts = $this->db->select($topProductsQuery);
    
    return [
        'products_stats' => $productsStats,
        'pending_orders' => $pendingOrders,
        'low_stock_products' => $lowStockProducts,
        'transaction_stats' => $transactionStats,
        'top_products' => $topProducts
    ];
}

// This should be added to the WarehouseController.php file
public function performInventory($inventoryData) {
    // Start a transaction
    $this->db->beginTransaction();
    
    try {
        foreach ($inventoryData as $item) {
            // Get current stock
            $productQuery = "SELECT id, name, stock_quantity FROM products WHERE id = ?";
            $product = $this->db->selectOne($productQuery, [$item['product_id']]);
            
            if (!$product) {
                continue; // Skip non-existent products
            }
            
            $actualQuantity = intval($item['actual_quantity']);
            $difference = $actualQuantity - $product['stock_quantity'];
            
            if ($difference !== 0) {
                // Update product quantity
                $updateQuery = "UPDATE products SET stock_quantity = ? WHERE id = ?";
                $updateResult = $this->db->execute($updateQuery, [$actualQuantity, $item['product_id']]);
                
                if (!$updateResult) {
                    throw new Exception('Error updating quantity for product ' . $product['name']);
                }
                
                // Record adjustment transaction
                $transactionType = $difference > 0 ? 'in' : 'out';
                $quantity = abs($difference);
                $notes = !empty($item['notes']) ? $item['notes'] : 'Inventory adjustment';
                
                $transactionQuery = "INSERT INTO inventory_transactions (
                                    product_id, quantity, transaction_type, reference_type, 
                                    notes, created_by
                                ) VALUES (?, ?, ?, 'adjustment', ?, ?)";
                
                $transactionParams = [
                    $item['product_id'],
                    $quantity,
                    $transactionType,
                    $notes,
                    $_SESSION['user_id'] ?? null
                ];
                
                $transactionResult = $this->db->execute($transactionQuery, $transactionParams);
                
                if (!$transactionResult) {
                    throw new Exception('Error recording transaction for product ' . $product['name']);
                }
            }
        }
        
        // Commit the transaction
        $this->db->commit();
        return [
            'success' => true,
            'message' => 'Inventory successfully updated'
        ];
    }
    catch (Exception $e) {
        // Rollback the transaction in case of error
        $this->db->rollBack();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Метод для обработки заказа
public function processOrder($orderId) {
    // Проверяем существование заказа
    $orderQuery = "SELECT * FROM orders WHERE id = ?";
    $order = $this->db->selectOne($orderQuery, [$orderId]);
    
    if (!$order) {
        return [
            'success' => false,
            'message' => 'Заказ не найден'
        ];
    }
    
    // Проверяем статус заказа
    if ($order['status'] !== 'processing') {
        return [
            'success' => false,
            'message' => 'Неверный статус заказа для обработки'
        ];
    }
    
    // Изменяем статус на "готов к отправке"
    $updateResult = $this->updateOrderStatus($orderId, 'ready_for_pickup', 'Заказ подготовлен к отправке');
    
    if (!$updateResult['success']) {
        return $updateResult;
    }
    
    return [
        'success' => true,
        'message' => 'Заказ успешно обработан и готов к отправке'
    ];
}

// Метод для просмотра всех транзакций инвентаря
public function getAllTransactions($page = 1, $perPage = 20) {
    $query = "SELECT it.*, 
             p.name as product_name, 
             u.name as user_name
             FROM inventory_transactions it
             LEFT JOIN products p ON it.product_id = p.id
             LEFT JOIN users u ON it.created_by = u.id
             ORDER BY it.created_at DESC";
    
    return $this->db->paginate($query, [], $page, $perPage);
}

// Метод для фильтрации транзакций по типу или товару
public function filterTransactions($type = null, $productId = null, $page = 1, $perPage = 20) {
    $query = "SELECT it.*, 
             p.name as product_name, 
             u.name as user_name
             FROM inventory_transactions it
             LEFT JOIN products p ON it.product_id = p.id
             LEFT JOIN users u ON it.created_by = u.id
             WHERE 1=1";
    
    $params = [];
    
    if ($type) {
        $query .= " AND it.transaction_type = ?";
        $params[] = $type;
    }
    
    if ($productId) {
        $query .= " AND it.product_id = ?";
        $params[] = $productId;
    }
    
    $query .= " ORDER BY it.created_at DESC";
    
    return $this->db->paginate($query, $params, $page, $perPage);
}
    
}