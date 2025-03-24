<?php
// admin/order_details.php
// Сторінка детальної інформації про замовлення

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/SalesController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('admin')) {
    header('Location: /login.php?redirect=admin/order_details');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Перевіряємо наявність ID замовлення
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$orderId = intval($_GET['id']);

// Ініціалізуємо контролер продажів
$salesController = new SalesController();

// Отримуємо деталі замовлення
$orderDetails = $salesController->getOrderDetails($orderId);

// Якщо замовлення не знайдене
if (!$orderDetails['success']) {
    header('Location: orders.php?error=not_found');
    exit;
}

// Функція для визначення класу статусу
function getStatusClass($status) {
    $classes = [
        'pending' => ['bg-yellow-100', 'text-yellow-800', 'Нове'],
        'processing' => ['bg-blue-100', 'text-blue-800', 'В обробці'],
        'ready_for_pickup' => ['bg-purple-100', 'text-purple-800', 'Готове'],
        'shipped' => ['bg-indigo-100', 'text-indigo-800', 'Відправлено'],
        'delivered' => ['bg-green-100', 'text-green-800', 'Доставлено'],
        'completed' => ['bg-green-100', 'text-green-800', 'Завершено'],
        'cancelled' => ['bg-red-100', 'text-red-800', 'Скасовано']
    ];
    
    return $classes[$status] ?? ['bg-gray-100', 'text-gray-800', $status];
}

// Функція для визначення класу статусу оплати
function getPaymentStatusClass($status) {
    $classes = [
        'pending' => ['bg-yellow-100', 'text-yellow-800', 'Очікує'],
        'paid' => ['bg-green-100', 'text-green-800', 'Оплачено'],
        'refunded' => ['bg-red-100', 'text-red-800', 'Повернуто']
    ];
    
    return $classes[$status] ?? ['bg-gray-100', 'text-gray-800', $status];
}
?>

<!DOCTYPE html>
<html lang="uk">
<head> <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Деталі замовлення #<?= $orderId ?> - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Бічне меню -->
        <div class="w-64 bg-red-900 text-white">
            <div class="p-4 font-bold text-xl">Винна крамниця</div>
            <nav class="mt-8">
                <a href="index.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    <span>Дашборд</span>
                </a>
                <a href="users.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-users mr-3"></i>
                    <span>Користувачі</span>
                </a>
                <a href="products.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-wine-bottle mr-3"></i>
                    <span>Товари</span>
                </a>
                <a href="categories.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-tags mr-3"></i>
                    <span>Категорії</span>
                </a>
                <a href="orders.php" class="flex items-center px-4 py-3 bg-red-800">
                    <i class="fas fa-shopping-cart mr-3"></i>
                    <span>Замовлення</span>
                </a>
                <a href="messages.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-envelope mr-3"></i>
                    <span>Повідомлення</span>
                </a>
                <a href="cameras.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-video mr-3"></i>
                    <span>Камери спостереження</span>
                </a>
                <a href="promotions.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-percent mr-3"></i>
                    <span>Акції</span>
                </a>
                <a href="statistics.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-chart-line mr-3"></i>
                    <span>Статистика</span>
                </a>
                <a href="settings.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-cog mr-3"></i>
                    <span>Налаштування</span>
                </a>
                <a href="../logout.php" class="flex items-center px-4 py-3 hover:bg-red-800 mt-8">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Деталі замовлення #<?= $orderId ?></h1>
                    <div class="flex items-center">
                        <div class="relative">
                            <button class="flex items-center text-gray-700 focus:outline-none">
                                <img src="../assets/images/avatar.jpg" alt="Avatar" class="h-8 w-8 rounded-full mr-2">
                                <span><?= $currentUser['name'] ?></span>
                                <i class="fas fa-chevron-down ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Контент сторінки -->
            <main class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Інформація про замовлення -->
                    <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold">Інформація про замовлення</h2>
                            <?php 
                            $statusInfo = getStatusClass($orderDetails['order']['status']);
                            $paymentInfo = getPaymentStatusClass($orderDetails['order']['payment_status']);
                            ?>
                            <div class="flex space-x-2">
                                <span class="px-2 py-1 rounded text-xs <?= $statusInfo[0] ?> <?= $statusInfo[1] ?>">
                                    <?= $statusInfo[2] ?>
                                </span>
                                <span class="px-2 py-1 rounded text-xs <?= $paymentInfo[0] ?> <?= $paymentInfo[1] ?>">
                                    <?= $paymentInfo[2] ?>
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Дата замовлення</p>
                                <p class="font-medium"><?= date('d.m.Y H:i', strtotime($orderDetails['order']['created_at'])) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Менеджер</p>
                                <p class="font-medium">
                                    <?= $orderDetails['order']['manager_name'] ?? 'Не призначено' ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Спосіб оплати</p>
                                <p class="font-medium">
                                    <?php 
                                    $paymentMethods = [
                                        'card' => 'Картка',
                                        'cash' => 'Готівка',
                                        'transfer' => 'Банківський переказ'
                                    ];
                                    echo $paymentMethods[$orderDetails['order']['payment_method']] ?? $orderDetails['order']['payment_method'];
                                    ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Загальна сума</p>
                                <p class="font-medium"><?= number_format($orderDetails['order']['total_amount'], 2) ?> ₴</p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h3 class="text-md font-semibold mb-2">Товари</h3>
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 text-left">
                                        <th class="p-2">Товар</th>
                                        <th class="p-2">Категорія</th>
                                        <th class="p-2">Ціна</th>
                                        <th class="p-2">Кількість</th>
                                        <th class="p-2">Сума</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderDetails['items'] as $item): ?>
                                    <tr class="border-b">
                                        <td class="p-2 flex items-center">
                                            <img src="../assets/images/<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="h-10 w-10 mr-2 rounded">
                                            <?= htmlspecialchars($item['product_name']) ?>
                                        </td>
                                        <td class="p-2"><?= htmlspecialchars($item['category_name']) ?></td>
                                        <td class="p-2"><?= number_format($item['price'], 2) ?> ₴</td>
                                        <td class="p-2"><?= $item['quantity'] ?></td>
                                        <td class="p-2"><?= number_format($item['price'] * $item['quantity'], 2) ?> ₴</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Інформація про клієнта -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Інформація про клієнта</h2>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-gray-600">ПІБ</p>
                                <p class="font-medium"><?= htmlspecialchars($orderDetails['order']['customer_name']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Email</p>
                                <p class="font-medium"><?= htmlspecialchars($orderDetails['order']['customer_email']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Телефон</p>
                                <p class="font-medium"><?= htmlspecialchars($orderDetails['order']['customer_phone']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Адреса доставки</p>
                                <p class="font-medium"><?= htmlspecialchars($orderDetails['order']['shipping_address']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>