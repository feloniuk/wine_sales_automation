<?php
// account/index.php
// Особистий кабінет клієнта

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('customer')) {
    header('Location: /login.php?redirect=account');
    exit;
}

// Отримуємо дані для профілю
$currentUser = $authController->getCurrentUser();
$customerController = new CustomerController();
$dashboardData = $customerController->getCustomerDashboard($currentUser['id']);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Особистий кабінет - Винна крамниця</title>
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
                        <img src="../assets/images/avatar.jpg" alt="Avatar" class="h-8 w-8 rounded-full mr-2">
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

    <!-- Основний контент -->
    <main class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row md:space-x-6">
            <!-- Бічне меню -->
            <div class="w-full md:w-64 mb-6 md:mb-0">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-4 bg-red-800 text-white">
                        <h2 class="text-lg font-semibold">Меню</h2>
                    </div>
                    <nav class="divide-y divide-gray-200">
                        <a href="index.php" class="block px-4 py-3 bg-red-50 text-red-800 font-semibold">
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
                            <?php if (count($dashboardData['unread_messages']) > 0): ?>
                            <span class="ml-2 bg-red-600 text-white rounded-full px-2 py-0.5 text-xs">
                                <?= count($dashboardData['unread_messages']) ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <a href="wishlist.php" class="block px-4 py-3 hover:bg-red-50">
                            <i class="fas fa-heart mr-2"></i> Список бажань
                        </a>
                        <a href="reviews.php" class="block px-4 py-3 hover:bg-red-50">
                            <i class="fas fa-star mr-2"></i> Мої відгуки
                        </a>
                    </nav>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden mt-6">
                    <div class="p-4 border-b">
                        <h2 class="text-lg font-semibold">Мій профіль</h2>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center mb-4">
                            <div class="h-16 w-16 rounded-full bg-red-100 flex items-center justify-center mr-4">
                                <i class="fas fa-user text-2xl text-red-800"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800"><?= htmlspecialchars($dashboardData['customer']['name']) ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($dashboardData['customer']['email']) ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($dashboardData['customer']['phone'] ?? 'Телефон не вказано') ?></p>
                            </div>
                        </div>
                        <a href="profile.php" class="block w-full py-2 px-4 bg-red-800 hover:bg-red-700 text-white text-center rounded-lg">
                            Редагувати профіль
                        </a>
                    </div>
                </div>
            </div>

            <!-- Основний контент -->
            <div class="flex-1">
                <!-- Вітання та основні показники -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h1 class="text-2xl font-semibold text-gray-800 mb-2">Ласкаво просимо, <?= htmlspecialchars($dashboardData['customer']['name']) ?>!</h1>
                    <p class="text-gray-600 mb-4">Ось статистика ваших покупок та рекомендовані товари.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                        <div class="bg-red-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-red-100 text-red-800 mr-4">
                                    <i class="fas fa-shopping-cart text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-gray-500">Замовлень</p>
                                    <p class="text-xl font-semibold"><?= $dashboardData['customer']['order_count'] ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-800 mr-4">
                                    <i class="fas fa-money-bill-wave text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-gray-500">Витрачено</p>
                                    <p class="text-xl font-semibold"><?= number_format($dashboardData['customer']['total_spent'], 2) ?> ₴</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-800 mr-4">
                                    <i class="fas fa-calendar-alt text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-gray-500">Останнє замовлення</p>
                                    <p class="text-xl font-semibold"><?= $dashboardData['customer']['last_order_date'] ? date('d.m.Y', strtotime($dashboardData['customer']['last_order_date'])) : 'Немає' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Останні замовлення -->
                <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                    <div class="p-6 border-b">
                        <h2 class="text-xl font-semibold">Останні замовлення</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">№</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($dashboardData['recent_orders'])): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">У вас ще немає замовлень</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($dashboardData['recent_orders'] as $order): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= $order['id'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d.m.Y', strtotime($order['created_at'])) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= number_format($order['total_amount'], 2) ?> ₴</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        switch ($order['status']) {
                                            case 'pending':
                                                echo '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Нове</span>';
                                                break;
                                            case 'processing':
                                                echo '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">В обробці</span>';
                                                break;
                                            case 'ready_for_pickup':
                                                echo '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">Готове до відправки</span>';
                                                break;
                                            case 'shipped':
                                                echo '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">Відправлено</span>';
                                                break;
                                            case 'delivered':
                                                echo '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Доставлено</span>';
                                                break;
                                            case 'completed':
                                                echo '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Завершено</span>';
                                                break;
                                            case 'cancelled':
                                                echo '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Скасовано</span>';
                                                break;
                                            default:
                                                echo '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">' . $order['status'] . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <a href="order_details.php?id=<?= $order['id'] ?>" class="text-red-800 hover:underline">Детальніше</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-6 border-t">
                        <a href="orders.php" class="text-red-800 hover:underline">Всі замовлення →</a>
                    </div>
                </div>

                <!-- Рекомендовані товари -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6 border-b">
                        <h2 class="text-xl font-semibold">Рекомендовані товари</h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($dashboardData['recommended_products'])): ?>
                        <p class="text-gray-500 text-center">У нас поки немає рекомендацій для вас</p>
                        <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <?php foreach ($dashboardData['recommended_products'] as $product): ?>
                            <div class="bg-white rounded-lg overflow-hidden shadow hover:shadow-md transition-shadow">
                                <a href="../product.php?id=<?= $product['id'] ?>">
                                    <img src="../assets/images/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-48 object-cover">
                                </a>
                                <div class="p-4">
                                    <a href="../product.php?id=<?= $product['id'] ?>" class="block mb-1">
                                        <h3 class="text-lg font-semibold text-gray-800 hover:text-red-800 transition-colors"><?= htmlspecialchars($product['name']) ?></h3>
                                    </a>
                                    <p class="text-sm text-gray-600 mb-2"><?= htmlspecialchars($product['category_name']) ?></p>
                                    <p class="text-red-800 font-bold mb-2"><?= number_format($product['price'], 2) ?> ₴</p>
                                    <button class="add-to-cart-button w-full py-2 px-4 bg-red-800 hover:bg-red-700 text-white rounded-lg text-sm"
                                            data-id="<?= $product['id'] ?>">
                                        Додати в кошик
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
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
                <div>
                    <h3 class="text-xl font-semibold mb-4">Категорії</h3>
                    <ul class="space-y-2">
                        <li><a href="../index.php?category=1" class="text-gray-400 hover:text-white">Червоні вина</a></li>
                        <li><a href="../index.php?category=2" class="text-gray-400 hover:text-white">Білі вина</a></li>
                        <li><a href="../index.php?category=3" class="text-gray-400 hover:text-white">Рожеві вина</a></li>
                        <li><a href="../index.php?category=4" class="text-gray-400 hover:text-white">Ігристі вина</a></li>
                        <li><a href="../index.php?category=5" class="text-gray-400 hover:text-white">Десертні вина</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Інформація</h3>
                    <ul class="space-y-2">
                        <li><a href="../about.php" class="text-gray-400 hover:text-white">Про нас</a></li>
                        <li><a href="../delivery.php" class="text-gray-400 hover:text-white">Доставка та оплата</a></li>
                        <li><a href="../contact.php" class="text-gray-400 hover:text-white">Контакти</a></li>
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
        // Підрахунок кількості товарів у кошику
        function updateCartCount() {
            fetch('../api/cart_count.php')
                .then(response => response.json())
                .then(data => {
                    document.querySelector('.cart-count').textContent = data.count;
                })
                .catch(error => console.error('Помилка:', error));
        }

        // Додавання товару в кошик
        document.querySelectorAll('.add-to-cart-button').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                
                fetch('../api/add_to_cart.php', {
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
                        alert('Товар успішно додано до кошика!');
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Помилка:', error));
            });
        });

        // Оновлення кількості товарів при завантаженні сторінки
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>