<?php
require 'auth.php';
require 'db.php';

// نیاز به ورود
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$error = null;
$success = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        
        if (empty($current_password) || empty($new_password)) {
            throw new Exception("رمز عبور فعلی و جدید نمی‌توانند خالی باشند.");
        }
        if (strlen($new_password) < 8 || !preg_match("/[0-9]/", $new_password) || !preg_match("/[a-zA-Z]/", $new_password)) {
            throw new Exception("رمز عبور جدید باید حداقل 8 کاراکتر و شامل حروف و اعداد باشد.");
        }
        
        // بررسی رمز عبور فعلی
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($current_password, $user['password'])) {
            throw new Exception("رمز عبور فعلی اشتباه است.");
        }
        
        // به‌روزرسانی رمز عبور
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $_SESSION['user_id']]);
        
        $success = "رمز عبور با موفقیت تغییر کرد.";
    } catch (Exception $e) {
        $error = "خطا: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تغییر رمز عبور</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">تغییر رمز عبور</h1>
            <div>
                <span class="text-gray-700">خوش آمدید، <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo $_SESSION['role'] == 'admin' ? 'مدیر' : 'کاربر'; ?>)</span>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <a href="users.php" class="text-blue-500 hover:underline ml-4">مدیریت کاربران</a>
                <?php endif; ?>
                <a href="logout.php" class="text-red-500 hover:underline ml-4">خروج</a>
            </div>
        </div>
        <?php if ($error): ?>
            <p class="text-red-500 mb-4 text-center"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="text-green-500 mb-4 text-center"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form method="POST" class="change-password-form bg-white p-6 rounded shadow">
            <div class="mb-4">
                <label class="block text-gray-700">رمز عبور فعلی</label>
                <input type="password" name="current_password" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">رمز عبور جدید</label>
                <input type="password" name="new_password" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 w-full">تغییر رمز عبور</button>
        </form>
        <a href="index.php" class="text-blue-500 mt-4 inline-block hover:underline text-center block">بازگشت به صفحه اصلی</a>
    </div>
</body>
</html>