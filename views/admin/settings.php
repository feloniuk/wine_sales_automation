<?php
// admin/settings.php
// Сторінка налаштувань системи

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('admin')) {
    header('Location: /login.php?redirect=admin/settings');
    exit;
}

// Отримуємо поточного користувача
$currentUser = $authController->getCurrentUser();

// Клас для роботи з налаштуваннями
class SystemSettings {
    private $settingsFile;

    public function __construct() {
        $this->settingsFile = ROOT_PATH . '/config/system_settings.json';
    }

    // Отримання поточних налаштувань
    public function getSettings() {
        if (!file_exists($this->settingsFile)) {
            return $this->getDefaultSettings();
        }

        $settings = json_decode(file_get_contents($this->settingsFile), true);
        return $settings ?? $this->getDefaultSettings();
    }

    // Налаштування за замовчуванням
    private function getDefaultSettings() {
        return [
            'system' => [
                'site_name' => 'Винна крамниця',
                'timezone' => 'Europe/Kiev',
                'items_per_page' => 10,
                'maintenance_mode' => false
            ],
            'email' => [
                'smtp_host' => 'localhost',
                'smtp_port' => 25,
                'smtp_username' => '',
                'smtp_password' => '',
                'smtp_encryption' => 'tls',
                'sender_email' => 'noreply@wineshop.com',
                'sender_name' => 'Винна крамниця'
            ]
        ];
    }

    // Збереження налаштувань
    public function saveSettings($settings) {
        // Валідація вхідних даних
        $currentSettings = $this->getSettings();
        
        // Оновлюємо лише передані значення
        $currentSettings['system'] = array_merge(
            $currentSettings['system'], 
            $settings['system'] ?? []
        );
        
        $currentSettings['email'] = array_merge(
            $currentSettings['email'], 
            $settings['email'] ?? []
        );

        // Записуємо налаштування у файл
        file_put_contents(
            $this->settingsFile, 
            json_encode($currentSettings, JSON_PRETTY_PRINT)
        );

        return true;
    }

    // Перевірка з'єднання SMTP
    public function testSmtpConnection($settings) {
        try {
            // Використовуємо PHPMailer для перевірки з'єднання
            require_once ROOT_PATH . '/vendor/autoload.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $settings['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $settings['smtp_username'];
            $mail->Password = $settings['smtp_password'];
            $mail->SMTPSecure = $settings['smtp_encryption'];
            $mail->Port = $settings['smtp_port'];

            $mail->setFrom($settings['sender_email'], $settings['sender_name']);
            $mail->addAddress($settings['sender_email']);
            $mail->Subject = 'Тестування SMTP з\'єднання';
            $mail->Body = 'Успішне підключення до SMTP-сервера.';

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Обробка форми налаштувань
$message = '';
$messageType = '';
$systemSettings = new SystemSettings();
$currentSettings = $systemSettings->getSettings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'system_settings':
            $systemData = [
                'system' => [
                    'site_name' => $_POST['site_name'] ?? $currentSettings['system']['site_name'],
                    'timezone' => $_POST['timezone'] ?? $currentSettings['system']['timezone'],
                    'items_per_page' => intval($_POST['items_per_page'] ?? $currentSettings['system']['items_per_page']),
                    'maintenance_mode' => isset($_POST['maintenance_mode']) ? true : false
                ]
            ];

            if ($systemSettings->saveSettings($systemData)) {
                $message = 'Системні налаштування успішно збережено.';
                $messageType = 'success';
                $currentSettings = $systemSettings->getSettings();
            } else {
                $message = 'Помилка при збереженні налаштувань.';
                $messageType = 'error';
            }
            break;

        case 'email_settings':
            $emailData = [
                'email' => [
                    'smtp_host' => $_POST['smtp_host'] ?? $currentSettings['email']['smtp_host'],
                    'smtp_port' => intval($_POST['smtp_port'] ?? $currentSettings['email']['smtp_port']),
                    'smtp_username' => $_POST['smtp_username'] ?? $currentSettings['email']['smtp_username'],
                    'smtp_encryption' => $_POST['smtp_encryption'] ?? $currentSettings['email']['smtp_encryption'],
                    'sender_email' => $_POST['sender_email'] ?? $currentSettings['email']['sender_email'],
                    'sender_name' => $_POST['sender_name'] ?? $currentSettings['email']['sender_name']
                ]
            ];

            // Оновлюємо пароль лише якщо він вказаний
            if (!empty($_POST['smtp_password'])) {
                $emailData['email']['smtp_password'] = $_POST['smtp_password'];
            }

            if ($systemSettings->saveSettings($emailData)) {
                // Перевірка з'єднання SMTP
                if (isset($_POST['test_connection'])) {
                    if ($systemSettings->testSmtpConnection($emailData['email'])) {
                        $message = 'Налаштування електронної пошти збережено. SMTP-з\'єднання успішне.';
                        $messageType = 'success';
                    } else {
                        $message = 'Налаштування збережено, але не вдалося встановити SMTP-з\'єднання.';
                        $messageType = 'warning';
                    }
                } else {
                    $message = 'Налаштування електронної пошти успішно збережено.';
                    $messageType = 'success';
                }

                $currentSettings = $systemSettings->getSettings();
            } else {
                $message = 'Помилка при збереженні налаштувань електронної пошти.';
                $messageType = 'error';
            }
            break;
    }
}

// Отримання списку часових поясів
$timezones = DateTimeZone::listIdentifiers();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Налаштування системи - Винна крамниця</title>
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
                <a href="settings.php" class="flex items-center px-4 py-3 bg-red-800">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Налаштування системи</h1>
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Системні налаштування -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Системні налаштування</h2>
                        <form action="settings.php" method="POST">
                            <input type="hidden" name="action" value="system_settings">
                            
                            <div class="mb-4">
                                <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">Назва сайту</label>
                                <input type="text" id="site_name" name="site_name" 
                                       value="<?= htmlspecialchars($currentSettings['system']['site_name']) ?>"
                                       class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            
                            <div class="mb-4">
                                <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">Часовий пояс</label>
                                <select id="timezone" name="timezone" 
                                        class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    <?php foreach ($timezones as $tz): ?>
                                    <option value="<?= $tz ?>" <?= $currentSettings['system']['timezone'] === $tz ? 'selected' : '' ?>>
                                        <?= $tz ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="items_per_page" class="block text-sm font-medium text-gray-700 mb-1">Елементів на сторінці</label>
                                <input type="number" id="items_per_page" name="items_per_page" min="5" max="50"
                                       value="<?= $currentSettings['system']['items_per_page'] ?>"
                                       class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                           <?= $currentSettings['system']['maintenance_mode'] ? 'checked' : '' ?>
                                           class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                                    <label for="maintenance_mode" class="ml-2 block text-sm text-gray-700">
                                        Режим технічного обслуговування
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    Тимчасово закриє сайт для всіх користувачів, крім адміністраторів
                                </p>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                                    Зберегти системні налаштування
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Налаштування електронної пошти -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Налаштування електронної пошти</h2>
                        <form action="settings.php" method="POST">
                            <input type="hidden" name="action" value="email_settings">
                            
                            <div class="mb-4">
                                <label for="smtp_host" class="block text-sm font-medium text-gray-700 mb-1">SMTP-сервер</label>
                                <input type="text" id="smtp_host" name="smtp_host" 
                                       value="<?= htmlspecialchars($currentSettings['email']['smtp_host']) ?>"
                                       class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            
                            <div class="mb-4">
                                <label for="smtp_port" class="block text-sm font-medium text-gray-700 mb-1">SMTP-порт</label>
                                <input type="number" id="smtp_port" name="smtp_port" min="1" max="65535"
                                       value="<?= $currentSettings['email']['smtp_port'] ?>"
                                       class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            
                            <div class="mb-4">
                                <label for="smtp_encryption" class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                                <select id="smtp_encryption" name="smtp_encryption" 
                                        class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    <option value="tls" <?= $currentSettings['email']['smtp_encryption'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                                    <option value="ssl" <?= $currentSettings['email']['smtp_encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                    <option value="" <?= empty($currentSettings['email']['smtp_encryption']) ? 'selected' : '' ?>>Без шифрування</option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="smtp_username" class="block text-sm font-medium text-gray-700 mb-1">SMTP-логін</label>
                                <input type="text" id="smtp_username" name="smtp_username" 
                                       value="<?= htmlspecialchars($currentSettings['email']['smtp_username']) ?>"
                                       class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            
                            <div class="mb-4">
                                <label for="smtp_password" class="block text-sm font-medium text-gray-700 mb-1">SMTP-пароль</label>
                                <input type="password" id="smtp_password" name="smtp_password" 
                                       placeholder="Залиште порожнім, щоб зберегти поточний"
                                       class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            
                            <div class="mb-4">
                                <label for="sender_email" class="block text-sm font-medium text-gray-700 mb-1">Email відправника</label>
                                <input type="email" id="sender_email" name="sender_email" 
                                       value="<?= htmlspecialchars($currentSettings['email']['sender_email']) ?>"
                                       class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            
                            <div class="mb-4">
                                <label for="sender_name" class="block text-sm font-medium text-gray-700 mb-1">Ім'я відправника</label>
                                <input type="text" id="sender_name" name="sender_name" 
                                       value="<?= htmlspecialchars($currentSettings['email']['sender_name']) ?>"
                                       class="border rounded w-full px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <input type="checkbox" id="test_connection" name="test_connection" 
                                           class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded mr-2">
                                    <label for="test_connection" class="text-sm text-gray-700">
                                        Перевірити SMTP-з'єднання
                                    </label>
                                </div>
                                
                                <button type="submit" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded">
                                    Зберегти налаштування пошти
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>