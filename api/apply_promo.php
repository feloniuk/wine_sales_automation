<?php
// api/apply_promo.php
// API для застосування промокоду

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';

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
if (!isset($_POST['promo_code']) || !isset($_POST['subtotal'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Відсутні обов\'язкові параметри'
    ]);
    exit;
}

// Отримуємо параметри
$promoCode = $_POST['promo_code'];
$subtotal = floatval($_POST['subtotal']);

// Ініціалізуємо контролер
$customerController = new CustomerController();

// Перевіряємо і застосовуємо промокод
$result = $customerController->applyDiscount($subtotal, $promoCode);

// Повертаємо результат
if ($result['success']) {
    echo json_encode([
        'success' => true,
        'discount' => $result['discount'],
        'new_total' => $result['new_total'],
        'message' => 'Промокод успішно застосований'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}