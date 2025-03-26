<?php
// sales/messages.php
// Сторінка повідомлень для менеджера з продажу

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/SalesController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('sales')) {
    header('Location: /login.php?redirect=sales/messages');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер продажів
$salesController = new SalesController();

// Отримуємо список повідомлень для менеджера
$messages = $salesController->getManagerMessages($currentUser['id']);

// Обробка прочитання повідомлень
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $messageId = intval($_GET['mark_read']);
    $salesController->markMessageAsRead($messageId, $currentUser['id']);
    header('Location: messages.php');
    exit;
}

// Обробка форми відправки нового повідомлення
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiverId = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $messageText = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    if ($receiverId <= 0) {
        $message = 'Будь ласка, виберіть отримувача повідомлення.';
        $messageType = 'error';
    } elseif (empty($subject) || empty($messageText)) {
        $message = 'Будь ласка, заповніть тему та текст повідомлення.';
        $messageType = 'error';
    } else {
        $result = $salesController->sendMessage($currentUser['id'], $receiverId, $subject, $messageText);
        
        if ($result['success']) {
            $message = 'Повідомлення успішно відправлено.';
            $messageType = 'success';
            
            // Оновлюємо список повідомлень
            $messages = $salesController->getManagerMessages($currentUser['id']);
        } else {
            $message = 'Помилка при відправленні повідомлення: ' . $result['message'];
            $messageType = 'error';
        }
    }
}

// Групуємо повідомлення за клієнтами
$messagesByCustomer = [];
$unreadCount = 0;

foreach ($messages as $msg) {
    $customerId = $msg['sender_id'] == $currentUser['id'] ? $msg['receiver_id'] : $msg['sender_id'];
    $customerName = $msg['sender_id'] == $currentUser['id'] ? $msg['receiver_name'] : $msg['sender_name'];
    
    if (!isset($messagesByCustomer[$customerId])) {
        $messagesByCustomer[$customerId] = [
            'name' => $customerName,
            'role' => $msg['sender_id'] == $currentUser['id'] ? $msg['receiver_role'] : $msg['sender_role'],
            'messages' => []
        ];
    }
    
    $messagesByCustomer[$customerId]['messages'][] = $msg;
    
    // Рахуємо непрочитані повідомлення
    if ($msg['is_read'] == 0 && $msg['receiver_id'] == $currentUser['id']) {
        $unreadCount++;
    }
}

// Сортуємо повідомлення за датою (останні спочатку)
foreach ($messagesByCustomer as &$customer) {
    usort($customer['messages'], function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
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
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Бічне меню -->
        <div class="w-64 bg-green-800 text-white">
            <div class="p-4 font-bold text-xl">Винна крамниця</div>
            <nav class="mt-8">
                <a href="index.php" class="flex items-center px-4 py-3 hover:bg-green-700">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    <span>Дашборд</span>
                </a>
                <a href="orders.php" class="flex items-center px-4 py-3 hover:bg-green-700">
                    <i class="fas fa-shopping-cart mr-3"></i>
                    <span>Замовлення</span>
                </a>
                <a href="customers.php" class="flex items-center px-4 py-3 hover:bg-green-700">
                    <i class="fas fa-users mr-3"></i>
                    <span>Клієнти</span>
                </a>
                <a href="messages.php" class="flex items-center px-4 py-3 bg-green-700">
                    <i class="fas fa-envelope mr-3"></i>
                    <span>Повідомлення</span>
                    <?php if ($unreadCount > 0): ?>
                    <span class="ml-auto bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                        <?= $unreadCount ?>
                    </span>
                    <?php endif; ?>
                </a>
                <a href="products.php" class="flex items-center px-4 py-3 hover:bg-green-700">
                    <i class="fas fa-wine-bottle mr-3"></i>
                    <span>Каталог</span>
                </a>
                <a href="new_order.php" class="flex items-center px-4 py-3 hover:bg-green-700">
                    <i class="fas fa-plus-circle mr-3"></i>
                    <span>Нове замовлення</span>
                </a>
                <a href="reports.php" class="flex items-center px-4 py-3 hover:bg-green-700">
                    <i class="fas fa-chart-bar mr-3"></i>
                    <span>Звіти</span>
                </a>
                <a href="../logout.php" class="flex items-center px-4 py-3 hover:bg-green-700 mt-8">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Повідомлення</h1>
                    <div class="flex items-center">
                        <div class="relative">
                            <button class="flex items-center text-gray-700 focus:outline-none">
                                <img src="../../assets/images/avatar.jpg" alt="Avatar" class="h-8 w-8 rounded-full mr-2">
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

                <!-- Кнопка нового повідомлення -->
                <div class="mb-6">
                    <button type="button" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded inline-flex items-center" 
                            onclick="document.getElementById('newMessageModal').classList.remove('hidden')">
                        <i class="fas fa-paper-plane mr-2"></i> Нове повідомлення
                    </button>
                </div>

                <!-- Список повідомлень -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold">Історія повідомлень</h2>
                    </div>
                    
                    <?php if (empty($messagesByCustomer)): ?>
                    <div class="p-6 text-center text-gray-500">
                        <i class="fas fa-envelope-open text-gray-300 text-6xl mb-4"></i>
                        <p>У вас поки немає повідомлень</p>
                    </div>
                    <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($messagesByCustomer as $customerId => $customer): ?>
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-user text-gray-500"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($customer['name']) ?></h3>
                                    <p class="text-sm text-gray-500"><?= $customer['role'] === 'customer' ? 'Клієнт' : 'Співробітник' ?></p>
                                </div>
                                <div class="ml-auto">
                                    <button type="button" class="text-green-600 hover:text-green-800 mr-2" 
                                            onclick="showReplyModal(<?= $customerId ?>, '<?= htmlspecialchars($customer['name']) ?>')">
                                        <i class="fas fa-reply"></i> Відповісти
                                    </button>
                                    <a href="customer_details.php?id=<?= $customerId ?>" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-user"></i> Профіль
                                    </a>
                                </div>
                            </div>
                            
                            <div class="space-y-4 ml-12">
                                <?php foreach ($customer['messages'] as $index => $msg): ?>
                                <?php if ($index >= 3) break; // Показываем только последние 3 сообщения ?>
                                <div class="<?= $msg['sender_id'] == $currentUser['id'] ? 'bg-green-50 ml-12' : 'bg-gray-50' ?> rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <span class="font-medium"><?= $msg['sender_id'] == $currentUser['id'] ? 'Ви' : htmlspecialchars($customer['name']) ?></span>
                                            <span class="text-xs text-gray-500 ml-2"><?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?></span>
                                        </div>
                                        <?php if ($msg['receiver_id'] == $currentUser['id'] && $msg['is_read'] == 0): ?>
                                        <a href="messages.php?mark_read=<?= $msg['id'] ?>" class="text-xs text-blue-600 hover:text-blue-800">
                                            Позначити як прочитане
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="font-medium"><?= htmlspecialchars($msg['subject']) ?></div>
                                    <div class="text-sm text-gray-700">
                                        <?= nl2br(htmlspecialchars(mb_substr($msg['message'], 0, 150) . (mb_strlen($msg['message']) > 150 ? '...' : ''))) ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <?php if (count($customer['messages']) > 3): ?>
                                <div class="text-center">
                                    <a href="customer_details.php?id=<?= $customerId ?>#messages" class="text-sm text-green-600 hover:text-green-800">
                                        Показати всі повідомлення (<?= count($customer['messages']) ?>)
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Модальне вікно для нового повідомлення -->
    <div id="newMessageModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Нове повідомлення</h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" onclick="document.getElementById('newMessageModal').classList.add('hidden')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="messages.php" method="POST">
                <div class="mb-4">
                    <label for="receiver_id" class="block text-sm font-medium text-gray-700 mb-2">Отримувач</label>
                    <select id="receiver_id" name="receiver_id" required
                            class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Виберіть отримувача</option>
                        <optgroup label="Клієнти">
                            <?php 
                            // В реальном проекте здесь должен быть запрос для получения списка клиентов
                            foreach ($messagesByCustomer as $id => $customer): 
                                if ($customer['role'] === 'customer'):
                            ?>
                            <option value="<?= $id ?>"><?= htmlspecialchars($customer['name']) ?></option>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </optgroup>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Тема</label>
                    <input type="text" id="subject" name="subject" required
                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div class="mb-4">
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Повідомлення</label>
                    <textarea id="message" name="message" rows="4" required
                              class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded" 
                            onclick="document.getElementById('newMessageModal').classList.add('hidden')">
                        Скасувати
                    </button>
                    <button type="submit" name="send_message" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                        Надіслати
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальне вікно для відповіді -->
    <div id="replyModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Відповісти: <span id="replyRecipientName"></span></h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" onclick="document.getElementById('replyModal').classList.add('hidden')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="messages.php" method="POST">
                <input type="hidden" id="reply_receiver_id" name="receiver_id">
                <div class="mb-4">
                    <label for="reply_subject" class="block text-sm font-medium text-gray-700 mb-2">Тема</label>
                    <input type="text" id="reply_subject" name="subject" required
                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div class="mb-4">
                    <label for="reply_message" class="block text-sm font-medium text-gray-700 mb-2">Повідомлення</label>
                    <textarea id="reply_message" name="message" rows="4" required
                              class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded" 
                            onclick="document.getElementById('replyModal').classList.add('hidden')">
                        Скасувати
                    </button>
                    <button type="submit" name="send_message" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                        Надіслати
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Функція для показу модального вікна відповіді
        function showReplyModal(customerId, customerName) {
            document.getElementById('reply_receiver_id').value = customerId;
            document.getElementById('replyRecipientName').textContent = customerName;
            document.getElementById('reply_subject').value = 'Re: ';
            document.getElementById('replyModal').classList.remove('hidden');
        }
    </script>
</body>
</html>