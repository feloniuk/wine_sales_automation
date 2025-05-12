<?php
// views/warehouse/products.php
// Сторінка управління товарами для начальника складу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/WarehouseController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('warehouse')) {
    header('Location: /login.php?redirect=warehouse/products');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер складу
$warehouseController = new WarehouseController();

// Отримуємо параметри фільтрації
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$stock = isset($_GET['stock']) ? $_GET['stock'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Отримуємо всі товари
$allProducts = $warehouseController->getAllProducts();

// Отримуємо всі категорії товарів для фільтра
$categories = [];
foreach ($allProducts as $product) {
    if (!isset($categories[$product['category_id']])) {
        $categories[$product['category_id']] = $product['category_name'];
    }
}

// Фільтруємо за категорією
$filteredProducts = $allProducts;
if ($category > 0) {
    $filteredProducts = array_filter($allProducts, function($product) use ($category) {
        return $product['category_id'] == $category;
    });
}

// Фільтруємо за статусом запасу
if ($stock === 'low') {
    $filteredProducts = array_filter($filteredProducts, function($product) {
        return $product['stock_quantity'] <= $product['min_stock'];
    });
} elseif ($stock === 'out') {
    $filteredProducts = array_filter($filteredProducts, function($product) {
        return $product['stock_quantity'] <= 0;
    });
}

// Фільтруємо за пошуком
if (!empty($search)) {
    $searchLower = strtolower($search);
    $filteredProducts = array_filter($filteredProducts, function($product) use ($searchLower) {
        return strpos(strtolower($product['name']), $searchLower) !== false ||
               strpos(strtolower($product['id']), $searchLower) !== false;
    });
}

// Сортування товарів (за замовчуванням за іменем)
usort($filteredProducts, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

// Пагінація
$perPage = 10;
$totalItems = count($filteredProducts);
$totalPages = ceil($totalItems / $perPage);
$page = min($page, max(1, $totalPages));
$offset = ($page - 1) * $perPage;

// Відрізаємо дані для поточної сторінки
$products = array_slice($filteredProducts, $offset, $perPage);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Товари - Винна крамниця</title>
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
                <!-- Фільтри та пошук -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <h2 class="text-lg font-semibold mb-4 md:mb-0">Фільтри</h2>
                        
                        <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-2">
                            <form action="products.php" method="GET" class="flex space-x-2">
                                <input type="text" name="search" placeholder="Пошук за назвою або ID" 
                                      value="<?= htmlspecialchars($search) ?>"
                                      class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                            
                            <div class="flex space-x-2">
                                <select name="category" id="category-filter" onchange="applyFilters()" class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Всі категорії</option>
                                    <?php foreach ($categories as $id => $name): ?>
                                    <option value="<?= $id ?>" <?= $category == $id ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <select name="stock" id="stock-filter" onchange="applyFilters()" class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Всі запаси</option>
                                    <option value="low" <?= $stock === 'low' ? 'selected' : '' ?>>Низький запас</option>
                                    <option value="out" <?= $stock === 'out' ? 'selected' : '' ?>>Відсутні на складі</option>
                                </select>
                                
                                <a href="products.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded flex items-center">
                                    <i class="fas fa-sync-alt mr-2"></i> Скинути
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Таблиця товарів -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b flex justify-between items-center">
                        <h2 class="text-lg font-semibold">Товари на складі</h2>
                    </div>
                    
                    <?php if (empty($products)): ?>
                    <div class="p-6 text-center text-gray-500">
                        <i class="fas fa-wine-bottle text-5xl mb-4"></i>
                        <p>Немає товарів, що відповідають вашим критеріям пошуку</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Товар</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Категорія</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ціна</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Запас</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Мін. запас</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($products as $product): ?>
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
                                                <?php if (isset($product['year'])): ?>
                                                <div class="text-sm text-gray-500"><?= $product['year'] ?> рік</div>
                                                <?php endif; ?>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="add_stock.php?id=<?= $product['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Керувати запасами">
                                                <i class="fas fa-plus-circle"></i>
                                            </a>
                                            <a href="#" onclick="showProductHistory(<?= $product['id'] ?>, '<?= htmlspecialchars(addslashes($product['name'])) ?>')" class="text-indigo-600 hover:text-indigo-900" title="Історія транзакцій">
                                                <i class="fas fa-history"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Пагінація -->
                    <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 bg-gray-50 border-t">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-700">
                                Показано <?= $offset + 1 ?> - <?= min($offset + $perPage, $totalItems) ?> з <?= $totalItems ?> товарів
                            </div>
                            <div class="flex space-x-1">
                                <?php 
                                $queryParams = [];
                                if (!empty($category)) $queryParams[] = "category=$category";
                                if (!empty($stock)) $queryParams[] = "stock=$stock";
                                if (!empty($search)) $queryParams[] = "search=" . urlencode($search);
                                $queryString = !empty($queryParams) ? '&' . implode('&', $queryParams) : '';
                                ?>
                                
                                <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?><?= $queryString ?>" 
                                   class="px-3 py-1 bg-white text-gray-700 border rounded hover:bg-gray-100">
                                    Попередня
                                </a>
                                <?php endif; ?>
                                
                                <?php
                                // Показуємо 5 номерів сторінок
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $startPage + 4);
                                $startPage = max(1, $endPage - 4);
                                
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                <a href="?page=<?= $i ?><?= $queryString ?>" 
                                   class="px-3 py-1 <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border' ?> rounded hover:bg-gray-100">
                                    <?= $i ?>
                                </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?><?= $queryString ?>" 
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

    <!-- Модальне вікно для історії транзакцій товару -->
    <div id="productHistoryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full max-h-screen overflow-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Історія транзакцій: <span id="productName"></span></h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" 
                        onclick="document.getElementById('productHistoryModal').classList.add('hidden')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="productHistoryContent" class="overflow-x-auto">
                <div class="text-center py-10">
                    <i class="fas fa-spinner fa-spin text-blue-600 text-4xl"></i>
                    <p class="mt-2 text-gray-600">Завантаження історії...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Функція для застосування фільтрів
        function applyFilters() {
            const category = document.getElementById('category-filter').value;
            const stock = document.getElementById('stock-filter').value;
            const searchParams = new URLSearchParams(window.location.search);
            
            let url = 'products.php?';
            
            if (category) {
                url += 'category=' + category + '&';
            }
            
            if (stock) {
                url += 'stock=' + stock + '&';
            }
            
            if (searchParams.has('search')) {
                url += 'search=' + searchParams.get('search') + '&';
            }
            
            // Видаляємо останній символ & з URL
            url = url.slice(0, -1);
            
            window.location.href = url;
        }
        
        // Функція для відображення історії транзакцій товару
        function showProductHistory(productId, productName) {
            // Показуємо модальне вікно
            const modal = document.getElementById('productHistoryModal');
            modal.classList.remove('hidden');
            
            // Встановлюємо назву товару
            document.getElementById('productName').textContent = productName;
            
            // Завантажуємо історію транзакцій
            fetch('get_product_history.php?product_id=' + productId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('productHistoryContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('productHistoryContent').innerHTML = `
                        <div class="text-center py-10">
                            <i class="fas fa-exclamation-circle text-red-600 text-4xl"></i>
                            <p class="mt-2 text-gray-600">Помилка завантаження історії: ${error}</p>
                        </div>
                    `;
                });
        }
    </script>
</body>
</html>