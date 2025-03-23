-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 24, 2025 at 10:00 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.0.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wine_sales_automation`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','warehouse','sales','customer') NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`, `email`, `phone`, `address`, `city`, `region`, `postal_code`, `created_at`, `status`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Адміністратор', 'admin@winery.com', '+380501234567', 'вул. Виноградна, 1', 'Київ', 'Київська', '01001', '2025-03-23 08:00:00', 'active'),
(2, 'warehouse', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'warehouse', 'Начальник складу', 'warehouse@winery.com', '+380502345678', 'вул. Виноградна, 1', 'Київ', 'Київська', '01001', '2025-03-23 08:00:00', 'active'),
(3, 'sales', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sales', 'Менеджер з продажу', 'sales@winery.com', '+380503456789', 'вул. Виноградна, 1', 'Київ', 'Київська', '01001', '2025-03-23 08:00:00', 'active'),
(4, 'customer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Олександр Петренко', 'customer1@example.com', '+380504567890', 'вул. Шевченка, 10, кв. 5', 'Київ', 'Київська', '01001', '2025-03-23 08:00:00', 'active'),
(5, 'customer2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Ірина Коваленко', 'customer2@example.com', '+380505678901', 'вул. Лесі Українки, 25, кв. 12', 'Львів', 'Львівська', '79000', '2025-03-23 08:00:00', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `name`, `description`, `image`, `created_at`) VALUES
(1, 'Червоні вина', 'Різноманітні сорти червоних вин', 'red_wine.jpg', '2025-03-23 08:00:00'),
(2, 'Білі вина', 'Різноманітні сорти білих вин', 'white_wine.jpg', '2025-03-23 08:00:00'),
(3, 'Рожеві вина', 'Колекція рожевих вин', 'rose_wine.jpg', '2025-03-23 08:00:00'),
(4, 'Ігристі вина', 'Шампанське та ігристі вина', 'sparkling_wine.jpg', '2025-03-23 08:00:00'),
(5, 'Десертні вина', 'Солодкі та напівсолодкі вина', 'dessert_wine.jpg', '2025-03-23 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `min_stock` int(11) NOT NULL DEFAULT 10,
  `year` int(4) DEFAULT NULL,
  `alcohol` decimal(4,2) DEFAULT NULL,
  `volume` int(11) DEFAULT 750,
  `image` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `details`, `price`, `stock_quantity`, `min_stock`, `year`, `alcohol`, `volume`, `image`, `featured`, `created_at`, `status`) VALUES
(1, 1, 'Каберне Преміум', 'Сухе червоне вино з багатим смаком та ароматом чорних ягід', 'Вино виготовлене з винограду сорту Каберне Совіньйон, вирощеного на південних схилах. Має насичений рубіновий колір та тривалий післясмак з нотками ванілі та дуба.', '359.00', 150, 20, 2020, '13.50', 750, 'cabernet_premium.jpg', 1, '2025-03-23 08:00:00', 'active'),
(2, 1, 'Мерло Класік', 'М\'яке червоне вино з оксамитовою текстурою', 'Вино з винограду сорту Мерло має ноти червоних фруктів, вишні та сливи. Ідеально поєднується з м\'ясом та сирами.', '289.00', 180, 15, 2021, '12.50', 750, 'merlot_classic.jpg', 0, '2025-03-23 08:00:00', 'active'),
(3, 2, 'Шардоне Резерв', 'Елегантне біле вино з фруктовими нотками', 'Витримане в дубових бочках вино з винограду сорту Шардоне. Має складний букет з нотами яблука, груші та цитрусових.', '329.00', 120, 15, 2022, '12.00', 750, 'chardonnay_reserve.jpg', 1, '2025-03-23 08:00:00', 'active'),
(4, 2, 'Совіньйон Блан', 'Свіже біле вино з яскравою кислотністю', 'Легке вино з винограду сорту Совіньйон Блан. Має аромат тропічних фруктів та цитрусових. Ідеально як аперитив.', '299.00', 140, 15, 2022, '11.50', 750, 'sauvignon_blanc.jpg', 0, '2025-03-23 08:00:00', 'active'),
(5, 3, 'Рожеве Преміум', 'Освіжаюче рожеве вино з нотками ягід', 'Виготовлене з червоних сортів винограду методом короткої мацерації. Має ніжний рожевий колір та фруктово-ягідний аромат.', '279.00', 100, 10, 2022, '12.00', 750, 'rose_premium.jpg', 1, '2025-03-23 08:00:00', 'active'),
(6, 4, 'Ігристе Брют', 'Сухе ігристе вино, виготовлене за класичною технологією', 'Елегантне ігристе вино з тонким ароматом білих квітів та яблук. Має стійку дрібну перлину та освіжаючий смак.', '399.00', 80, 10, 2021, '11.50', 750, 'sparkling_brut.jpg', 1, '2025-03-23 08:00:00', 'active'),
(7, 5, 'Портвейн Рубі', 'Солодке кріплене вино з багатим смаком', 'Традиційний портвейн з насиченим смаком червоних фруктів та ягід. Ідеально поєднується з десертами або як дижестив.', '459.00', 60, 8, 2019, '19.00', 500, 'port_ruby.jpg', 0, '2025-03-23 08:00:00', 'active'),
(8, 1, 'Піно Нуар', 'Елегантне червоне вино з фруктовими нотами', 'Легке червоне вино з сорту Піно Нуар. Має аромат вишні, полуниці та спецій. Відмінно поєднується з птицею та м\'ясними стравами.', '379.00', 90, 12, 2020, '13.00', 750, 'pinot_noir.jpg', 0, '2025-03-23 08:00:00', 'active'),
(9, 2, 'Рислінг', 'Ароматне біле вино з яскравою кислотністю', 'Вино з винограду сорту Рислінг. Має аромат зелених яблук, цитрусових та мінеральні нотки.', '309.00', 110, 12, 2022, '11.00', 750, 'riesling.jpg', 0, '2025-03-23 08:00:00', 'active'),
(10, 4, 'Ігристе Солодке', 'Солодке ігристе вино з фруктовим смаком', 'Ігристе вино з приємною солодкістю та ароматом стиглих фруктів. Ідеально для святкування або як аперитив.', '349.00', 70, 8, 2022, '10.50', 750, 'sparkling_sweet.jpg', 0, '2025-03-23 08:00:00', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `transaction_type` enum('in','out') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` enum('order','production','adjustment','return') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`id`, `product_id`, `quantity`, `transaction_type`, `reference_id`, `reference_type`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 200, 'in', NULL, 'production', 'Початкова поставка', 2, '2025-03-23 08:00:00'),
(2, 2, 200, 'in', NULL, 'production', 'Початкова поставка', 2, '2025-03-23 08:00:00'),
(3, 3, 150, 'in', NULL, 'production', 'Початкова поставка', 2, '2025-03-23 08:00:00'),
(4, 4, 150, 'in', NULL, 'production', 'Початкова поставка', 2, '2025-03-23 08:00:00'),
(5, 5, 100, 'in', NULL, 'production', 'Початкова поставка', 2, '2025-03-23 08:00:00'),
(6, 6, 100, 'in', NULL, 'production', 'Початкова поставка', 2, '2025-03-23 08:00:00'),
(7, 7, 80, 'in', NULL, 'production', 'Початкова поставка', 2, '2025-03-23 08:00:00'),
(8, 8, 100, 'in', NULL, 'production', 'Початкова поставка', 2, '2025-03-23 08:00:00'),
(9, 9, 120, 'in', NULL, 'production', 'Початкова поставка', 2, '2025-03-23 08:00:00'),
(10, 10, 80, 'in', NULL, 'production', 'Початкова поставка', 2, '2025-03-23 08:00:00'),
(11, 1, 20, 'out', 1, 'order', 'Замовлення #1', 2, '2025-03-23 09:00:00'),
(12, 3, 15, 'out', 1, 'order', 'Замовлення #1', 2, '2025-03-23 09:00:00'),
(13, 6, 10, 'out', 1, 'order', 'Замовлення #1', 2, '2025-03-23 09:00:00'),
(14, 2, 10, 'out', 2, 'order', 'Замовлення #2', 2, '2025-03-23 10:00:00'),
(15, 4, 5, 'out', 2, 'order', 'Замовлення #2', 2, '2025-03-23 10:00:00'),
(16, 5, 5, 'out', 2, 'order', 'Замовлення #2', 2, '2025-03-23 10:00:00'),
(17, 7, 10, 'out', 3, 'order', 'Замовлення #3', 2, '2025-03-23 11:00:00'),
(18, 8, 5, 'out', 3, 'order', 'Замовлення #3', 2, '2025-03-23 11:00:00'),
(19, 9, 5, 'out', 3, 'order', 'Замовлення #3', 2, '2025-03-23 11:00:00'),
(20, 10, 5, 'out', 3, 'order', 'Замовлення #3', 2, '2025-03-23 11:00:00'),
(21, 1, 10, 'out', 4, 'order', 'Замовлення #4', 2, '2025-03-24 08:00:00'),
(22, 3, 5, 'out', 4, 'order', 'Замовлення #4', 2, '2025-03-24 08:00:00'),
(23, 5, 5, 'out', 4, 'order', 'Замовлення #4', 2, '2025-03-24 08:00:00'),
(24, 2, 10, 'out', 5, 'order', 'Замовлення #5', 2, '2025-03-24 09:00:00'),
(25, 4, 5, 'out', 5, 'order', 'Замовлення #5', 2, '2025-03-24 09:00:00'),
(26, 6, 10, 'out', 5, 'order', 'Замовлення #5', 2, '2025-03-24 09:00:00'),
(27, 10, 5, 'out', 5, 'order', 'Замовлення #5', 2, '2025-03-24 09:00:00'),
(28, 3, 10, 'in', NULL, 'adjustment', 'Корекція інвентаризації', 2, '2025-03-24 10:00:00'),
(29, 5, 10, 'in', NULL, 'adjustment', 'Корекція інвентаризації', 2, '2025-03-24 10:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `sales_manager_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','processing','ready_for_pickup','shipped','delivered','cancelled','completed') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','refunded') NOT NULL DEFAULT 'pending',
  `payment_method` enum('card','bank_transfer','cash_on_delivery') DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `sales_manager_id`, `total_amount`, `status`, `payment_status`, `payment_method`, `shipping_address`, `shipping_cost`, `notes`, `created_at`, `updated_at`) VALUES
(1, 4, 3, 12157.00, 'delivered', 'paid', 'card', 'вул. Шевченка, 10, кв. 5, Київ, 01001', '150.00', 'Доставка в другій половині дня', '2025-03-23 09:00:00', '2025-03-23 15:00:00'),
(2, 5, 3, 4245.00, 'delivered', 'paid', 'bank_transfer', 'вул. Лесі Українки, 25, кв. 12, Львів, 79000', '200.00', 'Подзвонити за годину до доставки', '2025-03-23 10:00:00', '2025-03-23 16:00:00'),
(3, 4, 3, 7855.00, 'shipped', 'paid', 'card', 'вул. Шевченка, 10, кв. 5, Київ, 01001', '150.00', '', '2025-03-23 11:00:00', '2025-03-24 09:00:00'),
(4, 5, 3, 5325.00, 'processing', 'paid', 'card', 'вул. Лесі Українки, 25, кв. 12, Львів, 79000', '200.00', 'Подарункова упаковка', '2025-03-24 08:00:00', '2025-03-24 09:00:00'),
(5, 4, 3, 7845.00, 'pending', 'pending', 'cash_on_delivery', 'вул. Шевченка, 10, кв. 5, Київ, 01001', '150.00', '', '2025-03-24 09:00:00', '2025-03-24 09:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `discount`) VALUES
(1, 1, 1, 20, 359.00, 0.00),
(2, 1, 3, 15, 329.00, 0.00),
(3, 1, 6, 10, 399.00, 0.00),
(4, 2, 2, 10, 289.00, 0.00),
(5, 2, 4, 5, 299.00, 0.00),
(6, 2, 5, 5, 279.00, 0.00),
(7, 3, 7, 10, 459.00, 0.00),
(8, 3, 8, 5, 379.00, 0.00),
(9, 3, 9, 5, 309.00, 0.00),
(10, 3, 10, 5, 349.00, 0.00),
(11, 4, 1, 10, 359.00, 0.00),
(12, 4, 3, 5, 329.00, 0.00),
(13, 4, 5, 5, 279.00, 0.00),
(14, 5, 2, 10, 289.00, 0.00),
(15, 5, 4, 5, 299.00, 0.00),
(16, 5, 6, 10, 399.00, 0.00),
(17, 5, 10, 5, 349.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `cameras`
--

CREATE TABLE `cameras` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `stream_url` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cameras`
--

INSERT INTO `cameras` (`id`, `name`, `location`, `stream_url`, `status`, `created_at`) VALUES
(1, 'Камера 1', 'Склад', 'rtsp://192.168.1.100:554/cam1', 'active', '2025-03-23 08:00:00'),
(2, 'Камера 2', 'Відділ продажів', 'rtsp://192.168.1.101:554/cam2', 'active', '2025-03-23 08:00:00'),
(3, 'Камера 3', 'Зона відвантаження', 'rtsp://192.168.1.102:554/cam3', 'active', '2025-03-23 08:00:00'),
(4, 'Камера 4', 'Головний вхід', 'rtsp://192.168.1.103:554/cam4', 'active', '2025-03-23 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `subject` varchar(100) DEFAULT '',
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `subject`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 3, 'Питання щодо замовлення #1', 'Доброго дня! Цікавить, коли буде доставлено моє замовлення #1? Дякую.', 1, '2025-03-23 10:00:00'),
(2, 3, 4, 'Re: Питання щодо замовлення #1', 'Доброго дня! Ваше замовлення буде доставлено сьогодні до кінця дня. Дякуємо за покупку!', 1, '2025-03-23 10:30:00'),
(3, 5, 3, 'Додаткове питання', 'Чи можна замовити подарункову упаковку для вина?', 1, '2025-03-23 11:00:00'),
(4, 3, 5, 'Re: Додаткове питання', 'Так, звичайно! Ми можемо запропонувати подарункову упаковку. Вартість - 100 грн. Бажаєте додати до замовлення?', 1, '2025-03-23 11:30:00'),
(5, 5, 3, 'Re: Додаткове питання', 'Так, будь ласка, додайте подарункову упаковку до мого наступного замовлення.', 0, '2025-03-23 12:00:00'),
(6, 3, 2, 'Підготовка замовлення #4', 'Прошу підготувати товари для замовлення #4. Клієнт бажає отримати завтра.', 1, '2025-03-23 14:00:00'),
(7, 2, 3, 'Re: Підготовка замовлення #4', 'Зрозуміло, замовлення буде готове сьогодні до кінця дня.', 1, '2025-03-23 14:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `name`, `description`, `discount_percent`, `discount_amount`, `code`, `min_order_amount`, `start_date`, `end_date`, `created_at`, `status`) VALUES
(1, 'Весняний розпродаж', 'Знижка 10% на всі вина', 10.00, NULL, 'SPRING10', 1000.00, '2025-03-20', '2025-04-20', '2025-03-20 08:00:00', 'active'),
(2, 'Знижка при замовленні від 3000 грн', 'Отримайте знижку 500 грн при замовленні від 3000 грн', NULL, 500.00, 'SAVE500', 3000.00, '2025-03-15', '2025-05-15', '2025-03-15 08:00:00', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `product_id`, `customer_id`, `rating`, `review`, `created_at`, `status`) VALUES
(1, 1, 4, 5, 'Чудове вино! Багатий смак, гармонійний букет. Рекомендую!', '2025-03-23 12:00:00', 'approved'),
(2, 3, 4, 4, 'Гарне співвідношення ціни та якості. Приємний аромат.', '2025-03-23 12:30:00', 'approved'),
(3, 6, 5, 5, 'Ідеальне ігристе для святкувань. Всім дуже сподобалось!', '2025-03-23 13:00:00', 'approved'),
(4, 2, 5, 4, 'М\'яке, приємне вино. Добре поєднується з м\'ясними стравами.', '2025-03-23 13:30:00', 'approved');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `sales_manager_id` (`sales_manager_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `cameras`
--
ALTER TABLE `cameras`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `cameras`
--
ALTER TABLE `cameras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`sales_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;