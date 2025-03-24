<?php
// warehouse/index.php
// Головна сторінка начальника складу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/WarehouseController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('warehouse')) {
    header('Location: /login.php?redirect=warehouse');
    exit;
}

// Отримуємо дані для дешборду
$warehouseController = new WarehouseController();
$dashboardData = $warehouseController->getDashboardData();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель начальника складу - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Бічне меню -->
        <div class="w-64 bg-blue-900 text-white">
            <div class="p-4 font-bold text-xl">Винна крамниця</div>
            <nav class="mt-8">
                <a href="index.php" class="flex items-center px-4 py-3 bg-blue-800">
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
                <a href="orders.php" class="flex items-center px-4 py-3 hover:bg-blue-800">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Дашборд складу</h1>
                    <div class="flex items-center">
                        <div class="relative mr-4">
                            <span class="absolute top-0 right-0 -mt-1 -mr-1 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-xs"><?= count($dashboardData['low_stock_products']) ?></span>
                            <button class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-bell text-xl"></i>
                            </button>
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
                            <div class="p-3 rounded-full bg-blue-100 text-blue-800 mr-4">
                                <i class="fas fa-wine-bottle text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500">Всього товарів</p>
                                <p class="text-2xl font-semibold"><?= $dashboardData['products_stats']['total_products'] ?></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-gray-500 text-sm">Загальна вартість складу</p>
                            <p class="font-semibold"><?= number_format($dashboardData['products_stats']['total_stock_value'], 2) ?> ₴</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 text-red-800 mr-4">
                                <i class="fas fa-exclamation-triangle text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500">Низький запас</p>
                                <p class="text-2xl font-semibold"><?= $dashboardData['products_stats']['low_stock_count'] ?></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-gray-500 text-sm">Товари, що потребують поповнення</p>
                            <a href="low_stock.php" class="text-blue-600 hover:underline text-sm">Переглянути список &rarr;</a>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-800 mr-4">
                                <i class="fas fa-boxes text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500">Товарні одиниці</p>
                                <p class="text-2xl font-semibold"><?= $dashboardData['products_stats']['total_stock_items'] ?></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-gray-500 text-sm">Загальна кількість на складі</p>
                            <a href="inventory.php" class="text-blue-600 hover:underline text-sm">Інвентаризація &rarr;</a>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-800 mr-4">
                                <i class="fas fa-shipping-fast text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500">Очікують відправки</p>
                                <p class="text-2xl font-semibold"><?= count($dashboardData['pending_orders']) ?></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-gray-500 text-sm">Замовлення в обробці</p>
                            <a href="orders.php" class="text-blue-600 hover:underline text-sm">Переглянути замовлення &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- Графіки та статистика -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Транзакції за тиждень</h2>
                        <canvas id="transactionsChart" height="250"></canvas>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Топ товарів (за кількістю відправлень)</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Товар</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Категорія</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Видано</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Замовлення</th>
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
                                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?= $product['total_out'] ?> шт.</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?= $product['order_count'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Замовлення та низький запас -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Замовлення, що очікують -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold">Замовлення, що очікують відправки</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клієнт</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Створено</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach (array_slice($dashboardData['pending_orders'], 0, 5) as $order): ?>
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">#<?= $order['id'] ?></td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?= htmlspecialchars($order['customer_name']) ?></td>
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
                                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                                            <a href="order_details.php?id=<?= $order['id'] ?>" class="text-blue-600 hover:underline">Деталі</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 border-t">
                            <a href="orders.php" class="text-blue-600 hover:underline">Переглянути всі замовлення &rarr;</a>
                        </div>
                    </div>

                    <!-- Товари з низьким запасом -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold">Товари з низьким запасом</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Товар</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Залишок</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Мінімум</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach (array_slice($dashboardData['low_stock_products'], 0, 5) as $product): ?>
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full" src="../assets/images/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></div>
                                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($product['category_name']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                <?= $product['stock_quantity'] ?> шт.
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?= $product['min_stock'] ?> шт.</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                                            <a href="add_stock.php?id=<?= $product['id'] ?>" class="text-blue-600 hover:underline">Додати</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 border-t">
                            <a href="low_stock.php" class="text-blue-600 hover:underline">Переглянути всі товари з низьким запасом &rarr;</a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    // Графік транзакцій за тиждень
    const transactionsCtx = document.getElementById('transactionsChart').getContext('2d');
    const transactionsData = <?= json_encode($dashboardData['transaction_stats']) ?>;
    
    const dates = transactionsData.map(item => item.date);
    const inQuantities = transactionsData.map(item => item.in_quantity || 0);
    const outQuantities = transactionsData.map(item => item.out_quantity || 0);
    const inCounts = transactionsData.map(item => item.in_count || 0);
    const outCounts = transactionsData.map(item => item.out_count || 0);
    
    const transactionsChart = new Chart(transactionsCtx, {
        type: 'bar',
        data: {
            labels: dates.map(date => date.substring(5)), // format: MM-DD
            datasets: [
                {
                    label: 'Надходження (шт.)',
                    data: inQuantities,
                    backgroundColor: 'rgba(16, 185, 129, 0.5)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'Списання (шт.)',
                    data: outQuantities,
                    backgroundColor: 'rgba(239, 68, 68, 0.5)',
                    borderColor: 'rgba(239, 68, 68, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'Транзакції надходження',
                    data: inCounts,
                    type: 'line',
                    fill: false,
                    backgroundColor: 'rgba(16, 185, 129, 1)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    yAxisID: 'y1'
                },
                {
                    label: 'Транзакції списання',
                    data: outCounts,
                    type: 'line',
                    fill: false,
                    backgroundColor: 'rgba(239, 68, 68, 1)',
                    borderColor: 'rgba(239, 68, 68, 1)',
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
                        text: 'Кількість (шт.)'
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
                        text: 'Кількість транзакцій'
                    }
                }
            }
        }
    });
    </script>
</body>
</html>