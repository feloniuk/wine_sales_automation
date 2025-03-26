<?php
// api/update_product_quantity.php
// Оновлення кількості товару в замовленні через AJAX

session_start();

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__FILE__)));
require_once ROOT_PATH . '/config.php';

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

// Перевіряємо, чи існує сесія з обраними товарами
if (!isset($_SESSION['selected_products'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Список товарів порожній'
    ]);
    exit;
}

// Оновлюємо кількість товару
foreach ($_SESSION['selected_products'] as &$product) {
    if ($product['id'] == $productId) {
        // Перевіряємо максимальну кількість на складі
        $product['quantity'] = min($quantity, $product['stock_quantity']);
        break;
    }
}

// Повертаємо оновлений список товарів
echo json_encode([
    'success' => true,
    'selected_products' => $_SESSION['selected_products']
]);
exit;