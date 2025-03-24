<?php
// account/messages.php
// Сторінка повідомлень клієнта

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('customer')) {
    header('Location: /login.php?redirect=account/messages');
    exit;
}

// Отримуємо дані поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер для роботи з повідомленнями
$customerController = new CustomerController();

// Отримуємо повідомлення користувача
$messages = $customerController->getCustomerMessages($currentUser['id']);

// Обробка надсилання нового повідомлення
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $messageText = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($messageText)) {
        $message = 'Будь ласка, заповніть тему та текст повідомлення.';
        $messageType = 'error';
    } else {
        $result = $customerController->sendMessageToManager(
            $currentUser['id'], 
            $subject, 
            $messageText
        );

        if ($result['success']) {
            $message = 'Повідомлення успішно надіслано.';
            $messageType = 'success';
            
            // Оновлюємо список повідомлень
            $messages = $customerController->getCustomerMessages($currentUser['id']);
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
    <title>Повідомлення - Винна крамниця</title>
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
                        <a href="messages.php" class="block px-4 py-3 bg-red-50 text-red-800 font-semibold">
                            <i class="fas fa-envelope mr-2"></i> Повідомлення
                            <?php 
                            $unreadCount = count(array_filter($messages, function($msg) {
                                return $msg['direction'] === 'incoming' && $msg['is_read'] == 0;
                            })); 
                            if ($unreadCount > 0): ?>
                            <span class="ml-2 bg-red-600 text-white rounded-full px-2 py-0.5 text-xs">
                                <?= $unreadCount ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <a href="reviews.php" class="block px-4 py-3 hover:bg-red-50">
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

                <!-- Форма надсилання повідомлення -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Надіслати повідомлення</h2>
                    <form action="messages.php" method="POST">
                        <div class="mb-4">
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Тема</label>
                            <input type="text" id="subject" name="subject" required
                                   class="w-full border rounded px-3 py-2"
                                   placeholder="Введіть тему повідомлення">
                        </div>
                        <div class="mb-4">
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Повідомлення</label>
                            <textarea id="message" name="message" required rows="4"
                                      class="w-full border rounded px-3 py-2"
                                      placeholder="Введіть текст повідомлення"></textarea>
                        </div>
                        <button type="submit" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                            Надіслати повідомлення
                        </button>
                    </form>
                </div>

                <!-- Список повідомлень -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-xl font-semibold">Історія повідомлень</h2>
                    </div>
                    <?php if (empty($messages)): ?>
                        <div class="p-6 text-center text-gray-500">
                            <i class="fas fa-envelope-open-text text-4xl mb-4 text-gray-300"></i>
                            <p>У вас ще немає жодного повідомлення</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($messages as $msg): ?>
                            <div class="p-6 <?= $msg['direction'] === 'incoming' ? 'bg-gray-50' : '' ?>">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 class="font-semibold text-gray-800">
                                            <?= htmlspecialchars($msg['subject']) ?>
                                        </h3>
                                        <p class="text-sm text-gray-600">
                                            <?= $msg['direction'] === 'incoming' ? 'Від' : 'До' ?>: 
                                            <?= htmlspecialchars($msg['user_name']) ?> 
                                            (<?= htmlspecialchars($msg['user_role']) ?>)
                                        </p>
                                    </div>
                                    <span class="text-sm text-gray-500">
                                        <?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?>
                                    </span>
                                </div>
                                <p class="text-gray-700 mb-2">
                                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                </p>
                                <?php if ($msg['direction'] === 'incoming' && !$msg['is_read']): ?>
                                    <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">
                                        Нове
                                    </span>
                                <?php endif; ?>
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

        // Оновлення кількості товарів при завантаженні сторінки
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>