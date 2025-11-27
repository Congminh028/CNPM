<?php
require_once 'config.php';

// 1. KIỂM TRA QUYỀN TRUY CẬP
$current_role = getCurrentUserRole();
if ($current_role !== 'admin' && $current_role !== 'inventory') {
    header('Location: index.php');
    exit;
}

$error = [];
$success = '';
$receipt_code = 'PN-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT); // Mã phiếu ngẫu nhiên/tự động
$receipt_date = date('Y-m-d');
$supplier_name = '';
$items = []; // Lưu trữ các sản phẩm đã chọn (Chi tiết phiếu)

// Lấy danh sách sản phẩm (để đổ vào dropdown)
$stmt_products = $pdo->query("SELECT id, product_code, name, price FROM products ORDER BY name ASC");
$products_list = $stmt_products->fetchAll();

// ======================================================
// 3. XỬ LÝ FORM SUBMIT (Tạo Phiếu Nhập Mới)
// ======================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Lấy dữ liệu Header
    $receipt_code = trim($_POST['receipt_code']);
    $receipt_date = trim($_POST['receipt_date']);
    $supplier_name = trim($_POST['supplier_name']);
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $unit_prices = $_POST['unit_price'] ?? [];
    
    // Validation cơ bản
    if (empty($receipt_code) || empty($receipt_date)) {
        $error[] = "Mã Phiếu và Ngày Nhập không được để trống.";
    }
    if (count($product_ids) == 0) {
        $error[] = "Phiếu nhập phải có ít nhất một sản phẩm.";
    }

    $total_amount = 0;
    $receipt_details = [];

    // Xử lý và tính toán Chi tiết Phiếu
    if (empty($error)) {
        foreach ($product_ids as $index => $product_id) {
            $qty = intval($quantities[$index]);
            $u_price_raw = str_replace(['.', ','], '', $unit_prices[$index]); // Loại bỏ dấu phân cách
            $u_price = floatval($u_price_raw); 
            
            if ($qty <= 0 || $u_price <= 0) {
                $error[] = "Sản phẩm " . ($index + 1) . ": Số lượng và Giá nhập phải lớn hơn 0.";
                break;
            }
            
            $sub_total = $qty * $u_price;
            $total_amount += $sub_total;

            $receipt_details[] = [
                'product_id' => $product_id,
                'quantity' => $qty,
                'unit_price' => $u_price,
                'sub_total' => $sub_total,
            ];
        }
    }

    // Nếu không có lỗi, tiến hành lưu vào CSDL
    if (empty($error) && !empty($receipt_details)) {
        try {
            $pdo->beginTransaction();

            // A. INSERT vào bảng RECEIPTS (Header)
            $sql_receipt = "INSERT INTO receipts (receipt_code, receipt_date, supplier_name, total_amount, user_id, status) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_receipt = $pdo->prepare($sql_receipt);
            $stmt_receipt->execute([
                $receipt_code, $receipt_date, $supplier_name, $total_amount, 
                $_SESSION['user_id'] ?? 1, 'Completed'
            ]);
            $receipt_id = $pdo->lastInsertId();

            // B. INSERT vào bảng RECEIPT_DETAILS (Chi tiết) và CẬP NHẬT TỒN KHO
            foreach ($receipt_details as $detail) {
                // 1. INSERT Chi tiết
                $sql_detail = "INSERT INTO receipt_details (receipt_id, product_id, quantity, unit_price, sub_total) VALUES (?, ?, ?, ?, ?)";
                $stmt_detail = $pdo->prepare($sql_detail);
                $stmt_detail->execute([
                    $receipt_id, $detail['product_id'], $detail['quantity'], $detail['unit_price'], $detail['sub_total']
                ]);

                // 2. CẬP NHẬT TỒN KHO (Tăng stock_quantity)
                $sql_stock = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
                $stmt_stock = $pdo->prepare($sql_stock);
                $stmt_stock->execute([$detail['quantity'], $detail['product_id']]);
            }
            
            $pdo->commit();
            $success = "Tạo phiếu nhập **" . $receipt_code . "** thành công! Đã cập nhật tồn kho.";
            
            // 🔴 Chuyển hướng về trang danh sách phiếu nhập
            header('Location: receipt_list.php?success=' . urlencode($success));
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error[] = "Lỗi CSDL khi tạo phiếu nhập: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Phiếu Nhập - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css"> 
    <style>
        .main-content { margin-left: 250px; padding: 40px 60px; }
        .receipt-item-row td { vertical-align: middle; }
    </style>
</head>
<body>
<div class="sidebar d-none d-md-block">
    <div class="brand-logo"><i class="fas fa-box"></i> <?php echo APP_NAME; ?></div>
    <nav class="nav flex-column">
        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
        <a class="nav-link active" href="inventory.php"><i class="fas fa-box"></i> Quản lý kho</a>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <a href="receipt_list.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Quay lại Danh sách Phiếu</a>
        <span class="me-3 fw-bold">Xin chào, <?php echo getCurrentUser(); ?></span>
    </div>

    <h1 class="fw-bold mb-4 text-uppercase"><i class="fas fa-file-invoice me-2"></i> TẠO PHIẾU NHẬP HÀNG MỚI</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php foreach ($error as $err) echo "<p class='mb-0'>$err</p>"; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="card p-4 shadow-sm border-0 mb-4">
            <h5 class="card-title fw-bold text-primary mb-3">Thông tin chung</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="receipt_code" class="form-label fw-bold">Mã Phiếu Nhập</label>
                    <input type="text" class="form-control" id="receipt_code" name="receipt_code" 
                           value="<?php echo htmlspecialchars($_POST['receipt_code'] ?? $receipt_code); ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="receipt_date" class="form-label fw-bold">Ngày Nhập</label>
                    <input type="date" class="form-control" id="receipt_date" name="receipt_date" 
                           value="<?php echo htmlspecialchars($_POST['receipt_date'] ?? $receipt_date); ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="supplier_name" class="form-label fw-bold">Nhà Cung Cấp</label>
                    <input type="text" class="form-control" id="supplier_name" name="supplier_name" 
                           value="<?php echo htmlspecialchars($_POST['supplier_name'] ?? $supplier_name); ?>" placeholder="Tên nhà cung cấp">
                </div>
            </div>
        </div>

        <div class="card p-4 shadow-sm border-0 mb-4">
            <h5 class="card-title fw-bold text-primary mb-3">Chi tiết Sản phẩm</h5>
            <table class="table table-bordered" id="receipt-items-table">
                <thead>
                    <tr class="table-light">
                        <th style="width: 40%;">Sản phẩm</th>
                        <th style="width: 20%;">Số lượng nhập</th>
                        <th style="width: 25%;">Giá nhập/Đơn vị (<?php echo CURRENCY; ?>)</th>
                        <th style="width: 10%;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    </tbody>
            </table>
            <button type="button" class="btn btn-outline-primary w-50 mx-auto" id="add-item-btn">
                <i class="fas fa-plus me-2"></i> Thêm Sản phẩm
            </button>
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-success fw-bold btn-lg">
                <i class="fas fa-save me-2"></i> LƯU & HOÀN TẤT PHIẾU NHẬP
            </button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const productsList = <?php echo json_encode($products_list); ?>;
    const tableBody = document.querySelector('#receipt-items-table tbody');
    const addItemBtn = document.getElementById('add-item-btn');
    let itemCounter = 0;

    function createProductRow() {
        itemCounter++;
        const row = document.createElement('tr');
        row.classList.add('receipt-item-row');
        row.innerHTML = `
            <td>
                <select name="product_id[]" class="form-select product-select" required>
                    <option value="">-- Chọn Sản phẩm --</option>
                    ${productsList.map(p => `<option value="${p.id}">${p.product_code} - ${p.name} (Giá bán: ${formatCurrency(p.price)})</option>`).join('')}
                </select>
            </td>
            <td>
                <input type="number" name="quantity[]" class="form-control text-end item-qty" min="1" value="1" required>
            </td>
            <td>
                <input type="text" name="unit_price[]" class="form-control text-end item-price" required 
                       oninput="formatNumber(this)">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn"><i class="fas fa-trash"></i></button>
            </td>
        `;
        
        // Gắn sự kiện xóa
        row.querySelector('.remove-item-btn').addEventListener('click', () => {
            row.remove();
        });

        tableBody.appendChild(row);
    }

    function formatCurrency(number) {
        if (number === undefined || number === null) return '';
        return parseFloat(number).toLocaleString('vi-VN');
    }

    function formatNumber(input) {
        // Lấy giá trị hiện tại
        let value = input.value;

        // Xóa tất cả dấu phân cách và ký tự không phải số
        let cleanValue = value.replace(/[^0-9]/g, '');

        // Định dạng lại số (thêm dấu chấm phân cách hàng nghìn)
        let formattedValue = Number(cleanValue).toLocaleString('vi-VN');

        // Gán lại giá trị đã định dạng vào input
        input.value = formattedValue;
    }


    addItemBtn.addEventListener('click', createProductRow);
    
    // Tạo sẵn 1 dòng khi load trang
    createProductRow(); 
</script>
</body>
</html>