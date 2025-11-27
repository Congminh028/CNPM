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
$current_id = $_GET['id'] ?? null; // ID sản phẩm cần sửa

// Khởi tạo biến cho form
$product = [
    'id' => null, 
    'product_code' => '', 
    'name' => '', 
    'description' => '', 
    'price' => '', 
    'stock_quantity' => ''
];

// ======================================================
// 2. XỬ LÝ XÓA SẢN PHẨM (DELETE)
// Được gọi từ product_list.php qua JavaScript
// ======================================================
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success_msg = "Xóa sản phẩm thành công!";
        // Chuyển hướng thành công về trang danh sách
        header('Location: product_list.php?success=' . urlencode($success_msg));
        exit;
    } catch (PDOException $e) {
        // Bắt lỗi CSDL, ví dụ lỗi khóa ngoại (Foreign Key Constraint)
        $error_msg = "Không thể xóa sản phẩm. Có thể sản phẩm đang được sử dụng trong các giao dịch. Lỗi CSDL: " . $e->getMessage();
        // Chuyển hướng thất bại về trang danh sách kèm thông báo lỗi
        header('Location: product_list.php?error=' . urlencode($error_msg));
        exit;
    }
}


// ======================================================
// 3. LẤY DỮ LIỆU SẢN PHẨM CẦN SỬA (CHO FORM)
// ======================================================
if ($current_id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$current_id]);
    $existing_product = $stmt->fetch();
    if ($existing_product) {
        $product = $existing_product;
    } else {
        $error[] = "Không tìm thấy sản phẩm này.";
        $current_id = null;
    }
}

// ======================================================
// 4. XỬ LÝ FORM SUBMIT (Thêm hoặc Sửa)
// ======================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Lấy dữ liệu từ POST
    $code = trim($_POST['product_code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval(str_replace('.', '', $_POST['price'] ?? 0)); // Xóa dấu chấm phân cách hàng nghìn
    $stock = intval($_POST['stock_quantity'] ?? 0);
    $current_id = $_POST['product_id'] ?? null; // Lấy ID sản phẩm hiện tại (nếu đang sửa)

    // Validation
    if (empty($code) || empty($name)) {
        $error[] = "Mã sản phẩm và Tên hàng hoá không được để trống.";
    }
    if ($price <= 0) {
        $error[] = "Giá bán phải lớn hơn 0.";
    }
    
    // Kiểm tra trùng mã (chỉ khi thêm mới hoặc sửa mà mã bị thay đổi)
    $sql_check = "SELECT id FROM products WHERE product_code = ?";
    $params_check = [$code];
    
    if ($current_id) {
        // Nếu đang sửa, loại trừ chính sản phẩm đang sửa
        $sql_check .= " AND id != ?";
        $params_check[] = $current_id;
    }

    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute($params_check);

    if ($stmt_check->fetch()) {
        $error[] = "Mã sản phẩm đã tồn tại. Vui lòng chọn mã khác.";
    }


    // Nếu không có lỗi Validation
    if (empty($error)) {
        if ($current_id) {
            // ======================================================
            // UPDATE (Sửa)
            // ======================================================
            $sql = "UPDATE products SET product_code=?, name=?, description=?, price=?, stock_quantity=? WHERE id=?";
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$code, $name, $description, $price, $stock, $current_id]);
                $success = "Cập nhật sản phẩm thành công!";
            } catch (PDOException $e) {
                // Bắt lỗi CSDL khi UPDATE
                $error[] = "Lỗi CSDL khi sửa sản phẩm: " . $e->getMessage();
            }

        } else {
            // ======================================================
            // INSERT (Thêm mới)
            // ======================================================
            $sql = "INSERT INTO products (product_code, name, description, price, stock_quantity) VALUES (?, ?, ?, ?, ?)";
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$code, $name, $description, $price, $stock]);
                $success = "Thêm sản phẩm mới thành công!";
                
                // Reset form sau khi thêm thành công
                $product = [
                    'id' => null, 'product_code' => '', 'name' => '', 
                    'description' => '', 'price' => '', 'stock_quantity' => ''
                ];
            } catch (PDOException $e) {
                // Bắt lỗi CSDL khi INSERT
                $error[] = "Lỗi CSDL khi thêm sản phẩm: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_id ? 'Sửa Hàng hoá' : 'Thêm Hàng hoá'; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css"> 
    <style>
        .main-content { margin-left: 250px; padding: 40px 60px; }
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
        <a href="product_list.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Quay lại Danh sách</a>
        <span class="me-3 fw-bold">Xin chào, <?php echo getCurrentUser(); ?></span>
    </div>

    <h1 class="fw-bold mb-4 text-uppercase">
        <i class="fas fa-<?php echo $current_id ? 'edit' : 'plus'; ?> me-2"></i> 
        <?php echo $current_id ? 'Sửa Hàng hoá' : 'Thêm Hàng hoá Mới'; ?>
    </h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php foreach ($error as $err) echo "<p class='mb-0'>$err</p>"; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card p-4 shadow-sm border-0">
        <form action="" method="POST">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id'] ?? ''); ?>">

            <div class="mb-3">
                <label for="product_code" class="form-label fw-bold">Mã sản phẩm (SKU) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="product_code" name="product_code" 
                       value="<?php echo htmlspecialchars($_POST['product_code'] ?? $product['product_code']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label fw-bold">Tên hàng hoá <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?php echo htmlspecialchars($_POST['name'] ?? $product['name']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label fw-bold">Mô tả</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? $product['description']); ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="price" class="form-label fw-bold">Giá bán (<?php echo CURRENCY; ?>) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-end" id="price" name="price" 
                           value="<?php echo number_format(floatval($_POST['price'] ?? $product['price']), 0, ',', '.'); ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="stock_quantity" class="form-label fw-bold">Số lượng tồn kho ban đầu</label>
                    <input type="number" class="form-control text-end" id="stock_quantity" name="stock_quantity" 
                           value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? $product['stock_quantity']); ?>" 
                           min="0" <?php echo $current_id ? 'readonly' : 'required'; ?>> 
                </div>
            </div>

            <hr>

            <button type="submit" name="action" value="submit" class="btn btn-primary fw-bold">
                <i class="fas fa-save me-2"></i> 
                <?php echo $current_id ? 'LƯU THAY ĐỔI' : 'THÊM SẢN PHẨM'; ?>
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>