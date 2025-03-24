<?php
// warehouse/inventory.php
// Сторінка інвентаризації для начальника складу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/WarehouseController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('warehouse')) {
    header('Location: /login.php?redirect=warehouse/inventory');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер складу
$warehouseController = new WarehouseController();

// Отримуємо всі товари
$allProducts = $warehouseController->getAllProducts();

// Обробка форми інвентаризації
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_inventory'])) {
    $inventoryData = [];
    
    foreach ($_POST['actual_quantity'] as $productId => $quantity) {
        if (is_numeric($quantity)) {
            $inventoryData[] = [
                'product_id' => $productId,
                'actual_quantity' => intval($quantity),
                'notes' => isset($_POST['notes'][$productId]) ? $_POST['notes'][$productId] : ''
            ];
        }
    }
    
    if (!empty($inventoryData)) {
        $result = $warehouseController->performInventory($inventoryData);
        
        if ($result['success']) {
            $message = 'Інвентаризацію успішно проведено!';
            $messageType = 'success';
            
            // Оновлюємо список товарів після інвентаризації
            $allProducts = $warehouseController->getAllProducts();
        } else {
            $message = 'Помилка при проведенні інвентаризації: ' . $result['message'];
            $messageType = 'error';
        }
    } else {
        $message = 'Не вказано жодної кількості товару.';
        $messageType = 'warning';
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Інвентаризація - Винна крамниця</title>
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
                <a href="inventory.php" class="flex items-center px-4 py-3 bg-blue-800">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Інвентаризація</h1>
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

                <!-- Форма інвентаризації -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold">Провести інвентаризацію</h2>
                        <p class="text-sm text-gray-600 mt-1">Вкажіть фактичну кількість товарів на складі. Залиште поле порожнім, якщо кількість не змінилася.</p>
                    </div>
                    <form action="inventory.php" method="POST">
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Товар</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Категорія</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Поточна кількість</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Фактична кількість</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Різниця</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Примітки</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($allProducts as $product): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img src="../assets/images/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="h-10 w-10 rounded-full object-cover">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></div>
                                                    <div class="text-sm text-gray-500">ID: <?= $product['id'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($product['category_name']) ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600" id="current-<?= $product['id'] ?>"><?= $product['stock_quantity'] ?></td>
                                        <td class="px-4 py-3">
                                            <input type="number" name="actual_quantity[<?= $product['id'] ?>]" id="actual-<?= $product['id'] ?>" 
                                                   min="0" placeholder="<?= $product['stock_quantity'] ?>" 
                                                   class="border rounded px-3 py-2 text-sm w-24 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   onchange="calculateDifference(<?= $product['id'] ?>)">
                                        </td>
                                        <td class="px-4 py-3 text-sm" id="diff-<?= $product['id'] ?>">-</td>
                                        <td class="px-4 py-3">
                                            <input type="text" name="notes[<?= $product['id'] ?>]" 
                                                   placeholder="Причина розбіжності" 
                                                   class="border rounded px-3 py-2 text-sm w-full focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 text-right">
                            <button type="submit" name="submit_inventory" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Зберегти інвентаризацію
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Функція для розрахунку різниці між поточною і фактичною кількістю
        function calculateDifference(productId) {
            const currentElement = document.getElementById('current-' + productId);
            const actualElement = document.getElementById('actual-' + productId);
            const diffElement = document.getElementById('diff-' + productId);
            
            if (actualElement.value === '') {
                diffElement.textContent = '-';
                diffElement.className = 'px-4 py-3 text-sm';
                return;
            }
            
            const current = parseInt(currentElement.textContent, 10);
            const actual = parseInt(actualElement.value, 10);
            const diff = actual - current;
            
            diffElement.textContent = diff > 0 ? '+' + diff : diff;
            
            if (diff > 0) {
                diffElement.className = 'px-4 py-3 text-sm text-green-600 font-semibold';
            } else if (diff < 0) {
                diffElement.className = 'px-4 py-3 text-sm text-red-600 font-semibold';
            } else {
                diffElement.className = 'px-4 py-3 text-sm text-gray-600';
            }
        }
    </script>
</body>
</html>