<?php
// login.php
// Сторінка авторизації користувача

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

// Обробка форми авторизації
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    if (empty($username) || empty($password)) {
        $message = 'Будь ласка, введіть логін та пароль';
        $messageType = 'error';
    } else {
        $result = $authController->login($username, $password);
        
        if ($result['success']) {
            // Якщо є параметр перенаправлення, перенаправляємо на вказану сторінку
            if (!empty($redirect)) {
                header('Location: ' . $redirect);
                exit;
            }
            
            // Інакше перенаправляємо в залежності від ролі
            switch ($result['role']) {
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
    <title>Вхід в особистий кабінет - Винна крамниця</title>
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
                    <a href="login.php" class="hover:text-red-200 font-bold">Увійти</a>
                    <span>|</span>
                    <a href="register.php" class="hover:text-red-200">Реєстрація</a>
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
                <span class="text-gray-700">Вхід</span>
            </div>
        </div>

        <!-- Вміст сторінки -->
        <div class="max-w-md mx-auto">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6">
                    <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">Вхід в особистий кабінет</h1>
                    
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
                    
                    <form action="login.php<?= !empty($redirect) ? '?redirect=' . htmlspecialchars($redirect) : '' ?>" method="POST">
                        <div class="mb-4">
                            <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Логін або Email</label>
                            <input type="text" id="username" name="username" required 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                   value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                        </div>
                        
                        <div class="mb-6">
                            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Пароль</label>
                            <input type="password" id="password" name="password" required 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center">
                                <input id="remember" name="remember" type="checkbox" 
                                       class="h-4 w-4 text-red-800 focus:ring-red-700 border-gray-300 rounded">
                                <label for="remember" class="ml-2 block text-sm text-gray-700">
                                    Запам'ятати мене
                                </label>
                            </div>
                            <a href="forgot_password.php" class="text-sm text-red-700 hover:text-red-800">Забули пароль?</a>
                        </div>
                        
                        <div class="mb-6">
                            <button type="submit" class="w-full bg-red-800 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Увійти
                            </button>
                        </div>
                        
                        <p class="text-center text-sm text-gray-600">
                            Ще не зареєстровані? 
                            <a href="register.php<?= !empty($redirect) ? '?redirect=' . htmlspecialchars($redirect) : '' ?>" class="text-red-700 hover:text-red-800">
                                Зареєструватися
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