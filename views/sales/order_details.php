<?php
// sales/order_details.php
// Сторінка деталей замовлення для менеджера з продажу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/SalesController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('sales')) {
    header('Location: /login.php?redirect=sales/order_details');
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

// Ініціалізуємо контролер продажів
$salesController = new SalesController();

// Отримуємо деталі замовлення
$orderDetails = $salesController->getOrderDetails($orderId);
if (!$orderDetails['success']) {
    header('Location: orders.php');
    exit;
}

// Присвоюємо змінні з результатами
$order = $orderDetails['order'];
$items = $orderDetails['items'];

// Обробка форми оновлення статусу замовлення
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Оновлення статусу замовлення
    if (isset($_POST['update_status'])) {
        $newStatus = $_POST['status'];
        $notes = $_POST['status_notes'] ?? '';
        
        $result = $salesController->updateOrderStatus($orderId, $newStatus, $notes);
        
        if ($result['success']) {
            $message = 'Статус замовлення успішно оновлено.';
            $messageType = 'success';
            
            // Оновлюємо дані замовлення
            $orderDetails = $salesController->getOrderDetails($orderId);
            $order = $orderDetails['order'];
            $items = $orderDetails['items'];
        } else {
            $message = 'Помилка при оновленні статусу замовлення: ' . $result['message'];
            $messageType = 'error';
        }
    }
    
    // Оновлення статусу оплати
    if (isset($_POST['update_payment'])) {
        $newPaymentStatus = $_POST['payment_status'];
        $notes = $_POST['payment_notes'] ?? '';
        
        $result = $salesController->updatePaymentStatus($orderId, $newPaymentStatus, $notes);
        
        if ($result['success']) {
            $message = 'Статус оплати успішно оновлено.';
            $messageType = 'success';
            
            // Оновлюємо дані замовлення
            $orderDetails = $salesController->getOrderDetails($orderId);
            $order = $orderDetails['order'];
            $items = $orderDetails['items'];
        } else {
            $message = 'Помилка при оновленні статусу оплати: ' . $result['message'];
            $messageType = 'error';
        }
    }
    
    // Призначення менеджера для замовлення
    if (isset($_POST['assign_manager']) && $order['sales_manager_id'] != $currentUser['id']) {
        $result = $salesController->assignManager($orderId, $currentUser['id']);
        
        if ($result['success']) {
            $message = 'Ви призначені менеджером цього замовлення.';
            $messageType = 'success';
            
            // Оновлюємо дані замовлення
            $orderDetails = $salesController->getOrderDetails($orderId);
            $order = $orderDetails['order'];
            $items = $orderDetails['items'];
        } else {
            $message = 'Помилка при призначенні менеджера: ' . $result['message'];
            $messageType = 'error';
        }
    }
    
    // Відправка повідомлення клієнту
    if (isset($_POST['send_message'])) {
        $subject = $_POST['message_subject'];
        $messageContent = $_POST['message_content'];
        
        if (empty($subject) || empty($messageContent)) {
            $message = 'Будь ласка, заповніть тему та текст повідомлення.';
            $messageType = 'warning';
        } else {
            $result = $salesController->sendMessage($currentUser['id'], $order['customer_id'], $subject, $messageContent);
            
            if ($result['success']) {
                $message = 'Повідомлення успішно відправлено.';
                $messageType = 'success';
            } else {
                $message = 'Помилка при відправленні повідомлення: ' . $result['message'];
                $messageType = 'error';
            }
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

// Визначення класів для статусу оплати
$paymentStatusClass = '';
$paymentStatusIcon = '';

switch ($order['payment_status']) {
    case 'pending':
        $paymentStatusText = 'Очікує оплати';
        $paymentStatusClass = 'bg-yellow-100 text-yellow-800';
        $paymentStatusIcon = 'fas fa-clock';
        break;
    case 'paid':
        $paymentStatusText = 'Оплачено';
        $paymentStatusClass = 'bg-green-100 text-green-800';
        $paymentStatusIcon = 'fas fa-check-circle';
        break;
    case 'refunded':
        $paymentStatusText = 'Повернуто';
        $paymentStatusClass = 'bg-red-100 text-red-800';
        $paymentStatusIcon = 'fas fa-undo';
        break;
    default:
        $paymentStatusText = $order['payment_status'];
        $paymentStatusClass = 'bg-gray-100 text-gray-800';
        $paymentStatusIcon = 'fas fa-question-circle';
        break;
}

// Визначення дозволених переходів статусу
$allowedStatuses = [];
$isOrderManager = ($order['sales_manager_id'] == $currentUser['id']);

switch ($order['status']) {
    case 'pending':
        $allowedStatuses = ['processing', 'cancelled'];
        break;
    case 'processing':
        $allowedStatuses = ['ready_for_pickup', 'cancelled'];
        break;
    case 'ready_for_pickup':
        $allowedStatuses = ['shipped', 'cancelled'];
        break;
    case 'shipped':
        $allowedStatuses = ['delivered', 'cancelled'];
        break;
    case 'delivered':
        $allowedStatuses = ['completed'];
        break;
}

// Переклад типів оплати
$paymentMethodText = '';
switch ($order['payment_method']) {
    case 'card':
        $paymentMethodText = 'Оплата картою онлайн';
        break;
    case 'bank_transfer':
        $paymentMethodText = 'Банківський переказ';
        break;
    case 'cash_on_delivery':
        $paymentMethodText = 'Оплата при отриманні';
        break;
    default:
        $paymentMethodText = $order['payment_method'];
        break;
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
                    <h1 class="text-2xl font-semibold text-gray-800">Замовлення #<?= $orderId ?></h1>
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
                            <!-- Якщо замовлення не має менеджера - кнопка взяти в роботу -->
                            <?php if (empty($order['sales_manager_id']) && !$isOrderManager): ?>
                            <form action="order_details.php?id=<?= $orderId ?>" method="POST" class="inline-block">
                                <button type="submit" name="assign_manager" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                                    <i class="fas fa-user-check mr-2"></i> Взяти в роботу
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <!-- Друк замовлення -->
                            <a href="print_order.php?id=<?= $orderId ?>" target="_blank" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded inline-block ml-2">
                                <i class="fas fa-print mr-2"></i> Друкувати
                            </a>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <!-- Статус замовлення -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Статус замовлення</h3>
                            <div class="flex items-center">
                                <span class="<?= $statusClass ?> px-2.5 py-1 rounded-full text-xs font-medium inline-flex items-center">
                                    <i class="<?= $statusIcon ?> mr-1"></i> <?= $statusText ?>
                                </span>
                            </div>
                        </div>

                        <!-- Статус оплати -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Статус оплати</h3>
                            <div class="flex items-center">
                                <span class="<?= $paymentStatusClass ?> px-2.5 py-1 rounded-full text-xs font-medium inline-flex items-center">
                                    <i class="<?= $paymentStatusIcon ?> mr-1"></i> <?= $paymentStatusText ?>
                                </span>
                            </div>
                        </div>

                        <!-- Спосіб оплати -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Спосіб оплати</h3>
                            <p class="text-gray-800"><?= $paymentMethodText ?></p>
                        </div>

                        <!-- Відповідальний менеджер -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Відповідальний менеджер</h3>
                            <?php if ($order['sales_manager_id']): ?>
                            <p class="text-gray-800">
                                <?php if ($isOrderManager): ?>
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">
                                    <i class="fas fa-user mr-1"></i> Ви
                                </span>
                                <?php else: ?>
                                <?= htmlspecialchars($order['manager_name']) ?>
                                <?php endif; ?>
                            </p>
                            <?php else: ?>
                            <p class="text-gray-500">Не призначено</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Управління замовленням -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Форма оновлення статусу -->
                        <div>
                            <h3 class="font-medium text-gray-700 mb-3">Змінити статус замовлення</h3>
                            <form action="order_details.php?id=<?= $orderId ?>" method="POST">
                                <div class="mb-3">
                                    <select name="status" class="border rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-green-500" 
                                            <?= (!$isOrderManager || empty($allowedStatuses)) ? 'disabled' : '' ?>>
                                        <?php foreach ($allowedStatuses as $status): ?>
                                        <?php
                                        $statusLabel = '';
                                        switch ($status) {
                                            case 'processing': $statusLabel = 'В обробці'; break;
                                            case 'ready_for_pickup': $statusLabel = 'Готове до відправки'; break;
                                            case 'shipped': $statusLabel = 'Відправлено'; break;
                                            case 'delivered': $statusLabel = 'Доставлено'; break;
                                            case 'completed': $statusLabel = 'Завершено'; break;
                                            case 'cancelled': $statusLabel = 'Скасовано'; break;
                                            default: $statusLabel = $status; break;
                                        }
                                        ?>
                                        <option value="<?= $status ?>"><?= $statusLabel ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <textarea name="status_notes" placeholder="Примітки до зміни статусу" class="border rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-green-500" rows="2" <?= (!$isOrderManager || empty($allowedStatuses)) ? 'disabled' : '' ?>></textarea>
                                </div>
                                <button type="submit" name="update_status" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded" <?= (!$isOrderManager || empty($allowedStatuses)) ? 'disabled' : '' ?>>
                                    <i class="fas fa-sync-alt mr-2"></i> Оновити статус
                                </button>
                            </form>
                        </div>

                        <!-- Форма оновлення статусу оплати -->
                        <div>
                            <h3 class="font-medium text-gray-700 mb-3">Змінити статус оплати</h3>
                            <form action="order_details.php?id=<?= $orderId ?>" method="POST">
                                <div class="mb-3">
                                    <select name="payment_status" class="border rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-green-500" <?= !$isOrderManager ? 'disabled' : '' ?>>
                                        <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Очікує оплати</option>
                                        <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Оплачено</option>
                                        <option value="refunded" <?= $order['payment_status'] === 'refunded' ? 'selected' : '' ?>>Повернуто</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <textarea name="payment_notes" placeholder="Примітки до зміни статусу оплати" class="border rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-green-500" rows="2" <?= !$isOrderManager ? 'disabled' : '' ?>></textarea>
                                </div>
                                <button type="submit" name="update_payment" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded" <?= !$isOrderManager ? 'disabled' : '' ?>>
                                    <i class="fas fa-money-bill-wave mr-2"></i> Оновити статус оплати
                                </button>
                            </form>
                        </div>
                    </div>
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
                                <div class="w-24 flex-shrink-0 text-gray-500">Email:</div>
                                <div class="flex-1"><?= htmlspecialchars($order['customer_email']) ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Телефон:</div>
                                <div class="flex-1"><?= htmlspecialchars($order['customer_phone']) ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Адреса:</div>
                                <div class="flex-1"><?= htmlspecialchars($order['customer_address']) ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Місто:</div>
                                <div class="flex-1"><?= htmlspecialchars($order['customer_city']) ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Регіон:</div>
                                <div class="flex-1"><?= htmlspecialchars($order['customer_region']) ?></div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-24 flex-shrink-0 text-gray-500">Індекс:</div>
                                <div class="flex-1"><?= htmlspecialchars($order['customer_postal_code']) ?></div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="customer_details.php?id=<?= $order['customer_id'] ?>" class="text-green-600 hover:underline">
                                <i class="fas fa-user mr-1"></i> Перейти до профілю клієнта
                            </a>
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
                                <div class="w-24 flex-shrink-0 text-gray-500">Вартість:</div>
                                <div class="flex-1"><?= number_format($order['shipping_cost'], 2) ?> ₴</div>
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
                        
                        <!-- Форма для відправки повідомлення клієнту -->
                        <div class="mt-6">
                            <h3 class="font-medium text-gray-700 mb-3">Надіслати повідомлення клієнту</h3>
                            <form action="order_details.php?id=<?= $orderId ?>" method="POST">
                                <div class="mb-3">
                                    <input type="text" name="message_subject" placeholder="Тема" class="border rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-green-500" <?= !$isOrderManager ? 'disabled' : '' ?>>
                                </div>
                                <div class="mb-3">
                                    <textarea name="message_content" placeholder="Текст повідомлення" class="border rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-green-500" rows="3" <?= !$isOrderManager ? 'disabled' : '' ?>></textarea>
                                </div>
                                <button type="submit" name="send_message" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded" <?= !$isOrderManager ? 'disabled' : '' ?>>
                                    <i class="fas fa-paper-plane mr-2"></i> Надіслати
                                </button>
                            </form>
                        </div>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Знижка</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded-full object-cover" src="../assets/images/<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['product_name']) ?></div>
                                                <div class="text-sm text-gray-500">ID: <?= $item['product_id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= number_format($item['price'], 2) ?> ₴</div>
                                        <?php if ($item['price'] != $item['current_price']): ?>
                                        <div class="text-xs text-gray-500">Поточна: <?= number_format($item['current_price'], 2) ?> ₴</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $item['quantity'] ?> шт.
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($item['discount'] > 0): ?>
                                        <?= number_format($item['discount'], 2) ?> ₴
                                        <?php else: ?>
                                        -
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                        <?= number_format($item['price'] * $item['quantity'] - $item['discount'], 2) ?> ₴
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-right font-medium">Підсумок:</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium">
                                        <?= number_format($order['total_amount'] - $order['shipping_cost'], 2) ?> ₴
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-right font-medium">Доставка:</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium">
                                        <?= number_format($order['shipping_cost'], 2) ?> ₴
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-right text-lg font-bold">Загальна сума:</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-lg font-bold text-green-600">
                                        <?= number_format($order['total_amount'], 2) ?> ₴
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // JavaScript для взаємодії зі сторінкою
        document.addEventListener('DOMContentLoaded', function() {
            // Обробка натиснення на кнопку зміни статусу замовлення
            const statusSelect = document.querySelector('select[name="status"]');
            if (statusSelect) {
                statusSelect.addEventListener('change', function() {
                    if (this.value === 'cancelled') {
                        if (!confirm('Ви впевнені, що хочете скасувати замовлення? Це дія незворотна.')) {
                            this.value = ''; // Скидаємо значення, якщо користувач скасував дію
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>