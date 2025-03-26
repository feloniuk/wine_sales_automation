$topProducts = $salesController->getTopSellingProducts(10);

// Отримуємо статистику по категоріях
$categoryStats = $salesController->getPopularCategories(10);

// Групуємо статистику продажів за обраним періодом
function groupSalesData($salesStats, $period) {
    $groupedData = [];
    foreach ($salesStats as $stat) {
        $date = new DateTime($stat['date']);
        
        switch ($period) {
            case 'daily':
                $key = $date->format('Y-m-d');
                break;
            case 'weekly':
                $key = $date->format('Y-W');
                break;
            case 'monthly':
            default:
                $key = $date->format('Y-m');
                break;
        }
        
        if (!isset($groupedData[$key])) {
            $groupedData[$key] = [
                'total_sales' => 0,
                'order_count' => 0
            ];
        }
        
        $groupedData[$key]['total_sales'] += $stat['total_sales'];
        $groupedData[$key]['order_count'] += $stat['order_count'];
    }
    
    ksort($groupedData);
    return $groupedData;
}

$groupedSalesData = groupSalesData($salesStats, $period);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналітика продажів - Винна крамниця</title>
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
                <a href="index.php" class="flex items-center px-4 py-3 hover:bg-green-700">
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
                <a href="analytics.php" class="flex items-center px-4 py-3 bg-green-700">
                    <i class="fas fa-chart-line mr-3"></i>
                    <span>Аналітика</span>
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
                    <h1 class="text-2xl font-semibold text-gray-800">Аналітика продажів</h1>
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
                <!-- Фільтр аналітики -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <form action="analytics.php" method="GET" class="flex items-end space-x-4">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Початкова дата</label>
                            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">Кінцева дата</label>
                            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <div>
                            <label for="period" class="block text-sm font-medium text-gray-700">Період</label>
                            <select id="period" name="period" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                                <option value="daily" <?= $period === 'daily' ? 'selected' : '' ?>>Щоденно</option>
                                <option value="weekly" <?= $period === 'weekly' ? 'selected' : '' ?>>Щотижня</option>
                                <option value="monthly" <?= $period === 'monthly' ? 'selected' : '' ?>>Щомісяця</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                                <i class="fas fa-filter mr-2"></i> Застосувати
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Блок графіків -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Графік динаміки продажів -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Динаміка продажів</h2>
                        <canvas id="salesTrendChart" height="300"></canvas>
                    </div>

                    <!-- Графік категорій -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Продажі за категоріями</h2>
                        <canvas id="categoriesSalesChart" height="300"></canvas>
                    </div>
                </div>

                <!-- Блок таблиць -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Топ клієнтів -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Топ клієнтів</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клієнт</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Замовлень</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($topCustomers as $customer): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($customer['name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= $customer['order_count'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= number_format($customer['total_spent'], 2) ?> ₴</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Топ продуктів -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Топ товарів</h2>
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
                                    <?php foreach ($topProducts as $product): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($product['name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($product['category_name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= $product['total_quantity'] ?> шт.</td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= number_format($product['total_sales'], 2) ?> ₴</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Графік динаміки продажів
        const salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');
        const groupedSalesData = <?= json_encode($groupedSalesData) ?>;
        
        const salesTrendChart = new Chart(salesTrendCtx, {
            type: 'line',
            data: {
                labels: Object.keys(groupedSalesData),
                datasets: [
                    {
                        label: 'Замовлення',
                        data: Object.values(groupedSalesData).map(item => item.order_count),
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    },
                    {
                        label: 'Продажі (грн)',
                        data: Object.values(groupedSalesData).map(item => item.total_sales),
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Графік продажів за категоріями
        const categoriesSalesCtx = document.getElementById('categoriesSalesChart').getContext('2d');
        const categoryStats = <?= json_encode($categoryStats) ?>;
        
        const categoriesSalesChart = new Chart(categoriesSalesCtx, {
            type: 'pie',
            data: {
                labels: categoryStats.map(item => item.name),
                datasets: [{
                    data: categoryStats.map(item => item.total_sales),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(199, 199, 199, 0.8)',
                        'rgba(83, 102, 255, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    </script>
</body>
</html>