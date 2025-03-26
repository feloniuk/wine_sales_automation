<?php
// api/remove_product_from_order.php
// Видалення товару з замовлення через AJAX

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

// Перевіряємо наявність обов'язкового параметру
if (!isset($_POST['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Відсутній ідентифікатор товару'
    ]);
    exit;
}

// Отримуємо ID товару
$productId = intval($_POST['product_id']);

// Перевіряємо, чи існує сесія з обраними товарами
if (!isset($_SESSION['selected_products'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Список товарів порожній'
    ]);
    exit;
}

// Видаляємо товар з масиву
foreach ($_SESSION['selected_products'] as $key => $product) {
    if ($product['id'] == $productId) {
        unset($_SESSION['selected_products'][$key]);
        break;
    }
}

// Переіндексуємо масив
$_SESSION['selected_products'] = array_values($_SESSION['selected_products']);

// Повертаємо оновлений список товарів
echo json_encode([
    'success' => true,
    'selected_products' => $_SESSION['selected_products']
]);
exit;