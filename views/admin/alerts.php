<?php
// admin/alerts.php
// Сторінка системних повідомлень

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/AdminController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('admin')) {
    header('Location: /login.php?redirect=admin/alerts');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер адміністратора
$adminController = new AdminController();

// Отримуємо всі системні повідомлення
$alerts = $adminController->getSystemAlerts();

// Параметри фільтрації
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';

// Фільтруємо повідомлення
if (!empty($typeFilter)) {
    $filteredAlerts = array_filter($alerts, function($alert) use ($typeFilter) {
        return $alert['type'] === $typeFilter;
    });
    $alerts = $filteredAlerts;
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Системні повідомлення - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Бічне меню -->
        <div class="w-64 bg-red-900 text-white">
            <div class="p-4 font-bold text-xl">Винна крамниця</div>
            <nav class="mt-8">
                <a href="index.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    <span>Дашборд</span>
                </a>
                <a href="users.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-users mr-3"></i>
                    <span>Користувачі</span>
                </a>
                <a href="products.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-wine-bottle mr-3"></i>
                    <span>Товари</span>
                </a>
                <a href="categories.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-tags mr-3"></i>
                    <span>Категорії</span>
                </a>
                <a href="orders.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-shopping-cart mr-3"></i>
                    <span>Замовлення</span>
                </a>
                <a href="messages.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-envelope mr-3"></i>
                    <span>Повідомлення</span>
                </a>
                <a href="cameras.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-video mr-3"></i>
                    <span>Камери спостереження</span>
                </a>
                <a href="promotions.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-percent mr-3"></i>
                    <span>Акції</span>
                </a>
                <a href="statistics.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-chart-line mr-3"></i>
                    <span>Статистика</span>
                </a>
                <a href="settings.php" class="flex items-center px-4 py-3 hover:bg-red-800">
                    <i class="fas fa-cog mr-3"></i>
                    <span>Налаштування</span>
                </a>
                <a href="../logout.php" class="flex items-center px-4 py-3 hover:bg-red-800 mt-8">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Системні повідомлення</h1>
                    <div class="flex items-center">
                        <div class="relative">
                            <button class="flex items-center text-gray-700 focus:outline-none">
                                <img src="../assets/images/avatar.jpg" alt="Avatar" class="h-8 w-8 rounded-full mr-2">
                                <span><?= $currentUser['name'] ?></span>
                                <i class="fas fa-chevron-down ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Контент сторінки -->
            <main class="p-6">
                <!-- Фільтри -->
                <div class="mb-6 flex justify-between items-center">
                    <div class="flex space-x-2">
                        <a href="alerts.php" class="px-4 py-2 rounded bg-<?= empty($typeFilter) ? 'red-800 text-white' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                            Всі повідомлення
                        </a>
                        <a href="alerts.php?type=warning" class="px-4 py-2 rounded bg-<?= $typeFilter === 'warning' ? 'yellow-200 text-yellow-800' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Попередження
                        </a>
                        <a href="alerts.php?type=danger" class="px-4 py-2 rounded bg-<?= $typeFilter === 'danger' ? 'red-200 text-red-800' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                            <i class="fas fa-exclamation-circle mr-1"></i> Критичні
                        </a>
                        <a href="alerts.php?type=info" class="px-4 py-2 rounded bg-<?= $typeFilter === 'info' ? 'blue-200 text-blue-800' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                            <i class="fas fa-info-circle mr-1"></i> Інформаційні
                        </a>
                    </div>
                </div>

                <!-- Список системних повідомлень -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <?php if (empty($alerts)): ?>
                    <div class="p-6 text-center text-gray-500">
                        <i class="fas fa-bell-slash text-5xl mb-4"></i>
                        <p>Немає системних повідомлень. Все працює стабільно.</p>
                    </div>
                    <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($alerts as $alert): ?>
                        <div class="px-6 py-4 hover:bg-gray-50">
                            <div class="flex items-start">
                                <?php
                                $iconClass = '';
                                $textClass = '';
                                switch ($alert['type']) {
                                    case 'warning':
                                        $iconClass = 'fas fa-exclamation-triangle text-yellow-600';
                                        $textClass = 'text-yellow-800';
                                        break;
                                    case 'danger':
                                        $iconClass = 'fas fa-exclamation-circle text-red-600';
                                        $textClass = 'text-red-800';
                                        break;
                                    case 'info':
                                        $iconClass = 'fas fa-info-circle text-blue-600';
                                        $textClass = 'text-blue-800';
                                        break;
                                    default:
                                        $iconClass = 'fas fa-bell text-gray-600';
                                        $textClass = 'text-gray-800';
                                }
                                ?>
                                <i class="<?= $iconClass ?> mt-1 mr-3 text-lg"></i>
                                <div class="flex-1">
                                    <h3 class="font-semibold <?= $textClass ?>"><?= htmlspecialchars($alert['title']) ?></h3>
                                    <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($alert['message']) ?></p>
                                    <div class="text-xs text-gray-500 mt-2">
                                        <?= date('d.m.Y H:i', strtotime($alert['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>