<?php
// views/warehouse/reports.php
// Сторінка звітів для начальника складу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/WarehouseController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('warehouse')) {
    header('Location: /login.php?redirect=warehouse/reports');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер складу
$warehouseController = new WarehouseController();

// Отримання параметрів звіту
$reportType = isset($_GET['type']) ? $_GET['type'] : 'inventory';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Отримання даних залежно від типу звіту
$reportData = [];
$reportTitle = '';

switch ($reportType) {
    case 'inventory':
        $reportTitle = 'Звіт з інвентаризації';
        $reportData = $warehouseController->getAllProducts();
        break;
    case 'transactions':
        $reportTitle = 'Звіт з транзакцій (' . $startDate . ' - ' . $endDate . ')';
        // Використовуємо поточну сторінку і великий розмір на сторінку, щоб отримати всі дані
        $transactionsData = $warehouseController->filterTransactions(null, null, 1, 10000);
        $reportData = $transactionsData['data'];
        // Фільтруємо за датою
        $reportData = array_filter($reportData, function($item) use ($startDate, $endDate) {
            $itemDate = substr($item['created_at'], 0, 10);
            return $itemDate >= $startDate && $itemDate <= $endDate;
        });
        break;
    case 'low_stock':
        $reportTitle = 'Звіт про товари з низьким запасом';
        $allProducts = $warehouseController->getAllProducts();
        $reportData = array_filter($allProducts, function($product) {
            return $product['stock_quantity'] <= $product['min_stock'];
        });
        break;
    case 'orders':
        $reportTitle = 'Звіт по замовленнях (' . $startDate . ' - ' . $endDate . ')';
        $allOrders = $warehouseController->getPendingOrders();
        $reportData = array_filter($allOrders, function($order) use ($startDate, $endDate) {
            $orderDate = substr($order['created_at'], 0, 10);
            return $orderDate >= $startDate && $orderDate <= $endDate;
        });
        break;
    default:
        $reportTitle = 'Звіт з інвентаризації';
        $reportData = $warehouseController->getAllProducts();
        break;
}

// Генерування CSV-файлу для експорту
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.csv"');
    
    // Створюємо PHP output stream
    $output = fopen('php://output', 'w');
    
    // BOM для UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Заголовки колонок залежно від типу звіту
    switch ($reportType) {
        case 'inventory':
            fputcsv($output, ['ID', 'Назва', 'Категорія', 'Ціна', 'Кількість', 'Мін. запас', 'Статус']);
            foreach ($reportData as $row) {
                $status = 'Достатній запас';
                if ($row['stock_quantity'] <= 0) {
                    $status = 'Відсутній';
                } elseif ($row['stock_quantity'] <= $row['min_stock']) {
                    $status = 'Низький запас';
                }
                
                fputcsv($output, [
                    $row['id'],
                    $row['name'],
                    $row['category_name'],
                    $row['price'],
                    $row['stock_quantity'],
                    $row['min_stock'],
                    $status
                ]);
            }
            break;
        case 'transactions':
            fputcsv($output, ['ID', 'Дата', 'Товар', 'Тип операції', 'Кількість', 'Причина', 'Створив']);
            foreach ($reportData as $row) {
                $transactionType = $row['transaction_type'] === 'in' ? 'Надходження' : 'Списання';
                
                fputcsv($output, [
                    $row['id'],
                    $row['created_at'],
                    $row['product_name'],
                    $transactionType,
                    $row['quantity'],
                    $row['reference_type'],
                    $row['user_name'] ?? 'Система'
                ]);
            }
            break;
        case 'low_stock':
            fputcsv($output, ['ID', 'Назва', 'Категорія', 'Ціна', 'Кількість', 'Мін. запас', 'Різниця']);
            foreach ($reportData as $row) {
                fputcsv($output, [
                    $row['id'],
                    $row['name'],
                    $row['category_name'],
                    $row['price'],
                    $row['stock_quantity'],
                    $row['min_stock'],
                    $row['stock_quantity'] - $row['min_stock']
                ]);
            }
            break;
        case 'orders':
            fputcsv($output, ['ID', 'Дата', 'Клієнт', 'Статус', 'Оплата', 'Кількість товарів', 'Сума']);
            foreach ($reportData as $row) {
                fputcsv($output, [
                    $row['id'],
                    $row['created_at'],
                    $row['customer_name'],
                    $row['status'],
                    $row['payment_status'],
                    $row['items_count'] ?? '-',
                    $row['total_amount']
                ]);
            }
            break;
    }
    
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Звіти - Винна крамниця</title>
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
                <a href="orders.php" class="flex items-center px-4 py-3 hover:bg-blue-800">
                    <i class="fas fa-shipping-fast mr-3"></i>
                    <span>Замовлення</span>
                </a>
                <a href="transactions.php" class="flex items-center px-4 py-3 hover:bg-blue-800">
                    <i class="fas fa-exchange-alt mr-3"></i>
                    <span>Транзакції</span>
                </a>
                <a href="reports.php" class="flex items-center px-4 py-3 bg-blue-800">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Звіти</h1>
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
                <!-- Вибір типу звіту та параметрів -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold mb-4">Параметри звіту</h2>
                    <form action="reports.php" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <div class="md:col-span-2">
                            <label for="report-type" class="block text-sm font-medium text-gray-700 mb-1">Тип звіту</label>
                            <select id="report-type" name="type" class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="inventory" <?= $reportType === 'inventory' ? 'selected' : '' ?>>Інвентаризація</option>
                                <option value="transactions" <?= $reportType === 'transactions' ? 'selected' : '' ?>>Транзакції</option>
                                <option value="low_stock" <?= $reportType === 'low_stock' ? 'selected' : '' ?>>Товари з низьким запасом</option>
                                <option value="orders" <?= $reportType === 'orders' ? 'selected' : '' ?>>Замовлення</option>
                            </select>
                        </div>
                        
                        <div class="date-fields <?= in_array($reportType, ['transactions', 'orders']) ? '' : 'hidden' ?>">
                            <label for="start-date" class="block text-sm font-medium text-gray-700 mb-1">Початкова дата</label>
                            <input type="date" id="start-date" name="start_date" value="<?= $startDate ?>" class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="date-fields <?= in_array($reportType, ['transactions', 'orders']) ? '' : 'hidden' ?>">
                            <label for="end-date" class="block text-sm font-medium text-gray-700 mb-1">Кінцева дата</label>
                            <input type="date" id="end-date" name="end_date" value="<?= $endDate ?>" class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded w-full">
                                <i class="fas fa-filter mr-2"></i> Застосувати
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Заголовок та кнопки експорту -->
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-semibold text-gray-800"><?= $reportTitle ?></h2>
                    <div>
                        <a href="reports.php?type=<?= $reportType ?>&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&export=csv" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded inline-flex items-center">
                            <i class="fas fa-file-csv mr-2"></i> Експорт CSV
                        </a>
                    </div>
                </div>
                
                <!-- Відображення даних звіту -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <?php switch ($reportType): ?>
                        <?php case 'inventory': ?>
                            <!-- Звіт інвентаризації -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Товар</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Категорія</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ціна</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Кількість</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Мін. запас</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($reportData as $product): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?= $product['id'] ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <img class="h-10 w-10 rounded-full object-cover" 
                                                            src="../../assets/images/<?= $product['image'] ?>" 
                                                            alt="<?= htmlspecialchars($product['name']) ?>">
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= htmlspecialchars($product['category_name']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?= number_format($product['price'], 2) ?> ₴
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?= $product['stock_quantity'] <= $product['min_stock'] ? 'text-red-600' : 'text-green-600' ?>">
                                                <?= $product['stock_quantity'] ?> шт.
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= $product['min_stock'] ?> шт.
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($product['stock_quantity'] <= 0): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Відсутній
                                                </span>
                                                <?php elseif ($product['stock_quantity'] <= $product['min_stock']): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Низький запас
                                                </span>
                                                <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Достатній запас
                                                </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Графік розподілу запасів за категоріями -->
                            <div class="p-6 bg-gray-50 border-t">
                                <h3 class="text-lg font-semibold mb-4">Розподіл запасів за категоріями</h3>
                                <div class="h-64">
                                    <canvas id="inventoryChart"></canvas>
                                </div>
                            </div>
                            <?php break; ?>
                            
                        <?php case 'transactions': ?>
                            <!-- Звіт транзакцій -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Товар</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Тип</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Кількість</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Причина</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Користувач</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($reportData as $transaction): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $transaction['id'] ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d.m.Y H:i', strtotime($transaction['created_at'])) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($transaction['product_name']) ?></div>
                                                <div class="text-sm text-gray-500">ID: <?= $transaction['product_id'] ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($transaction['transaction_type'] === 'in'): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Надходження
                                                </span>
                                                <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Списання
                                                </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $transaction['quantity'] ?> шт.</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php
                                                switch ($transaction['reference_type']) {
                                                    case 'production': echo 'Поставка від виробника'; break;
                                                    case 'adjustment': echo 'Коригування запасів'; break;
                                                    case 'return': echo 'Повернення'; break;
                                                    case 'order': echo 'Замовлення #' . $transaction['reference_id']; break;
                                                    default: echo $transaction['reference_type']; break;
                                                }
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($transaction['user_name'] ?? 'Система') ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($reportData)): ?>
                                        <tr>
                                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                                Немає транзакцій за обраний період
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Графік транзакцій за період -->
                            <div class="p-6 bg-gray-50 border-t">
                                <h3 class="text-lg font-semibold mb-4">Транзакції за період</h3>
                                <div class="h-64">
                                    <canvas id="transactionsChart"></canvas>
                                </div>
                            </div>
                            <?php break; ?>
                            
                        <?php case 'low_stock': ?>
                            <!-- Звіт товарів з низьким запасом -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Товар</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Категорія</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ціна</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Кількість</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Мін. запас</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Різниця</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php if (empty($reportData)): ?>
                                        <tr>
                                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                                Немає товарів з низьким запасом
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($reportData as $product): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?= $product['id'] ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <img class="h-10 w-10 rounded-full object-cover" 
                                                            src="../../assets/images/<?= $product['image'] ?>" 
                                                            alt="<?= htmlspecialchars($product['name']) ?>">
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= htmlspecialchars($product['category_name']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?= number_format($product['price'], 2) ?> ₴
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600">
                                                <?= $product['stock_quantity'] ?> шт.
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= $product['min_stock'] ?> шт.
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600">
                                                <?= $product['stock_quantity'] - $product['min_stock'] ?> шт.
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="add_stock.php?id=<?= $product['id'] ?>" class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-plus-circle mr-1"></i> Додати
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Графік товарів з низьким запасом -->
                            <?php if (!empty($reportData)): ?>
                            <div class="p-6 bg-gray-50 border-t">
                                <h3 class="text-lg font-semibold mb-4">Товари з найнижчим запасом</h3>
                                <div class="h-64">
                                    <canvas id="lowStockChart"></canvas>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php break; ?>
                            
                        <?php case 'orders': ?>
                            <!-- Звіт замовлень -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
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
                                        <?php if (empty($reportData)): ?>
                                        <tr>
                                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                                Немає замовлень за обраний період
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($reportData as $order): ?>
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
                                                        $statusText = 'Готове';
                                                        break;
                                                    case 'shipped':
                                                        $statusClass = 'bg-purple-100 text-purple-800';
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
                                                <?= isset($order['items_count']) ? $order['items_count'] : '-' ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?= number_format($order['total_amount'], 2) ?> ₴
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="order_details.php?id=<?= $order['id'] ?>" class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-eye"></i> Деталі
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Графік замовлень за період -->
                            <?php if (!empty($reportData)): ?>
                            <div class="p-6 bg-gray-50 border-t">
                                <h3 class="text-lg font-semibold mb-4">Замовлення за період</h3>
                                <div class="h-64">
                                    <canvas id="ordersChart"></canvas>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php break; ?>
                    <?php endswitch; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Відображення/приховання полів дат залежно від типу звіту
        document.getElementById('report-type').addEventListener('change', function() {
            const dateFields = document.querySelectorAll('.date-fields');
            if (this.value === 'transactions' || this.value === 'orders') {
                dateFields.forEach(field => field.classList.remove('hidden'));
            } else {
                dateFields.forEach(field => field.classList.add('hidden'));
            }
        });
        
        // Створення графіків
        document.addEventListener('DOMContentLoaded', function() {
            // Графік інвентаризації
            <?php if ($reportType === 'inventory'): ?>
            const inventoryCtx = document.getElementById('inventoryChart').getContext('2d');
            
            // Групуємо товари за категоріями
            const categories = <?= json_encode(array_unique(array_column($reportData, 'category_name'))) ?>;
            const stockData = [];
            
            categories.forEach(category => {
                const categoryProducts = <?= json_encode($reportData) ?>.filter(product => product.category_name === category);
                const totalStock = categoryProducts.reduce((sum, product) => sum + parseInt(product.stock_quantity), 0);
                stockData.push(totalStock);
            });
            
            new Chart(inventoryCtx, {
                type: 'bar',
                data: {
                    labels: categories,
                    datasets: [{
                        label: 'Кількість товарів на складі',
                        data: stockData,
                        backgroundColor: 'rgba(59, 130, 246, 0.6)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Кількість (шт.)'
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
            
            // Графік транзакцій
            <?php if ($reportType === 'transactions' && !empty($reportData)): ?>
            const transactionsCtx = document.getElementById('transactionsChart').getContext('2d');
            
            // Створюємо об'єкт для зберігання кількості транзакцій за датами
            const transactions = {};
            <?= json_encode($reportData) ?>.forEach(transaction => {
                const date = transaction.created_at.substring(0, 10);
                if (!transactions[date]) {
                    transactions[date] = { in: 0, out: 0 };
                }
                
                if (transaction.transaction_type === 'in') {
                    transactions[date].in += parseInt(transaction.quantity);
                } else {
                    transactions[date].out += parseInt(transaction.quantity);
                }
            });
            
            // Сортуємо дати
            const dates = Object.keys(transactions).sort();
            const inData = dates.map(date => transactions[date].in);
            const outData = dates.map(date => transactions[date].out);
            
            new Chart(transactionsCtx, {
                type: 'bar',
                data: {
                    labels: dates,
                    datasets: [
                        {
                            label: 'Надходження',
                            data: inData,
                            backgroundColor: 'rgba(16, 185, 129, 0.6)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Списання',
                            data: outData,
                            backgroundColor: 'rgba(239, 68, 68, 0.6)',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Кількість (шт.)'
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
            
            // Графік товарів з низьким запасом
            <?php if ($reportType === 'low_stock' && !empty($reportData)): ?>
            const lowStockCtx = document.getElementById('lowStockChart').getContext('2d');
            
            // Отримуємо топ-10 товарів з найнижчим запасом
            const lowStockProducts = <?= json_encode($reportData) ?>
                .sort((a, b) => (a.stock_quantity - a.min_stock) - (b.stock_quantity - b.min_stock))
                .slice(0, 10);
                
            const productNames = lowStockProducts.map(product => product.name);
            const stockValues = lowStockProducts.map(product => product.stock_quantity);
            const minStockValues = lowStockProducts.map(product => product.min_stock);
            
            new Chart(lowStockCtx, {
                type: 'bar',
                data: {
                    labels: productNames,
                    datasets: [
                        {
                            label: 'Поточний запас',
                            data: stockValues,
                            backgroundColor: 'rgba(59, 130, 246, 0.6)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Мінімальний запас',
                            data: minStockValues,
                            backgroundColor: 'rgba(217, 119, 6, 0.6)',
                            borderColor: 'rgba(217, 119, 6, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Кількість (шт.)'
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
            
            // Графік замовлень
            <?php if ($reportType === 'orders' && !empty($reportData)): ?>
            const ordersCtx = document.getElementById('ordersChart').getContext('2d');
            
            // Створюємо об'єкт для зберігання кількості замовлень за датами
            const orders = {};
            <?= json_encode($reportData) ?>.forEach(order => {
                const date = order.created_at.substring(0, 10);
                if (!orders[date]) {
                    orders[date] = {
                        count: 0,
                        amount: 0
                    };
                }
                
                orders[date].count++;
                orders[date].amount += parseFloat(order.total_amount);
            });
            
            // Сортуємо дати
            const orderDates = Object.keys(orders).sort();
            const orderCounts = orderDates.map(date => orders[date].count);
            const orderAmounts = orderDates.map(date => orders[date].amount);
            
            new Chart(ordersCtx, {
                type: 'bar',
                data: {
                    labels: orderDates,
                    datasets: [
                        {
                            label: 'Кількість замовлень',
                            data: orderCounts,
                            backgroundColor: 'rgba(59, 130, 246, 0.6)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Сума замовлень (₴)',
                            data: orderAmounts,
                            type: 'line',
                            fill: false,
                            backgroundColor: 'rgba(16, 185, 129, 1)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Кількість замовлень'
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
                                text: 'Сума замовлень (₴)'
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>