<?php
require_once 'config.php';

// 1. KIỂM TRA QUYỀN TRUY CẬP
$current_role = getCurrentUserRole();
if ($current_role !== 'admin' && $current_role !== 'inventory') {
    header('Location: index.php');
    exit;
}

// Khởi tạo các biến để nhận thông báo thành công và lỗi (từ product_manage.php)
$search_term = trim($_GET['search'] ?? '');
$success_message = $_GET['success'] ?? ''; 
$error_message = $_GET['error'] ?? '';     

// Xây dựng truy vấn SQL cơ bản
$sql = "SELECT id, product_code, name, price, stock_quantity FROM products";
$params = [];

// Xử lý Tìm kiếm
if (!empty($search_term)) {
    $sql .= " WHERE name LIKE ? OR product_code LIKE ?";
    $params[] = "%" . $search_term . "%";
    $params[] = "%" . $search_term . "%";
}

$sql .= " ORDER BY id DESC"; // Sắp xếp theo ID mới nhất

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách Hàng hoá - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css"> 
    <style>
        .main-content { margin-left: 250px; padding: 40px 60px; }
        .table-clean th { font-weight: 600; color: var(--app-gray); border-bottom: 1px solid #eee; text-transform: uppercase; font-size: 12px; padding-bottom: 15px; }
        .table-clean td { padding: 15px 0; font-weight: 500; border-bottom: 1px solid #eee; }
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
        <a href="inventory.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Quay lại Kho</a>
        <span class="me-3 fw-bold">Xin chào, <?php echo getCurrentUser(); ?></span>
    </div>

    <h1 class="fw-bold mb-4 text-uppercase"><i class="fas fa-list-alt me-2"></i> Danh Sách Hàng Hoá (<?php echo count($products); ?>)</h1>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between mb-4">
        <a href="product_manage.php" class="btn btn-success fw-bold">
            <i class="fas fa-plus me-2"></i> Thêm Hàng Hoá Mới
        </a>
        <form action="" method="GET" class="d-flex w-50">
            <input type="text" name="search" class="form-control me-2" placeholder="Tìm kiếm theo Mã hoặc Tên..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search"></i></button>
            <?php if (!empty($search_term)): ?>
                <a href="product_list.php" class="btn btn-outline-danger ms-2"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card p-4 shadow-sm border-0">
        <?php if (count($products) > 0): ?>
        <table class="table table-clean w-100">
            <thead>
                <tr>
                    <th>Mã hàng</th>
                    <th>Tên hàng hoá</th>
                    <th>Giá bán</th>
                    <th>Tồn kho</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td class="fw-bold"><?php echo htmlspecialchars($p['product_code']); ?></td>
                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                    <td><?php echo number_format($p['price'], 0, ',', '.') . ' ' . CURRENCY; ?></td>
                    <td><span class="badge bg-secondary"><?php echo number_format($p['stock_quantity']); ?></span></td>
                    <td>
                        <a href="product_manage.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary me-2"><i class="fas fa-edit"></i> Sửa</a>
                        <a href="inventory_update.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning me-2"><i class="fas fa-warehouse"></i> Tồn kho</a>
                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $p['id']; ?>)">
                            <i class="fas fa-trash-alt"></i> Xóa
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="alert alert-warning text-center">Không tìm thấy sản phẩm nào.</div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm("Bạn có chắc chắn muốn xóa sản phẩm này không? Hành động này không thể hoàn tác.")) {
        // Chuyển hướng về product_manage.php để xử lý logic xóa
        window.location.href = 'product_manage.php?delete_id=' + id;
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>