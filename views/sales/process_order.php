<?php
// sales/process_order.php
// Обробка дій із замовленням для менеджера з продажу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/SalesController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('sales')) {
    header('Location: /login.php?redirect=sales/process_order');
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

// Перевіряємо, чи передано дію
$action = isset($_GET['action']) ? $_GET['action'] : '';
if (empty($action)) {
    header('Location: order_details.php?id=' . $orderId);
    exit;
}

// Ініціалізуємо контролер продажів
$salesController = new SalesController();

// Змінні для повідомлень
$message = '';
$messageType = '';

// Обробка дій
switch ($action) {
    case 'take':
        // Взяти замовлення в роботу (призначити себе менеджером)
        $result = $salesController->assignManager($orderId, $currentUser['id']);
        if ($result['success']) {
            $message = 'Ви успішно взяли замовлення #' . $orderId . ' в роботу.';
            $messageType = 'success';
        } else {
            $message = 'Помилка: ' . $result['message'];
            $messageType = 'error';
        }
        break;
        
    case 'process':
        // Змінити статус замовлення на "В обробці"
        $result = $salesController->updateOrderStatus($orderId, 'processing', 'Замовлення взято в обробку менеджером ' . $currentUser['name']);
        if ($result['success']) {
            $message = 'Статус замовлення #' . $orderId . ' змінено на "В обробці".';
            $messageType = 'success';
        } else {
            $message = 'Помилка: ' . $result['message'];
            $messageType = 'error';
        }
        break;
        
    case 'ready':
        // Змінити статус замовлення на "Готове до відправки"
        $result = $salesController->updateOrderStatus($orderId, 'ready_for_pickup', 'Замовлення готове до відправки, підготовлено менеджером ' . $currentUser['name']);
        if ($result['success']) {
            $message = 'Статус замовлення #' . $orderId . ' змінено на "Готове до відправки".';
            $messageType = 'success';
        } else {
            $message = 'Помилка: ' . $result['message'];
            $messageType = 'error';
        }
        break;
        
    case 'ship':
        // Змінити статус замовлення на "Відправлено"
        $result = $salesController->updateOrderStatus($orderId, 'shipped', 'Замовлення відправлено клієнту');
        if ($result['success']) {
            $message = 'Статус замовлення #' . $orderId . ' змінено на "Відправлено".';
            $messageType = 'success';
        } else {
            $message = 'Помилка: ' . $result['message'];
            $messageType = 'error';
        }
        break;
        
    case 'deliver':
        // Змінити статус замовлення на "Доставлено"
        $result = $salesController->updateOrderStatus($orderId, 'delivered', 'Замовлення доставлено клієнту');
        if ($result['success']) {
            $message = 'Статус замовлення #' . $orderId . ' змінено на "Доставлено".';
            $messageType = 'success';
        } else {
            $message = 'Помилка: ' . $result['message'];
            $messageType = 'error';
        }
        break;
        
    case 'complete':
        // Змінити статус замовлення на "Завершено"
        $result = $salesController->updateOrderStatus($orderId, 'completed', 'Замовлення успішно завершено');
        if ($result['success']) {
            $message = 'Статус замовлення #' . $orderId . ' змінено на "Завершено".';
            $messageType = 'success';
        } else {
            $message = 'Помилка: ' . $result['message'];
            $messageType = 'error';
        }
        break;
        
    case 'cancel':
        // Змінити статус замовлення на "Скасовано"
        $result = $salesController->updateOrderStatus($orderId, 'cancelled', 'Замовлення скасовано менеджером ' . $currentUser['name']);
        if ($result['success']) {
            $message = 'Статус замовлення #' . $orderId . ' змінено на "Скасовано".';
            $messageType = 'success';
        } else {
            $message = 'Помилка: ' . $result['message'];
            $messageType = 'error';
        }
        break;
        
    case 'payment_paid':
        // Змінити статус оплати на "Оплачено"
        $result = $salesController->updatePaymentStatus($orderId, 'paid', 'Оплата підтверджена менеджером ' . $currentUser['name']);
        if ($result['success']) {
            $message = 'Статус оплати замовлення #' . $orderId . ' змінено на "Оплачено".';
            $messageType = 'success';
        } else {
            $message = 'Помилка: ' . $result['message'];
            $messageType = 'error';
        }
        break;
        
    case 'payment_refund':
        // Змінити статус оплати на "Повернуто"
        $result = $salesController->updatePaymentStatus($orderId, 'refunded', 'Оплата повернута клієнту');
        if ($result['success']) {
            $message = 'Статус оплати замовлення #' . $orderId . ' змінено на "Повернуто".';
            $messageType = 'success';
        } else {
            $message = 'Помилка: ' . $result['message'];
            $messageType = 'error';
        }
        break;
        
    default:
        // Невідома дія
        $message = 'Невідома дія: ' . $action;
        $messageType = 'error';
        break;
}

// Зберігаємо повідомлення в сесії для показу на сторінці деталей замовлення
$_SESSION['message'] = $message;
$_SESSION['message_type'] = $messageType;

// Перенаправляємо на сторінку деталей замовлення
header('Location: order_details.php?id=' . $orderId);
exit;