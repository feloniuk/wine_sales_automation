<?php
// logout.php
// Скрипт для виходу з системи

// Підключаємо конфігурацію
define('ROOT_PATH', (dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';

// Ініціалізуємо контролер авторизації
$authController = new AuthController();

// Виконуємо вихід
$result = $authController->logout();

// Перенаправляємо на сторінку входу
header('Location: /login.php?logout=1');
exit();