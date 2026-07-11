<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'purchase', 'add');

$page_title = 'New Purchase';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = (int)$_POST['supplier_id'];
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $buy_prices = $_POST['buy_price'] ?? [];
    $sell_prices = $_POST['sell_price'] ?? [];
    $paid_amount = (float)$_POST['paid_amount'];

    if ($supplier_id <= 0 || count($product_ids) === 0) {
        setFlash('error', 'Please select a supplier and add at least one product.');
        redirect(BASE_URL . 'purchase/add.php');
    }

    $total_amount = 0;
    $items = [];

    foreach ($product_ids as $i => $pid) {
        $pid = (int)$pid;
        $qty = (int)$quantities[$i];
        $buy_price = (float)$buy_prices[$i];
        $sell_price = (float)$sell_prices[$i];

        if ($pid <= 0 || $qty <= 0) continue;

        $line_total = $qty * $buy_price;
        $total_amount += $line_total;

        $items[] = [
            'product_id' => $pid,
            'quantity' => $qty,
            'buy_price' => $buy_price,
            'sell_price' => $sell_price,
            'total' => $line_total
        ];
    }

    if (count($items) === 0) {
        setFlash('error', 'Please add valid product lines.');
        redirect(BASE_URL . 'purchase/add.php');
    }

    if ($paid_amount > $total_amount) {
        $paid_amount = $total_amount;
    }
    $due_amount = $total_amount - $paid_amount;

    $pdo->beginTransaction();
    try {
        $invoice_no = generateNumber('PUR', $pdo, 'purchases', 'invoice_no');

        $stmt = $pdo->prepare("INSERT INTO purchases (invoice_no, supplier_id, total_amount, paid_amount, due_amount, created_by) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$invoice_no, $supplier_id, $total_amount, $paid_amount, $due_amount, $_SESSION['user_id']]);
        $purchase_id = $pdo->lastInsertId();

        foreach ($items as $item) {
            $stmt = $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, buy_price, total) VALUES (?,?,?,?,?)");
            $stmt->execute([$purchase_id, $item['product_id'], $item['quantity'], $item['buy_price'], $item['total']]);

            updateStock($pdo, $item['product_id'], $item['quantity'], 'increase');

            if ($item['sell_price'] > 0) {
                $stmt = $pdo->prepare("UPDATE products SET buy_price = ?, sell_price = ? WHERE id = ?");
                $stmt->execute([$item['buy_price'], $item['sell_price'], $item['product_id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE products SET buy_price = ? WHERE id = ?");
                $stmt->execute([$item['buy_price'], $item['product_id']]);
            }
        }

        if ($paid_amount > 0) {
            addCashTransaction($pdo, 'purchase', $paid_amount, 'out', $purchase_id, 'Purchase Invoice ' . $invoice_no, $_SESSION['user_id']);
        }

        if ($due_amount > 0) {
            $stmt = $pdo->prepare("UPDATE suppliers SET total_due = total_due + ? WHERE id = ?");
            $stmt->execute([$due_amount, $supplier_id]);
        }

        $pdo->commit();
        setFlash('success', 'Purchase recorded successfully.');
        redirect(BASE_URL . 'purchase/view.php?id=' . $purchase_id);
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Something went wrong. Please try again.');
        redirect(BASE_URL . 'purchase/add.php');
    }
}

$suppliers = $pdo->query("SELECT * FROM suppliers WHERE status = 'active' ORDER BY name")->fetchAll();
$products = $pdo->query("SELECT id, name, brand, buy_price, sell_price, quantity FROM products WHERE status = 'active' ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">New Purchase</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
        </div>

        <form method="POST" id="purchaseForm">
            <div class="card-panel mb-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Supplier *</label>
                        <div class="input-group">
                            <select name="supplier_id" id="supplierSelect" class="form-select" required>
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= h($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-soft" data-bs-toggle="modal" data-bs-target="#quickAddSupplierModal" title="Add New Supplier">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-panel mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h6 class="fw-bold mb-0">Products</h6>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-soft btn-sm" data-bs-toggle="modal" data-bs-target="#quickAddProductModal"><i class="bi bi-plus-lg me-1"></i>New Product</button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="addRow()"><i class="bi bi-plus-lg me-1"></i>Add Row</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom items-table-mobile mb-0" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="width:28%">Product</th>
                                <th style="width:12%">Quantity</th>
                                <th style="width:17%">Buy Price</th>
                                <th style="width:17%">Sell Price</th>
                                <th style="width:16%">Total</th>
                                <th style="width:10%"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="card-panel">
                <div class="row g-3 justify-content-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Grand Total</label>
                        <input type="text" id="grandTotalDisplay" class="form-control fw-bold" readonly value="0.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Paid Amount *</label>
                        <input type="number" step="0.01" name="paid_amount" id="paidAmount" class="form-control" value="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Due Amount</label>
                        <input type="text" id="dueDisplay" class="form-control" readonly value="0.00">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary w-100 w-md-auto"><i class="bi bi-check-lg me-1"></i>Save Purchase</button>
                    <a href="index.php" class="btn btn-soft d-none d-md-inline-block">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Quick Add Supplier Modal -->
<div class="modal fade" id="quickAddSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Add New Supplier</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Supplier Name *</label>
                    <input type="text" id="quickSupplierName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Phone</label>
                    <input type="text" id="quickSupplierPhone" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="quickAddSupplier()">Save Supplier</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add Product Modal -->
<div class="modal fade" id="quickAddProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Add New Product</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Product Name *</label>
                    <input type="text" id="quickProductName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Brand</label>
                    <input type="text" id="quickProductBrand" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Category *</label>
                    <select id="quickProductCategory" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= h($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="small text-muted mb-0">This adds a new row for the product below with editable price and quantity.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="quickAddProduct()">Save Product</button>
            </div>
        </div>
    </div>
</div>

<script>
let productsData = <?= json_encode($products) ?>;

function productOptions() {
    let opts = '<option value="">Select Product</option>';
    productsData.forEach(p => {
        opts += `<option value="${p.id}" data-buyprice="${p.buy_price}" data-sellprice="${p.sell_price}">${p.name} ${p.brand ? '(' + p.brand + ')' : ''} - Stock: ${p.quantity}</option>`;
    });
    return opts;
}

function refreshAllProductDropdowns() {
    document.querySelectorAll('.product-select').forEach(sel => {
        const currentVal = sel.value;
        sel.innerHTML = productOptions();
        if (currentVal) sel.value = currentVal;
    });
}

function addRow() {
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.className = 'calc-row';
    tr.innerHTML = `
        <td data-label="Product">
            <select name="product_id[]" class="form-select product-select" required onchange="onProductChange(this)">
                ${productOptions()}
            </select>
        </td>
        <td data-label="Quantity"><input type="number" name="quantity[]" class="form-control qty-input" value="1" min="1" required oninput="calcRow(this)"></td>
        <td data-label="Buy Price"><input type="number" step="0.01" name="buy_price[]" class="form-control price-input" value="0" required oninput="calcRow(this)"></td>
        <td data-label="Sell Price"><input type="number" step="0.01" name="sell_price[]" class="form-control sell-price-input" value="0" required></td>
        <td data-label="Total" class="cell-total"><input type="text" class="form-control total-display fw-bold" readonly value="0.00"></td>
        <td class="cell-remove"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    return tr;
}

function onProductChange(select) {
    const row = select.closest('tr');
    const selectedOption = select.options[select.selectedIndex];
    const buyPrice = selectedOption?.dataset.buyprice || 0;
    const sellPrice = selectedOption?.dataset.sellprice || 0;
    row.querySelector('.price-input').value = buyPrice;
    row.querySelector('.sell-price-input').value = sellPrice;
    calcRow(row.querySelector('.price-input'));
}

function calcRow(input) {
    const row = input.closest('tr');
    calcRowTotal(row);
    calcGrandTotal();
}

function removeRow(btn) {
    btn.closest('tr').remove();
    calcGrandTotal();
}

function calcGrandTotal() {
    let grand = 0;
    document.querySelectorAll('#itemsBody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-input')?.value) || 0;
        const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
        grand += qty * price;
    });
    document.getElementById('grandTotalDisplay').value = grand.toFixed(2);
    calcDue();
}

function calcDue() {
    const grand = parseFloat(document.getElementById('grandTotalDisplay').value) || 0;
    let paid = parseFloat(document.getElementById('paidAmount').value) || 0;
    if (paid > grand) paid = grand;
    const due = grand - paid;
    document.getElementById('dueDisplay').value = due.toFixed(2);
}

document.getElementById('paidAmount').addEventListener('input', calcDue);

document.getElementById('purchaseForm').addEventListener('submit', function (e) {
    const rows = document.querySelectorAll('#itemsBody tr');
    if (rows.length === 0) {
        e.preventDefault();
        showToast('error', 'Please add at least one product.');
    }
});

function quickAddSupplier() {
    const name = document.getElementById('quickSupplierName').value.trim();
    const phone = document.getElementById('quickSupplierPhone').value.trim();

    if (name === '') { showToast('error', 'Supplier name is required.'); return; }

    const formData = new FormData();
    formData.append('name', name);
    formData.append('phone', phone);

    fetch('<?= BASE_URL ?>suppliers/quick-add.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('supplierSelect');
            const opt = document.createElement('option');
            opt.value = data.id;
            opt.textContent = data.name;
            opt.selected = true;
            select.appendChild(opt);

            document.getElementById('quickSupplierName').value = '';
            document.getElementById('quickSupplierPhone').value = '';

            bootstrap.Modal.getInstance(document.getElementById('quickAddSupplierModal')).hide();
            showToast('success', 'Supplier added successfully.');
        } else {
            showToast('error', data.message || 'Failed to add supplier.');
        }
    })
    .catch(() => showToast('error', 'Something went wrong.'));
}

function quickAddProduct() {
    const name = document.getElementById('quickProductName').value.trim();
    const brand = document.getElementById('quickProductBrand').value.trim();
    const category_id = document.getElementById('quickProductCategory').value;

    if (name === '' || category_id === '') { showToast('error', 'Product name and category are required.'); return; }

    const formData = new FormData();
    formData.append('name', name);
    formData.append('brand', brand);
    formData.append('category_id', category_id);

    fetch('<?= BASE_URL ?>products/quick-add.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            productsData.push({ id: data.id, name: data.name, brand: '', buy_price: 0, sell_price: 0, quantity: 0 });

            const newRow = addRow();
            refreshAllProductDropdowns();
            newRow.querySelector('.product-select').value = data.id;

            document.getElementById('quickProductName').value = '';
            document.getElementById('quickProductBrand').value = '';
            document.getElementById('quickProductCategory').value = '';

            bootstrap.Modal.getInstance(document.getElementById('quickAddProductModal')).hide();
            showToast('success', 'Product added. Now set its buy/sell price in the row below.');
        } else {
            showToast('error', data.message || 'Failed to add product.');
        }
    })
    .catch(() => showToast('error', 'Something went wrong.'));
}

addRow();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>