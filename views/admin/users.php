<?php
// admin/users.php
// Сторінка управління користувачами

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/AdminController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('admin')) {
    header('Location: /login.php?redirect=admin/users');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер адміністратора
$adminController = new AdminController();

// Отримуємо всіх користувачів
$users = $adminController->getAllUsers();

// Параметри фільтрації
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Фільтруємо користувачів
if (!empty($roleFilter) || !empty($search)) {
    $filteredUsers = [];
    
    foreach ($users as $user) {
        // Фільтр за роллю
        if (!empty($roleFilter) && $user['role'] !== $roleFilter) {
            continue;
        }
        
        // Фільтр за пошуком
        if (!empty($search) && 
            stripos($user['name'], $search) === false && 
            stripos($user['email'], $search) === false && 
            stripos($user['username'], $search) === false) {
            continue;
        }
        
        $filteredUsers[] = $user;
    }
    
    $users = $filteredUsers;
}

// Обробка форми додавання/редагування користувача
$message = '';
$messageType = '';
$editUser = null;

// Обробка форми додавання користувача
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $userData = [
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? '',
        'role' => $_POST['role'] ?? '',
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'address' => $_POST['address'] ?? '',
        'city' => $_POST['city'] ?? '',
        'region' => $_POST['region'] ?? '',
        'postal_code' => $_POST['postal_code'] ?? ''
    ];
    
    // Валідація обов'язкових полів
    if (empty($userData['username']) || empty($userData['password']) || 
        empty($userData['role']) || empty($userData['name']) || empty($userData['email'])) {
        $message = 'Будь ласка, заповніть всі обов\'язкові поля.';
        $messageType = 'error';
    } else {
        $result = $adminController->createUser($userData);
        
        if ($result['success']) {
            $message = 'Користувач успішно створений.';
            $messageType = 'success';
            // Оновлюємо список користувачів
            $users = $adminController->getAllUsers();
        } else {
            $message = 'Помилка при створенні користувача: ' . $result['message'];
            $messageType = 'error';
        }
    }
}

// Обробка форми редагування користувача
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $userId = intval($_POST['user_id'] ?? 0);
    $userData = [
        'username' => $_POST['username'] ?? '',
        'role' => $_POST['role'] ?? '',
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'address' => $_POST['address'] ?? '',
        'city' => $_POST['city'] ?? '',
        'region' => $_POST['region'] ?? '',
        'postal_code' => $_POST['postal_code'] ?? '',
        'status' => $_POST['status'] ?? 'active'
    ];
    
    // Додаємо пароль, якщо він змінений
    if (!empty($_POST['password'])) {
        $userData['password'] = $_POST['password'];
    }
    
    // Валідація обов'язкових полів
    if (empty($userData['username']) || empty($userData['role']) || 
        empty($userData['name']) || empty($userData['email'])) {
        $message = 'Будь ласка, заповніть всі обов\'язкові поля.';
        $messageType = 'error';
    } else {
        $result = $adminController->updateUser($userId, $userData);
        
        if ($result['success']) {
            $message = 'Дані користувача успішно оновлені.';
            $messageType = 'success';
            // Оновлюємо список користувачів
            $users = $adminController->getAllUsers();
        } else {
            $message = 'Помилка при оновленні даних користувача: ' . $result['message'];
            $messageType = 'error';
        }
    }
}

// Обробка зміни статусу користувача
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $userId = intval($_GET['toggle_status']);
    $isActive = ($_GET['status'] === 'active') ? false : true;
    
    $result = $adminController->toggleUserStatus($userId, $isActive);
    
    if ($result['success']) {
        $message = 'Статус користувача успішно змінено.';
        $messageType = 'success';
        // Оновлюємо список користувачів
        $users = $adminController->getAllUsers();
    } else {
        $message = 'Помилка при зміні статусу користувача: ' . $result['message'];
        $messageType = 'error';
    }
}

// Отримання даних для редагування
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $userId = intval($_GET['edit']);
    $editUser = $adminController->getUserById($userId);
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управління користувачами - Винна крамниця</title>
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
                <a href="users.php" class="flex items-center px-4 py-3 bg-red-800">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Управління користувачами</h1>
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
                    <!-- Форма додавання/редагування користувача -->
                    <div class="md:col-span-1">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h2 class="text-lg font-semibold mb-4"><?= $editUser ? 'Редагування користувача' : 'Додавання нового користувача' ?></h2>
                            <form action="users.php" method="POST">
                                <?php if ($editUser): ?>
                                <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
                                <?php endif; ?>
                                
                                <div class="mb-4">
                                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Логін *</label>
                                    <input type="text" id="username" name="username" required value="<?= $editUser ? htmlspecialchars($editUser['username']) : '' ?>"
                                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1"><?= $editUser ? 'Новий пароль (залиште порожнім, щоб не змінювати)' : 'Пароль *' ?></label>
                                    <input type="password" id="password" name="password" <?= $editUser ? '' : 'required' ?>
                                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Роль *</label>
                                    <select id="role" name="role" required class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                        <option value="">Виберіть роль</option>
                                        <option value="admin" <?= ($editUser && $editUser['role'] === 'admin') ? 'selected' : '' ?>>Адміністратор</option>
                                        <option value="warehouse" <?= ($editUser && $editUser['role'] === 'warehouse') ? 'selected' : '' ?>>Начальник складу</option>
                                        <option value="sales" <?= ($editUser && $editUser['role'] === 'sales') ? 'selected' : '' ?>>Менеджер з продажу</option>
                                        <option value="customer" <?= ($editUser && $editUser['role'] === 'customer') ? 'selected' : '' ?>>Клієнт</option>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">ПІБ *</label>
                                    <input type="text" id="name" name="name" required value="<?= $editUser ? htmlspecialchars($editUser['name']) : '' ?>"
                                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                    <input type="email" id="email" name="email" required value="<?= $editUser ? htmlspecialchars($editUser['email']) : '' ?>"
                                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
                                    <input type="tel" id="phone" name="phone" value="<?= $editUser ? htmlspecialchars($editUser['phone'] ?? '') : '' ?>"
                                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Адреса</label>
                                    <input type="text" id="address" name="address" value="<?= $editUser ? htmlspecialchars($editUser['address'] ?? '') : '' ?>"
                                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Місто</label>
                                        <input type="text" id="city" name="city" value="<?= $editUser ? htmlspecialchars($editUser['city'] ?? '') : '' ?>"
                                               class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    </div>
                                    <div>
                                        <label for="region" class="block text-sm font-medium text-gray-700 mb-1">Область</label>
                                        <input type="text" id="region" name="region" value="<?= $editUser ? htmlspecialchars($editUser['region'] ?? '') : '' ?>"
                                               class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Поштовий індекс</label>
                                    <input type="text" id="postal_code" name="postal_code" value="<?= $editUser ? htmlspecialchars($editUser['postal_code'] ?? '') : '' ?>"
                                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                                
                                <?php if ($editUser): ?>
                                <div class="mb-4">
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                                    <select id="status" name="status" class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                        <option value="active" <?= ($editUser['status'] === 'active') ? 'selected' : '' ?>>Активний</option>
                                        <option value="inactive" <?= ($editUser['status'] === 'inactive') ? 'selected' : '' ?>>Неактивний</option>
                                    </select>
                                </div>
                                <?php endif; ?>
                                
                                <div class="flex justify-end">
                                    <?php if ($editUser): ?>
                                    <button type="submit" name="update_user" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                                        Оновити користувача
                                    </button>
                                    <a href="users.php" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                        Скасувати
                                    </a>
                                    <?php else: ?>
                                    <button type="submit" name="add_user" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                                        Додати користувача
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Список користувачів -->
                    <div class="md:col-span-2">
                        <!-- Фільтри та пошук -->
                        <div class="mb-4 flex justify-between">
                            <div class="flex space-x-2">
                                <a href="users.php" class="px-4 py-2 rounded bg-<?= empty($roleFilter) ? 'red-800 text-white' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                    Всі
                                </a>
                                <a href="users.php?role=admin" class="px-4 py-2 rounded bg-<?= $roleFilter === 'admin' ? 'red-800 text-white' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                    Адміністратори
                                </a>
                                <a href="users.php?role=warehouse" class="px-4 py-2 rounded bg-<?= $roleFilter === 'warehouse' ? 'red-800 text-white' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                    Склад
                                </a>
                                <a href="users.php?role=sales" class="px-4 py-2 rounded bg-<?= $roleFilter === 'sales' ? 'red-800 text-white' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                    Менеджери
                                </a>
                                <a href="users.php?role=customer" class="px-4 py-2 rounded bg-<?= $roleFilter === 'customer' ? 'red-800 text-white' : 'gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                    Клієнти
                                </a>
                            </div>
                            
                            <form action="users.php" method="GET" class="flex">
                                <?php if (!empty($roleFilter)): ?>
                                <input type="hidden" name="role" value="<?= htmlspecialchars($roleFilter) ?>">
                                <?php endif; ?>
                                
                                <input type="text" name="search" placeholder="Пошук користувачів..." value="<?= htmlspecialchars($search) ?>" 
                                       class="border rounded-l px-3 py-2 focus:outline-none focus:ring-red-500">
                                <button type="submit" class="bg-red-800 text-white px-3 py-2 rounded-r hover:bg-red-700">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <?php if (empty($users)): ?>
                            <div class="p-6 text-center text-gray-500">
                                <i class="fas fa-users text-5xl mb-4"></i>
                                <p>Користувачі не знайдені. Спробуйте змінити параметри фільтрації або додайте нового користувача.</p>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Користувач</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Роль</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Контакти</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $user['id'] ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                        <i class="fas fa-user text-gray-500"></i>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></div>
                                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($user['username']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $roleClass = '';
                                                $roleText = '';
                                                switch ($user['role']) {
                                                    case 'admin': 
                                                        $roleClass = 'bg-red-100 text-red-800'; 
                                                        $roleText = 'Адміністратор'; 
                                                        break;
                                                    case 'warehouse': 
                                                        $roleClass = 'bg-blue-100 text-blue-800'; 
                                                        $roleText = 'Начальник складу'; 
                                                        break;
                                                    case 'sales': 
                                                        $roleClass = 'bg-green-100 text-green-800'; 
                                                        $roleText = 'Менеджер з продажу'; 
                                                        break;
                                                    case 'customer': 
                                                        $roleClass = 'bg-gray-100 text-gray-800'; 
                                                        $roleText = 'Клієнт'; 
                                                        break;
                                                    default: 
                                                        $roleClass = 'bg-gray-100 text-gray-800'; 
                                                        $roleText = $user['role']; 
                                                        break;
                                                }
                                                ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $roleClass ?>">
                                                    <?= $roleText ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?= htmlspecialchars($user['email']) ?></div>
                                                <div class="text-sm text-gray-500"><?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : '—' ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($user['status'] === 'active'): ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Активний
                                                </span>
                                                <?php else: ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Неактивний
                                                </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <a href="users.php?edit=<?= $user['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Редагувати">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="users.php?toggle_status=<?= $user['id'] ?>&status=<?= $user['status'] ?>" 
                                                      class="<?= $user['status'] === 'active' ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900' ?>" 
                                                      title="<?= $user['status'] === 'active' ? 'Деактивувати' : 'Активувати' ?>">
                                                        <?= $user['status'] === 'active' ? '<i class="fas fa-toggle-on"></i>' : '<i class="fas fa-toggle-off"></i>' ?>
                                                    </a>
                                                    <a href="user_details.php?id=<?= $user['id'] ?>" class="text-indigo-600 hover:text-indigo-900" title="Деталі">
                                                        <i class="fas fa-info-circle"></i>
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
</body>
</html>