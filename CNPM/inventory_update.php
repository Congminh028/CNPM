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
$current_id = $_GET['id'] ?? null; 
$product = null;

// ======================================================
// 2. LẤY DỮ LIỆU SẢN PHẨM CẦN KIỂM KÊ
// ======================================================
if (!$current_id) {
    die("Không có ID sản phẩm được cung cấp.");
}

$stmt = $pdo->prepare("SELECT id, product_code, name, stock_quantity FROM products WHERE id = ?");
$stmt->execute([$current_id]);
$product = $stmt->fetch();

if (!$product) {
    die("Không tìm thấy sản phẩm này.");
}

// Gán tồn kho hiện tại làm giá trị mặc định cho form
$old_stock = $product['stock_quantity'];
$new_stock_value = $old_stock;


// ======================================================
// 3. XỬ LÝ FORM SUBMIT (Cập nhật Tồn kho)
// ======================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $current_id = $_POST['product_id'] ?? null;
    $new_stock_value = intval($_POST['new_stock_quantity'] ?? 0);
    
    // Validation
    if ($new_stock_value < 0) {
        $error[] = "Số lượng tồn kho không được âm.";
    }

    if (empty($error)) {
        // 🔴 CHỈ UPDATE CỘT stock_quantity
        $sql = "UPDATE products SET stock_quantity=? WHERE id=?";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$new_stock_value, $current_id]);
            
            // Cập nhật thành công, CHUYỂN HƯỚNG về trang danh sách
            $success_msg = "Kiểm kê thành công! Tồn kho mới của " . $product['name'] . " là " . number_format($new_stock_value);
            header('Location: product_list.php?success=' . urlencode($success_msg));
            exit;

        } catch (PDOException $e) {
            $error[] = "Lỗi CSDL khi cập nhật tồn kho: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật Tồn kho - <?php echo APP_NAME; ?></title>
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
        <i class="fas fa-warehouse me-2"></i> CẬP NHẬT TỒN KHO SẢN PHẨM
    </h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php foreach ($error as $err) echo "<p class='mb-0'>$err</p>"; ?>
        </div>
    <?php endif; ?>

    <div class="card p-4 shadow-sm border-0 mb-4 bg-light">
        <p class="mb-1 fw-bold">Mã Hàng: <span class="badge bg-secondary"><?php echo htmlspecialchars($product['product_code']); ?></span></p>
        <p class="mb-1 fw-bold">Tên Hàng Hoá: <span class="text-primary"><?php echo htmlspecialchars($product['name']); ?></span></p>
        <p class="mb-0 fw-bold">Tồn Kho Hiện Tại: <span class="fs-4 text-danger"><?php echo number_format($old_stock); ?></span></p>
    </div>

    <div class="card p-4 shadow-sm border-0">
        <form action="" method="POST">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id'] ?? ''); ?>">

            <div class="mb-3">
                <label for="new_stock_quantity" class="form-label fw-bold fs-5">Số Lượng Tồn Kho Đã Kiểm Kê (New Count) <span class="text-danger">*</span></label>
                <input type="number" class="form-control form-control-lg text-end" id="new_stock_quantity" name="new_stock_quantity" 
                       value="<?php echo htmlspecialchars($new_stock_value); ?>" min="0" required>
            </div>

            <hr>

            <button type="submit" class="btn btn-success fw-bold btn-lg">
                <i class="fas fa-check-double me-2"></i> CẬP NHẬT TỒN KHO
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>