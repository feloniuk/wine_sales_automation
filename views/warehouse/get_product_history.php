<?php
// views/warehouse/get_product_history.php
// Script to get transaction history for a specific product

// Підключаємо конфігурацію
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/WarehouseController.php';

// Перевіряємо авторизацію
$authController = new AuthController();
if (!$authController->isLoggedIn() || !$authController->checkRole('warehouse')) {
    echo "<div class='text-center py-10'>
            <i class='fas fa-exclamation-circle text-red-600 text-4xl'></i>
            <p class='mt-2 text-gray-600'>Доступ заборонено</p>
          </div>";
    exit;
}

// Перевіряємо, чи передано ID товару
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
if ($productId <= 0) {
    echo "<div class='text-center py-10'>
            <i class='fas fa-exclamation-triangle text-yellow-600 text-4xl'></i>
            <p class='mt-2 text-gray-600'>Не вказано ідентифікатор товару</p>
          </div>";
    exit;
}

// Ініціалізуємо контролер складу
$warehouseController = new WarehouseController();

// Отримуємо історію транзакцій для товару
$transactions = $warehouseController->getProductTransactionHistory($productId);
?>

<!-- Виводимо історію транзакцій -->
<div class="overflow-x-auto">
    <table class="min-w-full">
        <thead>
            <tr class="bg-gray-50">
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Тип</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Кількість</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Причина</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Примітки</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Користувач</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($transactions)): ?>
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                    Історія транзакцій для цього товару відсутня
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= date('d.m.Y H:i', strtotime($transaction['created_at'])) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($transaction['transaction_type'] === 'in'): ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            Надходження
                        </span>
                        <?php else: ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                            Списання
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= $transaction['quantity'] ?> шт.
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php
                        switch ($transaction['reference_type']) {
                            case 'production': echo 'Поставка від виробника'; break;
                            case 'adjustment': echo 'Коригування запасів'; break;
                            case 'return': echo 'Повернення'; break;
                            case 'order': 
                                echo 'Замовлення #' . ($transaction['reference_id'] ?? ''); 
                                break;
                            default: echo htmlspecialchars($transaction['reference_type']); break;
                        }
                        ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <?= nl2br(htmlspecialchars($transaction['notes'] ?? '')) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= htmlspecialchars($transaction['user_name'] ?? 'Система') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>