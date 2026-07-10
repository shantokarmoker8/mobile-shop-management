<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'sales', 'add');

$page_title = 'Point of Sale';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $sell_prices = $_POST['sell_price'] ?? [];
    $discount = (float)$_POST['discount'];
    $paid_amount = (float)$_POST['paid_amount'];
    $sale_type = clean($_POST['sale_type']);

    if (count($product_ids) === 0) {
        setFlash('error', 'Please add at least one product.');
        redirect(BASE_URL . 'sales/pos.php');
    }

    $items = [];
    $total_amount = 0;
    $total_profit = 0;

    foreach ($product_ids as $i => $pid) {
        $pid = (int)$pid;
        $qty = (int)$quantities[$i];
        $sell_price = (float)$sell_prices[$i];

        if ($pid <= 0 || $qty <= 0) continue;

        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$pid]);
        $product = $stmt->fetch();

        if (!$product) continue;

        if ($qty > $product['quantity']) {
            setFlash('error', 'Insufficient stock for ' . $product['name'] . '. Available: ' . $product['quantity']);
            redirect(BASE_URL . 'sales/pos.php');
        }

        $line_total = $qty * $sell_price;
        $line_profit = ($sell_price - $product['buy_price']) * $qty;

        $total_amount += $line_total;
        $total_profit += $line_profit;

        $items[] = [
            'product_id' => $pid,
            'quantity' => $qty,
            'buy_price' => $product['buy_price'],
            'sell_price' => $sell_price,
            'total' => $line_total,
            'profit' => $line_profit
        ];
    }

    if (count($items) === 0) {
        setFlash('error', 'Please add valid product lines.');
        redirect(BASE_URL . 'sales/pos.php');
    }

    if ($discount > $total_amount) $discount = $total_amount;
    $net_total = $total_amount - $discount;

    if ($paid_amount > $net_total) $paid_amount = $net_total;
    $due_amount = $net_total - $paid_amount;

    if ($due_amount > 0 && !$customer_id) {
        setFlash('error', 'Due sale requires a registered customer. Please select a customer or collect full payment.');
        redirect(BASE_URL . 'sales/pos.php');
    }

    $pdo->beginTransaction();
    try {
        $invoice_no = generateNumber('INV', $pdo, 'sales', 'invoice_no');

        $stmt = $pdo->prepare("INSERT INTO sales (invoice_no, customer_id, sale_type, total_amount, discount, paid_amount, due_amount, total_profit, created_by) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$invoice_no, $customer_id, $sale_type, $total_amount, $discount, $paid_amount, $due_amount, $total_profit, $_SESSION['user_id']]);
        $sale_id = $pdo->lastInsertId();

        foreach ($items as $item) {
            $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, buy_price, sell_price, total, profit) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$sale_id, $item['product_id'], $item['quantity'], $item['buy_price'], $item['sell_price'], $item['total'], $item['profit']]);

            updateStock($pdo, $item['product_id'], $item['quantity'], 'decrease');
        }

        if ($paid_amount > 0) {
            addCashTransaction($pdo, 'sale', $paid_amount, 'in', $sale_id, 'Sale Invoice ' . $invoice_no, $_SESSION['user_id']);
        }

        if ($due_amount > 0 && $customer_id) {
            $stmt = $pdo->prepare("UPDATE customers SET total_due = total_due + ? WHERE id = ?");
            $stmt->execute([$due_amount, $customer_id]);
        }

        $pdo->commit();
        setFlash('success', 'Sale completed successfully.');
        redirect(BASE_URL . 'sales/view.php?id=' . $sale_id);
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Something went wrong. Please try again.');
        redirect(BASE_URL . 'sales/pos.php');
    }
}

$customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll();
$products = $pdo->query("SELECT id, name, brand, sell_price, buy_price, quantity, category_id FROM products WHERE status = 'active' AND quantity > 0 ORDER BY name")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Point of Sale</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-list-ul me-1"></i>Sales List</a>
        </div>

        <form method="POST" id="posForm">
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card-panel mb-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Customer (Optional)</label>
                                <div class="input-group">
                                    <select name="customer_id" id="customerSelect" class="form-select">
                                        <option value="">Walk-in Customer</option>
                                        <?php foreach ($customers as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> - <?= htmlspecialchars($c['phone']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-soft" data-bs-toggle="modal" data-bs-target="#quickAddCustomerModal" title="Add New Customer">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <p class="small text-muted mt-1 mb-0">Required if you want to give Due (partial payment).</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Sale Type</label>
                                <select name="sale_type" class="form-select">
                                    <option value="mobile">Mobile Sale</option>
                                    <option value="accessories">Accessories Sale</option>
                                    <option value="repair_parts">Repair Parts Sale</option>
                                    <option value="mixed" selected>Mixed</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card-panel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Products</h6>
                            <button type="button" class="btn btn-soft btn-sm" onclick="addRow()"><i class="bi bi-plus-lg me-1"></i>Add Product</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom mb-0" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th style="width:35%">Product</th>
                                        <th style="width:15%">Stock</th>
                                        <th style="width:15%">Quantity</th>
                                        <th style="width:15%">Price</th>
                                        <th style="width:15%">Total</th>
                                        <th style="width:5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-panel">
                        <h6 class="fw-bold mb-3">Payment Summary</h6>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Subtotal</label>
                            <input type="text" id="subtotalDisplay" class="form-control fw-bold" readonly value="0.00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Discount</label>
                            <input type="number" step="0.01" name="discount" id="discountInput" class="form-control" value="0" oninput="calcNetTotal()">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Net Total</label>
                            <input type="text" id="netTotalDisplay" class="form-control fw-bold text-primary" readonly value="0.00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Paid Amount *</label>
                            <input type="number" step="0.01" name="paid_amount" id="paidAmount" class="form-control" value="0" required oninput="calcDue()">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Due Amount</label>
                            <input type="text" id="dueDisplay" class="form-control" readonly value="0.00">
                        </div>

                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Complete Sale</button>
                        <p class="small text-muted text-center mt-2 mb-0">You can print the invoice from the next page if needed. Printing is optional.</p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Quick Add Customer Modal -->
<div class="modal fade" id="quickAddCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Add New Customer</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Customer Name *</label>
                    <input type="text" id="quickCustomerName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Phone</label>
                    <input type="text" id="quickCustomerPhone" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="quickAddCustomer()">Save Customer</button>
            </div>
        </div>
    </div>
</div>

<script>
const productsData = <?= json_encode($products) ?>;

function productOptions() {
    let opts = '<option value="">Select Product</option>';
    productsData.forEach(p => {
        opts += `<option value="${p.id}" data-price="${p.sell_price}" data-stock="${p.quantity}">${p.name} ${p.brand ? '(' + p.brand + ')' : ''}</option>`;
    });
    return opts;
}

function addRow() {
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.className = 'calc-row';
    tr.innerHTML = `
        <td>
            <select name="product_id[]" class="form-select form-select-sm product-select" required onchange="onProductChange(this)">
                ${productOptions()}
            </select>
        </td>
        <td><input type="text" class="form-control form-control-sm stock-display" readonly value="-"></td>
        <td><input type="number" name="quantity[]" class="form-control form-control-sm qty-input" value="1" min="1" required oninput="calcRow(this)"></td>
        <td><input type="number" step="0.01" name="sell_price[]" class="form-control form-control-sm price-input" value="0" required oninput="calcRow(this)"></td>
        <td><input type="text" class="form-control form-control-sm total-display" readonly value="0.00"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}

function onProductChange(select) {
    const row = select.closest('tr');
    const selectedOption = select.options[select.selectedIndex];
    const price = selectedOption?.dataset.price || 0;
    const stock = selectedOption?.dataset.stock || 0;
    row.querySelector('.price-input').value = price;
    row.querySelector('.stock-display').value = stock;
    row.querySelector('.qty-input').max = stock;
    calcRow(row.querySelector('.price-input'));
}

function calcRow(input) {
    const row = input.closest('tr');
    const stock = parseFloat(row.querySelector('.stock-display').value) || 0;
    const qtyInput = row.querySelector('.qty-input');
    let qty = parseFloat(qtyInput.value) || 0;

    if (qty > stock) {
        qty = stock;
        qtyInput.value = stock;
        showToast('warning', 'Quantity cannot exceed available stock (' + stock + ')');
    }

    calcRowTotal(row);
    calcSubtotal();
}

function removeRow(btn) {
    btn.closest('tr').remove();
    calcSubtotal();
}

function calcSubtotal() {
    let subtotal = 0;
    document.querySelectorAll('#itemsBody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-input')?.value) || 0;
        const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
        subtotal += qty * price;
    });
    document.getElementById('subtotalDisplay').value = subtotal.toFixed(2);
    calcNetTotal();
}

function calcNetTotal() {
    const subtotal = parseFloat(document.getElementById('subtotalDisplay').value) || 0;
    let discount = parseFloat(document.getElementById('discountInput').value) || 0;
    if (discount > subtotal) {
        discount = subtotal;
        document.getElementById('discountInput').value = discount;
    }
    const net = subtotal - discount;
    document.getElementById('netTotalDisplay').value = net.toFixed(2);
    calcDue();
}

function calcDue() {
    const net = parseFloat(document.getElementById('netTotalDisplay').value) || 0;
    let paid = parseFloat(document.getElementById('paidAmount').value) || 0;
    if (paid > net) {
        paid = net;
        document.getElementById('paidAmount').value = paid;
    }
    const due = net - paid;
    document.getElementById('dueDisplay').value = due.toFixed(2);
}

function quickAddCustomer() {
    const name = document.getElementById('quickCustomerName').value.trim();
    const phone = document.getElementById('quickCustomerPhone').value.trim();

    if (name === '') {
        showToast('error', 'Customer name is required.');
        return;
    }

    const formData = new FormData();
    formData.append('name', name);
    formData.append('phone', phone);

    fetch('<?= BASE_URL ?>customers/quick-add.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('customerSelect');
            const opt = document.createElement('option');
            opt.value = data.id;
            opt.textContent = data.name + (data.phone ? ' - ' + data.phone : '');
            opt.selected = true;
            select.appendChild(opt);

            document.getElementById('quickCustomerName').value = '';
            document.getElementById('quickCustomerPhone').value = '';

            const modal = bootstrap.Modal.getInstance(document.getElementById('quickAddCustomerModal'));
            modal.hide();

            showToast('success', 'Customer added successfully.');
        } else {
            showToast('error', data.message || 'Failed to add customer.');
        }
    })
    .catch(() => showToast('error', 'Something went wrong.'));
}

document.getElementById('posForm').addEventListener('submit', function (e) {
    const rows = document.querySelectorAll('#itemsBody tr');
    if (rows.length === 0) {
        e.preventDefault();
        showToast('error', 'Please add at least one product.');
    }
});

addRow();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>