<?php
// cart.php
// Сторінка кошика користувача

// Підключаємо конфігурацію
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';
require_once ROOT_PATH . '/controllers/AuthController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
$isLoggedIn = $authController->isLoggedIn();
$currentUser = $isLoggedIn ? $authController->getCurrentUser() : null;

// Ініціалізуємо контролер для роботи з кошиком
$customerController = new CustomerController();

// Отримуємо ID сесії з кукі або створюємо новий
$sessionId = isset($_COOKIE['cart_session_id']) ? $_COOKIE['cart_session_id'] : $customerController->generateCartSessionId();

// Якщо користувач авторизований, використовуємо його ID як сесію
if ($isLoggedIn) {
    $sessionId = 'user_' . $currentUser['id'];
}

// Обробка видалення товару з кошика
$message = '';
$messageType = '';

if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $itemId = intval($_GET['remove']);
    $result = $customerController->removeFromCart($itemId);
    
    if ($result['success']) {
        $message = 'Товар успішно видалено з кошика.';
        $messageType = 'success';
    } else {
        $message = 'Помилка при видаленні товару: ' . $result['message'];
        $messageType = 'error';
    }
}

// Обробка очищення кошика
if (isset($_GET['clear'])) {
    $result = $customerController->clearCart($sessionId);
    
    if ($result['success']) {
        $message = 'Кошик успішно очищено.';
        $messageType = 'success';
    } else {
        $message = 'Помилка при очищенні кошика: ' . $result['message'];
        $messageType = 'error';
    }
}

// Обробка оновлення кількості товару
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    $updated = false;
    
    if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $itemId => $quantity) {
            if (is_numeric($itemId) && is_numeric($quantity) && $quantity > 0) {
                $result = $customerController->updateCartQuantity($itemId, intval($quantity));
                if ($result['success']) {
                    $updated = true;
                }
            }
        }
    }
    
    if ($updated) {
        $message = 'Кошик успішно оновлено.';
        $messageType = 'success';
    }
}

// Обробка застосування промокоду
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_promo'])) {
    $promoCode = trim($_POST['promo_code']);
    $subtotal = floatval($_POST['subtotal']);
    
    if (!empty($promoCode)) {
        $result = $customerController->applyDiscount($subtotal, $promoCode);
        
        if ($result['success']) {
            $message = 'Промокод успішно застосований. Ваша знижка: ' . number_format($result['discount'], 2) . ' ₴';
            $messageType = 'success';
            
            // Зберігаємо дані знижки в сесії
            $_SESSION['discount'] = [
                'code' => $promoCode,
                'amount' => $result['discount'],
                'new_total' => $result['new_total']
            ];
        } else {
            $message = 'Помилка при застосуванні промокоду: ' . $result['message'];
            $messageType = 'error';
            
            // Очищаємо дані знижки з сесії, якщо вони були
            if (isset($_SESSION['discount'])) {
                unset($_SESSION['discount']);
            }
        }
    } else {
        $message = 'Будь ласка, введіть промокод.';
        $messageType = 'warning';
    }
}

// Отримання списку товарів у кошику
$cartItems = $customerController->getCart($sessionId);

// Обчислення загальної суми
$subtotal = 0;
$totalQuantity = 0;

foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $totalQuantity += $item['quantity'];
}

// Отримання знижки, якщо є
$discount = 0;
$totalWithDiscount = $subtotal;

if (isset($_SESSION['discount'])) {
    $discount = $_SESSION['discount']['amount'];
    $totalWithDiscount = $_SESSION['discount']['new_total'];
}

// Доставка (фіксована сума)
$shippingCost = 150.00;

// Загальна сума з урахуванням знижки та доставки
$total = $totalWithDiscount + $shippingCost;

// Отримання активних промокодів для відображення
$activePromotions = $customerController->getActivePromotions();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кошик - Винна крамниця</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-50">
    <header class="bg-red-800 text-white">
        <!-- Верхня панель -->
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center">
                <a href="index.php" class="font-bold text-2xl">Винна крамниця</a>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <form action="index.php" method="GET" class="flex">
                        <input type="text" name="search" placeholder="Пошук вина..." 
                               class="px-4 py-2 rounded-l text-gray-800 focus:outline-none" 
                               style="min-width: 300px;">
                        <button type="submit" class="bg-red-900 px-4 py-2 rounded-r hover:bg-red-700">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <a href="cart.php" class="relative">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <span class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs cart-count"><?= $totalQuantity ?></span>
                </a>
                <?php if ($isLoggedIn): ?>
                <div class="flex items-center space-x-2">
                    <a href="account/index.php" class="hover:text-red-200"><?= htmlspecialchars($currentUser['name']) ?></a>
                    <span>|</span>
                    <a href="logout.php" class="hover:text-red-200">Вихід</a>
                </div>
                <?php else: ?>
                <div class="flex items-center space-x-2">
                    <a href="login.php" class="hover:text-red-200">Увійти</a>
                    <span>|</span>
                    <a href="register.php" class="hover:text-red-200">Реєстрація</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Навігаційне меню -->
        <nav class="bg-red-900">
            <div class="container mx-auto px-4">
                <ul class="flex space-x-6 py-3">
                    <li><a href="index.php" class="hover:text-red-200">Головна</a></li>
                    <li><a href="index.php?category=1" class="hover:text-red-200">Червоні вина</a></li>
                    <li><a href="index.php?category=2" class="hover:text-red-200">Білі вина</a></li>
                    <li><a href="index.php?category=3" class="hover:text-red-200">Рожеві вина</a></li>
                    <li><a href="index.php?category=4" class="hover:text-red-200">Ігристі вина</a></li>
                    <li><a href="index.php?category=5" class="hover:text-red-200">Десертні вина</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-8">
        <!-- Шлях та заголовок -->
        <div class="mb-6">
            <div class="flex items-center text-sm text-gray-500 mb-2">
                <a href="index.php" class="hover:text-red-800">Головна</a>
                <span class="mx-2">/</span>
                <span class="text-gray-700">Кошик</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Кошик</h1>
        </div>

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

        <?php if (empty($cartItems)): ?>
        <!-- Порожній кошик -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6 text-center">
            <div class="mb-6">
                <i class="fas fa-shopping-cart text-gray-300 text-6xl"></i>
            </div>
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Ваш кошик порожній</h2>
            <p class="text-gray-600 mb-6">Почніть покупки прямо зараз і насолоджуйтесь неперевершеними винами.</p>
            <a href="index.php" class="bg-red-800 hover:bg-red-700 text-white px-6 py-3 rounded-lg inline-block">
                Перейти до каталогу
            </a>
        </div>
        <?php else: ?>
        <!-- Заповнений кошик -->
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Список товарів -->
            <div class="lg:w-2/3">
                <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
                    <div class="p-6 border-b">
                        <h2 class="text-xl font-semibold">Товари в кошику</h2>
                    </div>
                    <form method="POST" action="cart.php" id="cart-form">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Товар</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ціна</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Кількість</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($cartItems as $item): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-20 w-20">
                                                    <img class="h-full w-full object-cover" src="assets/images/<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <a href="product.php?id=<?= $item['product_id'] ?>" class="hover:text-red-800">
                                                            <?= htmlspecialchars($item['name']) ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= number_format($item['price'], 2) ?> ₴</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <button type="button" class="decrease-quantity bg-gray-200 px-3 py-2 rounded-l" data-item-id="<?= $item['id'] ?>">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" name="quantity[<?= $item['id'] ?>]" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock_quantity'] ?>" class="w-16 text-center py-2 border-t border-b border-gray-300" data-item-id="<?= $item['id'] ?>" data-price="<?= $item['price'] ?>">
                                                <button type="button" class="increase-quantity bg-gray-200 px-3 py-2 rounded-r" data-item-id="<?= $item['id'] ?>" data-max="<?= $item['stock_quantity'] ?>">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            <?php if ($item['quantity'] > $item['stock_quantity']): ?>
                                            <div class="text-xs text-red-600 mt-1">
                                                Доступно тільки <?= $item['stock_quantity'] ?> шт.
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 item-total" data-item-id="<?= $item['id'] ?>"><?= number_format($item['price'] * $item['quantity'], 2) ?> ₴</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <a href="cart.php?remove=<?= $item['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Ви впевнені, що хочете видалити цей товар з кошика?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-6 border-t flex justify-between">
                            <a href="cart.php?clear=1" class="text-gray-500 hover:text-red-600" onclick="return confirm('Ви впевнені, що хочете очистити кошик?')">
                                <i class="fas fa-trash-alt mr-2"></i> Очистити кошик
                            </a>
                            <button type="submit" name="update_cart" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                <i class="fas fa-sync-alt mr-2"></i> Оновити кошик
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Продовжити покупки -->
                <div class="mb-6">
                    <a href="index.php" class="text-red-800 hover:underline inline-flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Продовжити покупки
                    </a>
                </div>
            </div>
            
            <!-- Підсумок -->
            <div class="lg:w-1/3">
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Підсумок</h2>
                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Підсумок</span>
                            <span class="font-medium" id="subtotal"><?= number_format($subtotal, 2) ?> ₴</span>
                        </div>
                        <?php if ($discount > 0): ?>
                        <div class="flex justify-between text-green-600">
                            <span>Знижка (<?= htmlspecialchars($_SESSION['discount']['code']) ?>)</span>
                            <span>-<?= number_format($discount, 2) ?> ₴</span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Доставка</span>
                            <span class="font-medium"><?= number_format($shippingCost, 2) ?> ₴</span>
                        </div>
                        <div class="border-t pt-3 flex justify-between text-lg font-semibold">
                            <span>Всього</span>
                            <span class="text-red-800" id="total"><?= number_format($total, 2) ?> ₴</span>
                        </div>
                    </div>
                    
                    <!-- Промокод -->
                    <div class="mb-6">
                        <form method="POST" action="cart.php" class="flex flex-col space-y-2">
                            <input type="hidden" name="subtotal" id="promo-subtotal" value="<?= $subtotal ?>">
                            <div class="flex">
                                <input type="text" name="promo_code" placeholder="Промокод" class="flex-1 border rounded-l px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                                <button type="submit" name="apply_promo" class="bg-red-800 hover:bg-red-700 text-white px-4 py-2 rounded-r">
                                    Застосувати
                                </button>
                            </div>
                            <?php if (!empty($activePromotions)): ?>
                            <div class="text-sm text-gray-500">
                                <p>Доступні промокоди:</p>
                                <ul class="mt-1 space-y-1">
                                    <?php foreach ($activePromotions as $promotion): ?>
                                    <li class="flex justify-between">
                                        <span class="font-medium"><?= htmlspecialchars($promotion['code']) ?></span>
                                        <span>
                                            <?php if ($promotion['discount_percent']): ?>
                                            <?= $promotion['discount_percent'] ?>% знижка
                                            <?php elseif ($promotion['discount_amount']): ?>
                                            <?= number_format($promotion['discount_amount'], 2) ?> ₴ знижка
                                            <?php endif; ?>
                                        </span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <!-- Кнопка оформлення замовлення -->
                    <a href="checkout.php" class="block w-full bg-red-800 hover:bg-red-700 text-white text-center font-semibold px-4 py-3 rounded-lg">
                        Оформити замовлення
                    </a>
                    
                    <!-- Інформація про доставку -->
                    <div class="mt-6 text-sm text-gray-500">
                        <p class="flex items-center mb-2">
                            <i class="fas fa-truck mr-2"></i>
                            Доставка по всій Україні
                        </p>
                        <p class="flex items-center mb-2">
                            <i class="fas fa-lock mr-2"></i>
                            Безпечна оплата
                        </p>
                        <p class="flex items-center">
                            <i class="fas fa-phone mr-2"></i>
                            Підтримка клієнтів 24/7
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <footer class="bg-gray-900 text-white py-8 mt-16">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <h3 class="text-xl font-semibold mb-4">Винна крамниця</h3>
                    <p class="text-gray-400">Ваш надійний партнер у світі вина з 2015 року.</p>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Категорії</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php?category=1" class="text-gray-400 hover:text-white">Червоні вина</a></li>
                        <li><a href="index.php?category=2" class="text-gray-400 hover:text-white">Білі вина</a></li>
                        <li><a href="index.php?category=3" class="text-gray-400 hover:text-white">Рожеві вина</a></li>
                        <li><a href="index.php?category=4" class="text-gray-400 hover:text-white">Ігристі вина</a></li>
                        <li><a href="index.php?category=5" class="text-gray-400 hover:text-white">Десертні вина</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Контакти</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-2"></i>
                            <span>вул. Виноградна, 1, Київ, 01001</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone mr-2"></i>
                            <span>+380 (50) 123-45-67</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-2"></i>
                            <span>info@winery.ua</span>
                        </li>
                    </ul>
                    <div class="mt-4 flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400 text-sm">
                <p>&copy; 2025 Винна крамниця. Всі права захищено.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Функції для збільшення та зменшення кількості товару
            const quantityInputs = document.querySelectorAll('input[type="number"]');
            const decreaseButtons = document.querySelectorAll('.decrease-quantity');
            const increaseButtons = document.querySelectorAll('.increase-quantity');
            
            // Додаємо обробники подій для кнопок зменшення кількості
            decreaseButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-item-id');
                    const input = document.querySelector(`input[name="quantity[${itemId}]"]`);
                    
                    let value = parseInt(input.value) - 1;
                    if (value < 1) value = 1;
                    
                    input.value = value;
                    updateItemTotal(itemId);
                    updateTotals();
                });
            });

            // Додаємо обробники подій для кнопок збільшення кількості
            increaseButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-item-id');
                    const input = document.querySelector(`input[name="quantity[${itemId}]"]`);
                    const maxQuantity = parseInt(this.getAttribute('data-max')) || 100;
                    
                    let value = parseInt(input.value) + 1;
                    if (value > maxQuantity) {
                        value = maxQuantity;
                        alert(`Доступно лише ${maxQuantity} шт.`);
                    }
                    
                    input.value = value;
                    updateItemTotal(itemId);
                    updateTotals();
                });
            });

            // Оновлення при зміні кількості вручну
            quantityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const itemId = this.getAttribute('data-item-id');
                    const maxQuantity = parseInt(document.querySelector(`.increase-quantity[data-item-id="${itemId}"]`).getAttribute('data-max')) || 100;
                    
                    let value = parseInt(this.value);
                    if (isNaN(value) || value < 1) value = 1;
                    if (value > maxQuantity) {
                        value = maxQuantity;
                        alert(`Доступно лише ${maxQuantity} шт.`);
                    }
                    
                    this.value = value;
                    updateItemTotal(itemId);
                    updateTotals();
                });
            });

            // Функція для оновлення суми товару
            function updateItemTotal(itemId) {
                const input = document.querySelector(`input[name="quantity[${itemId}]"]`);
                const price = parseFloat(input.getAttribute('data-price'));
                const quantity = parseInt(input.value);
                const total = price * quantity;
                
                const totalElement = document.querySelector(`.item-total[data-item-id="${itemId}"]`);
                if (totalElement) {
                    totalElement.textContent = formatPrice(total);
                }
            }
            
            // Функція для оновлення загальної суми
            function updateTotals() {
                let subtotal = 0;
                
                // Розрахунок підсумку
                document.querySelectorAll('input[type="number"]').forEach(input => {
                    const price = parseFloat(input.getAttribute('data-price'));
                    const quantity = parseInt(input.value);
                    if (!isNaN(price) && !isNaN(quantity)) {
                        subtotal += price * quantity;
                    }
                });
                
                // Оновлення підсумку
                const subtotalElement = document.getElementById('subtotal');
                if (subtotalElement) {
                    subtotalElement.textContent = formatPrice(subtotal);
                }
                
                // Оновлення прихованого поля для промокоду
                const promoSubtotalInput = document.getElementById('promo-subtotal');
                if (promoSubtotalInput) {
                    promoSubtotalInput.value = subtotal.toFixed(2);
                }
                
                // Отримання знижки
                let discount = 0;
                const discountElement = document.querySelector('.text-green-600 span:last-child');
                if (discountElement) {
                    // Витягуємо числове значення з тексту "-123.45 ₴"
                    const discountText = discountElement.textContent.trim();
                    const discountMatch = discountText.match(/(\d+[.,]?\d*)/);
                    if (discountMatch && discountMatch[1]) {
                        discount = parseFloat(discountMatch[1].replace(',', '.'));
                    }
                }
                
                // Отримання вартості доставки (фіксована)
                const shippingCost = 150.00; // Фіксована вартість доставки
                
                // Розрахунок загальної суми
                const total = subtotal - discount + shippingCost;
                
                // Оновлення загальної суми
                const totalElement = document.getElementById('total');
                if (totalElement) {
                    totalElement.textContent = formatPrice(total);
                }
                
                // Оновлення кількості товарів у кошику у верхньому меню
                let cartCount = 0;
                document.querySelectorAll('input[type="number"]').forEach(input => {
                    cartCount += parseInt(input.value) || 0;
                });
                
                const cartCountElements = document.querySelectorAll('.cart-count');
                cartCountElements.forEach(element => {
                    element.textContent = cartCount;
                });
            }
            
            // Форматування ціни
            function formatPrice(price) {
                return price.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,') + ' ₴';
            }
            
            // Ініціалізація підсумку при завантаженні сторінки
            updateTotals();
        });
    </script>
</body>
</html>