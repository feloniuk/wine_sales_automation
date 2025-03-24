<?php
// account/profile.php
// Сторінка редагування профілю клієнта

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('customer')) {
    header('Location: /login.php?redirect=account/profile');
    exit;
}

// Отримуємо дані поточного користувача
$currentUser = $authController->getCurrentUser();

// Обробка форми оновлення профілю
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
        'address' => trim($_POST['address']),
        'city' => trim($_POST['city']),
        'region' => trim($_POST['region']),
        'postal_code' => trim($_POST['postal_code'])
    ];

    // Оновлення паролю (опціонально)
    if (!empty($_POST['new_password'])) {
        if (strlen($_POST['new_password']) < 6) {
            $message = 'Пароль повинен містити принаймні 6 символів.';
            $messageType = 'error';
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $message = 'Паролі не співпадають.';
            $messageType = 'error';
        } else {
            $userData['password'] = $_POST['new_password'];
        }
    }

    // Якщо немає помилок - оновлюємо профіль
    if (empty($message)) {
        $result = $authController->updateUserProfile($currentUser['id'], $userData);
        
        if ($result['success']) {
            $message = 'Профіль успішно оновлено.';
            $messageType = 'success';
            
            // Оновлюємо дані користувача
            $currentUser = $authController->getCurrentUser();
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редагування профілю - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Верхнє меню (як в інших сторінках особистого кабінету) -->
    <header class="bg-red-800 text-white">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center">
                <a href="../index.php" class="font-bold text-2xl">Винна крамниця</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="../../index.php" class="hover:text-red-200">Каталог</a>
                <a href="index.php" class="bg-red-700 px-3 py-1 rounded-lg hover:bg-red-600">Кабінет</a>
                <a href="../../cart.php" class="relative">
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
            <!-- Бічне меню (як в index.php особистого кабінету) -->
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
                        <a href="profile.php" class="block px-4 py-3 bg-red-50 text-red-800 font-semibold">
                            <i class="fas fa-user mr-2"></i> Особисті дані
                        </a>
                        <a href="messages.php" class="block px-4 py-3 hover:bg-red-50">
                            <i class="fas fa-envelope mr-2"></i> Повідомлення
                        </a>
                        <a href="reviews.php" class="block px-4 py-3 hover:bg-red-50">
                            <i class="fas fa-star mr-2"></i> Мої відгуки
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Основний контент -->
            <div class="flex-1">
                <div class="bg-white rounded-lg shadow p-6">
                    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Редагування профілю</h1>

                    <!-- Повідомлення про результат -->
                    <?php if (!empty($message)): ?>
                        <div class="mb-6 p-4 rounded 
                            <?= $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <form action="profile.php" method="POST" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Ім'я та прізвище</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?= htmlspecialchars($currentUser['name']) ?>"
                                       class="w-full border rounded px-3 py-2">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" id="email" name="email" required 
                                       value="<?= htmlspecialchars($currentUser['email']) ?>"
                                       class="w-full border rounded px-3 py-2">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>"
                                       class="w-full border rounded px-3 py-2">
                            </div>
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Місто</label>
                                <input type="text" id="city" name="city" 
                                       value="<?= htmlspecialchars($currentUser['city'] ?? '') ?>"
                                       class="w-full border rounded px-3 py-2">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Адреса</label>
                                <input type="text" id="address" name="address" 
                                       value="<?= htmlspecialchars($currentUser['address'] ?? '') ?>"
                                       class="w-full border rounded px-3 py-2">
                            </div>
                            <div>
                                <label for="region" class="block text-sm font-medium text-gray-700 mb-1">Область</label>
                                <input type="text" id="region" name="region" 
                                       value="<?= htmlspecialchars($currentUser['region'] ?? '') ?>"
                                       class="w-full border rounded px-3 py-2">
                            </div>
                        </div>

                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Поштовий індекс</label>
                            <input type="text" id="postal_code" name="postal_code" 
                                   value="<?= htmlspecialchars($currentUser['postal_code'] ?? '') ?>"
                                   class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mt-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Зміна паролю</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Новий пароль</label>
                                    <input type="password" id="new_password" name="new_password" 
                                           class="w-full border rounded px-3 py-2">
                                    <p class="text-xs text-gray-500 mt-1">Залиште порожнім, якщо не хочете змінювати</p>
                                </div>
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Підтвердження паролю</label>
                                    <input type="password" id="confirm_password" name="confirm_password" 
                                           class="w-full border rounded px-3 py-2">
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="bg-red-800 hover:bg-red-700 text-white px-6 py-2 rounded">
                                Зберегти зміни
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Футер (як в інших сторінках особистого кабінету) -->
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

        // Оновлення кількості товарів при завантаженні сторінки
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>