<?php
// checkout.php
// Сторінка оформлення замовлення

// Підключаємо конфігурацію
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/CustomerController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
$isLoggedIn = $authController->isLoggedIn();
$currentUser = $isLoggedIn ? $authController->getCurrentUser() : null;

// Якщо кошик порожній, перенаправляємо на сторінку кошика
$customerController = new CustomerController();
$sessionId = isset($_COOKIE['cart_session_id']) ? $_COOKIE['cart_session_id'] : $customerController->generateCartSessionId();
$cartItems = $customerController->getCart($sessionId);

if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// Обчислюємо загальну суму
$subtotal = 0;
$totalQuantity = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $totalQuantity += $item['quantity'];
}

// Ставка доставки
$shippingRate = 150.00; // Фіксована вартість доставки

// Загальна сума з доставкою
$total = $subtotal + $shippingRate;

// Отримання активних промокодів
$promotions = $customerController->getActivePromotions();

// Обробка форми
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Якщо користувач не авторизований, спочатку потрібно зареєструватися або увійти
    if (!$isLoggedIn) {
        $errors[] = 'Будь ласка, увійдіть в систему або зареєструйтеся для оформлення замовлення';
    } else {
        // Обробка промокоду, якщо вказаний
        if (!empty($_POST['promo_code'])) {
            $promoResult = $customerController->applyDiscount($total, $_POST['promo_code']);
            if ($promoResult['success']) {
                $total = $promoResult['new_total'];
            } else {
                $errors[] = $promoResult['message'];
            }
        }

        // Оформлення замовлення
        if (empty($errors)) {
            $orderData = [
                'shipping_address' => $_POST['address'] . ', ' . $_POST['city'] . ', ' . $_POST['region'] . ', ' . $_POST['postal_code'],
                'payment_method' => $_POST['payment_method'],
                'shipping_cost' => $shippingRate,
                'notes' => $_POST['notes'] ?? ''
            ];

            $result = $customerController->createOrder($currentUser['id'], $sessionId, $orderData);

            if ($result['success']) {
                $success = true;
                $orderId = $result['order_id'];
            } else {
                $errors[] = $result['message'];
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
    <title>Оформлення замовлення - Винна крамниця</title>
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
                <a href="cart.php" class="hover:text-red-800">Кошик</a>
                <span class="mx-2">/</span>
                <span class="text-gray-700">Оформлення замовлення</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Оформлення замовлення</h1>
        </div>

        <?php if (!$isLoggedIn): ?>
        <!-- Повідомлення для неавторизованих користувачів -->
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Будь ласка, <a href="login.php?redirect=checkout" class="font-medium underline text-yellow-700 hover:text-yellow-600">увійдіть</a> або <a href="register.php?redirect=checkout" class="font-medium underline text-yellow-700 hover:text-yellow-600">зареєструйтеся</a> для оформлення замовлення.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <!-- Помилки форми -->
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-times-circle text-red-500"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Виникли помилки при оформленні замовлення:</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <!-- Успішне оформлення замовлення -->
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-500"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">Замовлення успішно оформлено!</h3>
                    <div class="mt-2 text-sm text-green-700">
                        <p>Ваше замовлення №<?= $orderId ?> прийнято до обробки. Наш менеджер зв'яжеться з вами найближчим часом.</p>
                        <p class="mt-2">
                            <a href="account/order_details.php?id=<?= $orderId ?>" class="font-medium underline text-green-700 hover:text-green-600">Переглянути деталі замовлення</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center py-8">
            <a href="index.php" class="bg-red-800 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg inline-block">
                Повернутися до каталогу
            </a>
        </div>
        <?php else: ?>
        <!-- Форма оформлення замовлення -->
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Форма даних -->
            <div class="lg:w-2/3">
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Дані доставки</h2>
                    
                    <form action="checkout.php" method="POST" id="checkout-form">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Ім'я та прізвище</label>
                                <input type="text" id="name" name="name" value="<?= $isLoggedIn ? htmlspecialchars($currentUser['name']) : '' ?>" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
                                <input type="tel" id="phone" name="phone" value="<?= $isLoggedIn ? htmlspecialchars($currentUser['phone'] ?? '') : '' ?>" required
                                       placeholder="+380501234567"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" id="email" name="email" value="<?= $isLoggedIn ? htmlspecialchars($currentUser['email']) : '' ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div class="mb-4">
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Адреса</label>
                            <input type="text" id="address" name="address" value="<?= $isLoggedIn ? htmlspecialchars($currentUser['address'] ?? '') : '' ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Місто</label>
                                <input type="text" id="city" name="city" value="<?= $isLoggedIn ? htmlspecialchars($currentUser['city'] ?? '') : '' ?>" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                            </div>
                            <div>
                                <label for="region" class="block text-sm font-medium text-gray-700 mb-1">Область</label>
                                <input type="text" id="region" name="region" value="<?= $isLoggedIn ? htmlspecialchars($currentUser['region'] ?? '') : '' ?>" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                            </div>
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Поштовий індекс</label>
                                <input type="text" id="postal_code" name="postal_code" value="<?= $isLoggedIn ? htmlspecialchars($currentUser['postal_code'] ?? '') : '' ?>" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Примітки до замовлення</label>
                            <textarea id="notes" name="notes" rows="3" 
                                     class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"></textarea>
                            <p class="text-sm text-gray-500 mt-1">Додаткова інформація, побажання щодо доставки тощо.</p>
                        </div>

                        <h2 class="text-xl font-semibold mt-8 mb-4">Спосіб оплати</h2>
                        
                        <div class="space-y-4 mb-6">
                            <div class="flex items-center">
                                <input id="payment_card" name="payment_method" type="radio" value="card" checked
                                       class="h-4 w-4 text-red-800 focus:ring-red-500 border-gray-300">
                                <label for="payment_card" class="ml-3 block text-sm font-medium text-gray-700">
                                    Оплата картою онлайн
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input id="payment_bank" name="payment_method" type="radio" value="bank_transfer"
                                       class="h-4 w-4 text-red-800 focus:ring-red-500 border-gray-300">
                                <label for="payment_bank" class="ml-3 block text-sm font-medium text-gray-700">
                                    Банківський переказ
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input id="payment_cod" name="payment_method" type="radio" value="cash_on_delivery"
                                       class="h-4 w-4 text-red-800 focus:ring-red-500 border-gray-300">
                                <label for="payment_cod" class="ml-3 block text-sm font-medium text-gray-700">
                                    Оплата при отриманні
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Підсумок замовлення -->
            <div class="lg:w-1/3">
                <div class="bg-white rounded-lg shadow p-6 sticky top-6">
                    <h2 class="text-xl font-semibold mb-4">Підсумок замовлення</h2>
                    
                    <div class="border-t border-b py-4 mb-4">
                        <div class="max-h-60 overflow-y-auto">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="flex py-2">
                                <div class="w-16 h-16 flex-shrink-0">
                                    <img src="assets/images/<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-full h-full object-cover rounded">
                                </div>
                                <div class="ml-4 flex-1">
                                    <h3 class="text-sm font-medium text-gray-800"><?= htmlspecialchars($item['name']) ?></h3>
                                    <p class="text-sm text-gray-500"><?= $item['quantity'] ?> x <?= number_format($item['price'], 2) ?> ₴</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-800"><?= number_format($item['price'] * $item['quantity'], 2) ?> ₴</p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="space-y-2 mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Підсумок</span>
                            <span class="font-medium"><?= number_format($subtotal, 2) ?> ₴</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Доставка</span>
                            <span class="font-medium"><?= number_format($shippingRate, 2) ?> ₴</span>
                        </div>
                        <div class="flex justify-between text-lg font-semibold">
                            <span>Всього</span>
                            <span class="text-red-800"><?= number_format($total, 2) ?> ₴</span>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="promo_code" class="block text-sm font-medium text-gray-700 mb-1">Промокод</label>
                        <div class="flex">
                            <input type="text" id="promo_code" name="promo_code" form="checkout-form"
                                   class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-l-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                            <button type="button" id="apply-promo" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-r-md text-white bg-red-800 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Застосувати
                            </button>
                        </div>
                        <?php if (!empty($promotions)): ?>
                        <div class="mt-2 text-sm text-gray-500">
                            <p>Доступні промокоди:</p>
                            <ul class="list-disc pl-5 space-y-1">
                                <?php foreach ($promotions as $promotion): ?>
                                <li>
                                    <strong><?= htmlspecialchars($promotion['code']) ?></strong> - 
                                    <?= htmlspecialchars($promotion['name']) ?>
                                    <?php if ($promotion['discount_percent']): ?>
                                    (<?= $promotion['discount_percent'] ?>% знижка)
                                    <?php elseif ($promotion['discount_amount']): ?>
                                    (<?= number_format($promotion['discount_amount'], 2) ?> ₴ знижка)
                                    <?php endif; ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" form="checkout-form" <?= !$isLoggedIn ? 'disabled' : '' ?>
                            class="w-full py-3 px-4 border border-transparent rounded-md shadow-sm text-white bg-red-800 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 
                                  <?= !$isLoggedIn ? 'opacity-50 cursor-not-allowed' : '' ?>">
                        Оформити замовлення
                    </button>
                    
                    <?php if (!$isLoggedIn): ?>
                    <p class="text-sm text-gray-500 mt-2 text-center">
                        Для оформлення замовлення потрібно
                        <a href="login.php?redirect=checkout" class="text-red-800 hover:underline">увійти</a> або
                        <a href="register.php?redirect=checkout" class="text-red-800 hover:underline">зареєструватися</a>
                    </p>
                    <?php endif; ?>
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
                    <h3 class="text-xl font-semibold mb-4">Інформація</h3>
                    <ul class="space-y-2">
                        <li><a href="about.php" class="text-gray-400 hover:text-white">Про нас</a></li>
                        <li><a href="delivery.php" class="text-gray-400 hover:text-white">Доставка та оплата</a></li>
                        <li><a href="contact.php" class="text-gray-400 hover:text-white">Контакти</a></li>
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
        // Підрахунок кількості товарів у кошику
        function updateCartCount() {
            fetch('api/cart_count.php')
                .then(response => response.json())
                .then(data => {
                    document.querySelector('.cart-count').textContent = data.count;
                })
                .catch(error => console.error('Помилка:', error));
        }

        // Обробка промокоду
        document.getElementById('apply-promo').addEventListener('click', function() {
            const promoCode = document.getElementById('promo_code').value;
            if (!promoCode) return;

            fetch('api/apply_promo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `promo_code=${encodeURIComponent(promoCode)}&subtotal=${<?= $total ?>}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Промокод успішно застосований! Знижка: ' + data.discount + ' ₴');
                    location.reload(); // Перезавантажуємо сторінку для оновлення цін
                } else {
                    alert('Помилка: ' + data.message);
                }
            })
            .catch(error => console.error('Помилка:', error));
        });

        // Оновлення кількості товарів при завантаженні сторінки
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>