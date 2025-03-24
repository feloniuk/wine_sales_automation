<?php
// account/reviews.php
// Сторінка відгуків клієнта

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('customer')) {
    header('Location: /login.php?redirect=account/reviews');
    exit;
}

// Отримуємо дані поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер для роботи з товарами та відгуками
$customerController = new CustomerController();

// Отримуємо список замовлень для пошуку товарів, на які можна залишити відгук
$orders = $customerController->getCustomerOrders($currentUser['id']);

// Допоміжна функція для виведення зірок рейтингу
function renderStars($rating) {
    $output = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $output .= '<i class="fas fa-star text-yellow-400"></i>';
        } else {
            $output .= '<i class="far fa-star text-yellow-400"></i>';
        }
    }
    return $output;
}

// Обробка форми додавання відгуку
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review'])) {
    $productId = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $reviewText = trim($_POST['review_text']);

    if ($rating < 1 || $rating > 5) {
        $message = 'Будь ласка, виберіть коректний рейтинг.';
        $messageType = 'error';
    } elseif (empty($reviewText)) {
        $message = 'Текст відгуку не може бути порожнім.';
        $messageType = 'error';
    } else {
        $result = $customerController->addProductReview(
            $productId, 
            $currentUser['id'], 
            $rating, 
            $reviewText
        );

        if ($result['success']) {
            $message = 'Відгук успішно надіслано.';
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}

// Отримуємо список відгуків користувача
$reviewedProductIds = [];
$reviews = [];

if (!empty($orders)) {
    // Збираємо унікальні ID товарів з замовлень
    $productIds = [];
    foreach ($orders as $order) {
        // Деталізація замовлення
        $orderDetails = $customerController->getCustomerOrderDetails($order['id'], $currentUser['id']);
        
        if ($orderDetails['success']) {
            foreach ($orderDetails['items'] as $item) {
                $productIds[] = $item['product_id'];
            }
        }
    }

    // Отримуємо деталі товарів
    $productDetails = [];
    foreach ($productIds as $productId) {
        $details = $customerController->getProductDetails($productId);
        if ($details['success']) {
            $productDetails[$productId] = $details['product'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мої відгуки - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Верхнє меню -->
    <header class="bg-red-800 text-white">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center">
                <a href="../index.php" class="font-bold text-2xl">Винна крамниця</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="../index.php" class="hover:text-red-200">Каталог</a>
                <a href="index.php" class="bg-red-700 px-3 py-1 rounded-lg hover:bg-red-600">Кабінет</a>
                <a href="../cart.php" class="relative">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <span class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs cart-count">0</span>
                </a>
                <div class="relative">
                    <button class="flex items-center text-white focus:outline-none">
                        <img src="../../assets/images/avatar.jpg" alt="Avatar" class="h-8 w-8 rounded-full mr-2">
                        <span><?= htmlspecialchars($currentUser['name']) ?></span>
                        <i class="fas fa-chevron-down ml-2"></i>
                    </button>
                </div>
                <a href="../logout.php" class="hover:text-red-200">
                    <i class="fas fa-sign-out-alt text-xl"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row md:space-x-6">
            <!-- Бічне меню -->
            <div class="w-full md:w-64 mb-6 md:mb-0">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-4 bg-red-800 text-white">
                        <h2 class="text-lg font-semibold">Меню</h2>
                    </div>
                    <nav class="divide-y divide-gray-200">
                        <a href="index.php" class="block px-4 py-3 hover:bg-red-50">
                            <i class="fas fa-tachometer-alt mr-2"></i> Дашборд
                        </a>
                        <a href="orders.php" class="block px-4 py-3 hover:bg-red-50">
                            <i class="fas fa-shopping-cart mr-2"></i> Мої замовлення
                        </a>
                        <a href="profile.php" class="block px-4 py-3 hover:bg-red-50">
                            <i class="fas fa-user mr-2"></i> Особисті дані
                        </a>
                        <a href="messages.php" class="block px-4 py-3 hover:bg-red-50">
                            <i class="fas fa-envelope mr-2"></i> Повідомлення
                        </a>
                        <a href="reviews.php" class="block px-4 py-3 bg-red-50 text-red-800 font-semibold">
                            <i class="fas fa-star mr-2"></i> Мої відгуки
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Основний контент -->
            <div class="flex-1">
                <!-- Повідомлення про результат -->
                <?php if (!empty($message)): ?>
                    <div class="mb-6 p-4 rounded 
                        <?= $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Додавання нового відгуку -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Залишити відгук</h2>
                    
                    <!-- Список товарів з замовлень -->
                    <?php if (empty($productDetails)): ?>
                        <div class="text-center text-gray-500">
                            <i class="fas fa-shopping-bag text-4xl mb-4 text-gray-300"></i>
                            <p>У вас ще немає замовлених товарів для відгуку</p>
                        </div>
                    <?php else: ?>
                        <form action="reviews.php" method="POST">
                            <div class="mb-4">
                                <label for="product_id" class="block text-sm font-medium text-gray-700 mb-2">Виберіть товар</label>
                                <select id="product_id" name="product_id" required class="w-full border rounded px-3 py-2">
                                    <?php foreach ($productDetails as $productId => $product): ?>
                                        <option value="<?= $productId ?>">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ваша оцінка</label>
                                <div class="rating-input flex space-x-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <input type="radio" name="rating" value="<?= $i ?>" id="rating-<?= $i ?>" class="hidden" required>
                                    <label for="rating-<?= $i ?>" class="text-2xl text-gray-300 hover:text-yellow-400 cursor-pointer rating-star">
                                        <i class="far fa-star"></i>
                                    </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="review_text" class="block text-sm font-medium text-gray-700 mb-2">Ваш відгук</label>
                                <textarea id="review_text" name="review_text" rows="4" required 
                                          class="w-full border rounded px-3 py-2"
                                          placeholder="Напишіть свій відгук про товар"></textarea>
                            </div>
                            
                            <button type="submit" name="add_review" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                                Надіслати відгук
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Список моїх відгуків -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-xl font-semibold">Мої відгуки</h2>
                    </div>
                    
                    <?php 
                    // Отримуємо відгуки користувача
                    if (isset($currentUser['id'])) {
                        $reviews = $customerController->getCustomerMessages($currentUser['id']);
                    }
                    ?>

                    <?php if (empty($reviews)): ?>
                        <div class="p-6 text-center text-gray-500">
                            <i class="fas fa-comment-slash text-4xl mb-4 text-gray-300"></i>
                            <p>У вас ще немає жодного відгуку</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($reviews as $review): ?>
                                <div class="p-6">
                                    <div class="flex justify-between items-center mb-2">
                                        <div>
                                            <h3 class="font-semibold text-gray-800">
                                                <?= htmlspecialchars($review['subject']) ?>
                                            </h3>
                                            <div class="text-sm text-gray-600 mb-2">
                                                <?= renderStars(4) ?> <!-- приклад, реальний рейтинг має бути з бази -->
                                            </div>
                                        </div>
                                        <span class="text-sm text-gray-500">
                                            <?= date('d.m.Y H:i', strtotime($review['created_at'])) ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-700">
                                        <?= nl2br(htmlspecialchars($review['message'])) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Футер -->
    <footer class="bg-gray-900 text-white py-8 mt-16">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <h3 class="text-xl font-semibold mb-4">Винна крамниця</h3>
                    <p class="text-gray-400">Ваш надійний партнер у світі вина з 2015 року.</p>
                </div>
                <!-- Інші блоки футера -->
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400 text-sm">
                <p>&copy; 2025 Винна крамниця. Всі права захищено.</p>
            </div>
        </div>
    </footer>

    <script>
        // Підрахунок кількості товарів у кошику
        function updateCartCount() {
            fetch('../api/cart_count.php')
                .then(response => response.json())
                .then(data => {
                    document.querySelector('.cart-count').textContent = data.count;
                })
                .catch(error => console.error('Помилка:', error));
        }

        // Обробка вибору рейтингу
        document.querySelectorAll('.rating-star').forEach// Обробка вибору рейтингу
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

        // Оновлення кількості товарів при завантаженні сторінки
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>