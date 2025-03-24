<?php
// admin/orders.php
// Сторінка управління замовленнями

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/AdminController.php';
require_once ROOT_PATH . '/controllers/SalesController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('admin')) {
    header('Location: /login.php?redirect=admin/orders');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролери
$salesController = new SalesController();

// Параметри фільтрації та пагінації
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Отримуємо список замовлень з фільтрацією
if (!empty($status)) {
    $ordersData = $salesController->getOrdersByStatus($status, $page);
} elseif (!empty($search)) {
    $ordersData = $salesController->searchOrders($search, $page);
} else {
    $ordersData = $salesController->getAllOrders($page);
}

// Обробка оновлення статусу замовлення
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if ($orderId > 0 && !empty($status)) {
        $result = $salesController->updateOrderStatus($orderId, $status, $notes);
        
        if ($result['success']) {
            $message = 'Статус замовлення успішно оновлено.';
            $messageType = 'success';
            
            // Перезавантажуємо сторінку для оновлення даних
            header('Location: orders.php?status=' . $status . '&success=1');
            exit;
        } else {
            $message = 'Помилка при оновленні статусу замовлення: ' . $result['message'];
            $messageType = 'error';
        }
    } else {
        $message = 'Будь ласка, вкажіть ID замовлення та статус.';
        $messageType = 'error';
    }
}

// Обробка оновлення статусу оплати
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $orderId = intval($_POST['order_id'] ?? 0);
    $paymentStatus = $_POST['payment_status'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if ($orderId > 0 && !empty($paymentStatus)) {
        $result = $salesController->updatePaymentStatus($orderId, $paymentStatus, $notes);
        
        if ($result['success']) {
            $message = 'Статус оплати успішно оновлено.';
            $messageType = 'success';
            
            // Перезавантажуємо сторінку для оновлення даних
            header('Location: orders.php?success=1');
            exit;
        } else {
            $message = 'Помилка при оновленні статусу оплати: ' . $result['message'];
            $messageType = 'error';
        }
    } else {
        $message = 'Будь ласка, вкажіть ID замовлення та статус оплати.';
        $messageType = 'error';
    }
}

// Обробка призначення менеджера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_manager'])) {
    $orderId = intval($_POST['order_id'] ?? 0);
    $managerId = intval($_POST['manager_id'] ?? 0);
    
    if ($orderId > 0 && $managerId > 0) {
        $result = $salesController->assignManager($orderId, $managerId);
        
        if ($result['success']) {
            $message = 'Менеджер успішно призначений для замовлення.';
            $messageType = 'success';
            
            // Перезавантажуємо сторінку для оновлення даних
            header('Location: orders.php?success=1');
            exit;
        } else {
            $message = 'Помилка при призначенні менеджера: ' . $result['message'];
            $messageType = 'error';
        }
    } else {
        $message = 'Будь ласка, вкажіть ID замовлення та ID менеджера.';
        $messageType = 'error';
    }
}

// Отримуємо повідомлення про успіх операції
if (isset($_GET['success'])) {
    $message = 'Операція успішно виконана.';
    $messageType = 'success';
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управління замовленнями - Винна крамниця</title>
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
                <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
                    <div class="flex flex-wrap gap-2">
                        <a href="orders.php" class="px-4 py-2 rounded bg-<?= empty($status) ? 'red-800 text-white' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                            Всі
                        </a>
                        <a href="orders.php?status=pending" class="px-4 py-2 rounded bg-<?= $status === 'pending' ? 'yellow-200 text-yellow-800' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                            <i class="fas fa-clock mr-1"></i> Нові
                        </a>
                        <a href="orders.php?status=processing" class="px-4 py-2 rounded bg-<?= $status === 'processing' ? 'blue-200 text-blue-800' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                            <i class="fas fa-spinner mr-1"></i> В обробці
                        </a>
                        <a href="orders.php?status=ready_for_pickup" class="px-4 py-2 rounded bg-<?= $status === 'ready_for_pickup' ? 'purple-200 text-purple-800' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                            <i class="fas fa-box mr-1"></i> Готові
                        </a>
                        <a href="orders.php?status=shipped" class="px-4 py-2 rounded bg-<?= $status === 'shipped' ? 'indigo-200 text-indigo-800' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                            <i class="fas fa-truck mr-1"></i> Відправлені
                        </a>
                        <a href="orders.php?status=delivered" class="px-4 py-2 rounded bg-<?= $status === 'delivered' ? 'green-200 text-green-800' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                            <i class="fas fa-check mr-1"></i> Доставлені
                        </a>
                        <a href="orders.php?status=completed" class="px-4 py-2 rounded bg-<?= $status === 'completed' ? 'green-200 text-green-800' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                            <i class="fas fa-check-double mr-1"></i> Завершені
                        </a>
                        <a href="orders.php?status=cancelled" class="px-4 py-2 rounded bg-<?= $status === 'cancelled' ? 'red-200 text-red-800' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                            <i class="fas fa-ban mr-1"></i> Скасовані
                        </a>
                    </div>
                    
                    <!-- Пошук -->
                    <form action="orders.php" method="GET" class="flex">
                        <input type="text" name="search" placeholder="Пошук замовлень..." value="<?= htmlspecialchars($search) ?>" 
                               class="border rounded-l px-3 py-2 focus:outline-none focus:ring-red-500">
                        <button type="submit" class="bg-red-800 text-white px-3 py-2 rounded-r hover:bg-red-700">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                <!-- Список замовлень -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <?php if (empty($ordersData['data'])): ?>
                    <div class="p-6 text-center text-gray-500">
                        <i class="fas fa-shopping-cart text-5xl mb-4"></i>
                        <p>Замовлення не знайдені. Спробуйте змінити параметри фільтрації.</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клієнт</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Оплата</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Менеджер</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($ordersData['data'] as $order): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= $order['id'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($order['customer_name']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($order['customer_email']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($order['customer_phone']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
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
                                                $statusClass = 'bg-purple-100 text-purple-800'; 
                                                $statusText = 'Готове'; 
                                                break;
                                            case 'shipped': 
                                                $statusClass = 'bg-indigo-100 text-indigo-800'; 
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
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $paymentClass = '';
                                        $paymentText = '';
                                        switch ($order['payment_status']) {
                                            case 'pending': 
                                                $paymentClass = 'bg-yellow-100 text-yellow-800'; 
                                                $paymentText = 'Очікує'; 
                                                break;
                                            case 'paid': 
                                                $paymentClass = 'bg-green-100 text-green-800'; 
                                                $paymentText = 'Оплачено'; 
                                                break;
                                            case 'refunded': 
                                                $paymentClass = 'bg-red-100 text-red-800'; 
                                                $paymentText = 'Повернуто'; 
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
                                    <?php
// Изменения в коде для устранения ошибки
// Заменим строку в таблице замовлень
echo htmlspecialchars($order['sales_manager_id'] ? $order['sales_manager_name'] ?? 'Не призначено' : 'Не призначено');
?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="order_details.php?id=<?= $order['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Деталі">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" onclick="openStatusModal(<?= $order['id'] ?>, '<?= $order['status'] ?>')" class="text-green-600 hover:text-green-900" title="Змінити статус">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                            <button type="button" onclick="openPaymentModal(<?= $order['id'] ?>, '<?= $order['payment_status'] ?>')" class="text-purple-600 hover:text-purple-900" title="Змінити статус оплати">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                            <button type="button" onclick="openManagerModal(<?= $order['id'] ?>)" class="text-indigo-600 hover:text-indigo-900" title="Призначити менеджера">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Пагінація -->
                    <?php if ($ordersData['pagination']['total_pages'] > 1): ?>
                    <div class="px-6 py-4 border-t">
                        <div class="flex justify-between items-center">
                            <div>
                                Показано <?= ($ordersData['pagination']['current_page'] - 1) * $ordersData['pagination']['per_page'] + 1 ?> - 
                                <?= min($ordersData['pagination']['current_page'] * $ordersData['pagination']['per_page'], $ordersData['pagination']['total']) ?> 
                                з <?= $ordersData['pagination']['total'] ?> замовлень
                            </div>
                            <div class="flex space-x-2">
                                <?php
                                // Базовий URL для пагінації
                                $url = 'orders.php?';
                                if (!empty($status)) {
                                    $url .= 'status=' . urlencode($status) . '&';
                                }
                                if (!empty($search)) {
                                    $url .= 'search=' . urlencode($search) . '&';
                                }
                                ?>
                                
                                <?php if ($ordersData['pagination']['current_page'] > 1): ?>
                                <a href="<?= $url ?>page=<?= $ordersData['pagination']['current_page'] - 1 ?>" class="px-3 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php 
                                // Виводимо 5 сторінок навколо поточної
                                $start = max(1, $ordersData['pagination']['current_page'] - 2);
                                $end = min($ordersData['pagination']['total_pages'], $ordersData['pagination']['current_page'] + 2);
                                
                                // Додаємо першу сторінку
                                if ($start > 1): 
                                ?>
                                <a href="<?= $url ?>page=1" class="px-3 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">1</a>
                                <?php 
                                if ($start > 2): 
                                    echo '<span class="px-3 py-1">...</span>';
                                endif;
                                endif; 
                                
                                // Виводимо сторінки
                                for ($i = $start; $i <= $end; $i++): 
                                ?>
                                <a href="<?= $url ?>page=<?= $i ?>" class="px-3 py-1 rounded <?= $i === $ordersData['pagination']['current_page'] ? 'bg-red-800 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                    <?= $i ?>
                                </a>
                                <?php 
                                endfor;
                                
                                // Додаємо останню сторінку
                                if ($end < $ordersData['pagination']['total_pages']): 
                                    if ($end < $ordersData['pagination']['total_pages'] - 1): 
                                        echo '<span class="px-3 py-1">...</span>';
                                    endif;
                                ?>
                                <a href="<?= $url ?>page=<?= $ordersData['pagination']['total_pages'] ?>" class="px-3 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300"><?= $ordersData['pagination']['total_pages'] ?></a>
                                <?php endif; ?>
                                
                                <?php if ($ordersData['pagination']['current_page'] < $ordersData['pagination']['total_pages']): ?>
                                <a href="<?= $url ?>page=<?= $ordersData['pagination']['current_page'] + 1 ?>" class="px-3 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Модальне вікно для зміни статусу замовлення -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Зміна статусу замовлення</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeStatusModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="orders.php" method="POST" id="statusForm">
                <input type="hidden" name="order_id" id="statusOrderId">
                <input type="hidden" name="update_status" value="1">
                
                <div class="mb-4">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Статус замовлення</label>
                    <select id="status" name="status" class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-red-500">
                        <option value="pending">Нове</option>
                        <option value="processing">В обробці</option>
                        <option value="ready_for_pickup">Готове до відправки</option>
                        <option value="shipped">Відправлено</option>
                        <option value="delivered">Доставлено</option>
                        <option value="completed">Завершено</option>
                        <option value="cancelled">Скасовано</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="status_notes" class="block text-sm font-medium text-gray-700 mb-1">Примітки</label>
                    <textarea id="status_notes" name="notes" rows="2"
                              class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-red-500"></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2" onclick="closeStatusModal()">
                        Скасувати
                    </button>
                    <button type="submit" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                        Зберегти
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальне вікно для зміни статусу оплати -->
    <div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Зміна статусу оплати</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closePaymentModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="orders.php" method="POST" id="paymentForm">
                <input type="hidden" name="order_id" id="paymentOrderId">
                <input type="hidden" name="update_payment" value="1">
                
                <div class="mb-4">
                    <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-1">Статус оплати</label>
                    <select id="payment_status" name="payment_status" class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-red-500">
                        <option value="pending">Очікує оплати</option>
                        <option value="paid">Оплачено</option>
                        <option value="refunded">Повернуто</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="payment_notes" class="block text-sm font-medium text-gray-700 mb-1">Примітки</label>
                    <textarea id="payment_notes" name="notes" rows="2"
                              class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-red-500"></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2" onclick="closePaymentModal()">
                        Скасувати
                    </button>
                    <button type="submit" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                        Зберегти
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальне вікно для призначення менеджера -->
    <div id="managerModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Призначення менеджера</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeManagerModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="orders.php" method="POST" id="managerForm">
                <input type="hidden" name="order_id" id="managerOrderId">
                <input type="hidden" name="assign_manager" value="1">
                
                <div class="mb-4">
                    <label for="manager_id" class="block text-sm font-medium text-gray-700 mb-1">Менеджер</label>
                    <select id="manager_id" name="manager_id" class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-red-500">
                        <?php
                        // В реальному додатку тут повинен бути список менеджерів з контролера
                        // Це просто заглушка
                        ?>
                        <option value="3">Менеджер з продажу (ID: 3)</option>
                    </select>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2" onclick="closeManagerModal()">
                        Скасувати
                    </button>
                    <button type="submit" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                        Призначити
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Функції для модального вікна зміни статусу
        function openStatusModal(orderId, currentStatus) {
            document.getElementById('statusOrderId').value = orderId;
            document.getElementById('status').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }
        
        // Функції для модального вікна зміни статусу оплати
        function openPaymentModal(orderId, currentStatus) {
            document.getElementById('paymentOrderId').value = orderId;
            document.getElementById('payment_status').value = currentStatus;
            document.getElementById('paymentModal').classList.remove('hidden');
        }
        
        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }
        
        // Функції для модального вікна призначення менеджера
        function openManagerModal(orderId) {
            document.getElementById('managerOrderId').value = orderId;
            document.getElementById('managerModal').classList.remove('hidden');
        }
        
        function closeManagerModal() {
            document.getElementById('managerModal').classList.add('hidden');
        }
    </script>
</body>
</html>