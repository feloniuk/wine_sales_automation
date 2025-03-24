
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Винна крамниця - Головна</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .wine-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .category-card:hover {
            transform: scale(1.03);
            transition: all 0.3s ease;
        }
    </style>
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
                    <span class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs cart-count">0</span>
                </a>
                <div class="flex items-center space-x-2">
                    <a href="login.php" class="hover:text-red-200">Увійти</a>
                    <span>|</span>
                    <a href="register.php" class="hover:text-red-200">Реєстрація</a>
                </div>
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
                    <li><a href="about.php" class="hover:text-red-200">Про нас</a></li>
                    <li><a href="contact.php" class="hover:text-red-200">Контакти</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-8">
        <!-- Банер -->
        <section class="mb-12 relative rounded-lg overflow-hidden shadow-lg">
            <img src="assets/images/banner.jpg" alt="Винна колекція" class="w-full h-96 object-cover">
            <div class="absolute inset-0 bg-black bg-opacity-40 flex flex-col items-start justify-center text-white p-8">
                <h1 class="text-4xl font-bold mb-4">Винна крамниця</h1>
                <p class="text-xl mb-6 max-w-lg">Відкрийте для себе вишукані вина з кращих виноградників. Доставка по всій Україні.</p>
                <a href="#featured" class="bg-red-800 hover:bg-red-700 px-6 py-3 rounded-lg font-semibold transition duration-300">
                    Обрати вино
                </a>
            </div>
        </section>

        <!-- Категорії -->
        <section class="mb-12">
            <h2 class="text-3xl font-bold mb-6 text-gray-800">Категорії вин</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <a href="index.php?category=1" class="category-card bg-gradient-to-br from-red-800 to-red-900 rounded-lg p-6 text-white shadow-md">
                    <h3 class="text-xl font-semibold mb-2">Червоні вина</h3>
                    <p class="text-sm opacity-80">Багатий смак та аромат</p>
                </a>
                <a href="index.php?category=2" class="category-card bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg p-6 text-white shadow-md">
                    <h3 class="text-xl font-semibold mb-2">Білі вина</h3>
                    <p class="text-sm opacity-80">Свіжі та ароматні</p>
                </a>
                <a href="index.php?category=3" class="category-card bg-gradient-to-br from-pink-400 to-pink-500 rounded-lg p-6 text-white shadow-md">
                    <h3 class="text-xl font-semibold mb-2">Рожеві вина</h3>
                    <p class="text-sm opacity-80">Освіжаючі з ягідними нотками</p>
                </a>
                <a href="index.php?category=4" class="category-card bg-gradient-to-br from-blue-400 to-blue-500 rounded-lg p-6 text-white shadow-md">
                    <h3 class="text-xl font-semibold mb-2">Ігристі вина</h3>
                    <p class="text-sm opacity-80">Для святкувань та особливих моментів</p>
                </a>
                <a href="index.php?category=5" class="category-card bg-gradient-to-br from-purple-600 to-purple-700 rounded-lg p-6 text-white shadow-md">
                    <h3 class="text-xl font-semibold mb-2">Десертні вина</h3>
                    <p class="text-sm opacity-80">Солодкі та напівсолодкі</p>
                </a>
            </div>
        </section>

        <!-- Фільтри та сортування -->
        <section class="mb-4 flex justify-between items-center">
            <div class="flex space-x-2">
                <span class="text-gray-600">Сортувати за:</span>
                <a href="?sort=price_asc" class="text-red-800 hover:underline">Ціна ↑</a>
                <a href="?sort=price_desc" class="text-red-800 hover:underline">Ціна ↓</a>
                <a href="?sort=name" class="text-red-800 hover:underline">Назва</a>
            </div>
            <div class="text-gray-600">
                Показано <span class="font-semibold">12</span> з <span class="font-semibold">24</span> товарів
            </div>
        </section>

        <!-- Товари -->
        <section id="featured" class="mb-12">
            <h2 class="text-3xl font-bold mb-6 text-gray-800">Наші вина</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <!-- Карточка товару 1 -->
                <div class="wine-card bg-white rounded-lg overflow-hidden shadow-md">
                    <div class="relative">
                        <img src="assets/images/wine1.jpg" alt="Каберне Преміум" class="w-full h-64 object-cover">
                        <span class="absolute top-2 right-2 bg-red-800 text-white px-2 py-1 rounded text-sm">Новинка</span>
                    </div>
                    <div class="p-4">
                        <div class="flex justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">Каберне Преміум</h3>
                            <span class="text-red-800 font-bold">359 ₴</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Сухе червоне вино з багатим смаком та ароматом чорних ягід</p>
                        <div class="flex justify-between">
                            <a href="product.php?id=1" class="text-red-800 hover:underline text-sm">Детальніше</a>
                            <button class="add-to-cart bg-red-800 hover:bg-red-700 text-white px-3 py-1 rounded-lg text-sm" 
                                    data-id="1">
                                Додати в кошик
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Карточка товару 2 -->
                <div class="wine-card bg-white rounded-lg overflow-hidden shadow-md">
                    <div class="relative">
                        <img src="assets/images/wine2.jpg" alt="Мерло Класік" class="w-full h-64 object-cover">
                    </div>
                    <div class="p-4">
                        <div class="flex justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">Мерло Класік</h3>
                            <span class="text-red-800 font-bold">289 ₴</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">М'яке червоне вино з оксамитовою текстурою</p>
                        <div class="flex justify-between">
                            <a href="product.php?id=2" class="text-red-800 hover:underline text-sm">Детальніше</a>
                            <button class="add-to-cart bg-red-800 hover:bg-red-700 text-white px-3 py-1 rounded-lg text-sm" 
                                    data-id="2">
                                Додати в кошик
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Карточка товару 3 -->
                <div class="wine-card bg-white rounded-lg overflow-hidden shadow-md">
                    <div class="relative">
                        <img src="assets/images/wine3.jpg" alt="Шардоне Резерв" class="w-full h-64 object-cover">
                        <span class="absolute top-2 right-2 bg-red-800 text-white px-2 py-1 rounded text-sm">Популярне</span>
                    </div>
                    <div class="p-4">
                        <div class="flex justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">Шардоне Резерв</h3>
                            <span class="text-red-800 font-bold">329 ₴</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Елегантне біле вино з фруктовими нотками</p>
                        <div class="flex justify-between">
                            <a href="product.php?id=3" class="text-red-800 hover:underline text-sm">Детальніше</a>
                            <button class="add-to-cart bg-red-800 hover:bg-red-700 text-white px-3 py-1 rounded-lg text-sm" 
                                    data-id="3">
                                Додати в кошик
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Карточка товару 4 -->
                <div class="wine-card bg-white rounded-lg overflow-hidden shadow-md">
                    <div class="relative">
                        <img src="assets/images/wine4.jpg" alt="Совіньйон Блан" class="w-full h-64 object-cover">
                    </div>
                    <div class="p-4">
                        <div class="flex justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">Совіньйон Блан</h3>
                            <span class="text-red-800 font-bold">299 ₴</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Свіже біле вино з яскравою кислотністю</p>
                        <div class="flex justify-between">
                            <a href="product.php?id=4" class="text-red-800 hover:underline text-sm">Детальніше</a>
                            <button class="add-to-cart bg-red-800 hover:bg-red-700 text-white px-3 py-1 rounded-lg text-sm" 
                                    data-id="4">
                                Додати в кошик
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Карточка товару 5 -->
                <div class="wine-card bg-white rounded-lg overflow-hidden shadow-md">
                    <div class="relative">
                        <img src="assets/images/wine5.jpg" alt="Рожеве Преміум" class="w-full h-64 object-cover">
                    </div>
                    <div class="p-4">
                        <div class="flex justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">Рожеве Преміум</h3>
                            <span class="text-red-800 font-bold">279 ₴</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Освіжаюче рожеве вино з нотками ягід</p>
                        <div class="flex justify-between">
                            <a href="product.php?id=5" class="text-red-800 hover:underline text-sm">Детальніше</a>
                            <button class="add-to-cart bg-red-800 hover:bg-red-700 text-white px-3 py-1 rounded-lg text-sm" 
                                    data-id="5">
                                Додати в кошик
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Карточка товару 6 -->
                <div class="wine-card bg-white rounded-lg overflow-hidden shadow-md">
                    <div class="relative">
                        <img src="assets/images/wine6.jpg" alt="Ігристе Брют" class="w-full h-64 object-cover">
                        <span class="absolute top-2 right-2 bg-red-800 text-white px-2 py-1 rounded text-sm">Популярне</span>
                    </div>
                    <div class="p-4">
                        <div class="flex justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">Ігристе Брют</h3>
                            <span class="text-red-800 font-bold">399 ₴</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Сухе ігристе вино, виготовлене за класичною технологією</p>
                        <div class="flex justify-between">
                            <a href="product.php?id=6" class="text-red-800 hover:underline text-sm">Детальніше</a>
                            <button class="add-to-cart bg-red-800 hover:bg-red-700 text-white px-3 py-1 rounded-lg text-sm" 
                                    data-id="6">
                                Додати в кошик
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Карточка товару 7 -->
                <div class="wine-card bg-white rounded-lg overflow-hidden shadow-md">
                    <div class="relative">
                        <img src="assets/images/wine7.jpg" alt="Портвейн Рубі" class="w-full h-64 object-cover">
                    </div>
                    <div class="p-4">
                        <div class="flex justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">Портвейн Рубі</h3>
                            <span class="text-red-800 font-bold">459 ₴</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Солодке кріплене вино з багатим смаком</p>
                        <div class="flex justify-between">
                            <a href="product.php?id=7" class="text-red-800 hover:underline text-sm">Детальніше</a>
                            <button class="add-to-cart bg-red-800 hover:bg-red-700 text-white px-3 py-1 rounded-lg text-sm" 
                                    data-id="7">
                                Додати в кошик
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Карточка товару 8 -->
                <div class="wine-card bg-white rounded-lg overflow-hidden shadow-md">
                    <div class="relative">
                        <img src="assets/images/wine8.jpg" alt="Піно Нуар" class="w-full h-64 object-cover">
                    </div>
                    <div class="p-4">
                        <div class="flex justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">Піно Нуар</h3>
                            <span class="text-red-800 font-bold">379 ₴</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">Елегантне червоне вино з фруктовими нотами</p>
                        <div class="flex justify-between">
                            <a href="product.php?id=8" class="text-red-800 hover:underline text-sm">Детальніше</a>
                            <button class="add-to-cart bg-red-800 hover:bg-red-700 text-white px-3 py-1 rounded-lg text-sm" 
                                    data-id="8">
                                Додати в кошик
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Пагінація -->
        <section class="flex justify-center mb-12">
            <div class="flex space-x-2">
                <a href="#" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">1</a>
                <a href="#" class="px-4 py-2 bg-red-800 text-white rounded-lg">2</a>
                <a href="#" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">3</a>
                <span class="px-4 py-2">...</span>
                <a href="#" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">5</a>
            </div>
        </section>

        <!-- Переваги -->
        <section class="bg-gray-100 rounded-lg p-8 mb-12">
            <h2 class="text-3xl font-bold mb-6 text-gray-800 text-center">Наші переваги</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="flex flex-col items-center text-center">
                    <div class="bg-red-800 text-white rounded-full w-16 h-16 flex items-center justify-center mb-4">
                        <i class="fas fa-truck text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Швидка доставка</h3>
                    <p class="text-gray-600">Доставка по всій Україні протягом 1-3 днів</p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="bg-red-800 text-white rounded-full w-16 h-16 flex items-center justify-center mb-4">
                        <i class="fas fa-medal text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Гарантія якості</h3>
                    <p class="text-gray-600">Тільки перевірені та сертифіковані вина</p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="bg-red-800 text-white rounded-full w-16 h-16 flex items-center justify-center mb-4">
                        <i class="fas fa-hand-holding-dollar text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Найкращі ціни</h3>
                    <p class="text-gray-600">Регулярні акції та доступні ціни</p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="bg-red-800 text-white rounded-full w-16 h-16 flex items-center justify-center mb-4">
                        <i class="fas fa-headset text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Персональний підхід</h3>
                    <p class="text-gray-600">Консультації сомельє та індивідуальний підбір</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-white">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-semibold mb-4">Винна крамниця</h3>
                    <p class="text-gray-400">Ваш надійний партнер у світі вина з 2015 року. Ми пропонуємо найкращі вина з усього світу.</p>
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
                        <li><a href="terms.php" class="text-gray-400 hover:text-white">Умови використання</a></li>
                        <li><a href="privacy.php" class="text-gray-400 hover:text-white">Політика конфіденційності</a></li>
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
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-gray-950 py-4 text-center text-gray-400 text-sm">
            <p>&copy; 2025 Винна крамниця. Всі права захищено.</p>
        </div>
    </footer>

    <!-- Модальне вікно для додавання в кошик -->
    <div id="cartModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Товар додано до кошика</h3>
                <button id="closeCartModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="mb-4 text-gray-600">Товар успішно додано до кошика.</p>
            <div class="flex justify-between">
                <button id="continueShopping" class="px-4 py-2 border border-red-800 text-red-800 rounded hover:bg-red-50">
                    Продовжити покупки
                </button>
                <a href="cart.php" class="px-4 py-2 bg-red-800 text-white rounded hover:bg-red-700">
                    Перейти до кошика
                </a>
            </div>
        </div>
    </div>

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

        // Додавання товару в кошик
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                
                fetch('api/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&quantity=1`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartCount();
                        // Показати модальне вікно
                        document.getElementById('cartModal').classList.remove('hidden');
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Помилка:', error));
            });
        });

        // Закриття модального вікна
        document.getElementById('closeCartModal').addEventListener('click', function() {
            document.getElementById('cartModal').classList.add('hidden');
        });

        document.getElementById('continueShopping').addEventListener('click', function() {
            document.getElementById('cartModal').classList.add('hidden');
        });

        // Оновлення кількості товарів при завантаженні сторінки
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>