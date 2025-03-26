<?php
// config.php
// Центральний файл конфігурації 

// Визначаємо ROOT_PATH тільки якщо він ще не визначений
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

// Режим відладки
define('DEBUG_MODE', true);

// SQL логування
define('SQL_DEBUG', true);

// Назва сайту
define('SITE_NAME', 'Винна крамниця');

// Сторінок на сторінці пагінації
define('ITEMS_PER_PAGE', 12);

// Функція для визначення відносного шляху до файлу
function get_relative_path($path) {
    return ROOT_PATH . '/' . $path;
}

// Функція для автоматичного завантаження класів контролерів
function autoload_controller($className) {
    $path = ROOT_PATH . '/controllers/' . $className . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
}

// Функція для автоматичного завантаження класів моделей
function autoload_model($className) {
    $path = ROOT_PATH . '/models/' . $className . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
}

// Налаштування обробки помилок у режимі відладки
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Створюємо директорію для логів, якщо її немає
$logsDir = ROOT_PATH . '/logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}

// Встановлення обробника для неперехоплених винятків
set_exception_handler(function($exception) {
    // Шлях до файлу логу помилок
    $logFile = ROOT_PATH . '/logs/errors_' . date('Y-m-d') . '.log';
    
    // Формування повідомлення про помилку
    $message = '[' . date('Y-m-d H:i:s') . '] Uncaught Exception: ' . $exception->getMessage() . PHP_EOL;
    $message .= 'File: ' . $exception->getFile() . ' on line ' . $exception->getLine() . PHP_EOL;
    $message .= 'Stack trace: ' . PHP_EOL . $exception->getTraceAsString() . PHP_EOL . PHP_EOL;
    
    // Запис у лог файл
    file_put_contents($logFile, $message, FILE_APPEND);
    
    // Якщо режим відладки увімкнено, показуємо помилку
    if (DEBUG_MODE) {
        echo "<div style='background-color:#f8d7da; color:#721c24; padding:15px; margin:15px; border:1px solid #f5c6cb; border-radius:4px;'>";
        echo "<h3>Помилка!</h3>";
        echo "<p>" . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p>Файл: " . htmlspecialchars($exception->getFile()) . " на рядку " . $exception->getLine() . "</p>";
        if (DEBUG_MODE) {
            echo "<h4>Stack Trace:</h4>";
            echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        }
        echo "</div>";
    } else {
        // У продакшн режимі показуємо загальне повідомлення про помилку
        echo "<div style='background-color:#f8d7da; color:#721c24; padding:15px; margin:15px; border:1px solid #f5c6cb; border-radius:4px;'>";
        echo "<h3>Сталася помилка</h3>";
        echo "<p>Будь ласка, спробуйте пізніше або зверніться до адміністратора.</p>";
        echo "</div>";
    }
});

// Реєструємо функції автозавантаження
spl_autoload_register('autoload_controller');
spl_autoload_register('autoload_model');

// Отримуємо вміст кошика для показу кількості товарів

require_once ROOT_PATH . '/controllers/CustomerController.php';



$customerController = new CustomerController();
$authController = new AuthController();

$sessionId = isset($_COOKIE['cart_session_id']) ? $_COOKIE['cart_session_id'] : $customerController->generateCartSessionId();

if ($authController->isLoggedIn()) {
    // Якщо користувач авторизований, використовуємо його ID як сесію
    $currentUser = $authController->getCurrentUser();
    $sessionId = 'user_' . $currentUser['id'];
}

$cartItems = $customerController->getCart($sessionId);
$totalQuantity = 0;
foreach ($cartItems as $item) {
    $totalQuantity += $item['quantity'];
}