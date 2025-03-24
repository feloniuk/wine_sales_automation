<?php
// warehouse/transactions.php
// Сторінка історії транзакцій складу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/WarehouseController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('warehouse')) {
    header('Location: /login.php?redirect=warehouse/transactions');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер складу
$warehouseController = new WarehouseController();

// Отримуємо параметри фільтрації
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$type = isset($_GET['type']) ? $_GET['type'] : null;
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;

// Отримуємо транзакції з фільтрацією
$transactionsData = $warehouseController->filterTransactions($type, $productId, $page);
$transactions = $transactionsData['data'];
$pagination = $transactionsData['pagination'];

// Отримуємо список всіх товарів для фільтра
$allProducts = $warehouseController->getAllProducts();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Історія транзакцій - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                <a href="transactions.php" class="flex items-center px-4 py-3 bg-blue-800">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Історія транзакцій</h1>
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
                <!-- Фільтри -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold mb-4">Фільтрувати транзакції</h2>
                    <form action="transactions.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Тип транзакції</label>
                            <select id="type" name="type" class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Усі типи</option>
                                <option value="in" <?= $type === 'in' ? 'selected' : '' ?>>Надходження</option>
                                <option value="out" <?= $type === 'out' ? 'selected' : '' ?>>Списання</option>
                            </select>
                        </div>
                        <div>
                            <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">Товар</label>
                            <select id="product_id" name="product_id" class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Усі товари</option>
                                <?php foreach ($allProducts as $product): ?>
                                <option value="<?= $product['id'] ?>" <?= $productId === $product['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($product['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                                <i class="fas fa-filter mr-2"></i> Фільтрувати
                            </button>
                            <a href="transactions.php" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                <i class="fas fa-sync-alt mr-2"></i> Скинути
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Таблиця транзакцій -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold">Транзакції</h2>
                    </div>
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
                                <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        Транзакції не знайдено
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
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
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Пагінація -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="px-6 py-4 bg-gray-50 border-t">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-700">
                                Показано <?= ($pagination['current_page'] - 1) * $pagination['per_page'] + 1 ?> - 
                                <?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) ?> 
                                з <?= $pagination['total'] ?> транзакцій
                            </div>
                            <div class="flex space-x-1">
                                <?php if ($pagination['current_page'] > 1): ?>
                                <a href="?page=<?= $pagination['current_page'] - 1 ?><?= $type ? '&type=' . $type : '' ?><?= $productId ? '&product_id=' . $productId : '' ?>" 
                                   class="px-3 py-1 bg-white text-gray-700 border rounded hover:bg-gray-100">
                                    Попередня
                                </a>
                                <?php endif; ?>
                                
                                <?php
                                // Показуємо 5 номерів сторінок
                                $startPage = max(1, $pagination['current_page'] - 2);
                                $endPage = min($pagination['total_pages'], $startPage + 4);
                                $startPage = max(1, $endPage - 4);
                                
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                <a href="?page=<?= $i ?><?= $type ? '&type=' . $type : '' ?><?= $productId ? '&product_id=' . $productId : '' ?>" 
                                   class="px-3 py-1 <?= $i === $pagination['current_page'] ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border' ?> rounded hover:bg-gray-100">
                                    <?= $i ?>
                                </a>
                                <?php endfor; ?>
                                
                                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                <a href="?page=<?= $pagination['current_page'] + 1 ?><?= $type ? '&type=' . $type : '' ?><?= $productId ? '&product_id=' . $productId : '' ?>" 
                                   class="px-3 py-1 bg-white text-gray-700 border rounded hover:bg-gray-100">
                                    Наступна
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>