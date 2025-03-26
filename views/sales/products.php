<?php
// sales/products.php
// Сторінка каталогу товарів для менеджера з продажу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('sales')) {
    header('Location: /login.php?redirect=sales/products');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер клієнта для роботи з каталогом
$customerController = new CustomerController();

// Отримуємо параметри фільтрації та пагінації
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = ITEMS_PER_PAGE;
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

// Отримуємо список товарів
if (!empty($search)) {
    $productsData = $customerController->searchProducts($search, $page, $perPage, $sort);
    $title = 'Результати пошуку: ' . htmlspecialchars($search);
} elseif ($categoryId > 0) {
    $productsData = $customerController->getProductsByCategory($categoryId, $page, $perPage, $sort);
    
    // Знаходимо назву категорії
    $categories = $customerController->getAllCategories();
    $categoryName = '';
    foreach ($categories as $category) {
        if ($category['id'] == $categoryId) {
            $categoryName = $category['name'];
            break;
        }
    }
    
    $title = htmlspecialchars($categoryName);
} else {
    $productsData = $customerController->getAllProducts($page, $perPage, $sort);
    $title = 'Всі товари';
}

$products = $productsData['data'];
$pagination = $productsData['pagination'];

// Отримуємо всі категорії для фільтрації
$categories = $customerController->getAllCategories();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог товарів - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                <a href="products.php" class="flex items-center px-4 py-3 bg-green-700">
                    <i class="fas fa-wine-bottle mr-3"></i>
                    <span>Каталог</span>
                </a>
                <a href="new_order.php" class="flex items-center px-4 py-3 hover:bg-green-700">
                    <i class="fas fa-plus-circle mr-3"></i>
                    <span>Нове замовлення</span>
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
                    <h1 class="text-2xl font-semibold text-gray-800">Каталог товарів</h1>
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
                    <div class="flex flex-col md:flex-row gap-4">
                        <!-- Пошук -->
                        <div class="md:flex-1">
                            <form action="products.php" method="GET" class="flex">
                                <input type="text" name="search" placeholder="Пошук товарів..." 
                                       value="<?= htmlspecialchars($search) ?>"
                                       class="border rounded-l px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-green-500">
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-r">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                        
                        <!-- Фільтр за категорією -->
                        <div>
                            <form action="products.php" method="GET" class="flex">
                                <select name="category" class="border rounded-l px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="0">Всі категорії</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= $categoryId == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-r">
                                    <i class="fas fa-filter"></i>
                                </button>
                            </form>
                        </div>
                        
                        <!-- Сортування -->
                        <div>
                            <form action="products.php" method="GET" class="flex">
                                <?php if ($categoryId): ?>
                                <input type="hidden" name="category" value="<?= $categoryId ?>">
                                <?php endif; ?>
                                <?php if ($search): ?>
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                                <?php endif; ?>
                                
                                <select name="sort" class="border rounded-l px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Сортувати за...</option>
                                    <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>За назвою</option>
                                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Ціна: від низької до високої</option>
                                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Ціна: від високої до низької</option>
                                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Спочатку нові</option>
                                </select>
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-r">
                                    <i class="fas fa-sort"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Заголовок результатів -->
                <div class="mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800"><?= $title ?></h2>
                    <p class="text-gray-600">Показано <?= count($products) ?> з <?= $pagination['total'] ?> товарів</p>
                </div>

                <!-- Каталог товарів -->
                <?php if (empty($products)): ?>
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <div class="mb-6 text-gray-500">
                        <i class="fas fa-wine-bottle text-6xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Товари не знайдені</h3>
                    <p class="text-gray-600 mb-4">
                        <?php if (!empty($search)): ?>
                        На жаль, за вашим запитом "<?= htmlspecialchars($search) ?>" нічого не знайдено.
                        <?php elseif ($categoryId > 0): ?>
                        У цій категорії поки немає товарів.
                        <?php else: ?>
                        Наразі каталог товарів порожній.
                        <?php endif; ?>
                    </p>
                    <a href="products.php" class="inline-block bg-green-600 text-white rounded px-4 py-2 hover:bg-green-700">Повернутися до каталогу</a>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($products as $product): ?>
                    <!-- Карточка товару -->
                    <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="relative">
                            <img src="../../assets/images/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-56 object-cover">
                            <?php if ($product['featured']): ?>
                            <span class="absolute top-2 right-2 bg-red-600 text-white text-xs px-2 py-1 rounded">Популярне</span>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h3 class="text-lg font-semibold mb-2 text-gray-800"><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="text-sm text-gray-600 mb-4"><?= htmlspecialchars(mb_substr($product['description'], 0, 100)) ?>...</p>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-gray-500"><?= htmlspecialchars($product['category_name']) ?></span>
                                <span class="font-bold text-green-600"><?= number_format($product['price'], 2) ?> ₴</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm <?= $product['stock_quantity'] <= $product['min_stock'] ? 'text-red-600' : 'text-green-600' ?>">
                                    <?php if ($product['stock_quantity'] <= 0): ?>
                                    Немає в наявності
                                    <?php else: ?>
                                    В наявності: <?= $product['stock_quantity'] ?> шт.
                                    <?php endif; ?>
                                </span>
                                <a href="product_details.php?id=<?= $product['id'] ?>" class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-eye"></i> Деталі
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Пагінація -->
                <?php if ($pagination['total_pages'] > 1): ?>
                <div class="mt-8 flex justify-center">
                    <div class="flex space-x-1">
                        <?php if ($pagination['current_page'] > 1): ?>
                        <a href="?page=<?= $pagination['current_page'] - 1 ?><?= $categoryId ? '&category=' . $categoryId : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $sort ? '&sort=' . $sort : '' ?>" 
                           class="px-4 py-2 bg-white text-green-600 border border-green-200 rounded hover:bg-green-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php
                        // Показуємо 5 номерів сторінок
                        $startPage = max(1, $pagination['current_page'] - 2);
                        $endPage = min($pagination['total_pages'], $startPage + 4);
                        $startPage = max(1, $endPage - 4);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                        <a href="?page=<?= $i ?><?= $categoryId ? '&category=' . $categoryId : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $sort ? '&sort=' . $sort : '' ?>" 
                           class="px-4 py-2 <?= $i === $pagination['current_page'] ? 'bg-green-600 text-white' : 'bg-white text-green-600 border border-green-200' ?> rounded hover:bg-green-50">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                        <a href="?page=<?= $pagination['current_page'] + 1 ?><?= $categoryId ? '&category=' . $categoryId : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $sort ? '&sort=' . $sort : '' ?>" 
                           class="px-4 py-2 bg-white text-green-600 border border-green-200 rounded hover:bg-green-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>