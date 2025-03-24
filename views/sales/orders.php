<?php
// sales/orders.php
// Сторінка списку замовлень для менеджера з продажу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/SalesController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('sales')) {
    header('Location: /login.php?redirect=sales/orders');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер продажів
$salesController = new SalesController();

// Отримуємо параметри фільтрації та пагінації
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Отримуємо список замовлень з фільтрацією
if (!empty($status)) {
    $ordersData = $salesController->getOrdersByStatus($status, $page);
} elseif (!empty($search)) {
    $ordersData = $salesController->searchOrders($search, $page);
} else {
    // За замовчуванням показуємо замовлення менеджера
    $ordersData = $salesController->getManagerOrders($currentUser['id'], $page);
}

$orders = $ordersData['data'];
$pagination = $ordersData['pagination'];
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Замовлення - Винна крамниця</title>
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
                <a href="orders.php" class="flex items-center px-4 py-3 bg-green-700">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Замовлення</h1>
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
                <!-- Фільтри та пошук -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-3 md:space-y-0">
                        <!-- Фільтр за статусом -->
                        <div class="flex flex-wrap gap-2">
                            <a href="orders.php" class="<?= empty($status) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?> px-3 py-1 rounded-full text-sm">
                                Усі
                            </a>
                            <a href="orders.php?status=pending" class="<?= $status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' ?> px-3 py-1 rounded-full text-sm">
                                Нові
                            </a>
                            <a href="orders.php?status=processing" class="<?= $status === 'processing' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' ?> px-3 py-1 rounded-full text-sm">
                                В обробці
                            </a>
                            <a href="orders.php?status=ready_for_pickup" class="<?= $status === 'ready_for_pickup' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800' ?> px-3 py-1 rounded-full text-sm">
                                Готові до відправки
                            </a>
                            <a href="orders.php?status=shipped" class="<?= $status === 'shipped' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' ?> px-3 py-1 rounded-full text-sm">
                                Відправлені
                            </a>
                            <a href="orders.php?status=completed" class="<?= $status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?> px-3 py-1 rounded-full text-sm">
                                Завершені
                            </a>
                            <a href="orders.php?status=cancelled" class="<?= $status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' ?> px-3 py-1 rounded-full text-sm">
                                Скасовані
                            </a>
                        </div>
                        
                        <!-- Пошук -->
                        <div>
                            <form action="orders.php" method="GET" class="flex">
                                <input type="text" name="search" placeholder="Пошук за номером або клієнтом" 
                                       value="<?= htmlspecialchars($search) ?>"
                                       class="border rounded-l px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" 
                                       style="min-width: 250px;">
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-r">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Таблиця замовлень -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b flex justify-between items-center">
                        <h2 class="text-lg font-semibold">Список замовлень</h2>
                        <a href="new_order.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                            <i class="fas fa-plus mr-2"></i> Нове замовлення
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Оплата</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        <?php if (!empty($search)): ?>
                                            За запитом "<?= htmlspecialchars($search) ?>" замовлень не знайдено
                                        <?php elseif (!empty($status)): ?>
                                            Замовлень зі статусом "<?= $status ?>" не знайдено
                                        <?php else: ?>
                                            Замовлень не знайдено
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #<?= $order['id'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($order['customer_name']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($order['customer_phone'] ?? '') ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= number_format($order['total_amount'], 2) ?> ₴
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
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
                                        }
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $paymentStatusClass = '';
                                        $paymentStatusText = '';
                                        switch ($order['payment_status']) {
                                            case 'pending':
                                                $paymentStatusClass = 'bg-yellow-100 text-yellow-800';
                                                $paymentStatusText = 'Очікує';
                                                break;
                                            case 'paid':
                                                $paymentStatusClass = 'bg-green-100 text-green-800';
                                                $paymentStatusText = 'Оплачено';
                                                break;
                                            case 'refunded':
                                                $paymentStatusClass = 'bg-red-100 text-red-800';
                                                $paymentStatusText = 'Повернуто';
                                                break;
                                            default:
                                                $paymentStatusClass = 'bg-gray-100 text-gray-800';
                                                $paymentStatusText = $order['payment_status'];
                                        }
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $paymentStatusClass ?>">
                                            <?= $paymentStatusText ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="order_details.php?id=<?= $order['id'] ?>" class="text-green-600 hover:text-green-900 mr-3">
                                            <i class="fas fa-eye"></i> Переглянути
                                        </a>
                                        <?php if ($order['sales_manager_id'] == $currentUser['id'] || !$order['sales_manager_id']): ?>
                                        <a href="process_order.php?id=<?= $order['id'] ?>&action=take" class="text-blue-600 hover:text-blue-900">
                                            <?php if ($order['sales_manager_id'] == $currentUser['id']): ?>
                                                <i class="fas fa-cog"></i> Обробити
                                            <?php else: ?>
                                                <i class="fas fa-user-check"></i> Взяти в роботу
                                            <?php endif; ?>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Пагінація -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="px-6 py-4 bg-gray-50 border-t">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-700">
                                Показано <?= ($pagination['current_page'] - 1) * $pagination['per_page'] + 1 ?> - 
                                <?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) ?> 
                                з <?= $pagination['total'] ?> замовлень
                            </div>
                            <div class="flex space-x-1">
                                <?php if ($pagination['current_page'] > 1): ?>
                                <a href="?page=<?= $pagination['current_page'] - 1 ?><?= $status ? '&status=' . $status : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                   class="px-3 py-1 bg-white text-gray-700 border rounded hover:bg-gray-100">
                                    Попередня
                                </a>
                                <?php endif; ?>
                                
                                <?php
                                // Показуємо 5 номерів сторінок
                                $startPage = max(1, $pagination['current_page'] - 2);
                                $endPage = min($pagination['total_pages'], $startPage + 4);
                                $startPage = max(1, $endPage - 4);
                                
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                <a href="?page=<?= $i ?><?= $status ? '&status=' . $status : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                   class="px-3 py-1 <?= $i === $pagination['current_page'] ? 'bg-green-600 text-white' : 'bg-white text-gray-700 border' ?> rounded hover:bg-gray-100">
                                    <?= $i ?>
                                </a>
                                <?php endfor; ?>
                                
                                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                <a href="?page=<?= $pagination['current_page'] + 1 ?><?= $status ? '&status=' . $status : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                   class="px-3 py-1 bg-white text-gray-700 border rounded hover:bg-gray-100">
                                    Наступна
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>