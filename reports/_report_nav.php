<?php
$current_report = basename($_SERVER['SCRIPT_NAME']);
?>
<div class="d-flex gap-1 flex-wrap">
    <a href="sales.php" class="btn btn-sm <?= $current_report === 'sales.php' ? 'btn-primary' : 'btn-soft' ?>">Sales</a>
    <a href="purchase.php" class="btn btn-sm <?= $current_report === 'purchase.php' ? 'btn-primary' : 'btn-soft' ?>">Purchase</a>
    <a href="service.php" class="btn btn-sm <?= $current_report === 'service.php' ? 'btn-primary' : 'btn-soft' ?>">Service</a>
    <a href="expense.php" class="btn btn-sm <?= $current_report === 'expense.php' ? 'btn-primary' : 'btn-soft' ?>">Expense</a>
    <a href="profit.php" class="btn btn-sm <?= $current_report === 'profit.php' ? 'btn-primary' : 'btn-soft' ?>">Profit</a>
    <a href="cash.php" class="btn btn-sm <?= $current_report === 'cash.php' ? 'btn-primary' : 'btn-soft' ?>">Cash</a>
    <a href="stock.php" class="btn btn-sm <?= $current_report === 'stock.php' ? 'btn-primary' : 'btn-soft' ?>">Stock</a>
    <a href="customer-due.php" class="btn btn-sm <?= $current_report === 'customer-due.php' ? 'btn-primary' : 'btn-soft' ?>">Customer Due</a>
    <a href="supplier-due.php" class="btn btn-sm <?= $current_report === 'supplier-due.php' ? 'btn-primary' : 'btn-soft' ?>">Supplier Due</a>
</div>