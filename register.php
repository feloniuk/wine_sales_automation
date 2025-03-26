<?php
// register.php
// Сторінка реєстрації нового користувача

// Підключаємо конфігурацію
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';

// Перевіряємо, чи користувач вже авторизований
$authController = new AuthController();
if ($authController->isLoggedIn()) {
    // Перенаправляємо в залежності від ролі користувача
    $currentUser = $authController->getCurrentUser();
    switch ($currentUser['role']) {
        case 'admin':
            header('Location: views/admin/index.php');
            break;
        case 'warehouse':
            header('Location: views/warehouse/index.php');
            break;
        case 'sales':
            header('Location: views/sales/index.php');
            break;
        case 'customer':
            header('Location: views/account/index.php');
            break;
        default:
            header('Location: index.php');
    }
    exit;
}

// Отримуємо параметр перенаправлення, якщо є
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

// Обробка форми реєстрації
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'username' => isset($_POST['username']) ? trim($_POST['username']) : '',
        'password' => isset($_POST['password']) ? $_POST['password'] : '',
        'confirm_password' => isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '',
        'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
        'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
        'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : '',
        'address' => isset($_POST['address']) ? trim($_POST['address']) : '',
        'city' => isset($_POST['city']) ? trim($_POST['city']) : '',
        'region' => isset($_POST['region']) ? trim($_POST['region']) : '',
        'postal_code' => isset($_POST['postal_code']) ? trim($_POST['postal_code']) : ''
    ];
    
    // Базова валідація
    if (empty($userData['username']) || empty($userData['password']) || empty($userData['name']) || empty($userData['email'])) {
        $message = 'Будь ласка, заповніть всі обов\'язкові поля.';
        $messageType = 'error';
    } elseif ($userData['password'] !== $userData['confirm_password']) {
        $message = 'Паролі не співпадають.';
        $messageType = 'error';
    } elseif (strlen($userData['password']) < 6) {
        $message = 'Пароль повинен містити не менше 6 символів.';
        $messageType = 'error';
    } else {
        // Реєстрація користувача
        $result = $authController->registerCustomer($userData);
        
        if ($result['success']) {
            $message = 'Ви успішно зареєструвалися! Тепер ви можете увійти в систему.';
            $messageType = 'success';
            
            // Перенаправляємо на сторінку входу або відразу авторизуємо
            header('Location: login.php?registered=1' . (!empty($redirect) ? '&redirect=' . urlencode($redirect) : ''));
            exit;
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
    <title>Реєстрація - Винна крамниця</title>
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
                <div class="flex items-center space-x-2">
                    <a href="login.php" class="hover:text-red-200">Увійти</a>
                    <span>|</span>
                    <a href="register.php" class="hover:text-red-200 font-bold">Реєстрація</a>
                </div>
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
                <span class="text-gray-700">Реєстрація</span>
            </div>
        </div>

        <!-- Вміст сторінки -->
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6">
                    <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">Реєстрація нового користувача</h1>
                    
                    <?php if (!empty($message)): ?>
                        <div class="bg-<?= $messageType === 'error' ? 'red' : 'green' ?>-100 border-l-4 border-<?= $messageType === 'error' ? 'red' : 'green' ?>-500 text-<?= $messageType === 'error' ? 'red' : 'green' ?>-700 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-<?= $messageType === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm"><?= htmlspecialchars($message) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form action="register.php<?= !empty($redirect) ? '?redirect=' . htmlspecialchars($redirect) : '' ?>" method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Блок 1: Обов'язкові дані -->
                            <div>
                                <h2 class="font-semibold text-lg mb-4 text-gray-700">Обов'язкові дані</h2>
                                
                                <div class="mb-4">
                                    <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Логін *</label>
                                    <input type="text" id="username" name="username" required 
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Ім'я та прізвище *</label>
                                    <input type="text" id="name" name="name" required 
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                           value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email *</label>
                                    <input type="email" id="email" name="email" required 
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Пароль *</label>
                                    <input type="password" id="password" name="password" required minlength="6"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <p class="text-xs text-gray-500 mt-1">Мінімум 6 символів</p>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Підтвердження пароля *</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                </div>
                            </div>
                            
                            <!-- Блок 2: Додаткова інформація -->
                            <div>
                                <h2 class="font-semibold text-lg mb-4 text-gray-700">Додаткова інформація</h2>
                                
                                <div class="mb-4">
                                    <label for="phone" class="block text-gray-700 text-sm font-bold mb-2">Телефон</label>
                                    <input type="tel" id="phone" name="phone"
                                           placeholder="+380501234567"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                           value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="address" class="block text-gray-700 text-sm font-bold mb-2">Адреса</label>
                                    <input type="text" id="address" name="address"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                           value="<?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?>">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="city" class="block text-gray-700 text-sm font-bold mb-2">Місто</label>
                                    <input type="text" id="city" name="city"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                           value="<?= isset($_POST['city']) ? htmlspecialchars($_POST['city']) : '' ?>">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="region" class="block text-gray-700 text-sm font-bold mb-2">Область</label>
                                    <input type="text" id="region" name="region"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                           value="<?= isset($_POST['region']) ? htmlspecialchars($_POST['region']) : '' ?>">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="postal_code" class="block text-gray-700 text-sm font-bold mb-2">Поштовий індекс</label>
                                    <input type="text" id="postal_code" name="postal_code"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                           value="<?= isset($_POST['postal_code']) ? htmlspecialchars($_POST['postal_code']) : '' ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex items-center justify-between">
                            <div class="flex items-center">
                                <input id="terms" name="terms" type="checkbox" required
                                       class="h-4 w-4 text-red-800 focus:ring-red-700 border-gray-300 rounded">
                                <label for="terms" class="ml-2 block text-sm text-gray-700">
                                    Я погоджуюсь з <a href="terms.php" class="text-red-700 hover:text-red-800">умовами використання</a> та <a href="privacy.php" class="text-red-700 hover:text-red-800">політикою конфіденційності</a>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" class="w-full bg-red-800 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Зареєструватися
                            </button>
                        </div>
                        
                        <p class="text-center text-sm text-gray-600 mt-6">
                            Вже зареєстровані? 
                            <a href="login.php<?= !empty($redirect) ? '?redirect=' . htmlspecialchars($redirect) : '' ?>" class="text-red-700 hover:text-red-800">
                                Увійти
                            </a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </main>

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