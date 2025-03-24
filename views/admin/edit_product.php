<?php
// admin/edit_product.php
// Сторінка редагування товару

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/AdminController.php';
require_once ROOT_PATH . '/controllers/WarehouseController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('admin')) {
    header('Location: /login.php?redirect=admin/edit_product');
    exit;
}

// Перевіряємо наявність ID товару
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$productId = intval($_GET['id']);

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Ініціалізуємо контролери
$adminController = new AdminController();
$warehouseController = new WarehouseController();

// Отримуємо список категорій
$categories = $adminController->getProductCategories();

// Отримуємо дані товару
$product = $warehouseController->getProductById($productId);

// Якщо товар не знайдений
if (!$product) {
    header('Location: products.php?error=product_not_found');
    exit;
}

// Обробка форми редагування товару
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $category_id = intval($_POST['category_id'] ?? 0);
    $description = $_POST['description'] ?? '';
    $details = $_POST['details'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $min_stock = intval($_POST['min_stock'] ?? 10);
    $year = intval($_POST['year'] ?? 0);
    $alcohol = floatval($_POST['alcohol'] ?? 0);
    $volume = intval($_POST['volume'] ?? 750);
    $image = $product['image']; // Зберігаємо поточне зображення за замовчуванням
    $featured = isset($_POST['featured']) ? 1 : 0;
    $status = $_POST['status'] ?? 'active';

    // Перевірка обов'язкових полів
    if (empty($name) || $category_id <= 0 || $price <= 0) {
        $message = 'Будь ласка, заповніть всі обов\'язкові поля (назва, категорія, ціна).';
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
                    
                    // Видаляємо старе зображення, якщо воно не є дефолтним
                    if (!empty($product['image']) && $product['image'] !== 'default.jpg') {
                        $oldImagePath = $uploadDir . $product['image'];
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                } else {
                    $message = 'Помилка при завантаженні зображення.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Дозволені типи зображень: JPG, JPEG, PNG, GIF.';
                $messageType = 'error';
            }
        }

        // Якщо немає помилок, оновлюємо товар
        if (empty($message)) {
            $productData = [
                'name' => $name,
                'category_id' => $category_id,
                'description' => $description,
                'details' => $details,
                'price' => $price,
                'min_stock' => $min_stock,
                'year' => $year ?: null,
                'alcohol' => $alcohol ?: null,
                'volume' => $volume,
                'image' => $image,
                'featured' => $featured,
                'status' => $status
            ];

            $result = $warehouseController->updateProduct($productId, $productData);

            if ($result['success']) {
                $message = 'Товар успішно оновлено.';
                $messageType = 'success';
                
                // Перенаправлення на сторінку товарів
                header('Location: products.php?success=1');
                exit;
            } else {
                $message = 'Помилка при оновленні товару: ' . $result['message'];
                $messageType = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редагування товару - Винна крамниця</title>
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
                <a href="products.php" class="flex items-center px-4 py-3 bg-red-800">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Редагування товару</h1>
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

                <!-- Форма редагування товару -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold">Інформація про товар</h2>
                    </div>
                    <form action="edit_product.php?id=<?= $productId ?>" method="POST" enctype="multipart/form-data" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Основна інформація -->
                            <div>
                                <div class="mb-4">
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Назва товару *</label>
                                    <input type="text" id="name" name="name" required
                                           value="<?= htmlspecialchars($product['name']) ?>"
                                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Категорія *</label>
                                    <select id="category_id" name="category_id" required
                                            class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                        <option value="">Виберіть категорію</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= $product['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Короткий опис</label>
                                    <textarea id="description" name="description" rows="3"
                                              class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"><?= htmlspecialchars($product['description']) ?></textarea>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="details" class="block text-sm font-medium text-gray-700 mb-1">Детальний опис</label>
                                    <textarea id="details" name="details" rows="5"
                                              class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"><?= htmlspecialchars($product['details']) ?></textarea>
                                </div>
                                
                                <div
                                    <div class="mb-4">
                                    <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Зображення</label>
                                    <?php if (!empty($product['image'])): ?>
                                    <div class="mb-2">
                                        <img src="../../assets/images/<?= htmlspecialchars($product['image']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>" 
                                             class="h-32 w-32 object-cover rounded">
                                    </div>
                                    <?php endif; ?>
                                    <input type="file" id="image" name="image" accept="image/*"
                                           class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    <p class="text-xs text-gray-500 mt-1">Рекомендований розмір: 800x800 пікселів. Максимальний розмір: 2MB.</p>
                                </div>
                            </div>
                            
                            <!-- Додаткова інформація -->
                            <div>
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Ціна *</label>
                                        <div class="flex">
                                            <input type="number" id="price" name="price" step="0.01" min="0" required
                                                   value="<?= number_format($product['price'], 2, '.', '') ?>"
                                                   class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                            <span class="ml-2 text-gray-700 flex items-center">₴</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="min_stock" class="block text-sm font-medium text-gray-700 mb-1">Мінімальний запас</label>
                                        <input type="number" id="min_stock" name="min_stock" min="0" 
                                               value="<?= $product['min_stock'] ?>"
                                               class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Рік</label>
                                        <input type="number" id="year" name="year" min="1900" max="<?= date('Y') ?>" 
                                               value="<?= $product['year'] ? $product['year'] : '' ?>"
                                               class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    </div>
                                    <div>
                                        <label for="alcohol" class="block text-sm font-medium text-gray-700 mb-1">Вміст алкоголю (%)</label>
                                        <input type="number" id="alcohol" name="alcohol" step="0.1" min="0" max="100"
                                               value="<?= $product['alcohol'] ? number_format($product['alcohol'], 1, '.', '') : '' ?>"
                                               class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="volume" class="block text-sm font-medium text-gray-700 mb-1">Об'єм (мл)</label>
                                        <input type="number" id="volume" name="volume" min="0" 
                                               value="<?= $product['volume'] ?>"
                                               class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    </div>
                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                                        <select id="status" name="status"
                                                class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                            <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Активний</option>
                                            <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Неактивний</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="featured" name="featured" 
                                               <?= $product['featured'] ? 'checked' : '' ?>
                                               class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                                        <label for="featured" class="ml-2 block text-sm text-gray-700">
                                            Рекомендований товар (відображається на головній сторінці)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 border-t pt-6 flex justify-end">
                            <a href="products.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2">
                                Скасувати
                            </a>
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                                Оновити товар
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Попередній перегляд зображення
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.getElementById('image');
            const imagePreview = document.querySelector('#image + .image-preview');
            
            imageInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Можна додати попередній перегляд, якщо потрібно
                        console.log('Файл вибрано:', file.name);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html>