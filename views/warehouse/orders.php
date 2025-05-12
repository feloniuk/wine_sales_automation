<?php
// views/warehouse/orders.php
// Сторінка управління замовленнями для начальника складу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/WarehouseController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('warehouse')) {
    header('Location: /login.php?redirect=warehouse/orders');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер складу
$warehouseController = new WarehouseController();

// Отримуємо параметри фільтрації
$status = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Отримуємо список замовлень
$pendingOrders = $warehouseController->getPendingOrders();

// Фільтруємо за статусом, якщо вказано
$filteredOrders = [];
if (!empty($status)) {
    foreach ($pendingOrders as $order) {
        if ($order['status'] === $status) {
            $filteredOrders[] = $order;
        }
    }
} else {
    $filteredOrders = $pendingOrders;
}

// Фільтруємо за пошуком, якщо вказано
if (!empty($search)) {
    $searchLower = strtolower($search);
    $searchResults = [];
    foreach ($filteredOrders as $order) {
        if (
            strpos(strtolower($order['id']), $searchLower) !== false ||
            strpos(strtolower($order['customer_name']), $searchLower) !== false
        ) {
            $searchResults[] = $order;
        }
    }
    $filteredOrders = $searchResults;
}

// Пагінація
$perPage = 10;
$totalItems = count($filteredOrders);
$totalPages = ceil($totalItems / $perPage);
$page = min($page, max(1, $totalPages));
$offset = ($page - 1) * $perPage;

// Відрізаємо дані для поточної сторінки
$orders = array_slice($filteredOrders, $offset, $perPage);

// Обробка дій з замовленнями
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обробка замовлення
    if (isset($_POST['process_order']) && isset($_POST['order_id'])) {
        $orderId = intval($_POST['order_id']);
        $result = $warehouseController->processOrder($orderId);
        
        if ($result['success']) {
            $message = 'Замовлення #' . $orderId . ' успішно оброблено і готове до відправки.';
            $messageType = 'success';
            
            // Оновлюємо список замовлень
            $pendingOrders = $warehouseController->getPendingOrders();
        } else {
            $message = 'Помилка при обробці замовлення: ' . $result['message'];
            $messageType = 'error';
        }
    }
    
    // Скасування замовлення
    if (isset($_POST['cancel_order']) && isset($_POST['order_id']) && isset($_POST['cancel_notes'])) {
        $orderId = intval($_POST['order_id']);
        $notes = $_POST['cancel_notes'];
        $result = $warehouseController->updateOrderStatus($orderId, 'cancelled', $notes);
        
        if ($result['success']) {
            $message = 'Замовлення #' . $orderId . ' успішно скасовано.';
            $messageType = 'success';
            
            // Оновлюємо список замовлень
            $pendingOrders = $warehouseController->getPendingOrders();
        } else {
            $message = 'Помилка при скасуванні замовлення: ' . $result['message'];
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
    <title>Замовлення - Винна крамниця</title>
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
                    <h1 class="text-2xl font-semibold text-gray-800">Управління замовленнями</h1>
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

                <!-- Фільтри та пошук -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <h2 class="text-lg font-semibold mb-4 md:mb-0">Фільтри</h2>
                        
                        <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-2">
                            <form action="orders.php" method="GET" class="flex space-x-2">
                                <input type="text" name="search" placeholder="Пошук за № або клієнтом" 
                                      value="<?= htmlspecialchars($search) ?>"
                                      class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                            
                            <div class="flex space-x-2">
                                <a href="orders.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                    Всі
                                </a>
                                <a href="orders.php?status=pending" class="<?= $status === 'pending' ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-gray-300 hover:bg-gray-400' ?> text-white px-4 py-2 rounded">
                                    Нові
                                </a>
                                <a href="orders.php?status=processing" class="<?= $status === 'processing' ? 'bg-blue-500 hover:bg-blue-600' : 'bg-gray-300 hover:bg-gray-400' ?> text-white px-4 py-2 rounded">
                                    В обробці
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Таблиця замовлень -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold">Замовлення, що очікують відправки</h2>
                    </div>
                    
                    <?php if (empty($orders)): ?>
                    <div class="p-6 text-center text-gray-500">
                        <i class="fas fa-inbox text-5xl mb-4"></i>
                        <p>Немає замовлень, що відповідають вашим критеріям пошуку</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">№ замовлення</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клієнт</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Оплата</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Товарів</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($orders as $order): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #<?= $order['id'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($order['customer_name']) ?></div>
                                        <?php if (isset($order['customer_phone'])): ?>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($order['customer_phone']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
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
                                                $statusText = 'Готове до відправки';
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
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                        switch ($order['payment_status']) {
                                            case 'paid':
                                                $paymentClass = 'bg-green-100 text-green-800';
                                                $paymentText = 'Оплачено';
                                                break;
                                            case 'pending':
                                                $paymentClass = 'bg-yellow-100 text-yellow-800';
                                                $paymentText = 'Очікується';
                                                break;
                                            default:
                                                $paymentClass = 'bg-gray-100 text-gray-800';
                                                $paymentText = $order['payment_status'];
                                                break;
                                        }
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $paymentClass ?>">
                                            <?= $paymentText ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= isset($order['items_count']) ? $order['items_count'] : '-' ?> шт.
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= number_format($order['total_amount'], 2) ?> ₴
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="order_details.php?id=<?= $order['id'] ?>" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                                            <form action="orders.php" method="POST" class="inline-block">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <button type="submit" name="process_order" class="text-green-600 hover:text-green-900" 
                                                        onclick="return confirm('Ви впевнені, що хочете обробити замовлення #<?= $order['id'] ?>?')">
                                                    <i class="fas fa-box-open"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            <?php if ($order['status'] === 'pending' && !$order['sales_manager_id']): ?>
                                            <button type="button" class="text-red-600 hover:text-red-900" 
                                                   onclick="showCancelModal(<?= $order['id'] ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Пагінація -->
                    <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 bg-gray-50 border-t">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-700">
                                Показано <?= $offset + 1 ?> - <?= min($offset + $perPage, $totalItems) ?> з <?= $totalItems ?> замовлень
                            </div>
                            <div class="flex space-x-1">
                                <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?><?= !empty($status) ? '&status=' . $status : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                                   class="px-3 py-1 bg-white text-gray-700 border rounded hover:bg-gray-100">
                                    Попередня
                                </a>
                                <?php endif; ?>
                                
                                <?php
                                // Показуємо 5 номерів сторінок
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $startPage + 4);
                                $startPage = max(1, $endPage - 4);
                                
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                <a href="?page=<?= $i ?><?= !empty($status) ? '&status=' . $status : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                                   class="px-3 py-1 <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border' ?> rounded hover:bg-gray-100">
                                    <?= $i ?>
                                </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?><?= !empty($status) ? '&status=' . $status : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
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

    <!-- Модальне вікно для скасування замовлення -->
    <div id="cancelOrderModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Скасування замовлення</h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" 
                        onclick="document.getElementById('cancelOrderModal').classList.add('hidden')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="orders.php" method="POST">
                <input type="hidden" id="cancel_order_id" name="order_id" value="">
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

    <script>
        // Функція для відображення модального вікна скасування замовлення
        function showCancelModal(orderId) {
            document.getElementById('cancel_order_id').value = orderId;
            document.getElementById('cancelOrderModal').classList.remove('hidden');
        }
    </script>
</body>
</html>