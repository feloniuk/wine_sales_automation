<?php
// admin/messages.php
// Сторінка управління повідомленнями

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/AdminController.php';
require_once ROOT_PATH . '/controllers/SalesController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('admin')) {
    header('Location: /login.php?redirect=admin/messages');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролери
$adminController = new AdminController();
$salesController = new SalesController();

// Отримуємо всі повідомлення
$messages = $adminController->getAllMessages();

// Параметри фільтрації
$userFilter = isset($_GET['user']) ? intval($_GET['user']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$unreadOnly = isset($_GET['unread']) && $_GET['unread'] === '1';

// Фільтруємо повідомлення
if ($userFilter > 0 || !empty($search) || $unreadOnly) {
    $filteredMessages = [];
    
    foreach ($messages as $message) {
        // Фільтр за користувачем
        if ($userFilter > 0 && $message['sender_id'] != $userFilter && $message['receiver_id'] != $userFilter) {
            continue;
        }
        
        // Фільтр за пошуком
        if (!empty($search) && 
            stripos($message['subject'], $search) === false && 
            stripos($message['message'], $search) === false) {
            continue;
        }
        
        // Фільтр за непрочитаними
        if ($unreadOnly && $message['is_read'] == 1) {
            continue;
        }
        
        $filteredMessages[] = $message;
    }
    
    $messages = $filteredMessages;
}

// Обробка форми відправлення повідомлення
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $senderId = $currentUser['id'];
    $receiverId = intval($_POST['receiver_id'] ?? 0);
    $subject = $_POST['subject'] ?? '';
    $messageText = $_POST['message'] ?? '';
    
    if ($receiverId <= 0 || empty($subject) || empty($messageText)) {
        $message = 'Будь ласка, заповніть всі поля.';
        $messageType = 'error';
    } else {
        $result = $salesController->sendMessage($senderId, $receiverId, $subject, $messageText);
        
        if ($result['success']) {
            $message = 'Повідомлення успішно відправлено.';
            $messageType = 'success';
            // Оновлюємо список повідомлень
            $messages = $adminController->getAllMessages();
        } else {
            $message = 'Помилка при відправленні повідомлення: ' . $result['message'];
            $messageType = 'error';
        }
    }
}

// Обробка позначення повідомлення як прочитаного
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $messageId = intval($_GET['mark_read']);
    $result = $salesController->markMessageAsRead($messageId, $currentUser['id']);
    
    if ($result['success']) {
        $message = 'Повідомлення позначено як прочитане.';
        $messageType = 'success';
        // Оновлюємо список повідомлень
        $messages = $adminController->getAllMessages();
    } else {
        $message = 'Помилка при позначенні повідомлення: ' . $result['message'];
        $messageType = 'error';
    }
}

// Отримання списку користувачів для вибору отримувача
$users = $adminController->getAllUsers();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управління повідомленнями - Винна крамниця</title>
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
                <a href="messages.php" class="flex items-center px-4 py-3 bg-red-800">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Управління повідомленнями</h1>
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
                <!-- Повідомлення про результат операції -->
                <?php if (!empty($message)): ?>
                    <?php
                    $alertClass = '';
                    $iconClass = '';
                    if ($messageType === 'success') {
                        $alertClass = 'bg-green-100 border-green-500 text-green-700';
                        $iconClass = 'fas fa-check-circle text-green-500';
                    } elseif ($messageType === 'error') {
                        $alertClass = 'bg-red-100 border-red-500 text-red-700';
                        $iconClass = 'fas fa-exclamation-circle text-red-500';
                    } elseif ($messageType === 'warning') {
                        $alertClass = 'bg-yellow-100 border-yellow-500 text-yellow-700';
                        $iconClass = 'fas fa-exclamation-triangle text-yellow-500';
                    }
                    ?>
                    <div class="<?= $alertClass ?> border-l-4 p-4 mb-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="<?= $iconClass ?>"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm"><?= $message ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Список повідомлень -->
                <div class="md:col-span-3">
                        <!-- Фільтри та пошук -->
                        <div class="mb-4 flex flex-wrap justify-between">
                            <div class="flex space-x-2 items-center mb-2 md:mb-0">
                                <a href="messages.php" class="px-4 py-2 rounded bg-<?= empty($userFilter) && !$unreadOnly ? 'red-800 text-white' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                    Всі
                                </a>
                                <a href="messages.php?unread=1" class="px-4 py-2 rounded bg-<?= $unreadOnly ? 'red-800 text-white' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                    Непрочитані
                                </a>
                                <label class="flex items-center text-sm text-gray-600 ml-4">
                                    <span>Користувач:</span>
                                    <select onchange="if(this.value) window.location.href='messages.php?user='+this.value" class="ml-2 border rounded px-2 py-1">
                                        <option value="">Всі користувачі</option>
                                        <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>" <?= $userFilter == $user['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            
                            <form action="messages.php" method="GET" class="flex">
                                <?php if ($userFilter > 0): ?>
                                <input type="hidden" name="user" value="<?= $userFilter ?>">
                                <?php endif; ?>
                                <?php if ($unreadOnly): ?>
                                <input type="hidden" name="unread" value="1">
                                <?php endif; ?>
                                
                                <input type="text" name="search" placeholder="Пошук повідомлень..." value="<?= htmlspecialchars($search) ?>" 
                                       class="border rounded-l px-3 py-2 focus:outline-none focus:ring-red-500">
                                <button type="submit" class="bg-red-800 text-white px-3 py-2 rounded-r hover:bg-red-700">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <?php if (empty($messages)): ?>
                            <div class="p-6 text-center text-gray-500">
                                <i class="fas fa-envelope text-5xl mb-4"></i>
                                <p>Повідомлення не знайдені. Спробуйте змінити параметри фільтрації або відправте нове повідомлення.</p>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Відправник</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Одержувач</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Тема</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($messages as $msg): ?>
                                        <tr class="<?= $msg['is_read'] ? '' : 'bg-blue-50' ?>">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($msg['is_read']): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    <i class="fas fa-envelope-open text-gray-400 mr-1"></i> Прочитано
                                                </span>
                                                <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    <i class="fas fa-envelope text-blue-500 mr-1"></i> Нове
                                                </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($msg['sender_name']) ?></div>
                                                <div class="text-xs text-gray-500"><?= htmlspecialchars($msg['sender_role']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($msg['receiver_name']) ?></div>
                                                <div class="text-xs text-gray-500"><?= htmlspecialchars($msg['receiver_role']) ?></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($msg['subject']) ?></div>
                                                <div class="text-sm text-gray-500 truncate max-w-xs"><?= htmlspecialchars(substr($msg['message'], 0, 50)) . (strlen($msg['message']) > 50 ? '...' : '') ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <button type="button" onclick="viewMessage(<?= $msg['id'] ?>, '<?= htmlspecialchars(addslashes($msg['subject'])) ?>', '<?= htmlspecialchars(addslashes($msg['message'])) ?>', '<?= htmlspecialchars(addslashes($msg['sender_name'])) ?>', '<?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?>')" class="text-blue-600 hover:text-blue-900" title="Переглянути">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if (!$msg['is_read'] && $msg['receiver_id'] == $currentUser['id']): ?>
                                                    <a href="messages.php?mark_read=<?= $msg['id'] ?>" class="text-green-600 hover:text-green-900" title="Позначити як прочитане">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <button type="button" onclick="replyToMessage(<?= $msg['sender_id'] ?>, '<?= htmlspecialchars(addslashes($msg['subject'])) ?>')" class="text-indigo-600 hover:text-indigo-900" title="Відповісти">
                                                        <i class="fas fa-reply"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Форма відправлення повідомлення -->
                    <div class="md:col-span-3">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h2 class="text-lg font-semibold mb-4">Відправити повідомлення</h2>
                            <form action="messages.php" method="POST">
                                <input type="hidden" name="send_message" value="1">
                                
                                <div class="mb-4">
                                    <label for="receiver_id" class="block text-sm font-medium text-gray-700 mb-1">Одержувач *</label>
                                    <select id="receiver_id" name="receiver_id" required class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                        <option value="">Виберіть одержувача</option>
                                        <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Тема *</label>
                                    <input type="text" id="subject" name="subject" required 
                                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Повідомлення *</label>
                                    <textarea id="message" name="message" rows="5" required
                                              class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                                </div>
                                
                                <div class="flex justify-end">
                                    <button type="submit" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                                        Відправити
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    
                </div>
            </main>
        </div>
    </div>

    <!-- Модальне вікно для перегляду повідомлення -->
    <div id="viewMessageModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900" id="messageSubject"></h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeViewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-4">
                <div class="flex justify-between text-sm text-gray-500 mb-2">
                    <span id="messageSender"></span>
                    <span id="messageDate"></span>
                </div>
                <div class="border-t pt-4">
                    <p class="text-gray-700" id="messageContent"></p>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded" onclick="closeViewModal()">
                    Закрити
                </button>
            </div>
        </div>
    </div>

    <script>
        // Функції для перегляду повідомлення
        function viewMessage(id, subject, message, sender, date) {
            document.getElementById('messageSubject').textContent = subject;
            document.getElementById('messageSender').textContent = 'Від: ' + sender;
            document.getElementById('messageDate').textContent = date;
            document.getElementById('messageContent').textContent = message;
            document.getElementById('viewMessageModal').classList.remove('hidden');
        }
        
        function closeViewModal() {
            document.getElementById('viewMessageModal').classList.add('hidden');
        }
        
        // Функція для відповіді на повідомлення
        function replyToMessage(senderId, subject) {
            document.getElementById('receiver_id').value = senderId;
            document.getElementById('subject').value = 'Re: ' + subject;
            document.getElementById('message').focus();
            
            // Прокручування до форми відправлення
            document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>