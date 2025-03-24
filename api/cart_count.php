<?php
// api/cart_count.php
// API для отримання кількості товарів у кошику

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';

// Встановлюємо заголовки для JSON відповіді
header('Content-Type: application/json');

// Отримуємо ID сесії з кукі
$customerController = new CustomerController();
$sessionId = isset($_COOKIE['cart_session_id']) ? $_COOKIE['cart_session_id'] : $customerController->generateCartSessionId();

// Отримуємо товари з кошика
$cartItems = $customerController->getCart($sessionId);

// Обчислюємо загальну кількість
$totalQuantity = 0;
foreach ($cartItems as $item) {
    $totalQuantity += $item['quantity'];
}

// Повертаємо результат
echo json_encode([
    'success' => true,
    'count' => $totalQuantity
]);