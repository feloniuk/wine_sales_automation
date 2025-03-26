<?php
// sales/product_details.php
// Сторінка деталей товару для менеджера з продажу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('sales')) {
    header('Location: /login.php?redirect=sales/product_details');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Перевіряємо ID товару
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($productId <= 0) {
    header('Location: products.php');
    exit;
}

// Ініціалізуємо контролер клієнта для роботи з товарами
$customerController = new CustomerController();

// Отримуємо інформацію про товар
$productDetails = $customerController->getProductDetails($productId);
if (!$productDetails['success']) {
    header('Location: products.php');
    exit;
}

$product = $productDetails['product'];
$reviews = $productDetails['reviews'];
$recommended = $productDetails['recommended'];
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                <a href="products.php" class="flex items-center px-4 py-3 bg-green-700">
                    <i class="fas fa-wine-bottle mr-3"></i>
                    <span>Каталог</span>
                </a>
                <a href="new_order.php" class="flex items-center px-4 py-3 hover:bg-green-700">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Деталі товару</h1>
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
                <!-- Шлях -->
                <div class="mb-6">
                    <div class="flex items-center text-sm text-gray-500">
                        <a href="products.php" class="hover:text-green-800">Каталог</a>
                        <span class="mx-2">/</span>
                        <a href="products.php?category=<?= $product['category_id'] ?>" class="hover:text-green-800"><?= htmlspecialchars($product['category_name']) ?></a>
                        <span class="mx-2">/</span>
                        <span class="text-gray-700"><?= htmlspecialchars($product['name']) ?></span>
                    </div>
                </div>

                <!-- Швидкі дії -->
                <div class="mb-6">
                    <div class="flex flex-wrap gap-2">
                        <a href="new_order.php?add_product=<?= $productId ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded inline-flex items-center">
                            <i class="fas fa-plus-circle mr-2"></i> Додати до замовлення
                        </a>
                        <a href="products.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded inline-flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Повернутися до каталогу
                        </a>
                    </div>
                </div>

                <!-- Основна інформація про товар -->
                <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                    <div class="flex flex-col md:flex-row">
                        <!-- Зображення товару -->
                        <div class="md:w-1/3">
                            <img src="../../assets/images/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-auto object-cover">
                        </div>
                        
                        <!-- Дані товару -->
                        <div class="md:w-2/3 p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($product['name']) ?></h2>
                                    <p class="text-gray-600 mb-4"><?= htmlspecialchars($product['description']) ?></p>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-green-600 mb-2"><?= number_format($product['price'], 2) ?> ₴</div>
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                                        В наявності: <?= $product['stock_quantity'] ?> шт.
                                    </span>
                                    <?php else: ?>
                                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">
                                        Немає в наявності
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mt-6 border-t pt-6">
                                <h3 class="text-lg font-semibold mb-3">Характеристики:</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Категорія:</span>
                                        <span class="font-medium"><?= htmlspecialchars($product['category_name']) ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Рік:</span>
                                        <span class="font-medium"><?= $product['year'] ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Алкоголь:</span>
                                        <span class="font-medium"><?= $product['alcohol'] ?>%</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Об'єм:</span>
                                        <span class="font-medium"><?= $product['volume'] ?> мл</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">ID товару:</span>
                                        <span class="font-medium"><?= $product['id'] ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Мінімальний запас:</span>
                                        <span class="font-medium"><?= $product['min_stock'] ?> шт.</span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($product['details'])): ?>
                            <div class="mt-6 border-t pt-6">
                                <h3 class="text-lg font-semibold mb-3">Детальний опис:</h3>
                                <div class="text-gray-700"><?= nl2br(htmlspecialchars($product['details'])) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Відгуки клієнтів -->
                <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold">Відгуки клієнтів</h2>
                    </div>
                    <?php if (empty($reviews)): ?>
                    <div class="p-6 text-center text-gray-500">
                        Поки немає відгуків для цього товару
                    </div>
                    <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($reviews as $review): ?>
                        <div class="p-6">
                            <div class="flex justify-between mb-2">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-gray-500"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium"><?= htmlspecialchars($review['customer_name']) ?></div>
                                        <div class="text-sm text-gray-500"><?= date('d.m.Y', strtotime($review['created_at'])) ?></div>
                                    </div>
                                </div>
                                <div class="flex">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $review['rating']): ?>
                                    <i class="fas fa-star text-yellow-400"></i>
                                    <?php else: ?>
                                    <i class="far fa-star text-yellow-400"></i>
                                    <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="text-gray-700"><?= nl2br(htmlspecialchars($review['review'])) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Рекомендовані товари -->
                <?php if (!empty($recommended)): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold">Схожі товари</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <?php foreach ($recommended as $rec): ?>
                            <a href="product_details.php?id=<?= $rec['id'] ?>" class="block bg-white border rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                                <div class="h-40 overflow-hidden">
                                    <img src="../../assets/images/<?= $rec['image'] ?>" alt="<?= htmlspecialchars($rec['name']) ?>" class="w-full h-full object-cover">
                                </div>
                                <div class="p-3">
                                    <h3 class="font-medium text-gray-800 mb-1"><?= htmlspecialchars($rec['name']) ?></h3>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-500"><?= htmlspecialchars($rec['category_name']) ?></span>
                                        <span class="font-bold text-green-600"><?= number_format($rec['price'], 2) ?> ₴</span>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>