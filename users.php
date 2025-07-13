<?php
require 'auth.php';
require 'db.php';

// محدود کردن دسترسی به مدیر
if (!isAdmin()) {
    header("Location: index.php");
    exit;
}

try {
    $stmt = $pdo->query("SELECT u.id, u.username, u.role, p.can_add, p.can_edit, p.can_delete 
                         FROM users u 
                         LEFT JOIN editor_permissions p ON u.id = p.user_id 
                         ORDER BY u.username");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطا در دریافت داده‌ها: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مدیریت کاربران</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">مدیریت کاربران</h1>
            <div>
                <span class="text-gray-700">خوش آمدید، <?php echo htmlspecialchars($_SESSION['username']); ?> (مدیر)</span>
                <a href="register.php" class="text-blue-500 hover:underline ml-4">ثبت‌نام کاربر جدید</a>
                <a href="change_password.php" class="text-blue-500 hover:underline ml-4">تغییر رمز عبور</a>
                <a href="logout.php" class="text-red-500 hover:underline ml-4">خروج</a>
            </div>
        </div>
        <table class="w-full bg-white shadow rounded mb-8">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-2">نام کاربری</th>
                    <th class="p-2">نقش</th>
                    <th class="p-2">دسترسی‌ها</th>
                    <th class="p-2">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="p-2"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td class="p-2"><?php echo $user['role'] == 'admin' ? 'مدیر' : ($user['role'] == 'editor' ? 'ادیتور' : 'کاربر'); ?></td>
                        <td class="p-2">
                            <?php if ($user['role'] == 'editor'): ?>
                                <?php echo $user['can_add'] ? 'افزودن ' : ''; ?>
                                <?php echo $user['can_edit'] ? 'ویرایش ' : ''; ?>
                                <?php echo $user['can_delete'] ? 'حذف' : ''; ?>
                                <?php echo !$user['can_add'] && !$user['can_edit'] && !$user['can_delete'] ? 'هیچ' : ''; ?>
                            <?php else: ?>
                                <?php echo $user['role'] == 'admin' ? 'کامل' : 'مشاهده'; ?>
                            <?php endif; ?>
                        </td>
                        <td class="p-2">
                            <a href="edit_user.php?user_id=<?php echo $user['id']; ?>" class="text-yellow-500 hover:underline">ویرایش</a> |
                            <a href="delete.php?user_id=<?php echo $user['id']; ?>" class="text-red-500 hover:underline" onclick="return confirm('آیا مطمئن هستید؟');">حذف</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="index.php" class="text-blue-500 mt-4 inline-block hover:underline">بازگشت به صفحه اصلی</a>
    </div>
</body>
</html>