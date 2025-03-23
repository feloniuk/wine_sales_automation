<?php
// warehouse/add_stock.php
// Сторінка для додавання або списання товарів зі складу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/WarehouseController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('warehouse')) {
    header('Location: /login.php?redirect=warehouse/add_stock');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер складу
$warehouseController = new WarehouseController();

// Перевіряємо, чи передано ID товару
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = null;

if ($productId > 0) {
    $product = $warehouseController->getProductById($productId);
    if (!$product) {
        header('Location: products.php');
        exit;
    }
}

// Ініціалізуємо змінні для повідомлень
$message = '';
$messageType = '';

// Обробка форми
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaction'])) {
    $quantity = intval($_POST['quantity']);
    $transactionType = $_POST['transaction_type'];
    $referenceType = $_POST['reference_type'];
    $notes = $_POST['notes'];
    
    if ($quantity <= 0) {
        $message = 'Кількість повинна бути більше нуля.';
        $messageType = 'error';
    } else {
        $result = $warehouseController->updateStock(
            $productId,
            $quantity,
            $transactionType,
            $referenceType,
            null, // referenceId - додатковий ідентифікатор, якщо потрібно
            $notes
        );
        
        if ($result['success']) {
            $transactionTypeText = $transactionType === 'in' ? 'Надходження' : 'Списання';
            $message = "$transactionTypeText товару успішно виконано. Нова кількість: {$result['new_quantity']}";
            $messageType = 'success';
            
            // Оновлюємо дані товару
            $product = $warehouseController->getProductById($productId);
        } else {
            $message = 'Помилка при оновленні кількості товару: ' . $result['message'];
            $messageType = 'error';
        }
    }
}

// Отримуємо історію транзакцій для товару
$transactions = $warehouseController->getProductTransactionHistory($productId);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product ? htmlspecialchars($product['name']) : 'Товар' ?> - Керування запасами - Винна крамниця</title>
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
                <a href="products.php" class="flex items-center px-4 py-3 bg-blue-800">
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
                    <h1 class="text-2xl font-semibold text-gray-800">
                        <?= $product ? 'Керування запасами: ' . htmlspecialchars($product['name']) : 'Товар не знайдено' ?>
                    </h1>
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

                <?php if ($product): ?>
                <!-- Інформація про товар -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex flex-col md:flex-row items-start">
                        <div class="w-full md:w-1/4 mb-4 md:mb-0">
                            <img src="../assets/images/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-auto rounded-lg">
                        </div>
                        <div class="w-full md:w-3/4 md:pl-6">
                            <h2 class="text-xl font-semibold mb-2"><?= htmlspecialchars($product['name']) ?></h2>
                            <p class="text-gray-600 mb-4"><?= htmlspecialchars($product['description']) ?></p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h3 class="font-semibold text-gray-700 mb-2">Інформація про товар</h3>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">ID:</span>
                                            <span class="font-medium"><?= $product['id'] ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Категорія:</span>
                                            <span class="font-medium"><?= htmlspecialchars($product['category_name']) ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Рік:</span>
                                            <span class="font-medium"><?= $product['year'] ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Алкоголь:</span>
                                            <span class="font-medium"><?= $product['alcohol'] ?>%</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Об'єм:</span>
                                            <span class="font-medium"><?= $product['volume'] ?> мл</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h3 class="font-semibold text-gray-700 mb-2">Інформація про запаси</h3>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Ціна:</span>
                                            <span class="font-medium"><?= number_format($product['price'], 2) ?> ₴</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Поточна кількість:</span>
                                            <span class="font-medium <?= $product['stock_quantity'] <= $product['min_stock'] ? 'text-red-600' : 'text-green-600' ?>">
                                                <?= $product['stock_quantity'] ?> шт.
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Мінімальний запас:</span>
                                            <span class="font-medium"><?= $product['min_stock'] ?> шт.</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Статус запасу:</span>
                                            <?php if ($product['stock_quantity'] <= 0): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Відсутній на складі
                                            </span>
                                            <?php elseif ($product['stock_quantity'] <= $product['min_stock']): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Низький запас
                                            </span>
                                            <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Достатній запас
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Форма для надходження/списання товару -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Операції з товаром</h2>
                        <form action="add_stock.php?id=<?= $productId ?>" method="POST">
                            <div class="mb-4">
                                <label for="transaction_type" class="block text-sm font-medium text-gray-700 mb-1">Тип операції</label>
                                <select id="transaction_type" name="transaction_type" required
                                        class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="in">Надходження</option>
                                    <option value="out">Списання</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Кількість</label>
                                <input type="number" id="quantity" name="quantity" required min="1" value="1"
                                       class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="mb-4">
                                <label for="reference_type" class="block text-sm font-medium text-gray-700 mb-1">Причина</label>
                                <select id="reference_type" name="reference_type" required
                                        class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="production">Поставка від виробника</option>
                                    <option value="adjustment">Коригування запасів</option>
                                    <option value="return">Повернення</option>
                                    <option value="order">Замовлення</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Примітки</label>
                                <textarea id="notes" name="notes" rows="3" 
                                          class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>
                            <button type="submit" name="submit_transaction" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Виконати операцію
                            </button>
                        </form>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Рекомендації</h2>
                        <?php if ($product['stock_quantity'] <= 0): ?>
                        <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-600"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium">Товар відсутній на складі!</h3>
                                    <p class="mt-2 text-sm">Рекомендується негайно поповнити запаси. Клієнти не зможуть придбати цей товар.</p>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($product['stock_quantity'] <= $product['min_stock']): ?>
                        <div class="bg-yellow-100 text-yellow-700 p-4 rounded-lg mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium">Низький рівень запасів!</h3>
                                    <p class="mt-2 text-sm">Рекомендується поповнити запаси у найближчий час.</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="bg-blue-100 text-blue-700 p-4 rounded-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-600"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium">Статистика продажів</h3>
                                    <p class="mt-2 text-sm">
                                        Рекомендована кількість для закупівлі: 
                                        <span class="font-medium"><?= max(($product['min_stock'] * 2) - $product['stock_quantity'], 0) ?> шт.</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>