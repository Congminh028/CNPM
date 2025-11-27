<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';

// 1. KIỂM TRA ĐĂNG NHẬP VÀ VAI TRÒ
$current_role = $_SESSION['user_role'] ?? '';
$allowed_roles = ['admin', 'inventory', 'sales'];

if (!isLoggedIn() || !in_array($current_role, $allowed_roles)) {
    header("Location: index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
// Lấy thông báo từ Session (Flash Message)
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';

// Xóa thông báo khỏi Session sau khi đã lấy
unset($_SESSION['message']);
unset($_SESSION['error']);

// ------------------------------------------------------------------
// 2A. XỬ LÝ FORM THÊM SẢN PHẨM MỚI VÀO KHO
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_product') {
    
    $name = trim($_POST['name'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    
    // Kiểm tra dữ liệu hợp lệ
    if (empty($name) || $quantity <= 0 || $price <= 0) {
        $error = "Vui lòng điền Tên Sản phẩm, Số lượng và Đơn giá hợp lệ (> 0).";
    } else {
        try {
            // Kiểm tra xem sản phẩm đã tồn tại chưa
            $stmt_check = $pdo->prepare("SELECT id, stock_quantity FROM products WHERE name = ?");
            $stmt_check->execute([$name]);
            $existing_product = $stmt_check->fetch();

            if ($existing_product) {
                // SẢN PHẨM ĐÃ TỒN TẠI: CẬP NHẬT TỒN KHO
                $new_quantity = $existing_product['stock_quantity'] + $quantity;
                $stmt_update = $pdo->prepare("UPDATE products SET stock_quantity = ?, price = ?, user_id = ? WHERE id = ?");
                $stmt_update->execute([$new_quantity, $price, $current_user_id, $existing_product['id']]);
                
                $message = "Đã cập nhật tồn kho cho sản phẩm **" . htmlspecialchars($name) . "** thành: **" . $new_quantity . "**. Đơn giá: " . number_format($price) . CURRENCY . ".";
            } else {
                // SẢN PHẨM MỚI: THÊM VÀO CSDL
                $stmt_insert = $pdo->prepare("INSERT INTO products (user_id, name, price, stock_quantity) VALUES (?, ?, ?, ?)");
                $stmt_insert->execute([$current_user_id, $name, $price, $quantity]);
                
                $message = "Đã thêm mới sản phẩm **" . htmlspecialchars($name) . "** vào kho với số lượng **" . $quantity . "**. Đơn giá: " . number_format($price) . CURRENCY . ".";
            }
        } catch (PDOException $e) {
            $error = "LỖI CSDL khi thêm/cập nhật sản phẩm: " . $e->getMessage();
        }
    }
}

// ------------------------------------------------------------------
// inventory.php

// ------------------------------------------------------------------
// 2B. XỬ LÝ FORM CHỈNH SỬA SẢN PHẨM TRONG KHO
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_product') {
    
    // ... (Phần lấy dữ liệu từ POST đã có) ...
    $id = intval($_POST['edit_id'] ?? 0);
    $name = trim($_POST['edit_name'] ?? '');
    $quantity = intval($_POST['edit_quantity'] ?? 0);
    $price = floatval($_POST['edit_price'] ?? 0);
    
    if ($id > 0 && !empty($name) && $quantity >= 0 && $price >= 0) {
        try {
            // 1. LẤY DỮ LIỆU CŨ CHO MỤC ĐÍCH LƯU VẾT
            $stmt_old = $pdo->prepare("SELECT name, stock_quantity, price FROM products WHERE id = :id");
            $stmt_old->execute([':id' => $id]);
            $old_data = $stmt_old->fetch();

            if ($old_data) {
                // 2. CẬP NHẬT DỮ LIỆU SẢN PHẨM
                $sql = "UPDATE products SET user_id = :user_id, name = :name, stock_quantity = :quantity, price = :price WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':user_id' => $current_user_id,
                    ':name' => $name,
                    ':quantity' => $quantity,
                    ':price' => $price,
                    ':id' => $id
                ]);

                // 3. GHI LƯU VẾT (AUDIT LOG)
                $new_data = [
                    'name' => $name,
                    'stock_quantity' => $quantity,
                    'price' => $price
                ];
                
                // So sánh và ghi log cho từng trường thay đổi
                foreach ($new_data as $field => $new_value) {
                    $old_value = $old_data[$field];
                    
                    // Xử lý so sánh float cho trường price
                    if ($field === 'price') {
                        $old_value = floatval($old_value);
                    }

                    if ($old_value !== $new_value) {
                        log_audit_trail('products', $id, 'UPDATE', $field, $old_value, $new_value);
                    }
                }
                
                $_SESSION['message'] = "Đã cập nhật sản phẩm **$name** thành công.";
                
            } else {
                $_SESSION['error'] = "Lỗi: Không tìm thấy sản phẩm có ID $id.";
            }

        } catch (PDOException $e) {
            $_SESSION['error'] = "Lỗi CSDL khi cập nhật: " . $e->getMessage();
        }
    } 
    // ... (Phần xử lý lỗi đã có) ...

    header("Location: inventory.php");
    exit();
}
 
// ------------------------------------------------------------------
// 2C. XỬ LÝ FORM SỬA SẢN PHẨM
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_product') {
    
    $product_id = intval($_POST['edit_id'] ?? 0);
    $name = trim($_POST['edit_name'] ?? '');
    $quantity = intval($_POST['edit_quantity'] ?? 0);
    $price = floatval($_POST['edit_price'] ?? 0);
    
    // Kiểm tra dữ liệu hợp lệ
    if ($product_id <= 0 || empty($name) || $quantity < 0 || $price <= 0) {
        $error = "Vui lòng điền đầy đủ và hợp lệ (ID, Tên, Số lượng >= 0, Đơn giá > 0).";
    } else {
        try {
            // Cập nhật tên, số lượng tồn kho và giá bán
            $stmt_update = $pdo->prepare("UPDATE products SET name = ?, stock_quantity = ?, price = ?, user_id = ? WHERE id = ?");
            $stmt_update->execute([$name, $quantity, $price, $current_user_id, $product_id]);
            
            $message = "Đã cập nhật thành công sản phẩm **" . htmlspecialchars($name) . "** (ID: $product_id).";
        } catch (PDOException $e) {
            $error = "LỖI CSDL khi sửa sản phẩm: " . $e->getMessage();
        }
    }
}

// ------------------------------------------------------------------
// 3. TRUY VẤN DANH SÁCH TỒN KHO THỜI GIAN THỰC
// ------------------------------------------------------------------
$products = [];
$low_stock_threshold = 10; // Ngưỡng cảnh báo tồn kho thấp

try {
    // Chỉ lấy các sản phẩm có số lượng tồn kho > 0
    $sql = "
        SELECT 
            p.id, p.name, p.stock_quantity, p.price, p.created_at,
            u.fullname as created_by
        FROM products p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.stock_quantity > 0
        ORDER BY p.name ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "LỖI TRUY VẤN CSDL: " . $e->getMessage() . ". Vui lòng kiểm tra lại cấu trúc bảng.";
}

// Hàm format tiền tệ
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' ' . CURRENCY;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tồn kho - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    
    <style>
        .inventory-header { background-color: #fff3e0; border-radius: 15px; padding: 25px; margin-bottom: 30px; }
        .product-form .form-group { margin-bottom: 15px; }
        .product-form .form-control { border-radius: 8px; }
        .inventory-card { background-color: var(--card-bg); border: none; padding: 20px; border-radius: var(--border-radius-lg); }
        .low-stock { background-color: #fff0f0; color: #dc3545; font-weight: 600; }
        .low-stock .stock-qty { border: 1px dashed #dc3545; padding: 2px 5px; border-radius: 5px; }
        .table-inventory th { font-weight: 600; color: var(--app-gray); border-bottom: 1px solid #eee; text-transform: uppercase; font-size: 12px; padding-bottom: 15px; }
        .table-inventory td { padding: 15px 0; font-weight: 500; border-bottom: 1px solid #eee; }
        
        .sidebar .nav-link:nth-child(4) {
            background-color: var(--app-black);
            color: white;
            border-radius: 10px;
        }
    </style>
</head>
<body>

<div class="sidebar d-none d-md-block">
    <a href="index.php" class="brand-logo">
        <i class="fab fa-autoprefixer"></i> <?php echo APP_NAME; ?>
    </a>
    <nav class="nav flex-column">
        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
        <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-chart-line"></i> Bảng điều khiển</a>
        <a class="nav-link" href="sales.php"><i class="fas fa-cash-register"></i> POS / Bán hàng</a>
        <a class="nav-link active" href="inventory.php"><i class="fas fa-box"></i> Quản lý Kho</a> 
        <a class="nav-link" href="account.php"><i class="fas fa-user-circle"></i> Tài khoản</a>
    </nav>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="fw-bold"><i class="fas fa-box me-2 text-info"></i> Quản lý Tồn kho & Nhập hàng</h1>
        <div class="ms-auto d-flex align-items-center">
            <span class="me-3 fw-bold">Xin chào, <?php echo getCurrentUser(); ?></span>
            <a href="logout.php" class="btn-outline-app">Đăng xuất</a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger mb-4"><i class="fas fa-times-circle me-2"></i> <?php echo $error; ?></div>
    <?php elseif ($message): ?>
        <div class="alert alert-success mb-4"><i class="fas fa-check-circle me-2"></i> <?php echo $message; ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        
        <div class="col-lg-4">
            <div class="inventory-card shadow-sm">
                <h3 class="fw-bold mb-4 text-info"><i class="fas fa-cart-plus me-1"></i> Nhập/Cập nhật Sản phẩm</h3>
                <p class="text-muted small">Nếu sản phẩm đã tồn tại, số lượng sẽ được cộng dồn.</p>

                <form action="inventory.php" method="POST" class="product-form">
                    <input type="hidden" name="action" value="add_product">
                    
                    <div class="form-group">
                        <label class="form-label small fw-bold">Tên Sản phẩm</label>
                        <input type="text" name="name" class="form-control" placeholder="Ví dụ: Coca Cola Lon 330ml" required maxlength="255">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label small fw-bold">Số lượng (Nhập/Thêm vào)</label>
                        <input type="number" name="quantity" class="form-control" placeholder="100" required min="1">
                    </div>

                    <div class="form-group">
                        <label class="form-label small fw-bold">Đơn giá bán (Giá trị mới nhất)</label>
                        <input type="number" name="price" class="form-control" placeholder="15000" required min="1000">
                        <div class="form-text">Giá bán cho mỗi đơn vị sản phẩm.</div>
                    </div>

                    <button type="submit" class="btn btn-lg btn-info w-100 mt-3 rounded-pill fw-bold text-white">
                        <i class="fas fa-truck-loading me-2"></i> Xác nhận Nhập kho
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="p-3">
                <h2 class="fw-bold mb-4">Danh sách Sản phẩm Trong Kho (Tồn kho > 0)</h2>
                
                <table class="table table-inventory w-100">
                   <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th>Tên Sản phẩm</th>
                            <th width="150">Số lượng Tồn</th>
                            <th width="150">Đơn giá</th>
                            <th width="150">Người Nhập cuối</th>
                            <th width="100">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">Kho hàng trống. Vui lòng thêm sản phẩm mới.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $p): ?>
                            <?php 
                                $is_low_stock = $p['stock_quantity'] <= $low_stock_threshold; 
                                $row_class = $is_low_stock ? 'low-stock' : '';
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td><?php echo htmlspecialchars($p['id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($p['name']); ?>
                                    <?php if ($is_low_stock): ?>
                                        <span class="badge bg-danger ms-2">Sắp hết hàng!</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="stock-qty"><?php echo number_format($p['stock_quantity']); ?></span>
                                </td>
                                <td class="fw-bold text-dark"><?php echo formatCurrency($p['price']); ?></td>
                                <td><?php echo htmlspecialchars($p['created_by'] ?? 'Hệ thống'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editProductModal" 
                                            data-id="<?php echo $p['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($p['name']); ?>" 
                                            data-qty="<?php echo $p['stock_quantity']; ?>"
                                            data-price="<?php echo $p['price']; ?>"
                                            title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteProductModal" 
                                            data-id="<?php echo $p['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($p['name']); ?>"
                                            title="Xóa">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="inventory.php" method="POST">
                <input type="hidden" name="action" value="edit_product">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel"><i class="fas fa-edit me-2"></i> Sửa Sản phẩm: <span id="modal_product_name" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Tên Sản phẩm</label>
                        <input type="text" name="edit_name" id="edit_name" class="form-control" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label for="edit_quantity" class="form-label">Số lượng Tồn kho</label>
                        <input type="number" name="edit_quantity" id="edit_quantity" class="form-control" required min="0">
                    </div>
                    <div class="mb-3">
                        <label for="edit_price" class="form-label">Đơn giá bán</label>
                        <input type="number" name="edit_price" id="edit_price" class="form-control" required min="1000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Lưu Thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="inventory.php" method="POST">
                <input type="hidden" name="action" value="delete_product">
                <input type="hidden" name="product_id" id="delete_id">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteProductModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Xác nhận Xóa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn **XÓA HOÀN TOÀN** sản phẩm: <strong id="delete_product_name"></strong>?</p>
                    <p class="text-danger small">Hành động này có thể xóa mất sản phẩm.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt me-2"></i> Xóa Sản phẩm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // XỬ LÝ MODAL SỬA SẢN PHẨM
    var editProductModal = document.getElementById('editProductModal');
    editProductModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; 
        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var qty = button.getAttribute('data-qty');
        var price = button.getAttribute('data-price');
        
        var modalProductName = editProductModal.querySelector('#modal_product_name');
        var modalInputId = editProductModal.querySelector('#edit_id');
        var modalInputName = editProductModal.querySelector('#edit_name');
        var modalInputQty = editProductModal.querySelector('#edit_quantity');
        var modalInputPrice = editProductModal.querySelector('#edit_price');
        
        modalProductName.textContent = name;
        modalInputId.value = id;
        modalInputName.value = name;
        modalInputQty.value = qty;
        modalInputPrice.value = price;
    });

    // XỬ LÝ MODAL XÓA SẢN PHẨM
    var deleteProductModal = document.getElementById('deleteProductModal');
    deleteProductModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; 
        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        
        var modalDeleteName = deleteProductModal.querySelector('#delete_product_name');
        var modalDeleteId = deleteProductModal.querySelector('#delete_id');

        modalDeleteName.textContent = name;
        modalDeleteId.value = id;
    });
</script>
</body>
</html>