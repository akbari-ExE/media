<?php
require 'auth.php';
require 'db.php';

// محدود کردن دسترسی به مدیر
if (!isAdmin()) {
    header("Location: index.php");
    exit;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
if (!$user_id) {
    die("خطا: شناسه کاربر ارائه نشده است.");
}

try {
    $stmt = $pdo->prepare("SELECT u.username, u.role, p.can_add, p.can_edit, p.can_delete 
                           FROM users u 
                           LEFT JOIN editor_permissions p ON u.id = p.user_id 
                           WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        die("خطا: کاربر یافت نشد.");
    }
} catch (PDOException $e) {
    die("خطا در دریافت داده: " . $e->getMessage());
}

$error = null;
$success = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        if (empty($username)) {
            throw new Exception("نام کاربری نمی‌تواند خالی باشد.");
        }
        if (!in_array($role, ['admin', 'editor', 'user'])) {
            throw new Exception("نقش نامعتبر است.");
        }
        if (!empty($password) && (strlen($password) < 8 || !preg_match("/[0-9]/", $password) || !preg_match("/[a-zA-Z]/", $password))) {
            throw new Exception("رمز عبور باید حداقل 8 کاراکتر و شامل حروف و اعداد باشد.");
        }
        
        // بررسی عدم وجود نام کاربری
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception("نام کاربری قبلاً ثبت شده است.");
        }
        
        // به‌روزرسانی کاربر
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $hashed_password, $role, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $role, $user_id]);
        }
        
        // به‌روزرسانی دسترسی‌های ادیتور
        if ($role == 'editor') {
            $can_add = isset($_POST['can_add']) ? 1 : 0;
            $can_edit = isset($_POST['can_edit']) ? 1 : 0;
            $can_delete = isset($_POST['can_delete']) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO editor_permissions (user_id, can_add, can_edit, can_delete) 
                                   VALUES (?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE can_add = ?, can_edit = ?, can_delete = ?");
            $stmt->execute([$user_id, $can_add, $can_edit, $can_delete, $can_add, $can_edit, $can_delete]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM editor_permissions WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
        
        $success = "کاربر با موفقیت ویرایش شد.";
    } catch (Exception $e) {
        $error = "خطا: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ویرایش کاربر</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">ویرایش کاربر</h1>
            <div>
                <span class="text-gray-700">خوش آمدید، <?php echo htmlspecialchars($_SESSION['username']); ?> (مدیر)</span>
                <a href="register.php" class="text-blue-500 hover:underline ml-4">ثبت‌نام کاربر جدید</a>
                <a href="change_password.php" class="text-blue-500 hover:underline ml-4">تغییر رمز عبور</a>
                <a href="logout.php" class="text-red-500 hover:underline ml-4">خروج</a>
            </div>
        </div>
        <?php if ($error): ?>
            <p class="text-red-500 mb-4 text-center"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="text-green-500 mb-4 text-center"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form method="POST" class="edit-user-form bg-white p-6 rounded shadow">
            <div class="mb-4">
                <label class="block text-gray-700">نام کاربری</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">رمز عبور جدید (اختیاری)</label>
                <input type="password" name="password" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">نقش</label>
                <select name="role" id="role-select" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>کاربر</option>
                    <option value="editor" <?php echo $user['role'] == 'editor' ? 'selected' : ''; ?>>ادیتور</option>
                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>مدیر</option>
                </select>
            </div>
            <div class="mb-4" id="editor-permissions" style="display: <?php echo $user['role'] == 'editor' ? 'block' : 'none'; ?>;">
                <label class="block text-gray-700">دسترسی‌های ادیتور</label>
                <div class="flex flex-col space-y-2">
                    <label><input type="checkbox" name="can_add" value="1" <?php echo $user['can_add'] ? 'checked' : ''; ?>> افزودن</label>
                    <label><input type="checkbox" name="can_edit" value="1" <?php echo $user['can_edit'] ? 'checked' : ''; ?>> ویرایش</label>
                    <label><input type="checkbox" name="can_delete" value="1" <?php echo $user['can_delete'] ? 'checked' : ''; ?>> حذف</label>
                </div>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 w-full">ذخیره</button>
        </form>
        <script>
            document.getElementById('role-select').addEventListener('change', function() {
                document.getElementById('editor-permissions').style.display = this.value === 'editor' ? 'block' : 'none';
            });
        </script>
        <a href="users.php" class="text-blue-500 mt-4 inline-block hover:underline text-center block">بازگشت به لیست کاربران</a>
    </div>
</body>
</html>