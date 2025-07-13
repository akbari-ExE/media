<?php
require 'auth.php';
require 'db.php';

$error = null;
$success = null;
$is_admin = isAdmin();
$role = $is_admin && isset($_POST['role']) ? $_POST['role'] : 'user';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // اعتبارسنجی
        if (empty($username) || empty($password)) {
            throw new Exception("نام کاربری و رمز عبور نمی‌توانند خالی باشند.");
        }
        if (strlen($password) < 8 || !preg_match("/[0-9]/", $password) || !preg_match("/[a-zA-Z]/", $password)) {
            throw new Exception("رمز عبور باید حداقل 8 کاراکتر و شامل حروف و اعداد باشد.");
        }
        if ($is_admin && !in_array($role, ['admin', 'editor', 'user'])) {
            throw new Exception("نقش نامعتبر است.");
        }
        
        // بررسی عدم وجود نام کاربری
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception("نام کاربری قبلاً ثبت شده است.");
        }
        
        // هش کردن رمز عبور
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // ثبت کاربر جدید
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $role]);
        
        // افزودن دسترسی‌های پیش‌فرض برای ادیتور
        if ($role == 'editor') {
            $user_id = $pdo->lastInsertId();
            $can_add = isset($_POST['can_add']) ? 1 : 0;
            $can_edit = isset($_POST['can_edit']) ? 1 : 0;
            $can_delete = isset($_POST['can_delete']) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO editor_permissions (user_id, can_add, can_edit, can_delete) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $can_add, $can_edit, $can_delete]);
        }
        
        $success = "ثبت‌نام با موفقیت انجام شد." . ($is_admin ? "" : " اکنون می‌توانید وارد شوید.");
    } catch (Exception $e) {
        $error = "خطا: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ثبت‌نام</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4 text-center">ثبت‌نام</h1>
        <?php if ($error): ?>
            <p class="text-red-500 mb-4 text-center"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="text-green-500 mb-4 text-center"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form method="POST" class="register-form bg-white p-6 rounded shadow">
            <div class="mb-4">
                <label class="block text-gray-700">نام کاربری</label>
                <input type="text" name="username" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">رمز عبور</label>
                <input type="password" name="password" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <?php if ($is_admin): ?>
                <div class="mb-4">
                    <label class="block text-gray-700">نقش</label>
                    <select name="role" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="user">کاربر</option>
                        <option value="editor">ادیتور</option>
                        <option value="admin">مدیر</option>
                    </select>
                </div>
                <div class="mb-4" id="editor-permissions" style="display: none;">
                    <label class="block text-gray-700">دسترسی‌های ادیتور</label>
                    <div class="flex flex-col space-y-2">
                        <label><input type="checkbox" name="can_add" value="1"> افزودن</label>
                        <label><input type="checkbox" name="can_edit" value="1"> ویرایش</label>
                        <label><input type="checkbox" name="can_delete" value="1"> حذف</label>
                    </div>
                </div>
                <script>
                    document.querySelector('select[name="role"]').addEventListener('change', function() {
                        document.getElementById('editor-permissions').style.display = this.value === 'editor' ? 'block' : 'none';
                    });
                </script>
            <?php endif; ?>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 w-full">ثبت‌نام</button>
        </form>
        <a href="login.php" class="text-blue-500 mt-4 inline-block hover:underline text-center block">ورود</a>
        <a href="index.php" class="text-blue-500 mt-2 inline-block hover:underline text-center block">بازگشت به صفحه اصلی</a>
    </div>
</body>
</html>