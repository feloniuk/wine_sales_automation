<?php
// warehouse/order_details.php
// Сторінка деталей замовлення для начальника складу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/WarehouseController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('warehouse')) {
    header('Location: /login.php?redirect=warehouse/order_details');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Перевіряємо, чи передано ID замовлення
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($orderId <= 0) {
    header('Location: orders.php');
    exit;
}

// Ініціалізуємо контролер складу
$warehouseController = new WarehouseController();

// Отримуємо деталі замовлення
$orderDetails = $warehouseController->getOrderDetails($orderId);
if (!$orderDetails['success']) {
    header('Location: orders.php');
    exit;
}

// Присвоюємо змінні з результатами
$order = $orderDetails['order'];
$items = $orderDetails['items'];

// Обробка форми видачі товарів
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обробка замовлення (зміна статусу на "готове до відправки")
    if (isset($_POST['process_order'])) {
        $result = $warehouseController->processOrder($orderId);
        
        if ($result['success']) {
            $message = 'Замовлення успішно оброблено і готове до відправки.';
            $messageType = 'success';
            
            // Оновлюємо дані замовлення
            $orderDetails = $warehouseController->getOrderDetails($orderId);
            $order = $orderDetails['order'];
            $items = $orderDetails['items'];
        } else {
            $message = 'Помилка при обробці замовлення: ' . $result['message'];
            $messageType = 'error';
        }
    }
    
    // Відміна замовлення
    if (isset($_POST['cancel_order'])) {
        $notes = $_POST['cancel_notes'] ?? '';
        $result = $warehouseController->updateOrderStatus($orderId, 'cancelled', $notes);
        
        if ($result['success']) {
            $message = 'Замовлення успішно скасовано.';
            $messageType = 'success';
            
            // Оновлюємо дані замовлення
            $orderDetails = $warehouseController->getOrderDetails($orderId);
            $order = $orderDetails['order'];
            $items = $orderDetails['items'];
        } else {
            $message = 'Помилка при скасуванні замовлення: ' . $result['message'];
            $messageType = 'error';
        }
    }
}

// Визначення класів для статусу замовлення
$statusClass = '';
$statusIcon = '';

switch ($order['status']) {
    case 'pending':
        $statusText = 'Нове';
        $statusClass = 'bg-yellow-100 text-yellow-800';
        $statusIcon = 'fas fa-clock';
        break;
    case 'processing':
        $statusText = 'В обробці';
        $statusClass = 'bg-blue-100 text-blue-800';
        $statusIcon = 'fas fa-cog fa-spin';
        break;
    case 'ready_for_pickup':
        $statusText = 'Готове до відправки';
        $statusClass = 'bg-indigo-100 text-indigo-800';
        $statusIcon = 'fas fa-box';
        break;
    case 'shipped':
        $statusText = 'Відправлено';
        $statusClass = 'bg-purple-100 text-purple-800';
        $statusIcon = 'fas fa-shipping-fast';
        break;
    case 'delivered':
        $statusText = 'Доставлено';
        $statusClass = 'bg-green-100 text-green-800';
        $statusIcon = 'fas fa-check';
        break;
    case 'completed':
        $statusText = 'Завершено';
        $statusClass = 'bg-green-100 text-green-800';
        $statusIcon = 'fas fa-check-circle';
        break;
    case 'cancelled':
        $statusText = 'Скасовано';
        $statusClass = 'bg-red-100 text-red-800';
        $statusIcon = 'fas fa-times-circle';
        break;
    default:
        $statusText = $order['status'];
        $statusClass = 'bg-gray-100 text-gray-800';
        $statusIcon = 'fas fa-question-circle';
        break;
}

// Перевірка можливих дій
$canProcess = in_array($order['status'], ['pending', 'processing']);
$canCancel = in_array($order['status'], ['pending', 'processing']) && !$order['sales_manager_id'];
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
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Бічне меню -->
        <div class="w-64 bg-blue-900 text-white">
            <div class="p-4 font-bold text-xl">Винна крамниця</div>
            <nav class="mt-8">
                <a href="index.php" class="flex items-center px-4 py-3 hover:bg-blue-800">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    <span>Дашборд</span>
                </a>
                <a href="inventory.php" class="flex items-center px-4 py-3 hover:bg-blue-800">
                    <i class="fas fa-boxes mr-3"></i>
                    <span>Інвентаризація</span>
                </a>
                <a href="products.php" class="flex items-center px-4 py-3 hover:bg-blue-800">
                    <i class="fas fa-wine-bottle mr-3"></i>
                    <span>Товари</span>
                </a>
                <a href="orders.php" class="flex items-center px-4 py-3 bg-blue-800">
                    <i class="fas fa-shipping-fast mr-3"></i>
                    <span>Замовлення</span>
                </a>
                <a href="transactions.php" class="flex items-center px-4 py-3 hover:bg-blue-800">
                    <i class="fas fa-exchange-alt mr-3"></i>
                    <span>Транзакції</span>
                </a>
                <a href="reports.php" class="flex items-center px-4 py-3 hover:bg-blue-800">
                    <i class="fas fa-chart-bar mr-3"></i>
                    <span>Звіти</span>
                </a>
                <a href="../logout.php" class="flex items-center px-4 py-3 hover:bg-blue-800 mt-8">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Замовлення #<?= $orderId ?></h1>
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

                <!-- Статус і управління замовленням -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                        <div>
                            <h2 class="text-xl font-semibold">Замовлення #<?= $orderId ?></h2>
                            <p class="text-gray-600">Створено: <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <!-- Статус замовлення -->
                            <span class="<?= $statusClass ?> px-3 py-1 rounded-full text-sm font-medium inline-flex items-center">
                                <i class="<?= $statusIcon ?> mr-1"></i> <?= $statusText ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Дії з замовленням -->
                    <?php if ($order['status'] !== 'cancelled'): ?>
                    <div class="border-t pt-4">
                        <h3 class="font-medium text-gray-700 mb-3">Дії з замовленням</h3>
                        <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-2">
                            <?php if ($canProcess): ?>
                            <form action="order_details.php?id=<?= $orderId ?>" method="POST" class="inline-block">
                                <button type="submit" name="process_order" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded" onclick="return confirm('Ви впевнені, що хочете обробити замовлення? Товари будуть списані зі складу.')">
                                    <i class="fas fa-box-open mr-2"></i> Підготувати замовлення
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <?php if ($canCancel): ?>
                            <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded" onclick="document.getElementById('cancelOrderModal').classList.remove('hidden')">
                                <i class="fas fa-times mr-2"></i> Скасувати замовлення
                            </button>
                            <?php endif; ?>
                            
                            <a href="orders.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                <i class="fas fa-arrow-left mr-2"></i> Повернутися до списку
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Інформація про клієнта та доставку -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Інформація про клієнта -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Інформація про клієнта</h2>
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Ім'я:</div>
                                <div class="flex-1 font-medium"><?= htmlspecialchars($order['customer_name']) ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Телефон:</div>
                                <div class="flex-1"><?= htmlspecialchars($order['customer_phone'] ?? 'Не вказано') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Інформація про доставку -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Інформація про доставку</h2>
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Адреса:</div>
                                <div class="flex-1"><?= htmlspecialchars($order['shipping_address']) ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Менеджер:</div>
                                <div class="flex-1">
                                    <?php if ($order['sales_manager_id']): ?>
                                        <?= htmlspecialchars($order['sales_manager_name']) ?>
                                    <?php else: ?>
                                        <span class="text-yellow-600">Не призначено</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($order['notes'])): ?>
                        <div class="mt-4">
                            <h3 class="font-medium text-gray-700 mb-2">Примітки до замовлення:</h3>
                            <div class="bg-gray-50 p-3 rounded text-gray-700 text-sm whitespace-pre-line">
                                <?= htmlspecialchars($order['notes']) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Товари в замовленні -->
                <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold">Товари в замовленні</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Товар</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ціна</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Кількість</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Наявність</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded-full object-cover" src="../../assets/images/<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['product_name']) ?></div>
                                                <div class="text-sm text-gray-500">ID: <?= $item['product_id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= number_format($item['price'], 2) ?> ₴
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $item['quantity'] ?> шт.
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                        <?= number_format($item['price'] * $item['quantity'], 2) ?> ₴
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($item['stock_quantity'] >= $item['quantity']): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Є в наявності (<?= $item['stock_quantity'] ?> шт.)
                                        </span>
                                        <?php elseif ($item['stock_quantity'] > 0): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Не вистачає (<?= $item['stock_quantity'] ?> з <?= $item['quantity'] ?> шт.)
                                        </span>
                                        <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Немає в наявності
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-right font-medium">Підсумок:</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                        <?= number_format($order['total_amount'] - $order['shipping_cost'], 2) ?> ₴
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-right font-medium">Доставка:</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                        <?= number_format($order['shipping_cost'], 2) ?> ₴
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-right text-lg font-bold">Загальна сума:</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-lg font-bold text-blue-600">
                                        <?= number_format($order['total_amount'], 2) ?> ₴
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Модальне вікно для скасування замовлення -->
    <div id="cancelOrderModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Скасування замовлення</h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" onclick="document.getElementById('cancelOrderModal').classList.add('hidden')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="order_details.php?id=<?= $orderId ?>" method="POST">
                <div class="mb-4">
                    <label for="cancel_notes" class="block text-sm font-medium text-gray-700 mb-2">Причина скасування</label>
                    <textarea id="cancel_notes" name="cancel_notes" rows="3" required
                              class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded" 
                            onclick="document.getElementById('cancelOrderModal').classList.add('hidden')">
                        Відміна
                    </button>
                    <button type="submit" name="cancel_order" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                        Скасувати замовлення
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>