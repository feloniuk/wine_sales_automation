<?php
// admin/cameras.php
// Сторінка управління камерами спостереження

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/AdminController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('admin')) {
    header('Location: /login.php?redirect=admin/cameras');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер адміністратора
$adminController = new AdminController();

// Отримуємо список камер
$cameras = $adminController->getCameras();

// Обробка форми додавання/редагування камери
$message = '';
$messageType = '';
$editCamera = null;

// Обробка форми додавання нової камери
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_camera'])) {
    $name = $_POST['name'] ?? '';
    $location = $_POST['location'] ?? '';
    $stream_url = $_POST['stream_url'] ?? '';
    
    if (empty($name) || empty($location) || empty($stream_url)) {
        $message = 'Будь ласка, заповніть всі поля.';
        $messageType = 'error';
    } else {
        $result = $adminController->addCamera($name, $location, $stream_url);
        
        if ($result['success']) {
            $message = 'Камера успішно додана.';
            $messageType = 'success';
            // Оновлюємо список камер
            $cameras = $adminController->getCameras();
        } else {
            $message = 'Помилка при додаванні камери: ' . $result['message'];
            $messageType = 'error';
        }
    }
}

// Обробка форми редагування камери
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_camera'])) {
    $camera_id = intval($_POST['camera_id'] ?? 0);
    $name = $_POST['name'] ?? '';
    $location = $_POST['location'] ?? '';
    $stream_url = $_POST['stream_url'] ?? '';
    $status = $_POST['status'] ?? 'active';
    
    if ($camera_id <= 0 || empty($name) || empty($location) || empty($stream_url)) {
        $message = 'Будь ласка, заповніть всі поля.';
        $messageType = 'error';
    } else {
        $result = $adminController->updateCamera($camera_id, $name, $location, $stream_url, $status);
        
        if ($result['success']) {
            $message = 'Дані камери успішно оновлені.';
            $messageType = 'success';
            // Оновлюємо список камер
            $cameras = $adminController->getCameras();
        } else {
            $message = 'Помилка при оновленні даних камери: ' . $result['message'];
            $messageType = 'error';
        }
    }
}

// Обробка видалення камери
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $camera_id = intval($_GET['delete']);
    $result = $adminController->deleteCamera($camera_id);
    
    if ($result['success']) {
        $message = 'Камера успішно видалена.';
        $messageType = 'success';
        // Оновлюємо список камер
        $cameras = $adminController->getCameras();
    } else {
        $message = 'Помилка при видаленні камери: ' . $result['message'];
        $messageType = 'error';
    }
}

// Отримання даних для редагування
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $camera_id = intval($_GET['edit']);
    
    foreach ($cameras as $camera) {
        if ($camera['id'] == $camera_id) {
            $editCamera = $camera;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управління камерами - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .camera-view {
            aspect-ratio: 16/9;
            background-color: #000;
            position: relative;
            overflow: hidden;
        }
        .camera-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .camera-card:hover .camera-overlay {
            opacity: 1;
        }
    </style>
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
                <a href="cameras.php" class="flex items-center px-4 py-3 bg-red-800">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Управління камерами спостереження</h1>
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

                <!-- Форма додавання/редагування камери -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold mb-4"><?= $editCamera ? 'Редагування камери' : 'Додавання нової камери' ?></h2>
                    <form action="cameras.php" method="POST">
                        <?php if ($editCamera): ?>
                        <input type="hidden" name="camera_id" value="<?= $editCamera['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Назва камери</label>
                                <input type="text" id="name" name="name" required value="<?= $editCamera ? htmlspecialchars($editCamera['name']) : '' ?>"
                                       class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Розташування</label>
                                <input type="text" id="location" name="location" required value="<?= $editCamera ? htmlspecialchars($editCamera['location']) : '' ?>"
                                       class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label for="stream_url" class="block text-sm font-medium text-gray-700 mb-1">URL відеопотоку</label>
                                <input type="text" id="stream_url" name="stream_url" required value="<?= $editCamera ? htmlspecialchars($editCamera['stream_url']) : '' ?>"
                                       class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>
                        
                        <?php if ($editCamera): ?>
                        <div class="mt-4">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                            <select id="status" name="status" class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                <option value="active" <?= $editCamera['status'] === 'active' ? 'selected' : '' ?>>Активна</option>
                                <option value="inactive" <?= $editCamera['status'] === 'inactive' ? 'selected' : '' ?>>Неактивна</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-6">
                            <?php if ($editCamera): ?>
                            <button type="submit" name="update_camera" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                                Оновити камеру
                            </button>
                            <a href="cameras.php" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                Скасувати
                            </a>
                            <?php else: ?>
                            <button type="submit" name="add_camera" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                                Додати камеру
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Список камер -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($cameras as $camera): ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden camera-card">
                        <div class="camera-view bg-gray-800">
                            <!-- Чорний фон із заміною реального відео для демо -->
                            <div class="flex justify-center items-center h-full">
                                <i class="fas fa-video text-gray-600 text-4xl"></i>
                            </div>
                            <div class="camera-overlay">
                                <div class="flex space-x-2 mb-2">
                                    <a href="cameras.php?edit=<?= $camera['id'] ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" onclick="confirmDelete(<?= $camera['id'] ?>, '<?= htmlspecialchars($camera['name']) ?>')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="view_camera.php?id=<?= $camera['id'] ?>" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded">
                                        <i class="fas fa-expand"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between items-center">
                                <h3 class="font-semibold"><?= htmlspecialchars($camera['name']) ?></h3>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $camera['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $camera['status'] === 'active' ? 'Активна' : 'Неактивна' ?>
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm mt-1">
                                <i class="fas fa-map-marker-alt mr-1"></i> <?= htmlspecialchars($camera['location']) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($cameras)): ?>
                    <div class="col-span-3 bg-white rounded-lg shadow p-6 text-center text-gray-500">
                        <i class="fas fa-video-slash text-3xl mb-2"></i>
                        <p>Немає налаштованих камер. Додайте першу камеру за допомогою форми вище.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Функція для підтвердження видалення камери
        function confirmDelete(cameraId, cameraName) {
            if (confirm(`Ви впевнені, що хочете видалити камеру "${cameraName}"?`)) {
                window.location.href = `cameras.php?delete=${cameraId}`;
            }
        }
    </script>
</body>
</html>