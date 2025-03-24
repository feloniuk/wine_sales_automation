<?php
// sales/index.php
// Головна сторінка менеджера з продажу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/SalesController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('sales')) {
    header('Location: /login.php?redirect=sales');
    exit;
}

// Отримуємо дані для дешборду
$currentUser = $authController->getCurrentUser();
$salesController = new SalesController();
$dashboardData = $salesController->getDashboardData($currentUser['id']);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель менеджера з продажу - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Бічне меню -->
        <div class="w-64 bg-green-800 text-white">
            <div class="p-4 font-bold text-xl">Винна крамниця</div>
            <nav class="mt-8">
                <a href="index.php" class="flex items-center px-4 py-3 bg-green-700">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    <span>Дашборд</span>
                </a>
                <a href="orders.php" class="flex items-center px-4 py-3 hover:bg-green-700">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Дашборд менеджера</h1>
                    <div class="flex items-center">
                        <div class="relative mr-4">
                            <span class="absolute top-0 right-0 -mt-1 -mr-1 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-xs"><?= count($dashboardData['unread_messages']) ?></span>
                            <a href="messages.php" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-envelope text-xl"></i>
                            </a>
                        </div>
                        <div class="relative">
                            <button class="flex items-center text-gray-700 focus:outline-none">
                                <img src="../assets/images/avatar.jpg" alt="Avatar" class="h-8 w-8 rounded-full mr-2">
                                <span><?= $_SESSION['name'] ?></span>
                                <i class="fas fa-chevron-down ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Контент дашборду -->
            <main class="p-6">
                <!-- Основні показники -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-800 mr-4">
                                <i class="fas fa-shopping-cart text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500">Всього замовлень</p>
                                <p class="text-2xl font-semibold"><?= $dashboardData['orders_stats']['total_orders'] ?></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-gray-500 text-sm">Загальна сума продажів</p>
                            <p class="font-semibold"><?= number_format($dashboardData['orders_stats']['total_sales'], 2) ?> ₴</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-800 mr-4">
                                <i class="fas fa-clock text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500">Очікують дії</p>
                                <p class="text-2xl font-semibold"><?= $dashboardData['orders_stats']['pending_count'] + $dashboardData['orders_stats']['processing_count'] ?></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-gray-500 text-sm">Нові та в обробці замовлення</p>
                            <a href="orders.php?status=pending" class="text-green-600 hover:underline text-sm">Переглянути &rarr;</a>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-800 mr-4">
                                <i class="fas fa-check-circle text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500">Завершені</p>
                                <p class="text-2xl font-semibold"><?= $dashboardData['orders_stats']['completed_count'] ?></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-gray-500 text-sm">Успішно виконані замовлення</p>
                            <p class="font-semibold text-green-600"><?= number_format(($dashboardData['orders_stats']['completed_count'] / max(1, $dashboardData['orders_stats']['total_orders'])) * 100, 1) ?>% від загальних</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-800 mr-4">
                                <i class="fas fa-envelope text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500">Повідомлення</p>
                                <p class="text-2xl font-semibold"><?= count($dashboardData['unread_messages']) ?></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-gray-500 text-sm">Непрочитані повідомлення</p>
                            <a href="messages.php" class="text-green-600 hover:underline text-sm">Переглянути &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- Графіки та статистика -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Продажі за останній місяць</h2>
                        <canvas id="salesChart" height="250"></canvas>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Найпопулярніші товари</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Товар</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Категорія</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Продано</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($dashboardData['top_products'] as $product): ?>
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full" src="../assets/images/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?= htmlspecialchars($product['category_name']) ?></td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?= $product['total_quantity'] ?> шт.</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?= number_format($product['total_sales'], 2) ?> ₴</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Замовлення та клієнти -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Замовлення, що очікують -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold">Замовлення, що очікують дії</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клієнт</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($dashboardData['pending_orders'] as $order): ?>
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">#<?= $order['id'] ?></td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                                            <div class="flex flex-col">
                                                <span class="font-medium"><?= htmlspecialchars($order['customer_name']) ?></span>
                                                <span class="text-gray-500 text-xs"><?= htmlspecialchars($order['customer_phone'] ?? 'Телефон не вказано') ?></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?= number_format($order['total_amount'], 2) ?> ₴</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                                            <?php
                                            $statusClass = '';
                                            switch ($order['status']) {
                                                case 'pending': $statusClass = 'bg-yellow-100 text-yellow-800'; $status = 'Нове'; break;
                                                case 'processing': $statusClass = 'bg-blue-100 text-blue-800'; $status = 'В обробці'; break;
                                                default: $statusClass = 'bg-gray-100 text-gray-800'; $status = $order['status']; break;
                                            }
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                                <?= $status ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                                            <a href="order_details.php?id=<?= $order['id'] ?>" class="text-green-600 hover:underline">Деталі</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 border-t">
                            <a href="orders.php" class="text-green-600 hover:underline">Переглянути всі замовлення &rarr;</a>
                        </div>
                    </div>

                    <!-- Топ клієнтів -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold">Топ клієнти</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клієнт</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Замовлень</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Останнє</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($dashboardData['top_customers'] as $customer): ?>
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <i class="fas fa-user text-gray-500"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($customer['name']) ?></div>
                                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($customer['email']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?= $customer['order_count'] ?></td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?= number_format($customer['total_spent'], 2) ?> ₴</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?= date('d.m.Y', strtotime($customer['last_order_date'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 border-t">
                            <a href="customers.php" class="text-green-600 hover:underline">Переглянути всіх клієнтів &rarr;</a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    // Графік продажів за місяць
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesData = <?= json_encode($dashboardData['sales_by_day']) ?>;
    
    const dates = salesData.map(item => item.date);
    const sales = salesData.map(item => item.total_sales);
    const orderCounts = salesData.map(item => item.order_count);
    
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: dates.map(date => date.substring(5)), // format: MM-DD
            datasets: [
                {
                    label: 'Продажі (грн)',
                    data: sales,
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Кількість замовлень',
                    data: orderCounts,
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Продажі (грн)'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    },
                    title: {
                        display: true,
                        text: 'Кількість замовлень'
                    }
                }
            }
        }
    });
    </script>
</body>
</html>