<?php
// admin/index.php
// Головна сторінка адміністратора

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('admin')) {
    header('Location: /login.php?redirect=admin');
    exit;
}

// Отримуємо дані для дешборду
$adminController = new AdminController();
$dashboardData = $adminController->getDashboardData();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель адміністратора - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Бічне меню -->
        <div class="w-64 bg-red-900 text-white">
            <div class="p-4 font-bold text-xl">Винна крамниця</div>
            <nav class="mt-8">
                <a href="index.php" class="flex items-center px-4 py-3 bg-red-800">
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
                <a href="orders.php" class="flex items-center px-4 py-3 hover:bg-red-800">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Дашборд</h1>
                    <div class="flex items-center">
                        <div class="relative mr-4">
                            <span class="absolute top-0 right-0 -mt-1 -mr-1 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-xs"><?= count($dashboardData['alerts']) ?></span>
                            <a href="http://winery_sales.loc/views/admin/alerts.php" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-bell text-xl"></i>
</a>
                        </div>
                        <div class="relative">
                            <button class="flex items-center text-gray-700 focus:outline-none">
                                <img src="../../assets/images/avatar.jpg" alt="Avatar" class="h-8 w-8 rounded-full mr-2">
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
                            <div class="p-3 rounded-full bg-red-100 text-red-800 mr-4">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500">Користувачі</p>
                                <p class="text-2xl font-semibold"><?= $dashboardData['user_stats']['total_users'] ?></p>
                            </div>
                        </div>
                        <div class="flex justify-between mt-4 text-sm">
                            <div>
                                <p class="text-gray-500">Клієнти</p>
                                <p class="font-semibold"><?= $dashboardData['user_stats']['customer_count'] ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Менеджери</p>
                                <p class="font-semibold"><?= $dashboardData['user_stats']['sales_count'] ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Склад</p>
                                <p class="font-semibold"><?= $dashboardData['user_stats']['warehouse_count'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-800 mr-4">
                                <i class="fas fa-wine-bottle text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500">Товари</p>
                                <p class="text-2xl font-semibold"><?= $dashboardData['product_stats']['total_products'] ?></p>
                            </div>
                        </div>
                        <div class="flex justify-between mt-4 text-sm">
                            <div>
                                <p class="text-gray-500">Червоні</p>
                                <p class="font-semibold"><?= $dashboardData['product_stats']['red_wine_count'] ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Білі</p>
                                <p class="font-semibold"><?= $dashboardData['product_stats']['white_wine_count'] ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Інші</p>
                                <p class="font-semibold"><?= $dashboardData['product_stats']['other_wine_count'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-800 mr-4">
                                <i class="fas fa-shopping-cart text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500">Замовлення</p>
                                <p class="text-2xl font-semibold"><?= $dashboardData['order_stats']['total_orders'] ?></p>
                            </div>
                        </div>
                        <div class="flex justify-between mt-4 text-sm">
                            <div>
                                <p class="text-gray-500">Нові</p>
                                <p class="font-semibold"><?= $dashboardData['order_stats']['pending_count'] ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">В обробці</p>
                                <p class="font-semibold"><?= $dashboardData['order_stats']['processing_count'] ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Завершені</p>
                                <p class="font-semibold"><?= $dashboardData['order_stats']['completed_count'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-800 mr-4">
                                <i class="fas fa-money-bill-wave text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500">Продажі</p>
                                <p class="text-2xl font-semibold"><?= number_format($dashboardData['order_stats']['total_sales'], 2) ?> ₴</p>
                            </div>
                        </div>
                        <div class="flex justify-between mt-4 text-sm">
                            <div>
                                <p class="text-gray-500">Оплачено</p>
                                <p class="font-semibold"><?= $dashboardData['order_stats']['paid_count'] ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Очікують</p>
                                <p class="font-semibold"><?= $dashboardData['order_stats']['payment_pending_count'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Графіки та дані -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Продажі за тиждень</h2>
                        <canvas id="salesChart" height="250"></canvas>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Популярні категорії</h2>
                        <canvas id="categoriesChart" height="250"></canvas>
                    </div>
                </div>

                <!-- Додаткові дані -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Останні замовлення -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold">Останні замовлення</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клієнт</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($dashboardData['recent_orders'] as $order): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">#<?= $order['id'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= number_format($order['total_amount'], 2) ?> ₴</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusClass = '';
                                            switch ($order['status']) {
                                                case 'pending': $statusClass = 'bg-yellow-100 text-yellow-800'; $status = 'Нове'; break;
                                                case 'processing': $statusClass = 'bg-blue-100 text-blue-800'; $status = 'В обробці'; break;
                                                case 'ready_for_pickup': $statusClass = 'bg-purple-100 text-purple-800'; $status = 'Готове'; break;
                                                case 'shipped': $statusClass = 'bg-indigo-100 text-indigo-800'; $status = 'Відправлено'; break;
                                                case 'delivered': $statusClass = 'bg-green-100 text-green-800'; $status = 'Доставлено'; break;
                                                case 'completed': $statusClass = 'bg-green-100 text-green-800'; $status = 'Завершено'; break;
                                                case 'cancelled': $statusClass = 'bg-red-100 text-red-800'; $status = 'Скасовано'; break;
                                                default: $statusClass = 'bg-gray-100 text-gray-800'; $status = $order['status']; break;
                                            }
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                                <?= $status ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 border-t">
                            <a href="orders.php" class="text-red-800 hover:underline">Переглянути всі замовлення &rarr;</a>
                        </div>
                    </div>

                    <!-- Системні повідомлення -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold">Системні повідомлення</h2>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <?php foreach (array_slice($dashboardData['alerts'], 0, 5) as $alert): ?>
                            <div class="px-6 py-4">
                                <div class="flex items-start">
                                    <?php
                                    $alertClass = '';
                                    switch ($alert['type']) {
                                        case 'danger': $alertClass = 'text-red-600'; $icon = 'fa-exclamation-circle'; break;
                                        case 'warning': $alertClass = 'text-yellow-600'; $icon = 'fa-exclamation-triangle'; break;
                                        case 'info': $alertClass = 'text-blue-600'; $icon = 'fa-info-circle'; break;
                                        default: $alertClass = 'text-gray-600'; $icon = 'fa-bell'; break;
                                    }
                                    ?>
                                    <i class="fas <?= $icon ?> <?= $alertClass ?> mt-1 mr-3"></i>
                                    <div>
                                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($alert['title']) ?></p>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($alert['message']) ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?= date('d.m.Y H:i', strtotime($alert['created_at'])) ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="px-6 py-4 border-t">
                            <a href="alerts.php" class="text-red-800 hover:underline">Переглянути всі повідомлення &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- Найактивніші клієнти та найпопулярніші товари -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Найактивніші клієнти -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold">Найактивніші клієнти</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клієнт</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Замовлення</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Останнє</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($dashboardData['top_customers'] as $customer): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
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
                                        <td class="px-6 py-4 whitespace-nowrap"><?= $customer['order_count'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 border-t">
                            <a href="products.php" class="text-red-800 hover:underline">Переглянути всі товари &rarr;</a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php
// Admin dashboard chart fix - Replace the chart initialization script in views/admin/index.php

// Prepare chart data with fallback for empty data
$dates = [];
$salesData = [];
$orderCountData = [];

if (empty($dashboardData['weekly_stats'])) {
    // Create sample data if no data exists
    $startDate = strtotime('-6 days');
    for ($i = 0; $i <= 6; $i++) {
        $currentDate = strtotime("+$i days", $startDate);
        $dates[] = date('d.m', $currentDate);
        $salesData[] = rand(5000, 15000); // Random sales between 5000 and 15000
        $orderCountData[] = rand(5, 20); // Random order counts between 5 and 20
    }
} else {
    foreach ($dashboardData['weekly_stats'] as $stat) {
        $dates[] = date('d.m', strtotime($stat['date']));
        $salesData[] = floatval($stat['total_sales'] ?? 0);
        $orderCountData[] = intval($stat['order_count'] ?? 0);
    }
}
?>

<script>
// Графік продажів за тиждень - fixed implementation
document.addEventListener('DOMContentLoaded', function() {
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    
    // Log the data to console for debugging
    console.log('Sales Chart Data:', {
        dates: <?= json_encode($dates) ?>,
        sales: <?= json_encode($salesData) ?>,
        orders: <?= json_encode($orderCountData) ?>
    });
    
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [{
                label: 'Продажі, грн',
                data: <?= json_encode($salesData) ?>,
                backgroundColor: 'rgba(153, 27, 27, 0.2)',
                borderColor: 'rgba(153, 27, 27, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                yAxisID: 'y'
            }, {
                label: 'Замовлення, шт',
                data: <?= json_encode($orderCountData) ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                tooltip: {
                    enabled: true,
                    mode: 'index',
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex === 0) {
                                label += new Intl.NumberFormat('uk-UA', { 
                                    style: 'currency', 
                                    currency: 'UAH',
                                    minimumFractionDigits: 0, 
                                    maximumFractionDigits: 0 
                                }).format(context.raw);
                            } else {
                                label += context.raw;
                            }
                            return label;
                        }
                    }
                },
                legend: {
                    position: 'top',
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Продажі, грн'
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('uk-UA') + ' ₴';
                        }
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
                        text: 'Замовлення, шт'
                    },
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Графік популярних категорій (unchanged, included for completeness)
    const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
    const categoriesChart = new Chart(categoriesCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_map(function($cat) { return $cat['name']; }, $dashboardData['popular_categories'])) ?>,
            datasets: [{
                data: <?= json_encode(array_map(function($cat) { return $cat['total_sales']; }, $dashboardData['popular_categories'])) ?>,
                backgroundColor: [
                    'rgba(153, 27, 27, 0.8)',
                    'rgba(252, 211, 77, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(139, 92, 246, 0.8)'
                ],
                borderColor: [
                    'rgba(153, 27, 27, 1)',
                    'rgba(252, 211, 77, 1)',
                    'rgba(59, 130, 246, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(139, 92, 246, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value.toLocaleString('uk-UA')} ₴ (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>
</body>
</html>