<?php
// account/process_order.php
// Скрипт для обробки замовлень клієнта (відміна, повторення, тощо)

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('customer')) {
    header('Location: /login.php?redirect=account/orders');
    exit;
}

// Отримуємо дані поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер для роботи з замовленнями
$customerController = new CustomerController();

// Встановлюємо заголовки для JSON відповіді
header('Content-Type: application/json');

// Перевіряємо, чи це POST запит
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Метод не підтримується'
    ]);
    exit;
}

// Обробка дій з замовленням
$action = isset($_POST['action']) ? $_POST['action'] : '';
$orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

// Перевіряємо право доступу до замовлення
$orderDetails = $customerController->getCustomerOrderDetails($orderId, $currentUser['id']);
if (!$orderDetails['success']) {
    echo json_encode([
        'success' => false,
        'message' => 'Замовлення не знайдено або ви не маєте доступу до нього'
    ]);
    exit;
}

$result = [
    'success' => false,
    'message' => 'Невідома дія'
];

switch ($action) {
    case 'cancel':
        // Відміна замовлення
        if ($orderDetails['order']['status'] === 'pending' || $orderDetails['order']['status'] === 'processing') {
            // Логіка відміни замовлення
            // Оновлюємо статус замовлення
            $updateQuery = "UPDATE orders SET status = 'cancelled', notes = CONCAT(IFNULL(notes, ''), '\n', ?) WHERE id = ?";
            $cancelNote = date('Y-m-d H:i:s') . " - Замовлення скасовано клієнтом";
            $updateResult = $customerController->db->execute($updateQuery, [$cancelNote, $orderId]);
            
            if ($updateResult) {
                $result = [
                    'success' => true,
                    'message' => 'Замовлення успішно скасовано'
                ];
            } else {
                $result = [
                    'success' => false,
                    'message' => 'Помилка при скасуванні замовлення'
                ];
            }
        } else {
            $result = [
                'success' => false,
                'message' => 'Неможливо скасувати замовлення в поточному статусі'
            ];
        }
        break;
        
    case 'repeat':
        // Повторення замовлення (створення нового на основі існуючого)
        $items = $orderDetails['items'];
        
        // Перевіряємо наявність товарів
        $allAvailable = true;
        foreach ($items as $item) {
            // Отримуємо поточну інформацію про товар
            $productQuery = "SELECT stock_quantity FROM products WHERE id = ?";
            $product = $customerController->db->selectOne($productQuery, [$item['product_id']]);
            
            if (!$product || $product['stock_quantity'] < $item['quantity']) {
                $allAvailable = false;
                break;
            }
        }
        
        if ($allAvailable) {
            // Створюємо нове замовлення на основі існуючого
            // Отримуємо дані для нового замовлення
            $orderData = [
                'shipping_address' => $orderDetails['order']['shipping_address'],
                'payment_method' => $orderDetails['order']['payment_method'],
                'shipping_cost' => 150.00, // Фіксована вартість доставки
                'notes' => 'Повторне замовлення на основі #' . $orderId
            ];
            
            // Створюємо новий запис сесії кошика для повторного замовлення
            $sessionId = 'repeat_' . $currentUser['id'] . '_' . time();
            
            // Додаємо товари з попереднього замовлення до кошика
            foreach ($items as $item) {
                $addResult = $customerController->addToCart(
                    $sessionId,
                    $item['product_id'],
                    $item['quantity']
                );
                
                if (!$addResult['success']) {
                    $result = [
                        'success' => false,
                        'message' => 'Помилка при створенні замовлення: ' . $addResult['message']
                    ];
                    echo json_encode($result);
                    exit;
                }
            }
            
            // Створюємо нове замовлення
            $createResult = $customerController->createOrder(
                $currentUser['id'],
                $sessionId,
                $orderData
            );
            
            if ($createResult['success']) {
                $result = [
                    'success' => true,
                    'message' => 'Замовлення успішно повторено',
                    'order_id' => $createResult['order_id']
                ];
            } else {
                $result = [
                    'success' => false,
                    'message' => 'Помилка при створенні замовлення: ' . $createResult['message']
                ];
            }
        } else {
            $result = [
                'success' => false,
                'message' => 'Деякі товари з замовлення недоступні або їх недостатньо на складі'
            ];
        }
        break;
        
    case 'confirm_delivery':
        // Підтвердження отримання замовлення
        if ($orderDetails['order']['status'] === 'shipped' || $orderDetails['order']['status'] === 'delivered') {
            // Оновлюємо статус замовлення
            $updateQuery = "UPDATE orders SET status = 'completed', notes = CONCAT(IFNULL(notes, ''), '\n', ?) WHERE id = ?";
            $confirmNote = date('Y-m-d H:i:s') . " - Отримання підтверджено клієнтом";
            $updateResult = $customerController->db->execute($updateQuery, [$confirmNote, $orderId]);
            
            if ($updateResult) {
                $result = [
                    'success' => true,
                    'message' => 'Отримання замовлення підтверджено'
                ];
            } else {
                $result = [
                    'success' => false,
                    'message' => 'Помилка при підтвердженні отримання замовлення'
                ];
            }
        } else {
            $result = [
                'success' => false,
                'message' => 'Неможливо підтвердити отримання замовлення в поточному статусі'
            ];
        }
        break;
        
    case 'add_to_cart':
        // Додавання товарів з замовлення до кошика
        $items = $orderDetails['items'];
        
        // Отримуємо ID сесії з кукі або створюємо новий для кошика
        $sessionId = isset($_COOKIE['cart_session_id']) ? $_COOKIE['cart_session_id'] : $customerController->generateCartSessionId();
        
        // Якщо користувач авторизований, використовуємо його ID як сесію кошика
        if ($isLoggedIn) {
            $sessionId = 'user_' . $currentUser['id'];
        }
        
        $addedCount = 0;
        foreach ($items as $item) {
            $addResult = $customerController->addToCart(
                $sessionId,
                $item['product_id'],
                $item['quantity']
            );
            
            if ($addResult['success']) {
                $addedCount++;
            }
        }
        
        if ($addedCount > 0) {
            $result = [
                'success' => true,
                'message' => 'Товари успішно додано до кошика',
                'added_count' => $addedCount,
                'total_items' => count($items)
            ];
        } else {
            $result = [
                'success' => false,
                'message' => 'Не вдалося додати товари до кошика'
            ];
        }
        break;
}

// Повертаємо результат
echo json_encode($result);
exit;