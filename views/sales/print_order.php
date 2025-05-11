<?php
// sales/print_order.php
// Сторінка для друку замовлення

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/SalesController.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('sales')) {
    header('Location: /login.php?redirect=sales/print_order');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролери
$salesController = new SalesController();
$customerController = new CustomerController();

// Отримуємо ID замовлення з параметра
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId <= 0) {
    header('Location: orders.php');
    exit;
}

// Отримуємо деталі замовлення
$orderDetails = $salesController->getOrderDetails($orderId);

if (!$orderDetails['success']) {
    header('Location: orders.php');
    exit;
}

$order = $orderDetails['order'];
$orderItems = $orderDetails['items'];

// Отримуємо дані клієнта
if (isset($orderDetails['customer'])) {
    $customer = $orderDetails['customer'];
} else {
    // Пробуємо отримати дані клієнта різними способами, залежно від того, які методи доступні
    $customer = ['name' => 'Клієнт не знайдений', 'phone' => 'Не вказано', 'email' => 'Не вказано'];
    
    // Спроба 1: Використання методу getCustomers для отримання всіх клієнтів і пошуку потрібного
    try {
        if (method_exists($salesController, 'getCustomers')) {
            $customersData = $salesController->getCustomers(1, 1000);
            if ($customersData['success'] && !empty($customersData['data'])) {
                foreach ($customersData['data'] as $cust) {
                    if ($cust['id'] == $order['customer_id']) {
                        $customer = $cust;
                        break;
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Ігноруємо помилки
    }
}

// Отримуємо дані менеджера
if (isset($orderDetails['manager'])) {
    $manager = $orderDetails['manager'];
} else {
    // Дефолтні дані менеджера
    $manager = [
        'name' => 'Менеджер',
        'phone' => '+38 (044) 123-45-67',
        'email' => 'sales@wineshop.ua'
    ];
    
    // Спроба отримати дані менеджера, якщо такий метод існує
    try {
        if (method_exists($authController, 'getUserById') && isset($order['manager_id'])) {
            $managerResult = $authController->getUserById($order['manager_id']);
            if (isset($managerResult['success']) && $managerResult['success']) {
                $manager = $managerResult['user'];
            }
        } elseif (method_exists($authController, 'getCurrentUser')) {
            // Альтернативний варіант - використати поточного користувача
            $currentUserData = $authController->getCurrentUser();
            if (is_array($currentUserData) && !empty($currentUserData)) {
                $manager = $currentUserData;
            }
        }
    } catch (Exception $e) {
        // Ігноруємо помилки
    }
}

// Розрахунок сум
$subtotal = 0;
foreach ($orderItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$total = $subtotal + $order['shipping_cost'];

// Визначаємо статус замовлення українською
$statusLabels = [
    'pending' => 'Очікує підтвердження',
    'processing' => 'В обробці',
    'shipping' => 'Відправлено',
    'delivered' => 'Доставлено',
    'completed' => 'Виконано',
    'canceled' => 'Скасовано'
];

// Перевіряємо наявність ключа статусу в замовленні
$status = isset($order['status']) ? $order['status'] : 'pending';
$statusLabel = isset($statusLabels[$status]) ? $statusLabels[$status] : $status;

// Визначаємо спосіб оплати українською
$paymentMethodLabels = [
    'card' => 'Оплата картою онлайн',
    'bank_transfer' => 'Банківський переказ',
    'cash_on_delivery' => 'Оплата при отриманні'
];

// Перевіряємо наявність ключа способу оплати в замовленні
$paymentMethod = isset($order['payment_method']) ? $order['payment_method'] : '';
$paymentMethodLabel = isset($paymentMethodLabels[$paymentMethod]) ? 
    $paymentMethodLabels[$paymentMethod] : $paymentMethod;

// Формуємо номер замовлення
$orderNumber = 'WS-' . str_pad($order['id'] ?? $orderId, 6, '0', STR_PAD_LEFT);

// Формуємо дату у правильному форматі
$orderDate = isset($order['created_at']) ? date('d.m.Y', strtotime($order['created_at'])) : date('d.m.Y');

// Генеруємо унікальний код для QR-коду
$qrCodeData = 'order:' . ($order['id'] ?? $orderId) . 
              ';date:' . ($order['created_at'] ?? date('Y-m-d H:i:s')) . 
              ';customer:' . ($customer['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Замовлення <?= $orderNumber ?> - Друк - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        @media print {
            body {
                font-size: 12pt;
                line-height: 1.3;
            }
            .no-print {
                display: none !important;
            }
            .print-break {
                page-break-before: always;
            }
            .print-container {
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 0;
            }
            .print-header {
                margin-bottom: 20px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            table, th, td {
                border: 1px solid #000;
            }
            th, td {
                padding: 5px;
            }
            @page {
                size: A4;
                margin: 1cm;
            }
        }
        
        /* QR код (заглушка для прикладу) */
        .qr-code {
            display: inline-block;
            width: 100px;
            height: 100px;
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            position: relative;
        }
        .qr-code::after {
            content: "QR";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            font-weight: bold;
            color: #6b7280;
        }
        .logo-placeholder {
            width: 180px;
            height: 60px;
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-gray-100 p-4">
    <!-- Кнопки для друку (не друкуються) -->
    <div class="no-print mb-4 flex justify-between">
        <div>
            <button onclick="window.print();" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded mr-2">
                <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Друк
            </button>
            <a href="order_details.php?id=<?= $orderId ?>" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Повернутись до замовлення
            </a>
        </div>
        
        <div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Надіслати клієнту
            </button>
        </div>
    </div>
    
    <!-- Контейнер для друку -->
    <div class="print-container max-w-4xl mx-auto bg-white shadow-md p-8">
        <!-- Заголовок і дані компанії -->
        <div class="print-header flex justify-between items-start mb-8">
            <div>
                <div class="logo-placeholder mb-2">ВИННА КРАМНИЦЯ</div>
                <p class="text-gray-700">ТОВ "Винна Крамниця"</p>
                <p class="text-gray-700">вул. Виноградна, 45, м. Київ, 01001</p>
                <p class="text-gray-700">Тел: +38 (044) 123-45-67</p>
                <p class="text-gray-700">Email: info@wineshop.ua</p>
                <p class="text-gray-700">ЄДРПОУ: 12345678</p>
            </div>
            <div class="text-right">
                <div class="qr-code mb-2"></div>
                <p class="text-gray-700">Замовлення: <strong><?= $orderNumber ?></strong></p>
                <p class="text-gray-700">Дата: <strong><?= $orderDate ?></strong></p>
                <p class="text-gray-700">Статус: <strong><?= $statusLabel ?></strong></p>
            </div>
        </div>
        
        <!-- Дані клієнта і доставки -->
        <div class="grid grid-cols-2 gap-8 mb-8">
            <div>
                <h2 class="text-lg font-bold mb-2 border-b border-gray-300 pb-1">Інформація про клієнта</h2>
                <p class="mb-1"><strong>Ім'я:</strong> <?= htmlspecialchars($customer['name']) ?></p>
                <p class="mb-1"><strong>Телефон:</strong> <?= htmlspecialchars($customer['phone'] ?? 'Не вказано') ?></p>
                <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($customer['email'] ?? 'Не вказано') ?></p>
            </div>
            <div>
                <h2 class="text-lg font-bold mb-2 border-b border-gray-300 pb-1">Інформація про доставку</h2>
                <p class="mb-1"><strong>Адреса доставки:</strong> <?= htmlspecialchars($order['shipping_address'] ?? 'Не вказано') ?></p>
                <p class="mb-1"><strong>Спосіб оплати:</strong> <?= htmlspecialchars($paymentMethodLabel) ?></p>
                <p class="mb-1"><strong>Вартість доставки:</strong> <?= number_format($order['shipping_cost'] ?? 0, 2) ?> ₴</p>
            </div>
        </div>
        
        <!-- Товари замовлення -->
        <div class="mb-8">
            <h2 class="text-lg font-bold mb-2 border-b border-gray-300 pb-1">Товари</h2>
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 p-2 text-left">#</th>
                        <th class="border border-gray-300 p-2 text-left">Найменування</th>
                        <th class="border border-gray-300 p-2 text-right">Ціна, ₴</th>
                        <th class="border border-gray-300 p-2 text-right">Кількість</th>
                        <th class="border border-gray-300 p-2 text-right">Сума, ₴</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; ?>
                    <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td class="border border-gray-300 p-2"><?= $count++ ?></td>
                        <td class="border border-gray-300 p-2"><?= htmlspecialchars($item['name']) ?></td>
                        <td class="border border-gray-300 p-2 text-right"><?= number_format($item['price'], 2) ?></td>
                        <td class="border border-gray-300 p-2 text-right"><?= $item['quantity'] ?></td>
                        <td class="border border-gray-300 p-2 text-right"><?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-gray-100">
                        <td colspan="4" class="border border-gray-300 p-2 text-right font-bold">Вартість товарів:</td>
                        <td class="border border-gray-300 p-2 text-right"><?= number_format($subtotal, 2) ?> ₴</td>
                    </tr>
                    <tr class="bg-gray-100">
                        <td colspan="4" class="border border-gray-300 p-2 text-right font-bold">Вартість доставки:</td>
                        <td class="border border-gray-300 p-2 text-right"><?= number_format($order['shipping_cost'] ?? 0, 2) ?> ₴</td>
                    </tr>
                    <tr class="bg-gray-100">
                        <td colspan="4" class="border border-gray-300 p-2 text-right font-bold">Загальна сума:</td>
                        <td class="border border-gray-300 p-2 text-right font-bold"><?= number_format($total, 2) ?> ₴</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Примітки до замовлення -->
        <?php if (isset($order['notes']) && !empty($order['notes'])): ?>
        <div class="mb-8">
            <h2 class="text-lg font-bold mb-2 border-b border-gray-300 pb-1">Примітки до замовлення</h2>
            <p class="p-2 bg-gray-50 border border-gray-200 rounded"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Інформація про менеджера -->
        <div class="mb-8">
            <h2 class="text-lg font-bold mb-2 border-b border-gray-300 pb-1">Інформація про менеджера</h2>
            <p class="mb-1"><strong>Менеджер:</strong> <?= htmlspecialchars($manager['name']) ?></p>
            <p class="mb-1"><strong>Телефон:</strong> <?= htmlspecialchars($manager['phone'] ?? '+38 (044) 123-45-67') ?></p>
            <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($manager['email'] ?? 'sales@wineshop.ua') ?></p>
        </div>
        
        <!-- Підписи -->
        <div class="grid grid-cols-2 gap-8 mb-8">
            <div>
                <p class="mb-4">Замовлення склав:</p>
                <div class="border-b border-gray-400 mt-8 mb-2"></div>
                <p class="text-sm text-gray-600">Підпис менеджера</p>
            </div>
            <div>
                <p class="mb-4">Замовлення отримав:</p>
                <div class="border-b border-gray-400 mt-8 mb-2"></div>
                <p class="text-sm text-gray-600">Підпис клієнта</p>
            </div>
        </div>
        
        <!-- Нижній колонтитул -->
        <div class="text-center text-gray-500 text-sm mt-8 pt-4 border-t border-gray-300">
            <p>Дякуємо за замовлення в Винній Крамниці!</p>
            <p>© <?= date('Y') ?> ТОВ "Винна Крамниця". Всі права захищені.</p>
            <p class="mt-2">www.wineshop.ua</p>
        </div>
    </div>
    
    <script>
        // Автоматично викликаємо друк, якщо передано параметр auto_print
        <?php if (isset($_GET['auto_print']) && $_GET['auto_print'] == 1): ?>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
        <?php endif; ?>
    </script>
</body>
</html>