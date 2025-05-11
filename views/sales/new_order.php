<?php
// sales/new_order.php
// Сторінка створення нового замовлення менеджером продажу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/SalesController.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('sales')) {
    header('Location: /login.php?redirect=sales/new_order');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролери
$salesController = new SalesController();
$customerController = new CustomerController();

// Змінні для повідомлень
$message = '';
$messageType = '';

// Змінні для форми
$selectedCustomerId = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$selectedProducts = [];

// AJAX запит на отримання списку товарів
if (isset($_GET['ajax_search_products'])) {
    $search = isset($_GET['term']) ? trim($_GET['term']) : '';
    $category = isset($_GET['category']) ? intval($_GET['category']) : 0;
    
    if (empty($search) && $category <= 0) {
        echo json_encode([]);
        exit;
    }
    
    $page = 1;
    $perPage = 10;
    
    if (!empty($search)) {
        $productsData = $customerController->searchProducts($search, $page, $perPage);
    } elseif ($category > 0) {
        $productsData = $customerController->getProductsByCategory($category, $page, $perPage);
    } else {
        $productsData = $customerController->getAllProducts($page, $perPage);
    }
    
    $products = $productsData['data'];
    
    // Форматування результатів для Ajax
    $results = [];
    foreach ($products as $product) {
        $results[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'stock_quantity' => $product['stock_quantity'],
            'category' => $product['category_name']
        ];
    }
    
    echo json_encode($results);
    exit;
}

// AJAX запит на додавання товару до замовлення
if (isset($_POST['ajax_add_product'])) {
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Невірний ID товару']);
        exit;
    }
    
    $productDetails = $customerController->getProductDetails($productId);
    
    if (!$productDetails['success']) {
        echo json_encode(['success' => false, 'message' => 'Товар не знайдено']);
        exit;
    }
    
    if ($productDetails['product']['stock_quantity'] < $quantity) {
        echo json_encode([
            'success' => false, 
            'message' => 'Недостатня кількість товару на складі. Доступно: ' . $productDetails['product']['stock_quantity']
        ]);
        exit;
    }
    
    // Додаємо товар до сесійного списку обраних товарів
    if (!isset($_SESSION['selected_products'])) {
        $_SESSION['selected_products'] = [];
    }
    
    // Перевіряємо, чи товар вже обраний
    $productExists = false;
    foreach ($_SESSION['selected_products'] as &$product) {
        if ($product['id'] == $productId) {
            $product['quantity'] += $quantity;
            $productExists = true;
            break;
        }
    }
    
    if (!$productExists) {
        $_SESSION['selected_products'][] = [
            'id' => $productId,
            'name' => $productDetails['product']['name'],
            'price' => $productDetails['product']['price'],
            'image' => $productDetails['product']['image'],
            'stock_quantity' => $productDetails['product']['stock_quantity'],
            'quantity' => $quantity
        ];
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Товар "' . $productDetails['product']['name'] . '" додано до замовлення.'
    ]);
    exit;
}

// Якщо передано товар в GET, додаємо його до списку обраних товарів
if (isset($_GET['add_product']) && is_numeric($_GET['add_product'])) {
    $productId = intval($_GET['add_product']);
    $productDetails = $customerController->getProductDetails($productId);
    
    if ($productDetails['success']) {
        // Додаємо товар до сесійного списку обраних товарів
        if (!isset($_SESSION['selected_products'])) {
            $_SESSION['selected_products'] = [];
        }
        
        // Перевіряємо, чи товар вже обраний
        $productExists = false;
        foreach ($_SESSION['selected_products'] as &$product) {
            if ($product['id'] == $productId) {
                $product['quantity'] += 1;
                $productExists = true;
                break;
            }
        }
        
        if (!$productExists) {
            $_SESSION['selected_products'][] = [
                'id' => $productId,
                'name' => $productDetails['product']['name'],
                'price' => $productDetails['product']['price'],
                'image' => $productDetails['product']['image'],
                'stock_quantity' => $productDetails['product']['stock_quantity'],
                'quantity' => 1
            ];
        }
        
        $message = 'Товар "' . $productDetails['product']['name'] . '" додано до замовлення.';
        $messageType = 'success';
    }
}

// Отримуємо список обраних товарів з сесії
$selectedProducts = isset($_SESSION['selected_products']) ? $_SESSION['selected_products'] : [];

// Обробка видалення товару зі списку
if (isset($_POST['remove_product']) && isset($_POST['product_id'])) {
    $productId = intval($_POST['product_id']);
    
    foreach ($_SESSION['selected_products'] as $key => $product) {
        if ($product['id'] == $productId) {
            unset($_SESSION['selected_products'][$key]);
            $message = 'Товар видалено зі списку.';
            $messageType = 'success';
            break;
        }
    }
    
    // Переіндексуємо масив
    $_SESSION['selected_products'] = array_values($_SESSION['selected_products']);
    $selectedProducts = $_SESSION['selected_products'];
}

// Обробка оновлення кількості товару
if (isset($_POST['update_quantity']) && isset($_POST['quantities'])) {
    $quantities = $_POST['quantities'];
    
    foreach ($quantities as $productId => $quantity) {
        $productId = intval($productId);
        $quantity = intval($quantity);
        
        if ($quantity > 0) {
            foreach ($_SESSION['selected_products'] as &$product) {
                if ($product['id'] == $productId) {
                    $product['quantity'] = min($quantity, $product['stock_quantity']);
                    break;
                }
            }
        }
    }
    
    $message = 'Кількість товарів оновлено.';
    $messageType = 'success';
    $selectedProducts = $_SESSION['selected_products'];
}

// Обробка створення замовлення
if (isset($_POST['create_order'])) {
    $customerId = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    $shippingAddress = isset($_POST['shipping_address']) ? trim($_POST['shipping_address']) : '';
    $shippingCost = isset($_POST['shipping_cost']) ? floatval($_POST['shipping_cost']) : 0;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Перевірка даних
    if ($customerId <= 0) {
        $message = 'Будь ласка, виберіть клієнта.';
        $messageType = 'error';
    } elseif (empty($selectedProducts)) {
        $message = 'Будь ласка, додайте товари до замовлення.';
        $messageType = 'error';
    } elseif (empty($paymentMethod)) {
        $message = 'Будь ласка, виберіть спосіб оплати.';
        $messageType = 'error';
    } elseif (empty($shippingAddress)) {
        $message = 'Будь ласка, вкажіть адресу доставки.';
        $messageType = 'error';
    } else {
        // Формуємо дані для створення замовлення
        $orderData = [
            'customer_id' => $customerId,
            'manager_id' => $currentUser['id'],
            'items' => array_map(function($product) {
                return [
                    'product_id' => $product['id'],
                    'quantity' => $product['quantity'],
                    'discount' => 0
                ];
            }, $selectedProducts),
            'payment_method' => $paymentMethod,
            'shipping_address' => $shippingAddress,
            'shipping_cost' => $shippingCost,
            'notes' => $notes,
            'status' => 'processing' // Відразу встановлюємо статус "В обробці"
        ];
        
        // Створюємо замовлення
        $result = $salesController->createOrder($orderData);
        
        if ($result['success']) {
            // Очищаємо список обраних товарів
            unset($_SESSION['selected_products']);
            $selectedProducts = [];
            
            $message = 'Замовлення успішно створено! <a href="order_details.php?id=' . $result['order_id'] . '" class="text-green-800 font-medium underline">Перейти до деталей замовлення</a>';
            $messageType = 'success';
        } else {
            $message = 'Помилка при створенні замовлення: ' . $result['message'];
            $messageType = 'error';
        }
    }
}

// Отримуємо список клієнтів для форми
$customersData = $salesController->getCustomers(1, 1000); // Отримуємо всіх клієнтів
$customers = $customersData['data'];

// Отримуємо деталі обраного клієнта, якщо він обраний
$selectedCustomer = null;
if ($selectedCustomerId > 0) {
    foreach ($customers as $customer) {
        if ($customer['id'] == $selectedCustomerId) {
            $selectedCustomer = $customer;
            break;
        }
    }
}

// Отримуємо список категорій для фільтра
$categories = $customerController->getAllCategories();

// Розрахунок загальної суми замовлення
$subtotal = 0;
foreach ($selectedProducts as $product) {
    $subtotal += $product['price'] * $product['quantity'];
}

// Фіксована вартість доставки
$shippingCost = 150.00;

// Загальна сума
$total = $subtotal + $shippingCost;
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Нове замовлення - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Додаємо jQuery і jQuery UI для автозаповнення -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <style>
        /* Стилі для автозаповнення */
        .ui-autocomplete {
            max-height: 300px;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 9999;
            border-radius: 0.375rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .ui-menu-item {
            padding: 8px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .ui-menu-item:hover {
            background-color: #f3f4f6;
        }
        .product-item {
            display: flex;
            align-items: center;
        }
        .product-item img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            margin-right: 10px;
            border-radius: 4px;
        }
        .product-item-details {
            flex: 1;
        }
        .product-item-name {
            font-weight: 500;
        }
        .product-item-price {
            font-size: 0.875rem;
            color: #059669;
        }
        .product-item-stock {
            font-size: 0.75rem;
            color: #6b7280;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Бічне меню -->
        <div class="w-64 bg-green-800 text-white">
            <div class="p-4 font-bold text-xl">Винна крамниця</div>
            <nav class="mt-8">
                <a href="index.php" class="flex items-center px-4 py-3 hover:bg-green-700">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    <span>Дашборд</span>
                </a>
                <a href="orders.php" class="flex items-center px-4 py-3 hover:bg-green-700">
                    <i class="fas fa-shopping-cart mr-3"></i>
                    <span>Замовлення</span>
                </a>
                <a href="customers.php" class="flex items-center px-4 py-3 hover:bg-green-700">
                    <i class="fas fa-users mr-3"></i>
                    <span>Клієнти</span>
                </a>
                <a href="messages.php" class="flex items-center px-4 py-3 hover:bg-green-700">
                    <i class="fas fa-envelope mr-3"></i>
                    <span>Повідомлення</span>
                </a>
                <a href="products.php" class="flex items-center px-4 py-3 hover:bg-green-700">
                    <i class="fas fa-wine-bottle mr-3"></i>
                    <span>Каталог</span>
                </a>
                <a href="new_order.php" class="flex items-center px-4 py-3 bg-green-700">
                    <i class="fas fa-plus-circle mr-3"></i>
                    <span>Нове замовлення</span>
                </a>
                <a href="reports.php" class="flex items-center px-4 py-3 hover:bg-green-700">
                    <i class="fas fa-chart-bar mr-3"></i>
                    <span>Звіти</span>
                </a>
                <a href="../logout.php" class="flex items-center px-4 py-3 hover:bg-green-700 mt-8">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    <span>Вихід</span>
                </a>
            </nav>
        </div>

        <!-- Основний контент -->
        <div class="flex-1">
            <!-- Верхня панель -->
            <header class="bg-white shadow">
                <div class="flex items-center justify-between px-6 py-4">
                    <h1 class="text-2xl font-semibold text-gray-800">Створення нового замовлення</h1>
                    <div class="flex items-center">
                        <div class="relative">
                            <button class="flex items-center text-gray-700 focus:outline-none">
                                <img src="../../assets/images/avatar.jpg" alt="Avatar" class="h-8 w-8 rounded-full mr-2">
                                <span><?= $currentUser['name'] ?></span>
                                <i class="fas fa-chevron-down ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Контент сторінки -->
            <main class="p-6">
                <!-- Повідомлення про результат операції -->
                <?php if (!empty($message)): ?>
                    <?php
                    $alertClass = '';
                    $iconClass = '';
                    if ($messageType === 'success') {
                        $alertClass = 'bg-green-100 border-green-500 text-green-700';
                        $iconClass = 'fas fa-check-circle text-green-500';
                    } elseif ($messageType === 'error') {
                        $alertClass = 'bg-red-100 border-red-500 text-red-700';
                        $iconClass = 'fas fa-exclamation-circle text-red-500';
                    } elseif ($messageType === 'warning') {
                        $alertClass = 'bg-yellow-100 border-yellow-500 text-yellow-700';
                        $iconClass = 'fas fa-exclamation-triangle text-yellow-500';
                    }
                    ?>
                    <div class="<?= $alertClass ?> border-l-4 p-4 mb-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="<?= $iconClass ?>"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm"><?= $message ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Форма створення замовлення -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Ліва колонка - Вибір клієнта та дані доставки -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-lg shadow p-6 mb-6">
                            <h2 class="text-lg font-semibold mb-4">Клієнт та доставка</h2>
                            
                            <?php if (empty($selectedCustomer)): ?>
                            <!-- Вибір клієнта -->
                            <div class="mb-6">
                                <label class="block text-gray-700 font-medium mb-2">Виберіть клієнта</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <?php foreach ($customers as $customer): ?>
                                    <a href="new_order.php?customer_id=<?= $customer['id'] ?>" class="border rounded-lg p-4 hover:bg-green-50 hover:border-green-500 transition-colors">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                                <i class="fas fa-user text-gray-500"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium"><?= htmlspecialchars($customer['name']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($customer['phone'] ?? 'Телефон не вказано') ?></div>
                                            </div>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <!-- Форма з даними клієнта та доставки -->
                            <form action="new_order.php" method="POST" id="order-form">
                                <!-- Інформація про клієнта -->
                                <div class="mb-6">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                                <i class="fas fa-user text-gray-500"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium"><?= htmlspecialchars($selectedCustomer['name']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($selectedCustomer['phone'] ?? 'Телефон не вказано') ?></div>
                                            </div>
                                        </div>
                                        <a href="new_order.php" class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-times"></i> Змінити клієнта
                                        </a>
                                    </div>
                                </div>
                                
                                <input type="hidden" name="customer_id" value="<?= $selectedCustomer['id'] ?>">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <label for="shipping_address" class="block text-gray-700 font-medium mb-2">Адреса доставки</label>
                                        <textarea id="shipping_address" name="shipping_address" rows="3" 
                                                class="border rounded w-full p-2 focus:outline-none focus:ring-2 focus:ring-green-500"><?= htmlspecialchars($selectedCustomer['address'] ?? '') . ', ' . htmlspecialchars($selectedCustomer['city'] ?? '') . ', ' . htmlspecialchars($selectedCustomer['region'] ?? '') . ', ' . htmlspecialchars($selectedCustomer['postal_code'] ?? '') ?></textarea>
                                    </div>
                                    <div>
                                        <label for="payment_method" class="block text-gray-700 font-medium mb-2">Спосіб оплати</label>
                                        <select id="payment_method" name="payment_method" 
                                                class="border rounded w-full p-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                            <option value="">Виберіть спосіб оплати</option>
                                            <option value="card">Оплата картою онлайн</option>
                                            <option value="bank_transfer">Банківський переказ</option>
                                            <option value="cash_on_delivery">Оплата при отриманні</option>
                                        </select>
                                        
                                        <label for="shipping_cost" class="block text-gray-700 font-medium mb-2 mt-4">Вартість доставки (₴)</label>
                                        <input type="number" id="shipping_cost" name="shipping_cost" value="<?= $shippingCost ?>" min="0" step="0.01"
                                              class="border rounded w-full p-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        
                                        <label for="notes" class="block text-gray-700 font-medium mb-2 mt-4">Примітки до замовлення</label>
                                        <textarea id="notes" name="notes" rows="3" 
                                                class="border rounded w-full p-2 focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Вибір товарів -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h2 class="text-lg font-semibold mb-4">Товари</h2>
                            <?php if (empty($selectedCustomer)): ?>
                                <div class="text-center text-gray-500 p-6">
                                    <p>Спочатку виберіть клієнта</p>
                                </div>
                            <?php else: ?>
                                <!-- Пошук та додавання товарів -->
                                <div class="mb-6">
                                    <label for="product_search" class="block text-gray-700 font-medium mb-2">Пошук і додавання товарів</label>
                                    <div class="flex flex-col md:flex-row gap-4">
                                        <div class="md:w-1/3">
                                            <select id="product_category" class="border rounded w-full p-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                                <option value="0">Всі категорії</option>
                                                <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="md:flex-1">
                                            <div class="relative">
                                                <input type="text" id="product_search" placeholder="Введіть назву товару..." 
                                                       class="border rounded w-full p-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Обраний товар для додавання -->
                                <div id="selected_product_container" class="mb-6 hidden border rounded p-4 bg-gray-50">
                                    <div class="flex items-center">
                                        <img id="selected_product_image" src="" alt="" class="w-16 h-16 object-cover rounded mr-4">
                                        <div class="flex-1">
                                            <h3 id="selected_product_name" class="font-medium"></h3>
                                            <div class="flex justify-between">
                                                <p id="selected_product_price" class="text-green-600"></p>
                                                <p id="selected_product_stock" class="text-gray-500 text-sm"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex items-center">
                                        <label for="selected_product_quantity" class="block text-gray-700 mr-2">Кількість:</label>
                                        <input type="number" id="selected_product_quantity" value="1" min="1" 
                                               class="border rounded w-20 p-1 text-center mr-2">
                                        <button type="button" id="add_selected_product" class="bg-green-600 hover:bg-green-700 text-white px-4 py-1 rounded">
                                            Додати
                                        </button>
                                        <button type="button" id="cancel_selected_product" class="ml-2 text-gray-500 hover:text-gray-700">
                                            <i class="fas fa-times"></i> Скасувати
                                        </button>
                                        <input type="hidden" id="selected_product_id" value="">
                                        <input type="hidden" id="selected_product_max" value="">
                                    </div>
                                </div>
                                
                                <?php if (empty($selectedProducts)): ?>
                                <div id="empty_products_message" class="text-center text-gray-500 p-6">
                                    <p>Додайте товари до замовлення</p>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Список обраних товарів -->
                                <div id="selected_products_list" class="<?= empty($selectedProducts) ? 'hidden' : '' ?>">
                                    <div class="overflow-x-auto">
                                        <form action="new_order.php?customer_id=<?= $selectedCustomerId ?>" method="POST">
                                            <table class="<table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Товар</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ціна</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Кількість</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Всього</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php foreach ($selectedProducts as $product): ?>
                                                <tr>
                                                    <td class="px-4 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <img src="http://winery_sales.loc/assets/images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="h-10 w-10 rounded-md object-cover mr-3">
                                                            <div>
                                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-900"><?= number_format($product['price'], 2) ?> ₴</div>
                                                    </td>
                                                    <td class="px-4 py-4 whitespace-nowrap">
                                                        <input type="number" name="quantities[<?= $product['id'] ?>]" value="<?= $product['quantity'] ?>" min="1" max="<?= $product['stock_quantity'] ?>" class="border rounded w-16 p-1 text-center">
                                                    </td>
                                                    <td class="px-4 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-medium text-gray-900"><?= number_format($product['price'] * $product['quantity'], 2) ?> ₴</div>
                                                    </td>
                                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                                        <button type="submit" name="remove_product" class="text-red-500 hover:text-red-700">
                                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <div class="mt-3 flex justify-end">
                                            <button type="submit" name="update_quantity" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded mr-2">
                                                <i class="fas fa-sync-alt mr-1"></i> Оновити кількість
                                            </button>
                                        </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Права колонка - Підсумок замовлення -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow p-6 sticky top-6">
                            <h2 class="text-lg font-semibold mb-4">Підсумок замовлення</h2>
                            
                            <?php if (empty($selectedCustomer)): ?>
                                <div class="text-center text-gray-500 p-6">
                                    <p>Спочатку виберіть клієнта</p>
                                </div>
                            <?php else: ?>
                                <div class="mb-4">
                                    <table class="w-full text-gray-700">
                                        <tr>
                                            <td class="py-2">Кількість товарів:</td>
                                            <td class="py-2 text-right">
                                                <?php
                                                $totalQuantity = 0;
                                                foreach ($selectedProducts as $product) {
                                                    $totalQuantity += $product['quantity'];
                                                }
                                                echo $totalQuantity;
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="py-2">Вартість товарів:</td>
                                            <td class="py-2 text-right"><?= number_format($subtotal, 2) ?> ₴</td>
                                        </tr>
                                        <tr>
                                            <td class="py-2">Вартість доставки:</td>
                                            <td class="py-2 text-right"><?= number_format($shippingCost, 2) ?> ₴</td>
                                        </tr>
                                        <tr class="border-t">
                                            <td class="py-2 font-semibold">Загальна сума:</td>
                                            <td class="py-2 text-right font-semibold"><?= number_format($total, 2) ?> ₴</td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div>
                                    <button type="submit" form="order-form" name="create_order" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-medium <?= empty($selectedProducts) ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= empty($selectedProducts) ? 'disabled' : '' ?>>
                                        <i class="fas fa-check-circle mr-2"></i> Оформити замовлення
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- JavaScript для функціональності сторінки -->
    <script>
        $(document).ready(function() {
            let selectedProduct = null;
            
            // Функція автозаповнення пошуку товарів
            $("#product_search").autocomplete({
                source: function(request, response) {
                    const category = $("#product_category").val();
                    $.ajax({
                        url: "new_order.php",
                        dataType: "json",
                        data: {
                            ajax_search_products: 1,
                            term: request.term,
                            category: category
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    label: item.name,
                                    value: item.name,
                                    item: item
                                };
                            }));
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    showSelectedProduct(ui.item.item);
                    return false;
                }
            }).autocomplete("instance")._renderItem = function(ul, item) {
                return $("<li>")
                    .append("<div class='product-item'>" +
                        "<img src='http://winery_sales.loc/assets/images\/" + item.item.image + "' alt='" + item.item.name + "'>" +
                        "<div class='product-item-details'>" +
                        "<div class='product-item-name'>" + item.item.name + "</div>" +
                        "<div class='product-item-price'>" + item.item.price + " ₴</div>" +
                        "<div class='product-item-stock'>В наявності: " + item.item.stock_quantity + "</div>" +
                        "</div>" +
                        "</div>")
                    .appendTo(ul);
            };
            
            // Відображення обраного товару перед додаванням до списку
            function showSelectedProduct(product) {
                selectedProduct = product;
                
                $("#selected_product_id").val(product.id);
                $("#selected_product_name").text(product.name);
                $("#selected_product_price").text(product.price + " ₴");
                $("#selected_product_image").attr("src", "http://winery_sales.loc/assets/images\/" + product.image);
                $("#selected_product_stock").text("В наявності: " + product.stock_quantity);
                $("#selected_product_max").val(product.stock_quantity);
                $("#selected_product_quantity").attr("max", product.stock_quantity);
                $("#selected_product_quantity").val(1);
                
                $("#selected_product_container").removeClass("hidden");
                $("#product_search").val("");
            }
            
            // Скасування вибору товару
            $("#cancel_selected_product").click(function() {
                $("#selected_product_container").addClass("hidden");
                selectedProduct = null;
            });
            
            // Додавання товару до замовлення через AJAX
            $("#add_selected_product").click(function() {
                if (!selectedProduct) return;
                
                const productId = $("#selected_product_id").val();
                const quantity = parseInt($("#selected_product_quantity").val());
                
                $.ajax({
                    url: "new_order.php",
                    method: "POST",
                    dataType: "json",
                    data: {
                        ajax_add_product: 1,
                        product_id: productId,
                        quantity: quantity
                    },
                    success: function(response) {
                        if (response.success) {
                            // Оновлюємо сторінку після успішного додавання
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert("Помилка при додаванні товару до замовлення");
                    }
                });
            });
            
            // Оновлення вартості доставки в підсумку
            $("#shipping_cost").on("input", function() {
                const shippingCost = parseFloat($(this).val()) || 0;
                const subtotal = <?= $subtotal ?>;
                const total = subtotal + shippingCost;
                
                // Оновлюємо підсумок
                $("td:contains('Вартість доставки:')").next().text(shippingCost.toFixed(2) + " ₴");
                $("td:contains('Загальна сума:')").next().text(total.toFixed(2) + " ₴");
            });
            
            // Фільтрація за категорією при зміні категорії
            $("#product_category").change(function() {
                if ($("#product_search").val().length >= 2) {
                    $("#product_search").autocomplete("search");
                }
            });
        });
    </script>
</body>
</html>