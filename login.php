<?php
require 'db.php';
require 'auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            throw new Exception("نام کاربری و رمز عبور نمی‌توانند خالی باشند.");
        }
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            throw new Exception("نام کاربری یا رمز عبور اشتباه است.");
        }
    } catch (Exception $e) {
        $error = "خطا: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ورود</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4 text-center">ورود به سیستم</h1>
        <?php if ($error): ?>
            <p class="text-red-500 mb-4 text-center"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" class="login-form bg-white p-6 rounded shadow">
            <div class="mb-4">
                <label class="block text-gray-700">نام کاربری</label>
                <input type="text" name="username" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">رمز عبور</label>
                <input type="password" name="password" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 w-full">ورود</button>
        </form>
        <a href="index.php" class="text-blue-500 mt-4 inline-block hover:underline text-center block">بازگشت به صفحه اصلی</a>
    </div>
</body>
</html>