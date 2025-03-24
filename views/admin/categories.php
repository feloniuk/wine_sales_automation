<?php
// admin/categories.php
// Сторінка управління категоріями товарів

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/AdminController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('admin')) {
    header('Location: /login.php?redirect=admin/categories');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролер адміністратора
$adminController = new AdminController();

// Отримуємо список категорій
$categories = $adminController->getProductCategories();

// Обробка форми додавання/редагування категорії
$message = '';
$messageType = '';
$editCategory = null;

// Обробка форми додавання категорії
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $image = 'default_category.jpg'; // За замовчуванням
    
    // Перевірка обов'язкових полів
    if (empty($name)) {
        $message = 'Будь ласка, вкажіть назву категорії.';
        $messageType = 'error';
    } else {
        // Обробка завантаження зображення
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = ROOT_PATH . '/assets/images/';
            $fileName = basename($_FILES['image']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Перевіряємо тип файлу
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileExt, $allowedTypes)) {
                // Створюємо унікальне ім'я файлу
                $newFileName = uniqid() . '.' . $fileExt;
                $uploadPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    $image = $newFileName;
                } else {
                    $message = 'Помилка при завантаженні зображення.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Дозволені типи зображень: JPG, JPEG, PNG, GIF.';
                $messageType = 'error';
            }
        }

        // Додаємо категорію, якщо немає помилок
        if (empty($message)) {
            $result = $adminController->addProductCategory($name, $description, $image);
            
            if ($result['success']) {
                $message = 'Категорія успішно додана.';
                $messageType = 'success';
                // Оновлюємо список категорій
                $categories = $adminController->getProductCategories();
            } else {
                $message = 'Помилка при додаванні категорії: ' . $result['message'];
                $messageType = 'error';
            }
        }
    }
}

// Обробка форми редагування категорії
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $category_id = intval($_POST['category_id'] ?? 0);
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $image = null; // Не змінюємо зображення, якщо не завантажено нове
    
    // Перевірка обов'язкових полів
    if (empty($name) || $category_id <= 0) {
        $message = 'Будь ласка, вкажіть назву категорії.';
        $messageType = 'error';
    } else {
        // Обробка завантаження зображення
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = ROOT_PATH . '/assets/images/';
            $fileName = basename($_FILES['image']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Перевіряємо тип файлу
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileExt, $allowedTypes)) {
                // Створюємо унікальне ім'я файлу
                $newFileName = uniqid() . '.' . $fileExt;
                $uploadPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    $image = $newFileName;
                } else {
                    $message = 'Помилка при завантаженні зображення.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Дозволені типи зображень: JPG, JPEG, PNG, GIF.';
                $messageType = 'error';
            }
        }

        // Оновлюємо категорію, якщо немає помилок
        if (empty($message)) {
            $result = $adminController->updateProductCategory($category_id, $name, $description, $image);
            
            if ($result['success']) {
                $message = 'Категорія успішно оновлена.';
                $messageType = 'success';
                // Оновлюємо список категорій
                $categories = $adminController->getProductCategories();
            } else {
                $message = 'Помилка при оновленні категорії: ' . $result['message'];
                $messageType = 'error';
            }
        }
    }
}

// Обробка видалення категорії
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = intval($_GET['delete']);
    $result = $adminController->deleteProductCategory($category_id);
    
    if ($result['success']) {
        $message = 'Категорія успішно видалена.';
        $messageType = 'success';
        // Оновлюємо список категорій
        $categories = $adminController->getProductCategories();
    } else {
        $message = 'Помилка при видаленні категорії: ' . $result['message'];
        $messageType = 'error';
    }
}

// Отримання даних для редагування
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $category_id = intval($_GET['edit']);
    
    foreach ($categories as $category) {
        if ($category['id'] == $category_id) {
            $editCategory = $category;
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
    <title>Управління категоріями - Винна крамниця</title>
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
                <a href="categories.php" class="flex items-center px-4 py-3 bg-red-800">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Управління категоріями</h1>
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
                    <!-- Форма додавання/редагування категорії -->
                    <div class="md:col-span-1">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h2 class="text-lg font-semibold mb-4"><?= $editCategory ? 'Редагування категорії' : 'Додавання нової категорії' ?></h2>
                            <form action="categories.php" method="POST" enctype="multipart/form-data">
                                <?php if ($editCategory): ?>
                                <input type="hidden" name="category_id" value="<?= $editCategory['id'] ?>">
                                <?php endif; ?>
                                
                                <div class="mb-4">
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Назва категорії *</label>
                                    <input type="text" id="name" name="name" required value="<?= $editCategory ? htmlspecialchars($editCategory['name']) : '' ?>"
                                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Опис</label>
                                    <textarea id="description" name="description" rows="3"
                                              class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"><?= $editCategory ? htmlspecialchars($editCategory['description']) : '' ?></textarea>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Зображення</label>
                                    <?php if ($editCategory && !empty($editCategory['image'])): ?>
                                    <div class="mb-2">
                                        <img src="../assets/images/<?= $editCategory['image'] ?>" alt="<?= htmlspecialchars($editCategory['name']) ?>" class="h-32 w-32 object-cover rounded">
                                    </div>
                                    <?php endif; ?>
                                    <input type="file" id="image" name="image" accept="image/*"
                                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    <p class="text-xs text-gray-500 mt-1">Рекомендований розмір: 500x500 пікселів. Максимальний розмір: 2MB.</p>
                                </div>
                                
                                <div class="flex justify-end">
                                    <?php if ($editCategory): ?>
                                    <button type="submit" name="update_category" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                                        Оновити категорію
                                    </button>
                                    <a href="categories.php" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                        Скасувати
                                    </a>
                                    <?php else: ?>
                                    <button type="submit" name="add_category" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                                        Додати категорію
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Список категорій -->
                    <div class="md:col-span-2">
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <?php if (empty($categories)): ?>
                            <div class="p-6 text-center text-gray-500">
                                <i class="fas fa-tags text-5xl mb-4"></i>
                                <p>Категорії не знайдені. Додайте першу категорію за допомогою форми зліва.</p>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Зображення</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Назва</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Опис</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $category['id'] ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="h-10 w-10 rounded overflow-hidden">
                                                    <img src="../assets/images/<?= $category['image'] ?>" alt="<?= htmlspecialchars($category['name']) ?>" class="h-full w-full object-cover">
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($category['name']) ?></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-500"><?= nl2br(htmlspecialchars($category['description'])) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <a href="categories.php?edit=<?= $category['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Редагувати">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" onclick="confirmDelete(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')" class="text-red-600 hover:text-red-900" title="Видалити">
                                                        <i class="fas fa-trash"></i>
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
                </div>
            </main>
        </div>
    </div>

    <script>
        // Функція для підтвердження видалення категорії
        function confirmDelete(categoryId, categoryName) {
            if (confirm(`Ви впевнені, що хочете видалити категорію "${categoryName}"? Це також видалить всі товари в цій категорії!`)) {
                window.location.href = `categories.php?delete=${categoryId}`;
            }
        }
    </script>
</body>
</html>