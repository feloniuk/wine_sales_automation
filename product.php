<?php
// product.php
// Сторінка товару

// Підключаємо конфігурацію
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';
require_once ROOT_PATH . '/controllers/AuthController.php';

// Перевіряємо ID товару
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$productId = intval($_GET['id']);

// Отримуємо інформацію про товар
$customerController = new CustomerController();
$productDetails = $customerController->getProductDetails($productId);

// Якщо товар не знайдено, перенаправляємо на головну
if (!$productDetails['success']) {
    header('Location: index.php');
    exit;
}

$product = $productDetails['product'];
$reviews = $productDetails['reviews'];
$recommended = $productDetails['recommended'];

// Перевіряємо авторизацію
$authController = new AuthController();
$isLoggedIn = $authController->isLoggedIn();
$currentUser = $isLoggedIn ? $authController->getCurrentUser() : null;

// Обробка відправки відгуку
$reviewSubmitted = false;
$reviewError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_submit']) && $isLoggedIn) {
    $rating = intval($_POST['rating']);
    $reviewText = trim($_POST['review_text']);
    
    if ($rating < 1 || $rating > 5) {
        $reviewError = 'Будь ласка, виберіть рейтинг від 1 до 5';
    } elseif (empty($reviewText)) {
        $reviewError = 'Будь ласка, напишіть відгук';
    } else {
        $result = $customerController->addProductReview($productId, $currentUser['id'], $rating, $reviewText);
        if ($result['success']) {
            $reviewSubmitted = true;
        } else {
            $reviewError = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                               style="min-width: 300px;">
                        <button type="submit" class="bg-red-900 px-4 py-2 rounded-r hover:bg-red-700">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <a href="cart.php" class="relative">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <span class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs cart-count">0</span>
                </a>
                <?php if ($isLoggedIn): ?>
                <div class="flex items-center space-x-2">
                    <a href="account/index.php" class="hover:text-red-200"><?= htmlspecialchars($currentUser['name']) ?></a>
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
                    <li><a href="index.php" class="hover:text-red-200">Головна</a></li>
                    <li><a href="index.php?category=1" class="hover:text-red-200">Червоні вина</a></li>
                    <li><a href="index.php?category=2" class="hover:text-red-200">Білі вина</a></li>
                    <li><a href="index.php?category=3" class="hover:text-red-200">Рожеві вина</a></li>
                    <li><a href="index.php?category=4" class="hover:text-red-200">Ігристі вина</a></li>
                    <li><a href="index.php?category=5" class="hover:text-red-200">Десертні вина</a></li>
                    <li><a href="about.php" class="hover:text-red-200">Про нас</a></li>
                    <li><a href="contact.php" class="hover:text-red-200">Контакти</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-8">
        <!-- Шлях -->
        <div class="mb-6">
            <div class="flex items-center text-sm text-gray-500">
                <a href="index.php" class="hover:text-red-800">Головна</a>
                <span class="mx-2">/</span>
                <a href="index.php?category=<?= $product['category_id'] ?>" class="hover:text-red-800"><?= htmlspecialchars($product['category_name']) ?></a>
                <span class="mx-2">/</span>
                <span class="text-gray-700"><?= htmlspecialchars($product['name']) ?></span>
            </div>
        </div>

        <!-- Основна інформація про товар -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <div class="flex flex-col md:flex-row">
                <!-- Зображення товару -->
                <div class="md:w-1/3 mb-6 md:mb-0">
                    <div class="sticky top-6">
                        <img src="assets/images/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-auto rounded-lg">
                    </div>
                </div>
                
                <!-- Інформація про товар -->
                <div class="md:w-2/3 md:pl-8">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($product['name']) ?></h1>
                    
                    <div class="flex items-center mb-4">
                        <!-- Зірки рейтингу -->
                        <div class="flex items-center">
                            <?php
                            // Обчислюємо середній рейтинг
                            $avgRating = 0;
                            $totalRatings = count($reviews);
                            if ($totalRatings > 0) {
                                $sumRatings = array_sum(array_column($reviews, 'rating'));
                                $avgRating = $sumRatings / $totalRatings;
                            }
                            
                            // Відображаємо зірки
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $avgRating) {
                                    echo '<i class="fas fa-star text-yellow-400"></i>';
                                } else if ($i <= $avgRating + 0.5) {
                                    echo '<i class="fas fa-star-half-alt text-yellow-400"></i>';
                                } else {
                                    echo '<i class="far fa-star text-yellow-400"></i>';
                                }
                            }
                            ?>
                        </div>
                        <span class="ml-2 text-gray-600"><?= number_format($avgRating, 1) ?> (<?= $totalRatings ?> відгуків)</span>
                    </div>
                    
                    <div class="mb-6">
                        <p class="text-3xl font-bold text-red-800"><?= number_format($product['price'], 2) ?> ₴</p>
                        <?php if ($product['stock_quantity'] > 0): ?>
                        <p class="text-green-600 mt-1">В наявності (<?= $product['stock_quantity'] ?> шт.)</p>
                        <?php else: ?>
                        <p class="text-red-600 mt-1">Немає в наявності</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="prose max-w-none mb-6">
                        <p class="text-gray-700"><?= htmlspecialchars($product['description']) ?></p>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <h3 class="font-semibold text-gray-800 mb-2">Характеристики:</h3>
                        <ul class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <li class="flex justify-between">
                                <span class="text-gray-600">Рік:</span>
                                <span class="font-medium"><?= $product['year'] ?></span>
                            </li>
                            <li class="flex justify-between">
                                <span class="text-gray-600">Алкоголь:</span>
                                <span class="font-medium"><?= $product['alcohol'] ?>%</span>
                            </li>
                            <li class="flex justify-between">
                                <span class="text-gray-600">Об'єм:</span>
                                <span class="font-medium"><?= $product['volume'] ?> мл</span>
                            </li>
                            <li class="flex justify-between">
                                <span class="text-gray-600">Тип:</span>
                                <span class="font-medium"><?= htmlspecialchars($product['category_name']) ?></span>
                            </li>
                        </ul>
                    </div>
                    
                    <?php if (!empty($product['details'])): ?>
                    <div class="prose max-w-none mb-6">
                        <h3 class="font-semibold text-gray-800 mb-2">Опис:</h3>
                        <p class="text-gray-700"><?= nl2br(htmlspecialchars($product['details'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                        <?php if ($product['stock_quantity'] > 0): ?>
                        <div class="flex">
                            <button id="decrease-quantity" class="bg-gray-200 text-gray-700 px-3 py-2 rounded-l hover:bg-gray-300">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" id="quantity" value="1" min="1" max="<?= $product['stock_quantity'] ?>" 
                                   class="w-16 text-center border-t border-b border-gray-300 py-2">
                            <button id="increase-quantity" class="bg-gray-200 text-gray-700 px-3 py-2 rounded-r hover:bg-gray-300">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <button id="add-to-cart" class="flex-1 bg-red-800 hover:bg-red-700 text-white px-6 py-2 rounded-lg" data-id="<?= $product['id'] ?>">
                            <i class="fas fa-shopping-cart mr-2"></i> Додати в кошик
                        </button>
                        <?php else: ?>
                        <button disabled class="flex-1 bg-gray-400 text-white px-6 py-2 rounded-lg cursor-not-allowed">
                            <i class="fas fa-shopping-cart mr-2"></i> Немає в наявності
                        </button>
                        <?php endif; ?>
                        <button class="bg-white border border-red-800 text-red-800 hover:bg-red-50 px-4 py-2 rounded-lg">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Вкладки (Опис, Характеристики, Відгуки) -->
        <div class="mb-8">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button class="tab-button border-red-800 text-red-800 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="description">
                        Опис
                    </button>
                    <button class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="reviews">
                        Відгуки (<?= $totalRatings ?>)
                    </button>
                </nav>
            </div>
            
            <!-- Вміст вкладок -->
            <div class="py-6">
                <!-- Опис -->
                <div id="description-tab" class="tab-content">
                    <div class="prose max-w-none">
                        <?php if (!empty($product['details'])): ?>
                        <p class="text-gray-700"><?= nl2br(htmlspecialchars($product['details'])) ?></p>
                        <?php else: ?>
                        <p class="text-gray-700"><?= htmlspecialchars($product['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Відгуки -->
                <div id="reviews-tab" class="tab-content hidden">
                    <!-- Форма відгуку -->
                    <?php if ($isLoggedIn): ?>
                        <?php if ($reviewSubmitted): ?>
                        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700">
                                        Дякуємо за ваш відгук! Він буде опублікований після перевірки.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="bg-white rounded-lg shadow p-6 mb-6">
                            <h3 class="text-lg font-semibold mb-4">Залишити відгук</h3>
                            
                            <?php if (!empty($reviewError)): ?>
                            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-circle text-red-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-700"><?= htmlspecialchars($reviewError) ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <form action="product.php?id=<?= $product['id'] ?>#reviews" method="POST">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Оцінка</label>
                                    <div class="flex space-x-2 rating-input">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <input type="radio" name="rating" value="<?= $i ?>" id="rating-<?= $i ?>" class="hidden" <?= isset($_POST['rating']) && $_POST['rating'] == $i ? 'checked' : '' ?>>
                                        <label for="rating-<?= $i ?>" class="text-2xl text-gray-300 hover:text-yellow-400 cursor-pointer rating-star">
                                            <i class="far fa-star"></i>
                                        </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="review_text" class="block text-sm font-medium text-gray-700 mb-2">Відгук</label>
                                    <textarea id="review_text" name="review_text" rows="4" required 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"><?= isset($_POST['review_text']) ? htmlspecialchars($_POST['review_text']) : '' ?></textarea>
                                </div>
                                
                                <button type="submit" name="review_submit" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                                    Відправити відгук
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    Щоб залишити відгук, будь ласка, <a href="login.php?redirect=product.php?id=<?= $product['id'] ?>" class="font-medium underline text-yellow-700 hover:text-yellow-600">увійдіть</a> або <a href="register.php?redirect=product.php?id=<?= $product['id'] ?>" class="font-medium underline text-yellow-700 hover:text-yellow-600">зареєструйтеся</a>.
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Список відгуків -->
                    <?php if (empty($reviews)): ?>
                    <div class="text-gray-500 text-center py-8">
                        <p>Поки немає відгуків. Будьте першим, хто залишить відгук!</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($reviews as $review): ?>
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center mr-4">
                                            <i class="fas fa-user text-red-800"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-800"><?= htmlspecialchars($review['customer_name']) ?></h4>
                                            <p class="text-sm text-gray-500"><?= date('d.m.Y', strtotime($review['created_at'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $review['rating']): ?>
                                    <i class="fas fa-star text-yellow-400"></i>
                                    <?php else: ?>
                                    <i class="far fa-star text-yellow-400"></i>
                                    <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="text-gray-700"><?= nl2br(htmlspecialchars($review['review'])) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Рекомендовані товари -->
        <?php if (!empty($recommended)): ?>
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Рекомендовані товари</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach ($recommended as $rec): ?>
                <div class="bg-white rounded-lg overflow-hidden shadow hover:shadow-md transition-shadow">
                    <a href="product.php?id=<?= $rec['id'] ?>">
                        <img src="assets/images/<?= $rec['image'] ?>" alt="<?= htmlspecialchars($rec['name']) ?>" class="w-full h-48 object-cover">
                    </a>
                    <div class="p-4">
                        <a href="product.php?id=<?= $rec['id'] ?>" class="block mb-1">
                            <h3 class="text-lg font-semibold text-gray-800 hover:text-red-800 transition-colors"><?= htmlspecialchars($rec['name']) ?></h3>
                        </a>
                        <p class="text-red-800 font-bold mb-2"><?= number_format($rec['price'], 2) ?> ₴</p>
                        <button class="add-to-cart-button w-full py-2 px-4 bg-red-800 hover:bg-red-700 text-white rounded-lg text-sm"
                                data-id="<?= $rec['id'] ?>">
                            Додати в кошик
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

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

    <footer class="bg-gray-900 text-white py-8 mt-16">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <h3 class="text-xl font-semibold mb-4">Винна крамниця</h3>
                    <p class="text-gray-400">Ваш надійний партнер у світі вина з 2015 року.</p>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Категорії</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php?category=1" class="text-gray-400 hover:text-white">Червоні вина</a></li>
                        <li><a href="index.php?category=2" class="text-gray-400 hover:text-white">Білі вина</a></li>
                        <li><a href="index.php?category=3" class="text-gray-400 hover:text-white">Рожеві вина</a></li>
                        <li><a href="index.php?category=4" class="text-gray-400 hover:text-white">Ігристі вина</a></li>
                        <li><a href="index.php?category=5" class="text-gray-400 hover:text-white">Десертні вина</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Інформація</h3>
                    <ul class="space-y-2">
                        <li><a href="about.php" class="text-gray-400 hover:text-white">Про нас</a></li>
                        <li><a href="delivery.php" class="text-gray-400 hover:text-white">Доставка та оплата</a></li>
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
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400 text-sm">
                <p>&copy; 2025 Винна крамниця. Всі права захищено.</p>
            </div>
        </div>
    </footer>

    <script>
        // Переключення вкладок
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                // Змінюємо активну вкладку
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('border-red-800', 'text-red-800');
                    btn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                });
                this.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                this.classList.add('border-red-800', 'text-red-800');
                
                // Показуємо відповідний контент
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });
                document.getElementById(this.dataset.tab + '-tab').classList.remove('hidden');
            });
        });

        // Управління кількістю товару
        const quantityInput = document.getElementById('quantity');
        document.getElementById('decrease-quantity').addEventListener('click', function() {
            let value = parseInt(quantityInput.value);
            if (value > 1) {
                quantityInput.value = value - 1;
            }
        });
        document.getElementById('increase-quantity').addEventListener('click', function() {
            let value = parseInt(quantityInput.value);
            let max = parseInt(quantityInput.getAttribute('max'));
            if (value < max) {
                quantityInput.value = value + 1;
            }
        });

        // Відображення зірок у формі відгуку
        document.querySelectorAll('.rating-star').forEach((star, index) => {
            star.addEventListener('mouseover', function() {
                for (let i = 0; i <= index; i++) {
                    document.querySelectorAll('.rating-star')[i].querySelector('i').classList.remove('far');
                    document.querySelectorAll('.rating-star')[i].querySelector('i').classList.add('fas');
                }
            });
            star.addEventListener('mouseout', function() {
                document.querySelectorAll('.rating-star').forEach((s, i) => {
                    if (!document.getElementById('rating-' + (i+1)).checked) {
                        s.querySelector('i').classList.remove('fas');
                        s.querySelector('i').classList.add('far');
                    }
                });
            });
            star.addEventListener('click', function() {
                for (let i = 0; i <= index; i++) {
                    document.getElementById('rating-' + (i+1)).checked = true;
                    document.querySelectorAll('.rating-star')[i].querySelector('i').classList.remove('far');
                    document.querySelectorAll('.rating-star')[i].querySelector('i').classList.add('fas');
                }
                for (let i = index + 1; i < 5; i++) {
                    document.getElementById('rating-' + (i+1)).checked = false;
                    document.querySelectorAll('.rating-star')[i].querySelector('i').classList.remove('fas');
                    document.querySelectorAll('.rating-star')[i].querySelector('i').classList.add('far');
                }
            });
        });

        // Додавання товару в кошик
        document.getElementById('add-to-cart').addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const quantity = document.getElementById('quantity').value;
            
            fetch('api/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartCount();
                    document.getElementById('cartModal').classList.remove('hidden');
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Помилка:', error));
        });

        // Додавання рекомендованих товарів у кошик
        document.querySelectorAll('.add-to-cart-button').forEach(button => {
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
            
            // Ініціалізуємо відображення зірок у формі відгуку
            document.querySelectorAll('input[name="rating"]').forEach(radio => {
                if (radio.checked) {
                    const index = parseInt(radio.value) - 1;
                    for (let i = 0; i <= index; i++) {
                        document.querySelectorAll('.rating-star')[i].querySelector('i').classList.remove('far');
                        document.querySelectorAll('.rating-star')[i].querySelector('i').classList.add('fas');
                    }
                }
            });
            
            // Якщо є якір #reviews, переключаємося на вкладку відгуків
            if (window.location.hash === '#reviews') {
                document.querySelector('[data-tab="reviews"]').click();
            }
        });
    </script>
</body>
</html>