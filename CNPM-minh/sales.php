<?php
require_once 'config.php';

// 1. KIỂM TRA ĐĂNG NHẬP VÀ VAI TRÒ
$current_role = $_SESSION['user_role'] ?? '';
$allowed_roles = ['admin', 'sales']; 

if (!isLoggedIn() || !in_array($current_role, $allowed_roles)) {
    header("Location: index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
// Cho phép cả Admin và Sales đều có quyền quản trị cao nhất tại trang này
$is_admin = in_array($current_role, ['admin', 'sales']);
$message = '';
$error = '';

// Hàm format tiền tệ
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' ' . CURRENCY;
}

// Hàm hiển thị badge trạng thái
function getStatusBadge($status) {
    switch ($status) {
        case 'paid':
            return '<span class="badge bg-success rounded-pill">Đã thanh toán</span>';
        case 'pending':
            return '<span class="badge bg-warning rounded-pill">Chờ thanh toán</span>';
        case 'cancelled':
            return '<span class="badge bg-danger rounded-pill">Đã hủy</span>';
        default:
            return '<span class="badge bg-secondary rounded-pill">' . $status . '</span>';
    }
}
// ------------------------------------------------------------------
// 1. ENDPOINT AJAX LẤY CHI TIẾT HÓA ĐƠN
// ------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'get_sale_details' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $transaction_id = intval($_GET['id']);

    if ($transaction_id > 0) {
        try {
           // Lấy chi tiết sản phẩm của hóa đơn
           $stmt_details = $pdo->prepare("
    SELECT 
        td.product_id, td.quantity, td.price_at_sale, p.name as product_name
    FROM transaction_details td
    JOIN products p ON td.product_id = p.id
    WHERE td.transaction_id = ?
");
            $stmt_details->execute([$transaction_id]);
            $details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

            if ($details) {
                echo json_encode(['success' => true, 'details' => $details]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy chi tiết hóa đơn này.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID hóa đơn không hợp lệ.']);
    }
    exit(); // NGĂN CHẶN LOAD PHẦN CÒN LẠI CỦA TRANG
}

// ------------------------------------------------------------------
// 1. TRUY VẤN DANH SÁCH SẢN PHẨM CÒN TỒN KHO
// ------------------------------------------------------------------
$available_products = [];
$product_map = []; // Map để lưu thông tin sản phẩm (dùng cho JS và Server-side check)
$product_map_json = json_encode([]); // Khởi tạo JSON rỗng

try {
    $sql_products = "
        SELECT id, name, price, stock_quantity 
        FROM products 
        WHERE stock_quantity > 0
        ORDER BY name ASC
    ";
    $stmt_products = $pdo->prepare($sql_products);
    $stmt_products->execute();
    
    foreach ($stmt_products->fetchAll() as $product) {
        $product_map[$product['id']] = $product;
        $available_products[] = $product;
    }
    // Chuyển product_map sang JSON để dùng trong JS
    $product_map_json = json_encode($product_map);

} catch (PDOException $e) {
    $error = "LỖI TRUY VẤN SẢN PHẨM: " . $e->getMessage() . ". Vui lòng kiểm tra lại cấu trúc bảng 'products'.";
}


// ------------------------------------------------------------------
// 2. XỬ LÝ FORM THÊM PHIẾU BÁN HÀNG MỚI (ĐA SẢN PHẨM)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_sale_multi') {
    $customer_name = trim($_POST['customer_name'] ?? 'Khách lẻ');
    $cart_json = $_POST['cart_items'] ?? '[]'; // Nhận dữ liệu giỏ hàng JSON
    $cart_items = json_decode($cart_json, true);

    if (empty($cart_items)) {
        $error = "Giỏ hàng trống. Vui lòng thêm sản phẩm vào hóa đơn.";
    } else {
        try {
            $pdo->beginTransaction(); // Bắt đầu Transaction CSDL

            $actual_total = 0;
            $items_to_process = []; // [product_id => quantity]

            // 1. Kiểm tra tồn kho và tính toán tổng tiền chính xác trên Server
            foreach ($cart_items as $item) {
                // Đảm bảo item có đủ id và quantity
                $id = intval($item['id'] ?? 0);
                $quantity = intval($item['quantity'] ?? 0);
                
                $product = $product_map[$id] ?? null; 

                // Tăng cường kiểm tra an toàn để khắc phục triệt để lỗi đỏ
                if (!$product || !isset($product['name'], $product['price'], $product['stock_quantity'])) {
                    $pdo->rollBack();
                    $error = "Sản phẩm (ID: $id) không tồn tại, đã hết hàng, hoặc dữ liệu sản phẩm bị thiếu.";
                    break;
                }
                
                if ($quantity <= 0) {
                    continue;
                }

                if ($product['stock_quantity'] < $quantity) {
                    $pdo->rollBack();
                    $error = "KHÔNG ĐỦ HÀNG: **" . htmlspecialchars($product['name']) . "** chỉ còn: **" . number_format($product['stock_quantity']) . "** (Yêu cầu: $quantity).";
                    break;
                }

                $item_total = $product['price'] * $quantity;
                $actual_total += $item_total;

                $items_to_process[] = [
                    'product_id' => $id,
                    'quantity' => $quantity,
                    'price_at_sale' => $product['price'],
                    'new_stock' => $product['stock_quantity'] - $quantity
                ];
            }

            if (!$error) {
                
                // === BỔ SUNG LOGIC TÍNH THUẾ TRÊN SERVER ===
                $tax_rate_input = intval($_POST['tax_rate'] ?? 0); // Lấy tỷ lệ thuế từ input form
                $tax_rate = $tax_rate_input / 100;
                $tax_amount = $actual_total * $tax_rate;
                $final_total = $actual_total + $tax_amount; // Tổng tiền cuối cùng
                // ===========================================
                
                // 2. Ghi nhận giao dịch (transaction)
                // SỬ DỤNG $final_total THAY CHO $actual_total
                // Sửa: Thêm cột tax_amount vào câu lệnh INSERT
$stmt_trans = $pdo->prepare("INSERT INTO transactions (user_id, customer_name, total_amount, tax_amount, tax_rate, status) VALUES (?, ?, ?, ?, ?, 'paid')");

// Sửa: Thêm biến $tax_amount vào mảng dữ liệu gửi đi
$stmt_trans->execute([$current_user_id, $customer_name, $final_total, $tax_amount, $tax_rate_input]);
                $transaction_id = $pdo->lastInsertId();

                // 3. Ghi chi tiết giao dịch (transaction_details) & Cập nhật tồn kho
                $stmt_detail = $pdo->prepare("INSERT INTO transaction_details (transaction_id, product_id, quantity, price_at_sale) VALUES (?, ?, ?, ?)");
                $stmt_update = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");

                foreach ($items_to_process as $item) {
                    // Ghi chi tiết
                    $stmt_detail->execute([$transaction_id, $item['product_id'], $item['quantity'], $item['price_at_sale']]);
                    // Cập nhật tồn kho
                    $stmt_update->execute([$item['new_stock'], $item['product_id']]);
                }
                
                $pdo->commit(); // Commit Transaction

                $message = "Đã tạo thành công Phiếu bán hàng **#QLBH-" . str_pad($transaction_id, 4, '0', STR_PAD_LEFT) . "** (**" . count($items_to_process) . " sản phẩm**). Tổng tiền: **" . formatCurrency($final_total) . "**."; // <-- Dùng $final_total trong message
                
                // Tránh resubmission
                header("Location: sales.php?message=" . urlencode($message));
                exit();
            }

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Lỗi CSDL khi tạo phiếu hoặc trừ kho: " . $e->getMessage();
        }
    }
}


// ------------------------------------------------------------------
// 3. XỬ LÝ FORM CẬP NHẬT PHIẾU BÁN HÀNG (CÓ LƯU VẾT LOG)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_sale') {
    $transaction_id = intval($_POST['transaction_id'] ?? 0);
    $customer_name = trim($_POST['customer_name_edit'] ?? 'Khách lẻ');
    $status = trim($_POST['status_edit'] ?? 'paid');
    
    // Hàm hỗ trợ ghi log nhanh
    function writeAuditLog($pdo, $userId, $recordId, $field, $oldVal, $newVal) {
        // Chỉ ghi nếu giá trị thay đổi
        if ((string)$oldVal !== (string)$newVal) {
            $sql = "INSERT INTO audit_logs (user_id, table_name, record_id, action, field_name, old_value, new_value, timestamp) 
                    VALUES (?, 'transactions', ?, 'Update', ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $recordId, $field, (string)$oldVal, (string)$newVal]);
        }
    }

    // Nếu không phải Admin, chỉ cập nhật thông tin cơ bản
    if (!$is_admin) {
        try {
            // Lấy dữ liệu cũ để log
            $stmtOld = $pdo->prepare("SELECT customer_name, status FROM transactions WHERE id = ?");
            $stmtOld->execute([$transaction_id]);
            $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("UPDATE transactions SET customer_name = ?, status = ? WHERE id = ?");
            $stmt->execute([$customer_name, $status, $transaction_id]);
            
            // Ghi log cơ bản
            if ($oldData) {
                writeAuditLog($pdo, $current_user_id, $transaction_id, 'Khách hàng', $oldData['customer_name'], $customer_name);
                writeAuditLog($pdo, $current_user_id, $transaction_id, 'Trạng thái', $oldData['status'], $status);
            }

            $message = "Đã cập nhật thành công thông tin cơ bản...";
            header("Location: sales.php?message=" . urlencode($message));
            exit();
        } catch (PDOException $e) {
            $error = "Lỗi CSDL khi cập nhật phiếu: " . $e->getMessage();
        }
    } else {
        // --- XỬ LÝ CHO ADMIN (Full quyền) ---
        $updated_cart_json = $_POST['updated_cart_json'] ?? '[]'; 
        $updated_items = json_decode($updated_cart_json, true);

        if ($transaction_id <= 0) {
            $error = "Lỗi: Không tìm thấy ID hóa đơn.";
        } else {
            try {
                $pdo->beginTransaction();

                // 1. LẤY DỮ LIỆU CŨ ĐỂ SO SÁNH (Log & Hoàn kho)
                $stmt_old_trans = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
                $stmt_old_trans->execute([$transaction_id]);
                $old_trans_data = $stmt_old_trans->fetch(PDO::FETCH_ASSOC);

                // Lấy chi tiết sản phẩm cũ (kèm tên để log cho đẹp)
                $stmt_old_details = $pdo->prepare("
                    SELECT td.product_id, td.quantity, p.name 
                    FROM transaction_details td 
                    JOIN products p ON td.product_id = p.id 
                    WHERE td.transaction_id = ?
                ");
                $stmt_old_details->execute([$transaction_id]);
                $old_items = $stmt_old_details->fetchAll(PDO::FETCH_ASSOC);

                // Tạo chuỗi mô tả danh sách cũ: "SP A (x2), SP B (x1)"
                $old_items_str_arr = [];
                foreach ($old_items as $oi) {
                    $old_items_str_arr[] = $oi['name'] . " (x" . $oi['quantity'] . ")";
                }
                $old_items_log_string = implode(', ', $old_items_str_arr);


                // 2. HOÀN KHO các sản phẩm cũ
                $stmt_revert = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                foreach ($old_items as $item) {
                    $stmt_revert->execute([$item['quantity'], $item['product_id']]);
                }
                
                // 3. Xóa chi tiết giao dịch cũ
                $stmt_delete = $pdo->prepare("DELETE FROM transaction_details WHERE transaction_id = ?");
                $stmt_delete->execute([$transaction_id]);

                // 4. TÍNH TOÁN MỚI & TRỪ KHO MỚI
                $new_total = 0;
                $items_to_insert = [];
                $new_items_str_arr = []; // Mảng để tạo chuỗi log mới

                foreach ($updated_items as $item) {
                    $id = intval($item['id'] ?? 0);
                    $quantity = intval($item['quantity'] ?? 0);
                    
                    // Lấy thông tin mới nhất từ DB để đảm bảo chính xác
                    $stmt_prod = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ?");
                    $stmt_prod->execute([$id]);
                    $product = $stmt_prod->fetch(PDO::FETCH_ASSOC);

                    if (!$product || $quantity <= 0) continue; 
                    
                    if ($product['stock_quantity'] < $quantity) {
                        $pdo->rollBack();
                        $error = "KHÔNG ĐỦ HÀNG: **" . htmlspecialchars($product['name']) . "** chỉ còn: **" . number_format($product['stock_quantity']) . "** (Yêu cầu: $quantity).";
                        header("Location: sales.php?error=" . urlencode($error)); 
                        exit();
                    }

                    $item_price = $product['price'];
                    $new_total += $item_price * $quantity;

                    $items_to_insert[] = [
                        'product_id' => $id,
                        'quantity' => $quantity,
                        'price_at_sale' => $item_price,
                    ];
                    
                    // Thêm vào chuỗi log
                    $new_items_str_arr[] = $product['name'] . " (x" . $quantity . ")";

                    // Trừ kho
                    $stmt_update_stock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                    $stmt_update_stock->execute([$quantity, $id]);
                }

                $new_items_log_string = implode(', ', $new_items_str_arr);

                // 5. TÍNH THUẾ MỚI
                $tax_rate_input = intval($_POST['tax_rate_edit'] ?? 0);
                $tax_rate = $tax_rate_input / 100;
                $tax_amount = $new_total * $tax_rate;
                $final_new_total = $new_total + $tax_amount; 

                // 6. CHÈN CHI TIẾT MỚI
                $stmt_detail = $pdo->prepare("INSERT INTO transaction_details (transaction_id, product_id, quantity, price_at_sale) VALUES (?, ?, ?, ?)");
                foreach ($items_to_insert as $item) {
                    $stmt_detail->execute([$transaction_id, $item['product_id'], $item['quantity'], $item['price_at_sale']]);
                }

                // 7. CẬP NHẬT TRANSACTION CHÍNH
                $stmt = $pdo->prepare("
                    UPDATE transactions 
                    SET customer_name = ?, total_amount = ?, tax_amount = ?, tax_rate = ?, status = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$customer_name, $final_new_total, $tax_amount, $tax_rate_input, $status, $transaction_id]);
                
                // ==========================================================
                // 8. GHI LOG (AUDIT TRAIL)
                // ==========================================================
                // Log tên khách hàng
                writeAuditLog($pdo, $current_user_id, $transaction_id, 'Khách hàng', $old_trans_data['customer_name'], $customer_name);
                
                // Log trạng thái
                writeAuditLog($pdo, $current_user_id, $transaction_id, 'Trạng thái', $old_trans_data['status'], $status);
                
                // Log tổng tiền
                writeAuditLog($pdo, $current_user_id, $transaction_id, 'Tổng tiền', number_format($old_trans_data['total_amount']), number_format($final_new_total));
                
                // Log Thuế
                writeAuditLog($pdo, $current_user_id, $transaction_id, 'Thuế (%)', $old_trans_data['tax_rate'], $tax_rate_input);

                // Log danh sách sản phẩm (Quan trọng nhất)
                // Chỉ log nếu danh sách sản phẩm thay đổi
                if ($old_items_log_string !== $new_items_log_string) {
                    writeAuditLog($pdo, $current_user_id, $transaction_id, 'DS Sản phẩm', $old_items_log_string, $new_items_log_string);
                }
                // ==========================================================

                $pdo->commit(); 

                $message = "Đã cập nhật thành công Phiếu bán hàng **#QLBH-" . str_pad($transaction_id, 4, '0', STR_PAD_LEFT) . "**. Log lịch sử đã được ghi.";
                header("Location: sales.php?message=" . urlencode($message));
                exit(); 

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Lỗi CSDL khi cập nhật phiếu: " . $e->getMessage();
            }
        }
    }
}


// ------------------------------------------------------------------
// 4. TRUY VẤN DANH SÁCH PHIẾU BÁN HÀNG (CÓ CHỨC NĂNG TÌM KIẾM)
// ------------------------------------------------------------------
$transactions = [];
$limit = 20; 
$where_clauses = [];
$search_params = [];

$search_query = trim($_GET['search_query'] ?? '');
// Thiết lập $search_type mặc định là 'name' để tinh gọn logic
$search_type = $_GET['search_type'] ?? 'name';

if (!empty($search_query)) {
    if ($search_type === 'id' && is_numeric($search_query)) {
        $where_clauses[] = "t.id = :id";
        $search_params[':id'] = intval($search_query);
        $limit = 1; 
    } else {
        // Mặc định tìm theo tên khách hàng
        $where_clauses[] = "t.customer_name LIKE :name";
        $search_params[':name'] = '%' . $search_query . '%';
        $limit = 50; 
    }
}

try {
   $sql = "
    SELECT 
        t.id, t.customer_name, t.total_amount, t.tax_amount, t.tax_rate, t.status, t.created_at, 
        u.fullname as sales_person 
    FROM transactions t
    JOIN users u ON t.user_id = u.id
";
    
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(' AND ', $where_clauses);
    }
    
    $sql .= " ORDER BY t.created_at DESC ";
    $sql .= " LIMIT :limit"; 
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($search_params as $key => $value) {
        $param_type = ($key === ':id') ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $param_type);
    }

    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "LỖI TRUY VẤN CSDL: " . $e->getMessage() . ". Vui lòng kiểm tra lại cấu trúc bảng 'transactions'.";
}
// ------------------------------------------------------------------

// Xử lý message từ redirect
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}
// THÊM: Xử lý error từ redirect
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Bán hàng (POS) - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="style.css">
    
    <style>
        /* CSS cho trang sales (Điều chỉnh nhẹ cho phù hợp) */
        .form-sale .form-group { margin-bottom: 15px; }
        .form-sale .form-control { border-radius: 8px; }
        .transaction-card { background-color: var(--card-bg); border: none; padding: 20px; border-radius: var(--border-radius-lg); }
        .table-clean th { font-weight: 600; color: var(--app-gray); border-bottom: 1px solid #eee; text-transform: uppercase; font-size: 12px; padding-bottom: 15px; }
        .table-clean td { padding: 15px 0; font-weight: 500; border-bottom: 1px solid #eee; }
        .table-clean tbody tr:hover { background-color: #fcfcfc; }
        
        /* Cart Styles */
        #cart-items-list {
            min-height: 50px;
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid #eee;
            border-radius: 5px;
        }
        .cart-item {
            padding: 10px;
            border-bottom: 1px solid #f9f9f9;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item-name {
            font-weight: 600;
        }
        
        /* Sidebar Active State */
        .sidebar .nav-link:nth-child(3) { 
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
        <a class="nav-link active" href="sales.php"><i class="fas fa-cash-register"></i> POS / Bán hàng</a> 
        <a class="nav-link" href="inventory.php"><i class="fas fa-box"></i> Quản lý Kho</a> 
        <a class="nav-link" href="account.php"><i class="fas fa-user-circle"></i> Tài khoản</a>
        <a class="nav-link" href="#"><i class="fas fa-cog"></i> Cài đặt</a>
    </nav>
</div>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="fw-bold"><i class="fas fa-cash-register me-2 text-success"></i> Quản lý Bán hàng & Phiếu Thu</h1>
        <div class="ms-auto d-flex align-items-center">
            <span class="me-3 fw-bold">Xin chào, <?php echo getCurrentUser(); ?></span>
            <a href="logout.php" class="btn-outline-app">Đăng xuất</a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger mb-4"><i class="fas fa-times-circle me-2"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($message): ?>
        <div class="alert alert-success mb-4"><i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        
        <div class="col-lg-4">
            <div class="transaction-card shadow-sm">
                <h3 class="fw-bold mb-4 text-success"><i class="fas fa-shopping-cart me-1"></i> Tạo Phiếu Bán hàng (Đa sản phẩm)</h3>
                <p class="text-muted small">Thêm nhiều sản phẩm vào giỏ hàng trước khi xác nhận thanh toán.</p>

                <form action="sales.php" method="POST" id="saleForm" class="form-sale">
                    <input type="hidden" name="action" value="add_sale_multi">
                    <input type="hidden" name="cart_items" id="cart_items_input">
                    <input type="hidden" name="total_amount" id="total_amount_input">
                    
                    <div class="form-group">
                        <label class="form-label small fw-bold">Tên Khách hàng</label>
                        <input type="text" name="customer_name" class="form-control" value="Khách lẻ" maxlength="255">
                    </div>
                    <div class="form-group">
    <label class="form-label small fw-bold">Tỷ lệ Thuế (%)</label>
    <input type="number" name="tax_rate" id="tax_rate" class="form-control" value="10" min="0" max="100">
    <div class="form-text">Nhập tỷ lệ thuế áp dụng (ví dụ: 10 cho 10%).</div>
</div>

<hr>
                    <hr>
                    
                    <div class="form-group">
                        <label class="form-label small fw-bold">1. Chọn Sản phẩm</label>
                        <select id="product_id" class="form-control"> 
                            <option value="">-- Chọn sản phẩm có sẵn trong kho --</option>
                            <?php foreach ($available_products as $product): ?>
                                <option 
                                    value="<?php echo htmlspecialchars($product['id']); ?>"
                                >
                                    <?php echo htmlspecialchars($product['name']); ?> 
                                    (Giá: <?php echo number_format($product['price']); ?> | Tồn: <?php echo number_format($product['stock_quantity']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($available_products)): ?>
                            <div class="form-text text-danger">Kho hàng đang trống. Vui lòng nhập hàng trước khi bán!</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group row">
                        <div class="col-6">
                            <label class="form-label small fw-bold">2. Số lượng Bán</label>
                            <input type="number" id="sale_quantity" class="form-control" placeholder="1" required min="1" value="1">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <button type="button" id="add_to_cart_btn" class="btn btn-primary w-100 fw-bold" disabled>
                                <i class="fas fa-plus me-1"></i> Thêm vào giỏ
                            </button>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5 class="fw-bold mb-2">Giỏ hàng của Khách <span class="badge bg-secondary" id="cart-count">0</span></h5>
                    <div id="cart-items-list" class="bg-white p-2 mb-3">
                        <p class="text-center text-muted small m-0" id="empty-cart-text">Giỏ hàng trống.</p>
                        </div>
<div class="form-group mt-3 p-3 bg-light rounded">
    <div id="display_total">
        <h4 class="m-0 fw-bold">TỔNG TIỀN: <span class="text-success">0 <?php echo CURRENCY; ?></span></h4>
    </div>
</div>

                    <button type="submit" id="checkout_btn" class="btn btn-lg btn-success w-100 mt-3 rounded-pill fw-bold" disabled>
                        <i class="fas fa-receipt me-2"></i> Xác nhận Thanh toán & Trừ kho
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="p-3">
                <h2 class="fw-bold mb-4">Danh sách Phiếu Bán hàng Gần đây</h2>
                
                <div class="mb-4 p-3 bg-light rounded shadow-sm">
                    <form action="sales.php" method="GET" class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Từ khóa Tìm kiếm</label>
                            <input type="text" name="search_query" class="form-control" placeholder="Mã đơn (ID) hoặc Tên Khách hàng..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Tìm kiếm theo</label>
                            <select name="search_type" class="form-select">
                                <option value="name" <?php if ($search_type === 'name') echo 'selected'; ?>>Tên Khách hàng</option>
                                <option value="id" <?php if ($search_type === 'id') echo 'selected'; ?>>Mã đơn hàng (ID)</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex">
                            <button type="submit" class="btn btn-primary me-2 fw-bold"><i class="fas fa-search me-1"></i> Tìm Kiếm</button>
                            <?php if (!empty($search_query)): ?>
                                <a href="sales.php" class="btn btn-outline-secondary"><i class="fas fa-undo"></i></a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <table class="table table-clean w-100">
                    <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>NV Bán</th>
                            <th width="150">Tổng tiền</th>
                            <th width="100">Trạng thái</th>
                            <th width="150">Thời gian</th>
                            <th width="50" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <?php if (!empty($search_query)): ?>
                                        Không tìm thấy giao dịch nào phù hợp với từ khóa **"<?php echo htmlspecialchars($search_query); ?>"**.
                                    <?php else: ?>
                                        Chưa có giao dịch nào được ghi nhận.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($t['id']); ?></td>
                                <td>#QLBH-<?php echo str_pad(htmlspecialchars($t['id']), 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($t['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($t['sales_person']); ?></td>
                                <td class="fw-bold text-success"><?php echo formatCurrency($t['total_amount']); ?></td>
                                <td><?php echo getStatusBadge($t['status']); ?></td>
                                <td><?php echo date('H:i:s d/m/Y', strtotime($t['created_at'])); ?></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <?php if ($is_admin): ?>
 <a 
    href="#" 
    class="text-primary me-2 btn-edit-sale" 
    title="Sửa"
    data-bs-toggle="modal"
    data-bs-target="#editSaleModal"
    data-id="<?php echo htmlspecialchars($t['id']); ?>"
    data-customer-name="<?php echo htmlspecialchars($t['customer_name']); ?>"
    data-total-amount="<?php echo htmlspecialchars($t['total_amount']); ?>"
    data-status="<?php echo htmlspecialchars($t['status']); ?>"
    data-tax-rate="<?php echo isset($t['tax_rate']) ? htmlspecialchars($t['tax_rate']) : 0; ?>" 
>
    <i class="fas fa-edit"></i>
</a><?php else: ?>
<a 
    href="#" 
    class="text-muted small btn-edit-sale" 
    title="View"
    data-bs-toggle="modal"
    data-bs-target="#editSaleModal"
    data-id="<?php echo htmlspecialchars($t['id']); ?>"
    data-customer-name="<?php echo htmlspecialchars($t['customer_name']); ?>"
    data-total-amount="<?php echo htmlspecialchars($t['total_amount']); ?>"
    data-status="<?php echo htmlspecialchars($t['status']); ?>"
    data-tax-rate="<?php echo isset($t['tax_rate']) ? htmlspecialchars($t['tax_rate']) : 0; ?>" 
>
    View
</a>
<?php endif; ?>
                                    </div>
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

<div class="modal fade" id="editSaleModal" tabindex="-1" aria-labelledby="editSaleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editSaleModalLabel"><i class="fas fa-edit me-1"></i> Chỉnh sửa Hóa đơn</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="sales.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_sale"> 
                    <input type="hidden" name="transaction_id" id="edit_transaction_id">
                    
                    <div class="mb-3">
                        <label for="transaction_display_id" class="form-label small fw-bold">Mã Hóa đơn</label>
                        <input type="text" id="transaction_display_id" class="form-control" value="" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="customer_name_edit" class="form-label small fw-bold">Tên Khách hàng</label>
                        <input type="text" name="customer_name_edit" id="customer_name_edit" class="form-control" required maxlength="255">
                    </div>
                    
                    <div class="mb-3">
    <label for="total_amount_edit" class="form-label small fw-bold">Tổng tiền (Sau thuế)</label>
    <input type="text" name="total_amount_edit" id="total_amount_edit" class="form-control fw-bold text-success" value="" readonly>
    <div class="form-text">Tổng tiền được tính tự động dựa trên Chi tiết SP và Tỷ lệ Thuế.</div>
</div>



                    <div class="mb-3">
                        <label for="status_edit" class="form-label small fw-bold">Trạng thái</label>
                        <select name="status_edit" id="status_edit" class="form-select">
                            <option value="paid">Đã thanh toán</option>
                            <option value="pending">Chờ thanh toán</option>
                            <option value="cancelled">Đã hủy</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tax_rate_edit" class="form-label small fw-bold">Tỷ lệ Thuế (%)</label>
                        <input type="number" name="tax_rate_edit" id="tax_rate_edit" class="form-control" value="10" min="0" max="100">
                        <div class="form-text">Nhập tỷ lệ thuế áp dụng (ví dụ: 10 cho 10%).</div>
                    </div>
<hr>
                    <hr>
                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-list-alt me-1"></i> Chỉnh sửa Chi tiết Sản phẩm</h6>
                    
                    <div class="row g-2 mb-3 p-2 border rounded bg-light">
                        <div class="col-6">
                            <select id="edit_product_select" class="form-select small"> 
                                <option value="">-- Thêm Sản phẩm (Còn tồn) --</option>
                                <?php foreach ($available_products as $product): ?>
                                    <option 
                                        value="<?php echo htmlspecialchars($product['id']); ?>"
                                        data-price="<?php echo htmlspecialchars($product['price']); ?>"
                                        data-stock="<?php echo htmlspecialchars($product['stock_quantity']); ?>"
                                    >
                                        <?php echo htmlspecialchars($product['name']); ?> 
                                        (Tồn: <?php echo number_format($product['stock_quantity']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-3">
                            <input type="number" id="edit_sale_quantity" class="form-control form-control-sm" placeholder="SL" min="1" value="1">
                        </div>
                        <div class="col-3">
                            <button type="button" id="add_item_to_edit_btn" class="btn btn-sm btn-success w-100 fw-bold" disabled>Thêm</button>
                        </div>
                    </div>

                    <div id="edit_sale_details_list" class="border p-2 rounded" style="max-height: 250px; overflow-y: auto;">
                        <p class="text-center text-muted m-0">Đang tải chi tiết...</p>
                    </div>
                    
                    <input type="hidden" name="updated_cart_json" id="updated_cart_json">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-save me-1"></i> Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // 1. CẤU HÌNH & KHỞI TẠO
    const CURRENCY_SYMBOL = '<?php echo CURRENCY; ?>'; 
    const PRODUCT_MAP = <?php echo $product_map_json; ?>; 
    let cart = {}; // Giỏ hàng tạo mới
    let editCart = {}; // Giỏ hàng chỉnh sửa

    // Hàm format tiền tệ
    function formatVND(amount) {
        return amount.toLocaleString('en-US') + ' ' + CURRENCY_SYMBOL;
    }

    // ============================================================
    // PHẦN 1: LOGIC TẠO HÓA ĐƠN MỚI
    // ============================================================
    
    // Cập nhật hiển thị giỏ hàng (Tạo mới)
    function updateCartDisplay() {
        const cartList = document.getElementById('cart-items-list');
        const totalDisplayDiv = document.getElementById('display_total');
        const cartCount = document.getElementById('cart-count');
        const checkoutBtn = document.getElementById('checkout_btn');
        const cartItemsInput = document.getElementById('cart_items_input');
        const totalAmountInput = document.getElementById('total_amount_input');
        const productIdSelect = document.getElementById('product_id'); 

        const taxRateInput = document.getElementById('tax_rate');
        const taxRate = parseFloat(taxRateInput.value) / 100 || 0; 
        
        let totalBeforeTax = 0;
        let cartArray = [];

        cartList.innerHTML = ''; 

        for (const id in cart) {
            const item = cart[id];
            const itemTotal = item.price * item.quantity;
            totalBeforeTax += itemTotal;
            
            let html = `
                <div class="cart-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="cart-item-name">${item.name}</span>
                        <div class="small text-muted">
                            ${formatVND(item.price)} x ${item.quantity} = ${formatVND(itemTotal)}
                        </div>
                        <div class="small text-warning">Tồn kho còn: ${item.stock - item.quantity}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemFromCart(${item.id})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            cartList.innerHTML += html;
            cartArray.push({ id: item.id, quantity: item.quantity });
        }

        const taxAmount = totalBeforeTax * taxRate;
        const finalTotal = totalBeforeTax + taxAmount;

        if (cartArray.length === 0) {
            productIdSelect.setAttribute('required', 'required'); 
            totalDisplayDiv.innerHTML = `<h4 class="m-0 fw-bold">TỔNG TIỀN: <span class="text-success">0 ${CURRENCY_SYMBOL}</span></h4>`;
            cartList.innerHTML = `<p class="text-center text-muted small m-0" id="empty-cart-text">Giỏ hàng trống.</p>`;
            checkoutBtn.disabled = true;
        } else {
            productIdSelect.removeAttribute('required'); 
            checkoutBtn.disabled = false;
            
            totalDisplayDiv.innerHTML = `
                <span class="small text-muted d-block">Tiền hàng: ${formatVND(totalBeforeTax)}</span>
                <span class="small text-muted d-block">Thuế (${(taxRate*100).toFixed(0)}%): ${formatVND(taxAmount)}</span>
                <h4 class="m-0 fw-bold">TỔNG TIỀN: <span class="text-success">${formatVND(finalTotal)}</span></h4>
            `;
        }

        cartCount.textContent = cartArray.length;
        cartItemsInput.value = JSON.stringify(cartArray);
        totalAmountInput.value = finalTotal.toFixed(0); 
    }

    // Thêm sản phẩm vào giỏ (Tạo mới)
    function addItemToCart() {
        const productId = document.getElementById('product_id').value;
        let quantity = parseInt(document.getElementById('sale_quantity').value);

        if (!productId || quantity <= 0 || isNaN(quantity)) {
            alert("Vui lòng chọn sản phẩm và nhập số lượng hợp lệ.");
            return;
        }

        const product = PRODUCT_MAP[productId];
        if (!product) { alert("Sản phẩm lỗi."); return; }
        
        const currentStock = parseInt(product.stock_quantity);
        let quantity_in_cart = cart[productId] ? cart[productId].quantity : 0;
        let final_quantity = quantity_in_cart + quantity;

        if (final_quantity > currentStock) {
            alert(`LỖI: Quá tồn kho (${currentStock}).`);
            return;
        }

        cart[productId] = {
            id: productId, name: product.name, price: parseFloat(product.price),
            quantity: final_quantity, stock: currentStock 
        };
        
        updateCartDisplay();
        document.getElementById('product_id').value = '';
        document.getElementById('sale_quantity').value = '1';
        document.getElementById('add_to_cart_btn').disabled = true;
    }

    function removeItemFromCart(id) {
        delete cart[id];
        updateCartDisplay();
    }

    // ============================================================
    // PHẦN 2: LOGIC CHỈNH SỬA HÓA ĐƠN (EDIT)
    // ============================================================

    // Hàm cập nhật hiển thị Modal chỉnh sửa (TÍNH TOÁN QUAN TRỌNG)
    function updateEditCartDisplay() {
        const detailsList = document.getElementById('edit_sale_details_list');
        const totalAmountInputDisplay = document.getElementById('total_amount_edit');
        const updatedCartJsonInput = document.getElementById('updated_cart_json');
        const taxRateInput = document.getElementById('tax_rate_edit');
        
        // 1. LẤY THUẾ TỪ Ô INPUT
        let currentTaxRate = 0;
        if (taxRateInput && taxRateInput.value !== '') {
            currentTaxRate = parseFloat(taxRateInput.value) / 100;
        }

        let totalBeforeTax = 0;
        let html = '';
        let cartArray = [];

        detailsList.innerHTML = ''; 

        for (const id in editCart) {
            const item = editCart[id];
            const itemTotal = item.price * item.quantity;
            totalBeforeTax += itemTotal;
            
            html += `
                <div class="cart-item d-flex justify-content-between align-items-center p-2 border-bottom">
                    <div class="fw-bold">${item.name}</div>
                    <div class="d-flex align-items-center">
                        <input type="number" value="${item.quantity}" min="1" class="form-control form-control-sm me-2" style="width: 70px;" onchange="updateItemQuantity(${item.id}, this.value)">
                        <span class="text-success me-3">${formatVND(itemTotal)}</span>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemFromEditCart(${item.id})"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            `;
            
            cartArray.push({ id: item.id, quantity: item.quantity });
        }

        if (cartArray.length === 0) {
            html = `<p class="text-center text-muted m-0">Hóa đơn này không có sản phẩm.</p>`;
        }
        
        // 2. TÍNH TOÁN TỔNG (CỘNG THUẾ)
        const taxAmount = totalBeforeTax * currentTaxRate;
        const finalTotal = totalBeforeTax + taxAmount;

        detailsList.innerHTML = html;
        if (totalAmountInputDisplay) {
            totalAmountInputDisplay.value = finalTotal.toFixed(0); 
        }
        if (updatedCartJsonInput) {
            updatedCartJsonInput.value = JSON.stringify(cartArray);
        }
    }

    // Cập nhật số lượng trong Modal
    window.updateItemQuantity = function(id, newQuantity) {
        newQuantity = parseInt(newQuantity);
        if (newQuantity <= 0 || isNaN(newQuantity)) {
            alert("Số lượng phải lớn hơn 0.");
            updateEditCartDisplay(); return;
        }
        editCart[id].quantity = newQuantity;
        updateEditCartDisplay();
    }

    // Xóa item trong Modal
    window.removeItemFromEditCart = function(id) {
        if (confirm("Xóa sản phẩm này?")) {
            delete editCart[id];
            updateEditCartDisplay();
        }
    }

    // ============================================================
    // PHẦN 3: SỰ KIỆN (EVENT LISTENERS)
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        // Sự kiện cho nút Thêm hàng (Trang chủ)
        const addBtn = document.getElementById('add_to_cart_btn');
        const productSelect = document.getElementById('product_id');
        const taxInputMain = document.getElementById('tax_rate');

        if(addBtn) addBtn.addEventListener('click', addItemToCart);
        if(productSelect) {
            productSelect.addEventListener('change', function() {
                if(addBtn) addBtn.disabled = !this.value;
            });
        }
        if(taxInputMain) taxInputMain.addEventListener('input', updateCartDisplay);
        
        // Khởi chạy mặc định
        updateCartDisplay();

        // ----------------------------------------------------
        // SỰ KIỆN CHO MODAL (QUAN TRỌNG NHẤT)
        // ----------------------------------------------------
        const editSaleModal = document.getElementById('editSaleModal');
        if (editSaleModal) {
            // Khi Modal mở ra -> Nhận dữ liệu từ nút bấm
            editSaleModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; 

                // Lấy dữ liệu
                const id = button.getAttribute('data-id');
                const customerName = button.getAttribute('data-customer-name');
                const status = button.getAttribute('data-status');
                
                // LẤY THUẾ TỪ NÚT BẤM (Nếu rỗng thì mặc định 0)
                let taxRateRaw = button.getAttribute('data-tax-rate');
                let taxRate = (taxRateRaw && taxRateRaw !== '') ? taxRateRaw : 0;
                
                // Debug để xem code chạy chưa (Bấm F12 -> Console để xem)
                console.log("ID:", id, "Tax Rate nhận được:", taxRate);

                // Điền vào Form
                editSaleModal.querySelector('#edit_transaction_id').value = id;
                editSaleModal.querySelector('#transaction_display_id').value = '#QLBH-' + id.padStart(4, '0');
                editSaleModal.querySelector('#customer_name_edit').value = customerName;
                editSaleModal.querySelector('#status_edit').value = status; 
                
                // ĐIỀN THUẾ VÀO Ô INPUT
                const taxEditInput = editSaleModal.querySelector('#tax_rate_edit');
                if (taxEditInput) {
                    taxEditInput.value = taxRate; 
                }

                // Tải chi tiết sản phẩm
                editCart = {};
                const detailsList = editSaleModal.querySelector('#edit_sale_details_list');
                detailsList.innerHTML = '<p class="text-center text-muted m-0">Đang tải chi tiết...</p>';

                fetch(`sales.php?action=get_sale_details&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.details.length > 0) {
                            data.details.forEach(item => {
                                const productId = item.product_id; 
                                if (productId && PRODUCT_MAP[productId]) { 
                                    editCart[productId] = {
                                        id: productId,
                                        name: item.product_name,
                                        price: parseFloat(item.price_at_sale),
                                        quantity: parseInt(item.quantity),
                                        stock: parseInt(PRODUCT_MAP[productId].stock_quantity) 
                                    };
                                }
                            });
                            // TÍNH TOÁN LẠI NGAY LẬP TỨC (Dựa trên số thuế vừa điền)
                            updateEditCartDisplay(); 
                        } else {
                            detailsList.innerHTML = '<p class="text-danger text-center">Không tải được chi tiết.</p>';
                        }
                    });
            });

            // Sự kiện: Khi gõ thay đổi thuế trong Modal -> Tính lại tiền ngay
            const taxRateEditInput = document.getElementById('tax_rate_edit');
            if (taxRateEditInput) {
                taxRateEditInput.addEventListener('input', function() {
                    updateEditCartDisplay();
                });
            }
            
            // Sự kiện: Nút thêm sản phẩm trong Modal
            const addItemEditBtn = document.getElementById('add_item_to_edit_btn');
            const editProductSelect = document.getElementById('edit_product_select');
            
            if(editProductSelect) {
                editProductSelect.addEventListener('change', function() {
                    if(addItemEditBtn) addItemEditBtn.disabled = !this.value;
                });
            }

            if(addItemEditBtn) {
                addItemEditBtn.addEventListener('click', function() {
                    const select = document.getElementById('edit_product_select');
                    const productId = select.value;
                    let quantity = parseInt(document.getElementById('edit_sale_quantity').value);
                    
                    if (!productId || quantity <= 0) return;
                    
                    const product = PRODUCT_MAP[productId];
                    const price = parseFloat(select.options[select.selectedIndex].getAttribute('data-price'));
                    const stock = parseInt(select.options[select.selectedIndex].getAttribute('data-stock'));
                    
                    let final_quantity = editCart[productId] ? editCart[productId].quantity + quantity : quantity;
                    
                    if (final_quantity > stock) {
                        alert(`Kho chỉ còn ${stock}.`); return;
                    }

                    editCart[productId] = { id: productId, name: product.name, price: price, quantity: final_quantity, stock: stock };
                    updateEditCartDisplay();
                    
                    select.value = '';
                    document.getElementById('edit_sale_quantity').value = '1';
                    addItemEditBtn.disabled = true;
                });
            }
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>