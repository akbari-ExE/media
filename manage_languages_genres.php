<?php
require 'auth.php';
require 'db.php';

// محدود کردن دسترسی به مدیر
if (!isAdmin()) {
    header("Location: index.php");
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['add_language'])) {
            $name = trim($_POST['language_name']);
            if (empty($name)) {
                throw new Exception("نام زبان نمی‌تواند خالی باشد.");
            }
            $stmt = $pdo->prepare("INSERT INTO languages (name) VALUES (?)");
            $stmt->execute([$name]);
            $success = "زبان با موفقیت اضافه شد.";
        } elseif (isset($_POST['add_genre'])) {
            $name = trim($_POST['genre_name']);
            if (empty($name)) {
                throw new Exception("نام ژانر نمی‌تواند خالی باشد.");
            }
            $stmt = $pdo->prepare("INSERT INTO genres (name) VALUES (?)");
            $stmt->execute([$name]);
            $success = "ژانر با موفقیت اضافه شد.";
        } elseif (isset($_POST['delete_language'])) {
            $language_id = (int)$_POST['language_id'];
            $stmt = $pdo->prepare("DELETE FROM languages WHERE id = ?");
            $stmt->execute([$language_id]);
            $success = "زبان با موفقیت حذف شد.";
        } elseif (isset($_POST['delete_genre'])) {
            $genre_id = (int)$_POST['genre_id'];
            $stmt = $pdo->prepare("DELETE FROM genres WHERE id = ?");
            $stmt->execute([$genre_id]);
            $success = "ژانر با موفقیت حذف شد.";
        }
    } catch (Exception $e) {
        $error = "خطا: " . $e->getMessage();
    }
}

try {
    $stmt = $pdo->query("SELECT id, name FROM languages ORDER BY name");
    $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->query("SELECT id, name FROM genres ORDER BY name");
    $genres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطا در دریافت داده‌ها: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مدیریت زبان‌ها و ژانرها</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">مدیریت زبان‌ها و ژانرها</h1>
            <div>
                <span class="text-gray-700">خوش آمدید، <?php echo htmlspecialchars($_SESSION['username']); ?> (مدیر)</span>
                <a href="users.php" class="text-blue-500 hover:underline ml-4">مدیریت کاربران</a>
                <a href="change_password.php" class="text-blue-500 hover:underline ml-4">تغییر رمز عبور</a>
                <a href="logout.php" class="text-red-500 hover:underline ml-4">خروج</a>
            </div>
        </div>
        <?php if ($error): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="text-green-500 mb-4"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <!-- افزودن زبان -->
        <h2 class="text-xl font-bold mb-4">افزودن زبان</h2>
        <form method="POST" class="bg-white p-4 rounded shadow mb-8">
            <div class="mb-4">
                <label class="block text-gray-700">نام زبان</label>
                <input type="text" name="language_name" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <button type="submit" name="add_language" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">افزودن زبان</button>
        </form>

        <!-- نمایش و حذف زبان‌ها -->
        <h2 class="text-xl font-bold mb-4">زبان‌های موجود</h2>
        <?php if (empty($languages)): ?>
            <p class="text-gray-500 mb-4">هیچ زبانی یافت نشد.</p>
        <?php else: ?>
            <table class="w-full bg-white shadow rounded mb-8">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2">نام زبان</th>
                        <th class="p-2">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($languages as $lang): ?>
                        <tr>
                            <td class="p-2"><?php echo htmlspecialchars($lang['name']); ?></td>
                            <td class="p-2">
                                <form method="POST" onsubmit="return confirm('آیا مطمئن هستید؟');">
                                    <input type="hidden" name="language_id" value="<?php echo $lang['id']; ?>">
                                    <button type="submit" name="delete_language" class="text-red-500 hover:underline">حذف</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- افزودن ژانر -->
        <h2 class="text-xl font-bold mb-4">افزودن ژانر</h2>
        <form method="POST" class="bg-white p-4 rounded shadow mb-8">
            <div class="mb-4">
                <label class="block text-gray-700">نام ژانر</label>
                <input type="text" name="genre_name" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <button type="submit" name="add_genre" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">افزودن ژانر</button>
        </form>

        <!-- نمایش و حذف ژانرها -->
        <h2 class="text-xl font-bold mb-4">ژانرهای موجود</h2>
        <?php if (empty($genres)): ?>
            <p class="text-gray-500 mb-4">هیچ ژانری یافت نشد.</p>
        <?php else: ?>
            <table class="w-full bg-white shadow rounded">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2">نام ژانر</th>
                        <th class="p-2">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($genres as $genre): ?>
                        <tr>
                            <td class="p-2"><?php echo htmlspecialchars($genre['name']); ?></td>
                            <td class="p-2">
                                <form method="POST" onsubmit="return confirm('آیا مطمئن هستید؟');">
                                    <input type="hidden" name="genre_id" value="<?php echo $genre['id']; ?>">
                                    <button type="submit" name="delete_genre" class="text-red-500 hover:underline">حذف</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <a href="index.php" class="text-blue-500 mt-4 inline-block hover:underline">بازگشت</a>
    </div>
</body>
</html>