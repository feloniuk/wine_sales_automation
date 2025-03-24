<?php
// admin/products.php
// Сторінка управління товарами

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/AdminController.php';
require_once ROOT_PATH . '/controllers/WarehouseController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('admin')) {
    header('Location: /login.php?redirect=admin/products');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролери
$warehouseController = new WarehouseController();
$adminController = new AdminController();

// Отримуємо список категорій для фільтрації
$categories = $adminController->getProductCategories();

// Параметри фільтрації
$categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$stockFilter = isset($_GET['stock']) ? $_GET['stock'] : '';

// Отримуємо всі товари
$products = $warehouseController->getAllProducts();

// Фільтруємо товари, якщо встановлені фільтри
if ($categoryFilter > 0 || !empty($search) || $stockFilter === 'low') {
    $filteredProducts = [];
    
    foreach ($products as $product) {
        // Фільтр за категорією
        if ($categoryFilter > 0 && $product['category_id'] != $categoryFilter) {
            continue;
        }
        
        // Фільтр за пошуком
        if (!empty($search) && 
            stripos($product['name'], $search) === false && 
            stripos($product['description'], $search) === false) {
            continue;
        }
        
        // Фільтр за наявністю на складі
        if ($stockFilter === 'low' && $product['stock_quantity'] > $product['min_stock']) {
            continue;
        }
        
        $filteredProducts[] = $product;
    }
    
    $products = $filteredProducts;
}

// Обробка видалення товару
$message = '';
$messageType = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $productId = intval($_GET['delete']);
    
    // Реалізуйте метод видалення товару в контролері
    // Це спрощений приклад, для демонстрації
    $result = ['success' => true, 'message' => 'Товар успішно видалено'];
    
    if ($result['success']) {
        $message = 'Товар успішно видалено.';
        $messageType = 'success';
        // Оновлюємо список товарів
        $products = $warehouseController->getAllProducts();
    } else {
        $message = 'Помилка при видаленні товару: ' . $result['message'];
        $messageType = 'error';
    }
}

// Обробка зміни статусу товару
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $productId = intval($_GET['toggle_status']);
    $newStatus = ($_GET['status'] === 'active') ? 'inactive' : 'active';
    
    // Оновлюємо статус товару
    $data = ['status' => $newStatus];
    $result = $warehouseController->updateProduct($productId, $data);
    
    if ($result['success']) {
        $message = 'Статус товару успішно змінено.';
        $messageType = 'success';
        // Оновлюємо список товарів
        $products = $warehouseController->getAllProducts();
    } else {
        $message = 'Помилка при зміні статусу товару: ' . $result['message'];
        $messageType = 'error';
    }
}

// Обробка оновлення кількості товару
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $productId = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $transactionType = $_POST['transaction_type'] ?? 'in';
    $notes = $_POST['notes'] ?? '';
    
    if ($productId > 0 && $quantity > 0) {
        $result = $warehouseController->updateStock(
            $productId, 
            $quantity, 
            $transactionType, 
            'manual', 
            null, 
            $notes
        );
        
        if ($result['success']) {
            $message = 'Кількість товару успішно оновлено.';
            $messageType = 'success';
            // Оновлюємо список товарів
            $products = $warehouseController->getAllProducts();
        } else {
            $message = 'Помилка при оновленні кількості товару: ' . $result['message'];
            $messageType = 'error';
        }
    } else {
        $message = 'Будь ласка, вкажіть дійсні значення для товару та кількості.';
        $messageType = 'error';
    }
}

// Отримання повідомлення про успішне додавання товару з add_product.php
if (isset($_GET['success'])) {
    $message = 'Товар успішно додано.';
    $messageType = 'success';
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управління товарами - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                <a href="products.php" class="flex items-center px-4 py-3 bg-red-800">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Управління товарами</h1>
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

                <!-- Панель інструментів -->
                <div class="mb-6 flex justify-between items-center">
                    <a href="add_product.php" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                        <i class="fas fa-plus mr-2"></i> Додати товар
                    </a>
                    
                    <div class="flex space-x-2">
                        <!-- Фільтр за категорією -->
                        <form action="products.php" method="GET" class="flex space-x-2">
                            <!-- Зберігаємо інші параметри фільтрації -->
                            <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                            <?php endif; ?>
                            <?php if (!empty($stockFilter)): ?>
                            <input type="hidden" name="stock" value="<?= htmlspecialchars($stockFilter) ?>">
                            <?php endif; ?>
                            
                            <select name="category" id="category" class="border rounded px-3 py-2 focus:outline-none focus:ring-red-500" onchange="this.form.submit()">
                                <option value="0">Всі категорії</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $categoryFilter == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        
                        <!-- Фільтр за наявністю на складі -->
                        <form action="products.php" method="GET" class="flex space-x-2">
                            <!-- Зберігаємо інші параметри фільтрації -->
                            <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                            <?php endif; ?>
                            <?php if ($categoryFilter > 0): ?>
                            <input type="hidden" name="category" value="<?= $categoryFilter ?>">
                            <?php endif; ?>
                            
                            <select name="stock" id="stock" class="border rounded px-3 py-2 focus:outline-none focus:ring-red-500" onchange="this.form.submit()">
                                <option value="">Всі товари</option>
                                <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Низький запас</option>
                            </select>
                        </form>
                        
                        <!-- Пошук -->
                        <form action="products.php" method="GET" class="flex">
                            <!-- Зберігаємо інші параметри фільтрації -->
                            <?php if ($categoryFilter > 0): ?>
                            <input type="hidden" name="category" value="<?= $categoryFilter ?>">
                            <?php endif; ?>
                            <?php if (!empty($stockFilter)): ?>
                            <input type="hidden" name="stock" value="<?= htmlspecialchars($stockFilter) ?>">
                            <?php endif; ?>
                            
                            <input type="text" name="search" placeholder="Пошук товарів..." value="<?= htmlspecialchars($search) ?>" 
                                   class="border rounded-l px-3 py-2 focus:outline-none focus:ring-red-500">
                            <button type="submit" class="bg-red-800 text-white px-3 py-2 rounded-r hover:bg-red-700">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Список товарів -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <?php if (empty($products)): ?>
                    <div class="p-6 text-center text-gray-500">
                        <i class="fas fa-wine-bottle text-5xl mb-4"></i>
                        <p>Товари не знайдені. Спробуйте змінити параметри фільтрації або <a href="add_product.php" class="text-red-800 hover:underline">додайте новий товар</a>.</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Зображення</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Найменування</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Категорія</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ціна</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Запас</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="h-10 w-10 rounded overflow-hidden">
                                            <img src="../../assets/images/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="h-full w-full object-cover">
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></div>
                                        <div class="text-sm text-gray-500">ID: <?= $product['id'] ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($product['category_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= number_format($product['price'], 2) ?> ₴
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($product['stock_quantity'] <= $product['min_stock']): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            <?= $product['stock_quantity'] ?> шт. (низький запас)
                                        </span>
                                        <?php else: ?>
                                        <span class="text-sm text-gray-900">
                                            <?= $product['stock_quantity'] ?> шт.
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($product['status'] === 'active'): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Активний
                                        </span>
                                        <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Неактивний
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="edit_product.php?id=<?= $product['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Редагувати">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="products.php?toggle_status=<?= $product['id'] ?>&status=<?= $product['status'] ?>" 
                                               class="<?= $product['status'] === 'active' ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900' ?>" 
                                               title="<?= $product['status'] === 'active' ? 'Деактивувати' : 'Активувати' ?>">
                                                <?= $product['status'] === 'active' ? '<i class="fas fa-toggle-on"></i>' : '<i class="fas fa-toggle-off"></i>' ?>
                                            </a>
                                            <button type="button" onclick="openStockModal(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')" class="text-indigo-600 hover:text-indigo-900" title="Оновити запас">
                                                <i class="fas fa-boxes"></i>
                                            </button>
                                            <button type="button" onclick="confirmDelete(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')" class="text-red-600 hover:text-red-900" title="Видалити">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Модальне вікно для оновлення запасу -->
    <div id="stockModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900" id="stockModalTitle">Оновлення запасу</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeStockModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="products.php" method="POST" id="stockForm">
                <input type="hidden" name="product_id" id="stockProductId">
                <input type="hidden" name="update_stock" value="1">
                
                <div class="mb-4">
                    <label for="transaction_type" class="block text-sm font-medium text-gray-700 mb-1">Тип транзакції</label>
                    <select id="transaction_type" name="transaction_type" class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-red-500">
                        <option value="in">Надходження (додати)</option>
                        <option value="out">Списання (відняти)</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Кількість</label>
                    <input type="number" id="quantity" name="quantity" min="1" value="1" required
                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-red-500">
                </div>
                
                <div class="mb-4">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Примітки</label>
                    <textarea id="notes" name="notes" rows="2"
                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-red-500"></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2" onclick="closeStockModal()">
                        Скасувати
                    </button>
                    <button type="submit" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                        Оновити запас
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Функція для підтвердження видалення товару
        function confirmDelete(productId, productName) {
            if (confirm(`Ви впевнені, що хочете видалити товар "${productName}"?`)) {
                window.location.href = `products.php?delete=${productId}`;
            }
        }
        
        // Функції для модального вікна оновлення запасу
        function openStockModal(productId, productName) {
            document.getElementById('stockModalTitle').textContent = `Оновлення запасу: ${productName}`;
            document.getElementById('stockProductId').value = productId;
            document.getElementById('stockModal').classList.remove('hidden');
        }
        
        function closeStockModal() {
            document.getElementById('stockModal').classList.add('hidden');
        }
    </script>
</body>
</html>