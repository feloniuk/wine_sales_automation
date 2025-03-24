<?php
// admin/promotions.php
// Сторінка управління акціями та промокодами

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/AdminController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('admin')) {
    header('Location: /login.php?redirect=admin/promotions');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер адміністратора
$adminController = new AdminController();

// Отримуємо всі акції
$promotions = $adminController->getAllPromotions();

// Обробка форми додавання акції/промокоду
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_promotion'])) {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $discountPercent = !empty($_POST['discount_percent']) ? floatval($_POST['discount_percent']) : null;
    $discountAmount = !empty($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : null;
    $code = $_POST['code'] ?? '';
    $minOrderAmount = !empty($_POST['min_order_amount']) ? floatval($_POST['min_order_amount']) : null;
    $startDate = $_POST['start_date'] ?? date('Y-m-d');
    $endDate = $_POST['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
    
    // Валідуємо обов'язкові поля
    if (empty($name) || empty($code) || (empty($discountPercent) && empty($discountAmount))) {
        $message = 'Будь ласка, заповніть всі обов\'язкові поля і вкажіть тип знижки.';
        $messageType = 'error';
    } else {
        // Перевіряємо, що вказано тільки один тип знижки
        if (!empty($discountPercent) && !empty($discountAmount)) {
            $message = 'Будь ласка, вкажіть тільки один тип знижки: відсоток або суму.';
            $messageType = 'error';
        } else {
            $result = $adminController->addPromotion(
                $name,
                $description,
                $discountPercent,
                $discountAmount,
                $code,
                $minOrderAmount,
                $startDate,
                $endDate
            );
            
            if ($result['success']) {
                $message = 'Акція/промокод успішно додані.';
                $messageType = 'success';
                // Оновлюємо список акцій
                $promotions = $adminController->getAllPromotions();
            } else {
                $message = 'Помилка при додаванні акції/промокоду: ' . $result['message'];
                $messageType = 'error';
            }
        }
    }
}

// Обробка зміни статусу акції
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $promotionId = intval($_GET['toggle_status']);
    $isActive = ($_GET['status'] === 'active') ? false : true;
    
    $result = $adminController->togglePromotionStatus($promotionId, $isActive);
    
    if ($result['success']) {
        $message = 'Статус акції успішно змінено.';
        $messageType = 'success';
        // Оновлюємо список акцій
        $promotions = $adminController->getAllPromotions();
    } else {
        $message = 'Помилка при зміні статусу акції: ' . $result['message'];
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управління акціями - Винна крамниця</title>
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
                <a href="promotions.php" class="flex items-center px-4 py-3 bg-red-800">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Управління акціями та промокодами</h1>
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

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Форма додавання акції/промокоду -->
                    <div class="md:col-span-1">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h2 class="text-lg font-semibold mb-4">Додавання нової акції</h2>
                            <form action="promotions.php" method="POST">
                                <input type="hidden" name="add_promotion" value="1">
                                
                                <div class="mb-4">
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Назва акції *</label>
                                    <input type="text" id="name" name="name" required
                                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Промокод *</label>
                                    <input type="text" id="code" name="code" required
                                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    <p class="text-xs text-gray-500 mt-1">Код, який користувачі будуть вводити для отримання знижки</p>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Опис акції</label>
                                    <textarea id="description" name="description" rows="3"
                                              class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Тип знижки *</label>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="discount_percent" class="flex items-center text-sm text-gray-600">
                                                <input type="radio" id="discount_type_percent" name="discount_type" value="percent" class="mr-2" checked
                                                       onclick="toggleDiscountType('percent')">
                                                <span>Відсоток (%)</span>
                                            </label>
                                            <input type="number" id="discount_percent" name="discount_percent" min="0" max="100" 
                                                   class="border rounded w-full px-3 py-2 mt-1 focus:outline-none focus:ring-2 focus:ring-red-500">
                                        </div>
                                        <div>
                                            <label for="discount_amount" class="flex items-center text-sm text-gray-600">
                                                <input type="radio" id="discount_type_amount" name="discount_type" value="amount" class="mr-2"
                                                       onclick="toggleDiscountType('amount')">
                                                <span>Сума (₴)</span>
                                            </label>
                                            <input type="number" id="discount_amount" name="discount_amount" min="0" step="0.01" 
                                                   class="border rounded w-full px-3 py-2 mt-1 focus:outline-none focus:ring-2 focus:ring-red-500"
                                                   disabled>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="min_order_amount" class="block text-sm font-medium text-gray-700 mb-1">Мінімальна сума замовлення (₴)</label>
                                    <input type="number" id="min_order_amount" name="min_order_amount" min="0" step="0.01"
                                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    <p class="text-xs text-gray-500 mt-1">Залиште порожнім, якщо обмеження немає</p>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Дата початку</label>
                                        <input type="date" id="start_date" name="start_date" 
                                               value="<?= date('Y-m-d') ?>"
                                               class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    </div>
                                    <div>
                                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Дата закінчення</label>
                                        <input type="date" id="end_date" name="end_date"
                                               value="<?= date('Y-m-d', strtotime('+30 days')) ?>"
                                               class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    </div>
                                </div>
                                
                                <div class="flex justify-end">
                                    <button type="submit" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                                        Додати акцію
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Список акцій/промокодів -->
                    <div class="md:col-span-2">
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <?php if (empty($promotions)): ?>
                            <div class="p-6 text-center text-gray-500">
                                <i class="fas fa-percent text-5xl mb-4"></i>
                                <p>Акції та промокоди не знайдені. Додайте першу акцію за допомогою форми зліва.</p>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Код</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Назва</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Знижка</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Період дії</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($promotions as $promotion): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    <?= htmlspecialchars($promotion['code']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($promotion['name']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars(substr($promotion['description'], 0, 50)) . (strlen($promotion['description']) > 50 ? '...' : '') ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($promotion['discount_percent']): ?>
                                                <div class="text-sm text-gray-900"><?= $promotion['discount_percent'] ?>%</div>
                                                <?php elseif ($promotion['discount_amount']): ?>
                                                <div class="text-sm text-gray-900"><?= number_format($promotion['discount_amount'], 2) ?> ₴</div>
                                                <?php endif; ?>
                                                
                                                <?php if ($promotion['min_order_amount']): ?>
                                                <div class="text-xs text-gray-500">від <?= number_format($promotion['min_order_amount'], 2) ?> ₴</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?= date('d.m.Y', strtotime($promotion['start_date'])) ?></div>
                                                <div class="text-sm text-gray-500">до <?= date('d.m.Y', strtotime($promotion['end_date'])) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php 
                                                $isActive = $promotion['status'] === 'active';
                                                $today = date('Y-m-d');
                                                $isInDate = $today >= $promotion['start_date'] && $today <= $promotion['end_date'];
                                                $statusClass = '';
                                                $statusText = '';
                                                
                                                if ($isActive && $isInDate) {
                                                    $statusClass = 'bg-green-100 text-green-800';
                                                    $statusText = 'Активна';
                                                } elseif ($isActive && !$isInDate) {
                                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                                    $statusText = 'Неактивна (поза періодом)';
                                                } else {
                                                    $statusClass = 'bg-red-100 text-red-800';
                                                    $statusText = 'Вимкнена';
                                                }
                                                ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                                    <?= $statusText ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <a href="edit_promotion.php?id=<?= $promotion['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Редагувати">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="promotions.php?toggle_status=<?= $promotion['id'] ?>&status=<?= $promotion['status'] ?>" 
                                                      class="<?= $isActive ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900' ?>" 
                                                      title="<?= $isActive ? 'Деактивувати' : 'Активувати' ?>">
                                                        <?= $isActive ? '<i class="fas fa-toggle-on"></i>' : '<i class="fas fa-toggle-off"></i>' ?>
                                                    </a>
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
                </div>
            </main>
        </div>
    </div>

    <script>
        // Функція для перемикання типу знижки
        function toggleDiscountType(type) {
            if (type === 'percent') {
                document.getElementById('discount_percent').disabled = false;
                document.getElementById('discount_amount').disabled = true;
                document.getElementById('discount_amount').value = '';
            } else {
                document.getElementById('discount_percent').disabled = true;
                document.getElementById('discount_percent').value = '';
                document.getElementById('discount_amount').disabled = false;
            }
        }
    </script>
</body>
</html>