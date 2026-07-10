<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'service', 'add');

$page_title = 'New Service Job';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = clean($_POST['customer_name']);
    $customer_phone = clean($_POST['customer_phone']);
    $brand = clean($_POST['brand']);
    $model = clean($_POST['model']);
    $imei = clean($_POST['imei']);
    $problem_description = clean($_POST['problem_description']);
    $labour_charge = (float)$_POST['labour_charge'];
    $discount = (float)$_POST['discount'];
    $paid_amount = (float)$_POST['paid_amount'];

    $part_ids = $_POST['part_product_id'] ?? [];
    $part_qtys = $_POST['part_quantity'] ?? [];
    $part_prices = $_POST['part_price'] ?? [];

    if ($customer_name === '' || $customer_phone === '' || $problem_description === '') {
        setFlash('error', 'Customer name, phone, and problem description are required.');
        redirect(BASE_URL . 'service/add.php');
    }

    $parts = [];
    $parts_total = 0;

    foreach ($part_ids as $i => $pid) {
        $pid = (int)$pid;
        $qty = (int)$part_qtys[$i];
        $price = (float)$part_prices[$i];

        if ($pid <= 0 || $qty <= 0) continue;

        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$pid]);
        $product = $stmt->fetch();

        if (!$product) continue;

        if ($qty > $product['quantity']) {
            setFlash('error', 'Insufficient stock for ' . $product['name'] . '. Available: ' . $product['quantity']);
            redirect(BASE_URL . 'service/add.php');
        }

        $line_total = $qty * $price;
        $parts_total += $line_total;

        $parts[] = [
            'product_id' => $pid,
            'quantity' => $qty,
            'price' => $price,
            'total' => $line_total
        ];
    }

    $total_amount = $labour_charge + $parts_total;
    if ($discount > $total_amount) $discount = $total_amount;
    $net_total = $total_amount - $discount;

    if ($paid_amount > $net_total) $paid_amount = $net_total;
    $due_amount = $net_total - $paid_amount;

    $pdo->beginTransaction();
    try {
        $job_no = generateNumber('SRV', $pdo, 'service_jobs', 'job_no');

        $stmt = $pdo->prepare("INSERT INTO service_jobs 
            (job_no, customer_name, customer_phone, brand, model, imei, problem_description, labour_charge, parts_total, discount, total_amount, paid_amount, due_amount, status, created_by) 
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $job_no, $customer_name, $customer_phone, $brand, $model, $imei ?: null,
            $problem_description, $labour_charge, $parts_total, $discount,
            $net_total, $paid_amount, $due_amount, 'pending', $_SESSION['user_id']
        ]);
        $job_id = $pdo->lastInsertId();

        foreach ($parts as $part) {
            $stmt = $pdo->prepare("INSERT INTO service_job_parts (service_job_id, product_id, quantity, price, total) VALUES (?,?,?,?,?)");
            $stmt->execute([$job_id, $part['product_id'], $part['quantity'], $part['price'], $part['total']]);

            updateStock($pdo, $part['product_id'], $part['quantity'], 'decrease');
        }

        if ($paid_amount > 0) {
            addCashTransaction($pdo, 'sale', $paid_amount, 'in', $job_id, 'Service Job ' . $job_no, $_SESSION['user_id']);
        }

        $pdo->commit();
        setFlash('success', 'Service job created successfully.');
        redirect(BASE_URL . 'service/view.php?id=' . $job_id);
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Something went wrong. Please try again.');
        redirect(BASE_URL . 'service/add.php');
    }
}

$products = $pdo->query("SELECT p.id, p.name, p.brand, p.sell_price, p.quantity FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE c.type = 'repair_parts' AND p.status = 'active' AND p.quantity > 0 
                          ORDER BY p.name")->fetchAll();

$customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">New Service Job</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
        </div>

        <form method="POST" id="serviceForm">
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card-panel mb-3">
                        <h6 class="fw-bold mb-3">Customer & Device Info</h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Select Customer</label>
                                <div class="input-group">
                                    <select id="customerSelect" class="form-select" onchange="onCustomerChange(this)">
                                        <option value="">-- Select Existing Customer or Enter Manually Below --</option>
                                        <?php foreach ($customers as $c): ?>
                                        <option value="<?= $c['id'] ?>" data-name="<?= h($c['name']) ?>" data-phone="<?= h($c['phone']) ?>">
                                            <?= h($c['name']) ?> - <?= h($c['phone']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-soft" data-bs-toggle="modal" data-bs-target="#quickAddCustomerModal" title="Add New Customer">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Customer Name *</label>
                                <input type="text" name="customer_name" id="customerNameInput" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Phone *</label>
                                <input type="text" name="customer_phone" id="customerPhoneInput" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Brand</label>
                                <input type="text" name="brand" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Model</label>
                                <input type="text" name="model" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">IMEI (Optional)</label>
                                <input type="text" name="imei" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Problem Description *</label>
                                <textarea name="problem_description" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card-panel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Used Repair Parts</h6>
                            <button type="button" class="btn btn-soft btn-sm" onclick="addPartRow()"><i class="bi bi-plus-lg me-1"></i>Add Part</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom mb-0" id="partsTable">
                                <thead>
                                    <tr>
                                        <th style="width:40%">Part</th>
                                        <th style="width:15%">Stock</th>
                                        <th style="width:15%">Quantity</th>
                                        <th style="width:15%">Price</th>
                                        <th style="width:15%">Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="partsBody"></tbody>
                            </table>
                        </div>
                        <p class="small text-muted mt-2 mb-0">Repair parts are optional. Leave empty if no parts are used.</p>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-panel">
                        <h6 class="fw-bold mb-3">Charges & Payment</h6>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Service Charge</label>
                            <input type="number" step="0.01" name="labour_charge" id="labourCharge" class="form-control" value="0" oninput="calcTotal()">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Parts Total</label>
                            <input type="text" id="partsTotalDisplay" class="form-control" readonly value="0.00">
                        </div>

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

                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Save Service Job</button>
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
const partsData = <?= json_encode($products) ?>;

function partOptions() {
    let opts = '<option value="">Select Part</option>';
    partsData.forEach(p => {
        opts += `<option value="${p.id}" data-price="${p.sell_price}" data-stock="${p.quantity}">${p.name} ${p.brand ? '(' + p.brand + ')' : ''}</option>`;
    });
    return opts;
}

function addPartRow() {
    const tbody = document.getElementById('partsBody');
    const tr = document.createElement('tr');
    tr.className = 'calc-row';
    tr.innerHTML = `
        <td>
            <select name="part_product_id[]" class="form-select form-select-sm part-select" onchange="onPartChange(this)">
                ${partOptions()}
            </select>
        </td>
        <td><input type="text" class="form-control form-control-sm stock-display" readonly value="-"></td>
        <td><input type="number" name="part_quantity[]" class="form-control form-control-sm qty-input" value="1" min="1" oninput="calcPartRow(this)"></td>
        <td><input type="number" step="0.01" name="part_price[]" class="form-control form-control-sm price-input" value="0" oninput="calcPartRow(this)"></td>
        <td><input type="text" class="form-control form-control-sm total-display" readonly value="0.00"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removePartRow(this)"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}

function onPartChange(select) {
    const row = select.closest('tr');
    const selectedOption = select.options[select.selectedIndex];
    const price = selectedOption?.dataset.price || 0;
    const stock = selectedOption?.dataset.stock || 0;
    row.querySelector('.price-input').value = price;
    row.querySelector('.stock-display').value = stock;
    row.querySelector('.qty-input').max = stock;
    calcPartRow(row.querySelector('.price-input'));
}

function calcPartRow(input) {
    const row = input.closest('tr');
    const stock = parseFloat(row.querySelector('.stock-display').value) || 0;
    const qtyInput = row.querySelector('.qty-input');
    let qty = parseFloat(qtyInput.value) || 0;

    if (stock > 0 && qty > stock) {
        qty = stock;
        qtyInput.value = stock;
        showToast('warning', 'Quantity cannot exceed available stock (' + stock + ')');
    }

    calcRowTotal(row);
    calcPartsTotal();
}

function removePartRow(btn) {
    btn.closest('tr').remove();
    calcPartsTotal();
}

function calcPartsTotal() {
    let partsTotal = 0;
    document.querySelectorAll('#partsBody tr').forEach(row => {
        const select = row.querySelector('.part-select');
        if (!select.value) return;
        const qty = parseFloat(row.querySelector('.qty-input')?.value) || 0;
        const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
        partsTotal += qty * price;
    });
    document.getElementById('partsTotalDisplay').value = partsTotal.toFixed(2);
    calcTotal();
}

function calcTotal() {
    const partsTotal = parseFloat(document.getElementById('partsTotalDisplay').value) || 0;
    const labour = parseFloat(document.getElementById('labourCharge').value) || 0;
    const subtotal = partsTotal + labour;
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

function onCustomerChange(select) {
    const selectedOption = select.options[select.selectedIndex];
    if (!selectedOption.value) return;
    document.getElementById('customerNameInput').value = selectedOption.dataset.name || '';
    document.getElementById('customerPhoneInput').value = selectedOption.dataset.phone || '';
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
            opt.dataset.name = data.name;
            opt.dataset.phone = data.phone;
            opt.textContent = data.name + (data.phone ? ' - ' + data.phone : '');
            opt.selected = true;
            select.appendChild(opt);

            document.getElementById('customerNameInput').value = data.name;
            document.getElementById('customerPhoneInput').value = data.phone;

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
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>