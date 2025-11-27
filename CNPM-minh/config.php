<?php
// DÒNG NÀY PHẢI LÀ DÒNG ĐẦU TIÊN CỦA FILE (trừ <?php)
session_start();

// 1. Cấu hình thông tin Website
define('APP_NAME', 'QUẢN LÝ BÁN HÀNG SIÊU THỊ'); 
define('CURRENCY', '₫'); 

// 2. Cấu hình Kết nối CSDL (PHP/MySQL)
$db_host = "localhost";
$db_user = "root"; // THAY THẾ bằng username MySQL của bạn
$db_pass = ""; // THAY THẾ bằng mật khẩu MySQL của bạn
$db_name = "quan_ly_sieu_thi"; // Tên CSDL đã tạo

// 3. THIẾT LẬP KẾT NỐI PDO TOÀN CỤC
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

// 4. Hàm kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

// 5. Hàm lấy tên người dùng hiện tại
function getCurrentUser() {
    return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Khách';
}
// KHÔNG CÓ THẺ ĐÓNG PHP
// 6. Hàm ghi lại lịch sử chỉnh sửa (Audit Trail)
/**
 * Ghi lại log lịch sử chỉnh sửa vào bảng audit_logs
 *
 * @param string $table_name Tên bảng bị ảnh hưởng (e.g., 'products', 'transactions')
 * @param int $record_id ID của bản ghi bị ảnh hưởng
 * @param string $action Hành động (e.g., 'CREATE', 'UPDATE', 'DELETE')
 * @param string|null $field_name Tên trường bị thay đổi (chỉ dùng cho UPDATE)
 * @param mixed $old_value Giá trị cũ (chỉ dùng cho UPDATE)
 * @param mixed $new_value Giá trị mới (chỉ dùng cho UPDATE)
 * @return bool
 */
function log_audit_trail($table_name, $record_id, $action, $field_name = null, $old_value = null, $new_value = null) {
    global $pdo;
    // Lấy ID người dùng hiện tại từ session (đã định nghĩa trong config.php)
    $user_id = $_SESSION['user_id'] ?? null; 

    if (!$user_id) {
        return false;
    }

    try {
        $sql = "INSERT INTO audit_logs (user_id, table_name, record_id, action, field_name, old_value, new_value)
                VALUES (:user_id, :table_name, :record_id, :action, :field_name, :old_value, :new_value)";
        $stmt = $pdo->prepare($sql);

        // Chuyển giá trị về chuỗi để lưu vào DB (sử dụng json_encode nếu là mảng/object)
        $stmt->execute([
            ':user_id' => $user_id,
            ':table_name' => $table_name,
            ':record_id' => $record_id,
            ':action' => $action,
            ':field_name' => $field_name,
            ':old_value' => is_scalar($old_value) ? (string)$old_value : json_encode($old_value),
            ':new_value' => is_scalar($new_value) ? (string)$new_value : json_encode($new_value),
        ]);
        return true;
    } catch (PDOException $e) {
        // Có thể ghi lỗi vào log hệ thống: error_log("Audit Log Error: " . $e->getMessage());
        return false;
    }
}