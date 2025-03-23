/
├── config.php               # Основные настройки приложения
├── init.php                 # Файл инициализации
├── index.php                # Главная страница (каталог продуктов)
│
├── assets/                  # Статические файлы
│   ├── css/
│   ├── js/
│   └── images/
│
├── config/                  # Конфигурационные файлы
│   └── database.php         # Настройки БД и класс для работы с БД
│
├── controllers/             # Контроллеры
│   ├── AdminController.php      # Функционал администратора
│   ├── AuthController.php       # Авторизация и регистрация
│   ├── CustomerController.php   # Функционал покупателя
│   ├── SalesController.php      # Функционал менеджера по продажам
│   ├── WarehouseController.php  # Функционал начальника склада
│   └── logout.php               # Выход из системы
│
├── models/                  # Модели данных
│   ├── Product.php          # Модель продукта
│   ├── Order.php            # Модель заказа
│   └── User.php             # Модель пользователя
│
├── utils/                   # Утилиты
│   └── Logger.php           # Логирование
│
├── views/                   # Представления
│   ├── admin/               # Страницы администратора
│   │   ├── dashboard.php    # Панель управления
│   │   ├── users.php        # Управление пользователями
│   │   ├── products.php     # Управление продуктами
│   │   ├── orders.php       # Управление заказами
│   │   ├── cameras.php      # Камеры наблюдения
│   │   ├── warehouse.php    # Доступ к складу
│   │   └── settings.php     # Настройки
│   │
│   ├── warehouse/           # Страницы начальника склада
│   │   ├── dashboard.php    # Панель управления
│   │   ├── inventory.php    # Инвентаризация
│   │   ├── receive.php      # Прием товаров
│   │   ├── issue.php        # Выдача товаров
│   │   └── transactions.php # История транзакций
│   │
│   ├── sales/               # Страницы менеджера по продажам
│   │   ├── dashboard.php    # Панель управления
│   │   ├── orders.php       # Управление заказами
│   │   ├── customers.php    # Управление клиентами
│   │   └── messages.php     # Сообщения
│   │
│   ├── customer/            # Страницы покупателя
│   │   ├── dashboard.php    # Личный кабинет
│   │   ├── orders.php       # История заказов
│   │   ├── cart.php         # Корзина
│   │   └── profile.php      # Профиль
│   │
│   ├── auth/                # Страницы авторизации
│   │   ├── login.php        # Вход
│   │   └── register.php     # Регистрация
│   │
│   └── common/              # Общие страницы
│       ├── catalog.php      # Каталог продуктов
│       ├── product.php      # Страница продукта
│       └── messages.php     # Система сообщений
│
└── db.sql                   # SQL для создания БД