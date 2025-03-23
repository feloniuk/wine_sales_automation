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
                                    <p class="text-xl font-semibold"><?= $dashboardData['customer']['last_order_date'] ? date('d.m.Y', strtotime($dashboardData['customer']['last_order_date'])) : 'Немає