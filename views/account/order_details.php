<?php
// account/order_details.php
// Сторінка детальної інформації про замовлення клієнта

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('customer')) {
    header('Location: /login.php?redirect=account/order_details');
    exit;
}

// Отримуємо дані поточного користувача
$currentUser = $authController->getCurrentUser();

// Перевіряємо наявність ID замовлення
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($orderId <= 0) {
    header('Location: orders.php');
    exit;
}

// Ініціалізуємо контролер для роботи з замовленнями
$customerController = new CustomerController();

// Отримуємо деталі замовлення
$orderDetails = $customerController->getCustomerOrderDetails($orderId, $currentUser['id']);

// Перевіряємо успішність отримання замовлення
if (!$orderDetails['success']) {
    header('Location: orders.php');
    exit;
}

$order = $orderDetails['order'];
$items = $orderDetails['items'];

// Функція для виведення статусу замовлення
function renderOrderStatus($status) {
    $statusLabels = [
        'pending' => ['text' => 'Нове', 'class' => 'bg-yellow-100 text-yellow-800'],
        'processing' => ['text' => 'В обробці', 'class' => 'bg-blue-100 text-blue-800'],
        'ready_for_pickup' => ['text' => 'Готове до відправки', 'class' => 'bg-purple-100 text-purple-800'],
        'shipped' => ['text' => 'Відправлено', 'class' => 'bg-indigo-100 text-indigo-800'],
        'delivered' => ['text' => 'Доставлено', 'class' => 'bg-green-100 text-green-800'],
        'completed' => ['text' => 'Завершено', 'class' => 'bg-green-100 text-green-800'],
        'cancelled' => ['text' => 'Скасовано', 'class' => 'bg-red-100 text-red-800']
    ];

    $statusInfo = $statusLabels[$status] ?? ['text' => $status, 'class' => 'bg-gray-100 text-gray-800'];
    return sprintf(
        '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full %s">%s</span>', 
        $statusInfo['class'], 
        $statusInfo['text']
    );
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Замовлення #<?= $orderId ?> - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Верхнє меню -->
    <header class="bg-red-800 text-white">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center">
                <a href="../../index.php" class="font-bold text-2xl">Винна крамниця</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="../index.php" class="hover:text-red-200">Каталог</a>
                <a href="index.php" class="bg-red-700 px-3 py-1 rounded-lg hover:bg-red-600">Кабінет</a>
                <a href="../cart.php" class="relative">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <span class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs cart-count">0</span>
                </a>
                <div class="relative">
                    <button class="flex items-center text-white focus:outline-none">
                        <img src="../../assets/images/avatar.jpg" alt="Avatar" class="h-8 w-8 rounded-full mr-2">
                        <span><?= htmlspecialchars($currentUser['name']) ?></span>
                        <i class="fas fa-chevron-down ml-2"></i>
                    </button>
                </div>
                <a href="../logout.php" class="hover:text-red-200">
                    <i class="fas fa-sign-out-alt text-xl"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row md:space-x-6">
            <!-- Бічне меню -->
            <div class="w-full md:w-64 mb-6 md:mb-0">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-4 bg-red-800 text-white">
                        <h2 class="text-lg font-semibold">Меню</h2>
                    </div>
                    <nav class="divide-y divide-gray-200">
                        <a href="index.php" class="block px-4 py-3 hover:bg-red-50">
                            <i class="fas fa-tachometer-alt mr-2"></i> Дашборд
                        </a>
                        <a href="orders.php" class="block px-4 py-3 bg-red-50 text-red-800 font-semibold">
                            <i class="fas fa-shopping-cart mr-2"></i> Мої замовлення
                        </a>
                        <a href="profile.php" class="block px-4 py-3 hover:bg-red-50">
                            <i class="fas fa-user mr-2"></i> Особисті дані
                        </a>
                        <a href="messages.php" class="block px-4 py-3 hover:bg-red-50">
                            <i class="fas fa-envelope mr-2"></i> Повідомлення
                        </a>
                        <a href="reviews.php" class="block px-4 py-3 hover:bg-red-50">
                            <i class="fas fa-star mr-2"></i> Мої відгуки
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Основний контент -->
            <div class="flex-1">
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h1 class="text-2xl font-semibold">Замовлення #<?= $orderId ?></h1>
                        <div>
                            <?= renderOrderStatus($order['status']) ?>
                        </div>
                    </div>
                    <div class="text-gray-600 mb-4">
                        Створено: <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                    </div>

                    <!-- Інформація про доставку -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <h3 class="font-semibold mb-2">Адреса доставки</h3>
                            <p><?= htmlspecialchars($order['shipping_address']) ?></p>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-2">Менеджер замовлення</h3>
                            <p><?= !empty($order['manager_name']) ? htmlspecialchars($order['manager_name']) : 'Не призначено' ?></p>
                        </div>
                    </div>

                    <!-- Список товарів -->
                    <div class="mb-6">
                        <h3 class="font-semibold mb-2">Товари в замовленні</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-2 text-left">Товар</th>
                                        <th class="px-4 py-2 text-left">Ціна</th>
                                        <th class="px-4 py-2 text-left">Кількість</th>
                                        <th class="px-4 py-2 text-left">Сума</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr class="border-b">
                                        <td class="px-4 py-2">
                                            <div class="flex items-center">
                                                <img src="../../assets/images/<?= $item['image'] ?? 'default.jpg' ?>" 
                                                     alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                                     class="w-16 h-16 object-cover mr-4">
                                                <span><?= htmlspecialchars($item['product_name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2"><?= number_format($item['price'], 2) ?> ₴</td>
                                        <td class="px-4 py-2"><?= $item['quantity'] ?> шт.</td>
                                        <td class="px-4 py-2"><?= number_format($item['price'] * $item['quantity'], 2) ?> ₴</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-50">
                                        <td colspan="3" class="px-4 py-2 text-right font-semibold">Підсумок:</td>
                                        <td class="px-4 py-2"><?= number_format($order['total_amount'] - $order['shipping_cost'], 2) ?> ₴</td>
                                    </tr>
                                    <tr class="bg-gray-50">
                                        <td colspan="3" class="px-4 py-2 text-right font-semibold">Доставка:</td>
                                        <td class="px-4 py-2"><?= number_format($order['shipping_cost'], 2) ?> ₴</td>
                                    </tr>
                                    <tr class="bg-gray-50 font-bold">
                                        <td colspan="3" class="px-4 py-2 text-right">Всього:</td>
                                        <td class="px-4 py-2 text-red-800"><?= number_format($order['total_amount'], 2) ?> ₴</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Додаткова інформація -->
                    <?php if (!empty($order['notes'])): ?>
                    <div class="bg-gray-50 p-4 rounded">
                        <h3 class="font-semibold mb-2">Додаткові примітки</h3>
                        <p><?= htmlspecialchars($order['notes']) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Кнопка повернення -->
                    <div class="mt-6">
                        <a href="orders.php" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                            Повернутися до списку замовлень
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Футер -->
    <footer class="bg-gray-900 text-white py-8 mt-16">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <h3 class="text-xl font-semibold mb-4">Винна крамниця</h3>
                    <p class="text-gray-400">Ваш надійний партнер у світі вина з 2015 року.</p>
                </div>
                <!-- Інші блоки футера -->
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400 text-sm">
                <p>&copy; 2025 Винна крамниця. Всі права захищено.</p>
            </div>
        </div>
    </footer>

    <script>
        // Підрахунок кількості товарів у кошику
        function updateCartCount() {
            fetch('../api/cart_count.php')
                .then(response => response.json())
                .then(data => {
                    document.querySelector('.cart-count').textContent = data.count;
                })
                .catch(error => console.error('Помилка:', error));
        }

        // Оновлення кількості товарів при завантаженні сторінки
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>