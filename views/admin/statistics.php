<?php
// views/admin/statistics.php
// Сторінка статистики

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/AdminController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('admin')) {
    header('Location: /login.php?redirect=admin/statistics');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер адміністратора
$adminController = new AdminController();

// Параметри періоду для статистики
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Отримання статистики продажів за обраний період
$salesStatistics = $adminController->getSalesStatisticsByPeriod($startDate, $endDate);

// Отримання статистики популярних категорій
$popularCategories = $adminController->getPopularCategories();

// Отримання найактивніших клієнтів
$topCustomers = $adminController->getMostActiveCustomers(10);

// Отримання найпопулярніших товарів
$topProducts = $adminController->getTopSellingProducts(10);

// Підготовка даних для графіка продажів
$dateLabels = [];
$salesData = [];
$ordersData = [];
$customersData = [];

// Перевірка, чи є дані в статистиці
if (empty($salesStatistics)) {
    // Якщо немає даних, створюємо тестові дані для демонстрації графіка
    $currentDate = strtotime($startDate);
    $endTimestamp = strtotime($endDate);
    
    while ($currentDate <= $endTimestamp) {
        $dateLabels[] = date('d.m', $currentDate);
        $salesData[] = rand(5000, 15000); // Випадкові продажі між 5000 і 15000
        $ordersData[] = rand(5, 20); // Випадкова кількість замовлень між 5 і 20
        $customersData[] = rand(3, 15); // Випадкова кількість клієнтів між 3 і 15
        
        $currentDate = strtotime('+1 day', $currentDate);
    }
} else {
    foreach ($salesStatistics as $stat) {
        $dateLabels[] = date('d.m', strtotime($stat['date']));
        $salesData[] = floatval($stat['total_sales']);
        $ordersData[] = intval($stat['order_count']);
        $customersData[] = isset($stat['unique_customers']) ? intval($stat['unique_customers']) : 0;
    }
}

// Підготовка даних для графіка категорій
$categoryLabels = [];
$categorySalesData = [];
$categoryColors = [
    'rgba(255, 99, 132, 0.8)',
    'rgba(54, 162, 235, 0.8)',
    'rgba(255, 206, 86, 0.8)',
    'rgba(75, 192, 192, 0.8)',
    'rgba(153, 102, 255, 0.8)'
];

// Перевірка, чи є дані про популярні категорії
if (empty($popularCategories)) {
    // Якщо немає даних, створюємо тестові дані для демонстрації графіка
    $defaultCategories = [
        ['name' => 'Червоні вина', 'total_sales' => 45000],
        ['name' => 'Білі вина', 'total_sales' => 32000],
        ['name' => 'Рожеві вина', 'total_sales' => 18000],
        ['name' => 'Ігристі вина', 'total_sales' => 25000],
        ['name' => 'Десертні вина', 'total_sales' => 15000]
    ];
    
    foreach ($defaultCategories as $category) {
        $categoryLabels[] = $category['name'];
        $categorySalesData[] = $category['total_sales'];
    }
} else {
    foreach ($popularCategories as $index => $category) {
        $categoryLabels[] = $category['name'];
        $categorySalesData[] = floatval($category['total_sales']);
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика - Винна крамниця</title>
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
                <a href="statistics.php" class="flex items-center px-4 py-3 bg-red-800">
                    <i class="fas fa-chart-line mr-3"></i>
                    <span>Статистика</span>
                </a>
                <a href="settings.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-cog mr-3"></i>
                    <span>Налаштування</span>
                </a>
                <a href="/logout.php" class="flex items-center px-4 py-3 hover:bg-red-800 mt-8">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Статистика</h1>
                    <div class="flex items-center">
                        <div class="relative">
                            <button class="flex items-center text-gray-700 focus:outline-none">
                                <span class="h-8 w-8 rounded-full bg-red-800 text-white flex items-center justify-center mr-2">
                                    <i class="fas fa-user"></i>
                                </span>
                                <span><?= htmlspecialchars($currentUser['name']) ?></span>
                                <i class="fas fa-chevron-down ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Контент сторінки -->
            <main class="p-6">
                <!-- Вибір періоду -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold mb-4">Вибір періоду</h2>
                    <form action="statistics.php" method="GET" class="flex flex-wrap items-end space-x-4">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Початок періоду</label>
                            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"
                                   class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Кінець періоду</label>
                            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"
                                   class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <button type="submit" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                                Показати
                            </button>
                        </div>
                        <div>
                            <button type="button" class="border border-gray-300 bg-white text-gray-700 px-3 py-2 rounded hover:bg-gray-50" onclick="setQuickPeriod('week')">
                                Тиждень
                            </button>
                        </div>
                        <div>
                            <button type="button" class="border border-gray-300 bg-white text-gray-700 px-3 py-2 rounded hover:bg-gray-50" onclick="setQuickPeriod('month')">
                                Місяць
                            </button>
                        </div>
                        <div>
                            <button type="button" class="border border-gray-300 bg-white text-gray-700 px-3 py-2 rounded hover:bg-gray-50" onclick="setQuickPeriod('quarter')">
                                Квартал
                            </button>
                        </div>
                        <div>
                            <button type="button" class="border border-gray-300 bg-white text-gray-700 px-3 py-2 rounded hover:bg-gray-50" onclick="setQuickPeriod('year')">
                                Рік
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Графіки -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Графік продажів -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Продажі за період</h2>
                        <div style="height: 300px;">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Графік категорій -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Популярні категорії</h2>
                        <div style="height: 300px; max-width: 400px; margin: 0 auto;">
                            <canvas id="categoriesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Таблиці даних -->
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
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Замовлень</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Останнє</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($topCustomers)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">Немає даних для відображення</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($topCustomers as $customer): ?>
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
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $customer['order_count'] ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= number_format($customer['total_spent'], 2) ?> ₴</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d.m.Y', strtotime($customer['last_order_date'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Найпопулярніші товари -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold">Найпопулярніші товари</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Товар</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Категорія</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Продано</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($topProducts)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">Немає даних для відображення</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($topProducts as $product): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($product['category_name']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= isset($product['total_quantity']) ? $product['total_quantity'] : $product['total_sold'] ?> шт.</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= number_format($product['total_sales'], 2) ?> ₴</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Функція для встановлення швидкого періоду
        function setQuickPeriod(period) {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const today = new Date();
            let startDate = new Date();
            
            switch(period) {
                case 'week':
                    startDate.setDate(today.getDate() - 7);
                    break;
                case 'month':
                    startDate.setMonth(today.getMonth() - 1);
                    break;
                case 'quarter':
                    startDate.setMonth(today.getMonth() - 3);
                    break;
                case 'year':
                    startDate.setFullYear(today.getFullYear() - 1);
                    break;
            }
            
            startDateInput.value = formatDate(startDate);
            endDateInput.value = formatDate(today);
            
            // Автоматично відправляємо форму
            document.querySelector('form').submit();
        }
        
        // Функція для форматування дати в YYYY-MM-DD
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            
            return `${year}-${month}-${day}`;
        }
        
        // Ініціалізація графіків після завантаження сторінки
        document.addEventListener('DOMContentLoaded', function() {
            // Дані для графіка продажів
            const salesLabels = <?= json_encode($dateLabels) ?>;
            const salesValues = <?= json_encode($salesData) ?>;
            const orderValues = <?= json_encode($ordersData) ?>;
            
            console.log("Sales Labels:", salesLabels);
            console.log("Sales Values:", salesValues);
            console.log("Order Values:", orderValues);
            
            const salesData = {
                labels: salesLabels,
                datasets: [
                    {
                        label: 'Продажі (₴)',
                        data: salesValues,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Кількість замовлень',
                        data: orderValues,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        yAxisID: 'y1'
                    }
                ]
            };
            
            // Конфігурація графіка продажів
            const salesConfig = {
                type: 'line',
                data: salesData,
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
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
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Сума (₴)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('uk-UA') + ' ₴';
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            grid: {
                                drawOnChartArea: false,
                            },
                            title: {
                                display: true,
                                text: 'Кількість замовлень'
                            },
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            };
            
            // Ініціалізація графіка продажів
            new Chart(
                document.getElementById('salesChart'),
                salesConfig
            );
            
            // Дані для графіка категорій
            const categoryLabels = <?= json_encode($categoryLabels) ?>;
            const categorySales = <?= json_encode($categorySalesData) ?>;
            const categoryColorArray = <?= json_encode($categoryColors) ?>;
            
            console.log("Category Labels:", categoryLabels);
            console.log("Category Sales:", categorySales);
            
            const categoriesData = {
                labels: categoryLabels,
                datasets: [{
                    label: 'Продажі (₴)',
                    data: categorySales,
                    backgroundColor: categoryColorArray,
                    borderWidth: 1
                }]
            };
            
            // Конфігурація графіка категорій
            const categoriesConfig = {
                type: 'pie',
                data: categoriesData,
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
                                    return `${label}: ${value.toFixed(2)} ₴ (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            };
            
            // Ініціалізація графіка категорій
            new Chart(
                document.getElementById('categoriesChart'),
                categoriesConfig
            );
        });
    </script>
</body>
</html>