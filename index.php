<?php
// index.php
// Головна сторінка каталогу товарів

// Підключаємо конфігурацію
define('ROOT_PATH', ((__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';
require_once ROOT_PATH . '/controllers/AuthController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
$isLoggedIn = $authController->isLoggedIn();
$currentUser = $isLoggedIn ? $authController->getCurrentUser() : null;

// Ініціалізуємо контролер для роботи з каталогом
$customerController = new CustomerController();

// Отримання ID сесії з кукі або створення нового для кошика
$sessionId = isset($_COOKIE['cart_session_id']) ? $_COOKIE['cart_session_id'] : $customerController->generateCartSessionId();

// Якщо користувач авторизований, використовуємо його ID як сесію кошика
if ($isLoggedIn) {
    $sessionId = 'user_' . $currentUser['id'];
}

// Отримуємо вміст кошика для показу кількості товарів
$cartItems = $customerController->getCart($sessionId);
$totalQuantity = 0;
foreach ($cartItems as $item) {
    $totalQuantity += $item['quantity'];
}

// Отримання всіх категорій для меню
$categories = $customerController->getAllCategories();

// Отримання параметрів фільтрації з GET-запиту
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

// Визначення кількості товарів на сторінці
$perPage = ITEMS_PER_PAGE;

// Отримання списку товарів з урахуванням фільтрів
if (!empty($search)) {
    // Пошук товарів
    $productsData = $customerController->searchProducts($search, $page, $perPage);
    $title = 'Результати пошуку: ' . htmlspecialchars($search);
} elseif ($categoryId > 0) {
    // Товари конкретної категорії
    $productsData = $customerController->getProductsByCategory($categoryId, $page, $perPage);
    
    // Знаходимо назву категорії
    $categoryName = '';
    foreach ($categories as $category) {
        if ($category['id'] == $categoryId) {
            $categoryName = $category['name'];
            break;
        }
    }
    
    $title = htmlspecialchars($categoryName);
} else {
    // Всі товари
    $productsData = $customerController->getAllProducts($page, $perPage);
    $title = 'Наші вина';
}

// Отримуємо дані з результату
$products = $productsData['data'];
$pagination = $productsData['pagination'];

// Отримання промоакцій для банера
$promotions = $customerController->getActivePromotions();

// Отримання рекомендованих товарів
$featuredProducts = $customerController->getFeaturedProducts();

?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Винна крамниця - Головна</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .wine-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .category-card:hover {
            transform: scale(1.03);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50">
    <header class="bg-red-800 text-white">
        <!-- Верхня панель -->
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center">
                <a href="index.php" class="font-bold text-2xl">Винна крамниця</a>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <form action="index.php" method="GET" class="flex">
                        <input type="text" name="search" placeholder="Пошук вина..." 
                               class="px-4 py-2 rounded-l text-gray-800 focus:outline-none" 
                               style="min-width: 300px;"
                               value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="bg-red-900 px-4 py-2 rounded-r hover:bg-red-700">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <a href="cart.php" class="relative">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <span class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs cart-count"><?= $totalQuantity ?></span>
                </a>
                <?php if ($isLoggedIn): ?>
                <div class="flex items-center space-x-2">
                    <a href="views/<?= htmlspecialchars($currentUser['role']) ?>/index.php" class="hover:text-red-200"><?= htmlspecialchars($currentUser['name']) ?></a>
                    <span>|</span>
                    <a href="logout.php" class="hover:text-red-200">Вихід</a>
                </div>
                <?php else: ?>
                <div class="flex items-center space-x-2">
                    <a href="login.php" class="hover:text-red-200">Увійти</a>
                    <span>|</span>
                    <a href="register.php" class="hover:text-red-200">Реєстрація</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Навігаційне меню -->
        <nav class="bg-red-900">
            <div class="container mx-auto px-4">
                <ul class="flex space-x-6 py-3">
                    <li><a href="index.php" class="<?= empty($categoryId) && empty($search) ? 'text-white font-bold' : 'hover:text-red-200' ?>">Головна</a></li>
                    <?php foreach ($categories as $category): ?>
                    <li><a href="index.php?category=<?= $category['id'] ?>" class="<?= $categoryId == $category['id'] ? 'text-white font-bold' : 'hover:text-red-200' ?>"><?= htmlspecialchars($category['name']) ?></a></li>
                    <?php endforeach; ?>
                
                </ul>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-8">
        <?php if (empty($categoryId) && empty($search)): ?>
        <!-- Банер (тільки на головній сторінці) -->
        <section class="mb-12 relative rounded-lg overflow-hidden shadow-lg">
            <img src="assets/images/banner.webp" alt="Винна колекція" class="w-full h-96 object-cover">
            <div class="absolute inset-0 bg-black bg-opacity-40 flex flex-col items-start justify-center text-white p-8">
                <h1 class="text-4xl font-bold mb-4">Винна крамниця</h1>
                <p class="text-xl mb-6 max-w-lg">Відкрийте для себе вишукані вина з кращих виноградників. Доставка по всій Україні.</p>
                <?php if (!empty($promotions)): ?>
                <div class="bg-red-800 px-6 py-3 rounded-lg font-semibold inline-block">
                    <span>Акція: <?= htmlspecialchars($promotions[0]['name']) ?></span>
                    <?php if ($promotions[0]['discount_percent']): ?>
                    <span class="ml-2">-<?= $promotions[0]['discount_percent'] ?>%</span>
                    <?php elseif ($promotions[0]['discount_amount']): ?>
                    <span class="ml-2">-<?= number_format($promotions[0]['discount_amount'], 0) ?> ₴</span>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <a href="#products" class="bg-red-800 hover:bg-red-700 px-6 py-3 rounded-lg font-semibold transition duration-300">
                    Обрати вино
                </a>
                <?php endif; ?>
            </div>
        </section>

        <!-- Категорії (тільки на головній сторінці) -->
        <section class="mb-12">
            <h2 class="text-3xl font-bold mb-6 text-gray-800">Категорії вин</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <?php foreach ($categories as $category): ?>
                <a href="index.php?category=<?= $category['id'] ?>" class="category-card bg-gradient-to-br from-red-800 to-red-900 rounded-lg p-6 text-white shadow-md">
                    <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($category['name']) ?></h3>
                    <p class="text-sm opacity-80"><?= htmlspecialchars(mb_substr($category['description'], 0, 30)) ?>...</p>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Фільтри та сортування -->
        <section class="mb-4 flex justify-between items-center">
            <div class="flex space-x-2">
                <span class="text-gray-600">Сортувати за:</span>
                <a href="<?= $categoryId ? "index.php?category=$categoryId&sort=price_asc" : "index.php?sort=price_asc" ?><?= $search ? "&search=".urlencode($search) : "" ?>" class="text-red-800 hover:underline <?= $sort == 'price_asc' ? 'font-bold' : '' ?>">Ціна ↑</a>
                <a href="<?= $categoryId ? "index.php?category=$categoryId&sort=price_desc" : "index.php?sort=price_desc" ?><?= $search ? "&search=".urlencode($search) : "" ?>" class="text-red-800 hover:underline <?= $sort == 'price_desc' ? 'font-bold' : '' ?>">Ціна ↓</a>
                <a href="<?= $categoryId ? "index.php?category=$categoryId&sort=name" : "index.php?sort=name" ?><?= $search ? "&search=".urlencode($search) : "" ?>" class="text-red-800 hover:underline <?= $sort == 'name' ? 'font-bold' : '' ?>">Назва</a>
            </div>
            <div class="text-gray-600">
                Показано <span class="font-semibold"><?= count($products) ?></span> з <span class="font-semibold"><?= $pagination['total'] ?></span> товарів
            </div>
        </section>

        <!-- Товари -->
        <section id="products" class="mb-12">
            <h2 class="text-3xl font-bold mb-6 text-gray-800"><?= $title ?></h2>
            
            <?php if (empty($products)): ?>
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
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
                <a href="index.php" class="inline-block bg-red-800 text-white rounded px-4 py-2 hover:bg-red-700">Повернутися на головну</a>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($products as $product): ?>
                <!-- Карточка товару -->
                <div class="wine-card bg-white rounded-lg overflow-hidden shadow-md">
                    <div class="relative">
                        <img src="assets/images/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-64 object-cover">
                        <?php if ($product['featured']): ?>
                        <span class="absolute top-2 right-2 bg-red-800 text-white px-2 py-1 rounded text-sm">Популярне</span>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <div class="flex justify-between">
                            <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($product['name']) ?></h3>
                            <span class="text-red-800 font-bold"><?= number_format($product['price'], 0) ?> ₴</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4"><?= htmlspecialchars(mb_substr($product['description'], 0, 60)) ?>...</p>
                        <div class="flex justify-between">
                            <a href="product.php?id=<?= $product['id'] ?>" class="text-red-800 hover:underline text-sm">Детальніше</a>
                            <button class="add-to-cart bg-red-800 hover:bg-red-700 text-white px-3 py-1 rounded-lg text-sm" 
                                    data-id="<?= $product['id'] ?>">
                                Додати в кошик
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <!-- Пагінація -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <section class="flex justify-center mb-12">
            <div class="flex space-x-2">
                <?php 
                // Визначення параметрів URL для пагінації
                $urlParams = [];
                if ($categoryId > 0) $urlParams[] = "category=$categoryId";
                if (!empty($search)) $urlParams[] = "search=" . urlencode($search);
                if (!empty($sort)) $urlParams[] = "sort=$sort";
                $urlBase = "index.php?" . implode('&', $urlParams);
                if (!empty($urlParams)) $urlBase .= '&';
                
                // Визначення діапазону сторінок для відображення
                $startPage = max(1, $pagination['current_page'] - 2);
                $endPage = min($pagination['total_pages'], $startPage + 4);
                $startPage = max(1, $endPage - 4);
                
                // Кнопка "Попередня"
                if ($pagination['current_page'] > 1):
                ?>
                <a href="<?= $urlBase ?>page=<?= $pagination['current_page'] - 1 ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <!-- Номери сторінок -->
                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <a href="<?= $urlBase ?>page=<?= $i ?>" class="px-4 py-2 <?= $i == $pagination['current_page'] ? 'bg-red-800 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?> rounded-lg">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <!-- Кнопка "Наступна" -->
                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                <a href="<?= $urlBase ?>page=<?= $pagination['current_page'] + 1 ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Переваги -->
        <section class="bg-gray-100 rounded-lg p-8 mb-12">
            <h2 class="text-3xl font-bold mb-6 text-gray-800 text-center">Наші переваги</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="flex flex-col items-center text-center">
                    <div class="bg-red-800 text-white rounded-full w-16 h-16 flex items-center justify-center mb-4">
                        <i class="fas fa-truck text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Швидка доставка</h3>
                    <p class="text-gray-600">Доставка по всій Україні протягом 1-3 днів</p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="bg-red-800 text-white rounded-full w-16 h-16 flex items-center justify-center mb-4">
                        <i class="fas fa-medal text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Гарантія якості</h3>
                    <p class="text-gray-600">Тільки перевірені та сертифіковані вина</p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="bg-red-800 text-white rounded-full w-16 h-16 flex items-center justify-center mb-4">
                        <i class="fas fa-hand-holding-dollar text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Найкращі ціни</h3>
                    <p class="text-gray-600">Регулярні акції та доступні ціни</p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="bg-red-800 text-white rounded-full w-16 h-16 flex items-center justify-center mb-4">
                        <i class="fas fa-headset text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Персональний підхід</h3>
                    <p class="text-gray-600">Консультації сомельє та індивідуальний підбір</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-white">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-semibold mb-4">Винна крамниця</h3>
                    <p class="text-gray-400">Ваш надійний партнер у світі вина з 2015 року. Ми пропонуємо найкращі вина з усього світу.</p>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Категорії</h3>
                    <ul class="space-y-2">
                        <?php foreach ($categories as $category): ?>
                        <li><a href="index.php?category=<?= $category['id'] ?>" class="text-gray-400 hover:text-white"><?= htmlspecialchars($category['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Інформація</h3>
                    <ul class="space-y-2">
                        <li><a href="about.php" class="text-gray-400 hover:text-white">Про нас</a></li>
                        <li><a href="delivery.php" class="text-gray-400 hover:text-white">Доставка та оплата</a></li>
                        <li><a href="terms.php" class="text-gray-400 hover:text-white">Умови використання</a></li>
                        <li><a href="privacy.php" class="text-gray-400 hover:text-white">Політика конфіденційності</a></li>
                        <li><a href="contact.php" class="text-gray-400 hover:text-white">Контакти</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Контакти</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-2"></i>
                            <span>вул. Виноградна, 1, Київ, 01001</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone mr-2"></i>
                            <span>+380 (50) 123-45-67</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-2"></i>
                            <span>info@winery.ua</span>
                        </li>
                    </ul>
                    <div class="mt-4 flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-gray-950 py-4 text-center text-gray-400 text-sm">
            <p>&copy; 2025 Винна крамниця. Всі права захищено.</p>
        </div>
    </footer>

    <!-- Модальне вікно для додавання в кошик -->
    <div id="cartModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Товар додано до кошика</h3>
                <button id="closeCartModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="mb-4 text-gray-600">Товар успішно додано до кошика.</p>
            <div class="flex justify-between">
                <button id="continueShopping" class="px-4 py-2 border border-red-800 text-red-800 rounded hover:bg-red-50">
                    Продовжити покупки
                </button>
                <a href="cart.php" class="px-4 py-2 bg-red-800 text-white rounded hover:bg-red-700">
                    Перейти до кошика
                </a>
            </div>
        </div>
    </div>

    <script>
        // Додавання товару в кошик
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                
                fetch('api/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&quantity=1`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartCount();
                        let cartCountSpan = document.querySelector(".cart-count");
                        if (cartCountSpan) {
                            cartCountSpan.textContent = parseInt(cartCountSpan.textContent || "0") + 1;
                        }

                        // Показати модальне вікно
                        document.getElementById('cartModal').classList.remove('hidden');
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Помилка:', error));
            });
        });

        // Закриття модального вікна
        document.getElementById('closeCartModal').addEventListener('click', function() {
            document.getElementById('cartModal').classList.add('hidden');
        });

        document.getElementById('continueShopping').addEventListener('click', function() {
            document.getElementById('cartModal').classList.add('hidden');
        });

        // Підрахунок кількості товарів у кошику
        function updateCartCount() {
            fetch('api/cart_count.php')
                .then(response => response.json())
                .then(data => {
                    document.querySelector('.cart-count').textContent = data.count;
                })
                .catch(error => console.error('Помилка:', error));
        }

        // Оновлення кількості товарів при завантаженні сторінки
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>