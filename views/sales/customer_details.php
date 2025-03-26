<?php
// sales/customer_details.php
// Сторінка деталей клієнта для менеджера з продажу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/SalesController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('sales')) {
    header('Location: /login.php?redirect=sales/customer_details');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Перевіряємо, чи передано ID клієнта
$customerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($customerId <= 0) {
    header('Location: customers.php');
    exit;
}

// Ініціалізуємо контролер продажів
$salesController = new SalesController();

// Отримуємо деталі клієнта
$customerDetails = $salesController->getCustomerDetails($customerId);
if (!$customerDetails['success']) {
    header('Location: customers.php');
    exit;
}

// Присвоюємо змінні з результатами
$customer = $customerDetails['customer'];
$orders = $customerDetails['orders'];
$favoriteProducts = $customerDetails['favorite_products'];
$messages = $customerDetails['messages'];

// Обробка форми відправки повідомлення
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = $_POST['subject'];
    $messageText = $_POST['message'];
    
    if (empty($subject) || empty($messageText)) {
        $message = 'Будь ласка, заповніть тему та текст повідомлення.';
        $messageType = 'warning';
    } else {
        $result = $salesController->sendMessage($currentUser['id'], $customerId, $subject, $messageText);
        
        if ($result['success']) {
            $message = 'Повідомлення успішно відправлено.';
            $messageType = 'success';
            
            // Оновлюємо список повідомлень
            $customerDetails = $salesController->getCustomerDetails($customerId);
            $messages = $customerDetails['messages'];
        } else {
            $message = 'Помилка при відправленні повідомлення: ' . $result['message'];
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($customer['name']) ?> - Клієнт - Винна крамниця</title>
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
                <a href="customers.php" class="flex items-center px-4 py-3 bg-green-700">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Клієнт: <?= htmlspecialchars($customer['name']) ?></h1>
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

                <!-- Швидкі дії -->
                <div class="mb-6">
                    <div class="flex flex-wrap gap-2">
                        <a href="new_order.php?customer_id=<?= $customerId ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded inline-flex items-center">
                            <i class="fas fa-plus-circle mr-2"></i> Нове замовлення
                        </a>
                        <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded inline-flex items-center" 
                                onclick="document.getElementById('messageModal').classList.remove('hidden')">
                            <i class="fas fa-envelope mr-2"></i> Надіслати повідомлення
                        </button>
                        <a href="customers.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded inline-flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Повернутися до списку
                        </a>
                    </div>
                </div>

                <!-- Інформація про клієнта та статистика -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Основна інформація -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Контактна інформація</h2>
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Ім'я:</div>
                                <div class="flex-1 font-medium"><?= htmlspecialchars($customer['name']) ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Email:</div>
                                <div class="flex-1"><?= htmlspecialchars($customer['email']) ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Телефон:</div>
                                <div class="flex-1"><?= htmlspecialchars($customer['phone'] ?? 'Не вказано') ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Логін:</div>
                                <div class="flex-1"><?= htmlspecialchars($customer['username']) ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Статус:</div>
                                <div class="flex-1">
                                    <?php if ($customer['status'] === 'active'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Активний
                                    </span>
                                    <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Неактивний
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Адреса -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Адреса доставки</h2>
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Адреса:</div>
                                <div class="flex-1"><?= htmlspecialchars($customer['address'] ?? 'Не вказано') ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Місто:</div>
                                <div class="flex-1"><?= htmlspecialchars($customer['city'] ?? 'Не вказано') ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Регіон:</div>
                                <div class="flex-1"><?= htmlspecialchars($customer['region'] ?? 'Не вказано') ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Індекс:</div>
                                <div class="flex-1"><?= htmlspecialchars($customer['postal_code'] ?? 'Не вказано') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Статистика -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Статистика</h2>
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <div class="w-36 flex-shrink-0 text-gray-500">Замовлень:</div>
                                <div class="flex-1 font-medium"><?= $customer['order_count'] ?? 0 ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-36 flex-shrink-0 text-gray-500">Витрачено коштів:</div>
                                <div class="flex-1 font-medium"><?= isset($customer['total_spent']) ? number_format($customer['total_spent'], 2) . ' ₴' : '0.00 ₴' ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-36 flex-shrink-0 text-gray-500">Останнє замовлення:</div>
                                <div class="flex-1"><?= isset($customer['last_order_date']) ? date('d.m.Y', strtotime($customer['last_order_date'])) : '-' ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-36 flex-shrink-0 text-gray-500">Реєстрація:</div>
                                <div class="flex-1"><?= date('d.m.Y', strtotime($customer['created_at'])) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Останні замовлення та улюблені товари -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Останні замовлення -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold">Замовлення клієнта</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="5" class="px-4 py-2 text-center text-gray-500">
                                            Клієнт ще не робив замовлень
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">#<?= $order['id'] ?></td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm font-medium">
                                            <?= number_format($order['total_amount'], 2) ?> ₴
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            switch ($order['status']) {
                                                case 'pending':
                                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                                    $statusText = 'Нове';
                                                    break;
                                                case 'processing':
                                                    $statusClass = 'bg-blue-100 text-blue-800';
                                                    $statusText = 'В обробці';
                                                    break;
                                                case 'ready_for_pickup':
                                                    $statusClass = 'bg-indigo-100 text-indigo-800';
                                                    $statusText = 'Готове';
                                                    break;
                                                case 'shipped':
                                                    $statusClass = 'bg-purple-100 text-purple-800';
                                                    $statusText = 'Відправлено';
                                                    break;
                                                case 'delivered':
                                                    $statusClass = 'bg-green-100 text-green-800';
                                                    $statusText = 'Доставлено';
                                                    break;
                                                case 'completed':
                                                    $statusClass = 'bg-green-100 text-green-800';
                                                    $statusText = 'Завершено';
                                                    break;
                                                case 'cancelled':
                                                    $statusClass = 'bg-red-100 text-red-800';
                                                    $statusText = 'Скасовано';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-gray-100 text-gray-800';
                                                    $statusText = $order['status'];
                                                    break;
                                            }
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                                <?= $statusText ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                                            <a href="order_details.php?id=<?= $order['id'] ?>" class="text-green-600 hover:text-green-900">
                                                Деталі
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Улюблені товари -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold">Улюблені товари</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Товар</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Категорія</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Кількість</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Замовлень</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($favoriteProducts)): ?>
                                    <tr>
                                        <td colspan="4" class="px-4 py-2 text-center text-gray-500">
                                            Немає даних по улюбленим товарам
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($favoriteProducts as $product): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full object-cover" src="../../assets/images/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($product['category_name']) ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                            <?= $product['total_quantity'] ?> шт.
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                            <?= $product['order_count'] ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Історія спілкування -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold">Історія спілкування</h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php if (empty($messages)): ?>
                        <div class="p-6 text-center text-gray-500">
                            Немає повідомлень для відображення
                        </div>
                        <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                        <div class="p-6">
                            <div class="flex items-start <?= $msg['direction'] === 'outgoing' ? 'justify-end' : '' ?>">
                                <div class="<?= $msg['direction'] === 'outgoing' ? 'bg-green-100' : 'bg-gray-100' ?> rounded-lg p-4 max-w-lg">
                                    <div class="flex items-center mb-2">
                                        <?php if ($msg['direction'] === 'incoming'): ?>
                                        <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center mr-2">
                                            <i class="fas fa-user text-gray-500"></i>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <p class="font-medium text-sm">
                                                <?= $msg['direction'] === 'outgoing' ? 'Ви' : htmlspecialchars($customer['name']) ?>
                                            </p>
                                            <p class="text-xs text-gray-500"><?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?></p>
                                        </div>
                                        <?php if ($msg['direction'] === 'outgoing'): ?>
                                        <div class="h-8 w-8 rounded-full bg-green-200 flex items-center justify-center ml-2">
                                            <i class="fas fa-user text-green-500"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-sm font-medium mb-1"><?= htmlspecialchars($msg['subject']) ?></div>
                                    <div class="text-sm"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Модальне вікно для відправки повідомлення -->
    <div id="messageModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Надіслати повідомлення</h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" onclick="document.getElementById('messageModal').classList.add('hidden')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="customer_details.php?id=<?= $customerId ?>" method="POST">
                <div class="mb-4">
                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Тема</label>
                    <input type="text" id="subject" name="subject" required
                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div class="mb-4">
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Повідомлення</label>
                    <textarea id="message" name="message" rows="4" required
                              class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded" 
                            onclick="document.getElementById('messageModal').classList.add('hidden')">
                        Скасувати
                    </button>
                    <button type="submit" name="send_message" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                        Надіслати
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>