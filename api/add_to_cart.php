<?php
// api/add_to_cart.php
// API для додавання товарів до кошика

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';
require_once ROOT_PATH . '/controllers/AuthController.php';

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

// Перевіряємо наявність обов'язкових параметрів
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Відсутні обов\'язкові параметри'
    ]);
    exit;
}

// Отримуємо параметри
$productId = intval($_POST['product_id']);
$quantity = intval($_POST['quantity']);

// Валідуємо quantity
if ($quantity <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Кількість повинна бути більше нуля'
    ]);
    exit;
}

// Отримуємо ID сесії з кукі або створюємо новий
$customerController = new CustomerController();
$sessionId = isset($_COOKIE['cart_session_id']) ? $_COOKIE['cart_session_id'] : $customerController->generateCartSessionId();

// Перевіряємо авторизацію
$authController = new AuthController();
if ($authController->isLoggedIn()) {
    // Якщо користувач авторизований, використовуємо його ID як сесію
    $currentUser = $authController->getCurrentUser();
    $sessionId = 'user_' . $currentUser['id'];
}

// Додаємо товар до кошика
$result = $customerController->addToCart($sessionId, $productId, $quantity);

// Повертаємо результат
echo json_encode($result);