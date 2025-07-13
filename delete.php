<?php
require 'auth.php';
require 'db.php';

// محدود کردن دسترسی به مدیر یا ادیتور با مجوز حذف
if (!canDelete($pdo)) {
    header("Location: index.php");
    exit;
}

try {
    if (isset($_GET['movie_id'])) {
        $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
        $stmt->execute([(int)$_GET['movie_id']]);
    } elseif (isset($_GET['series_id'])) {
        $stmt = $pdo->prepare("DELETE FROM series WHERE id = ?");
        $stmt->execute([(int)$_GET['series_id']]);
    } elseif (isset($_GET['season_id'])) {
        $stmt = $pdo->prepare("DELETE FROM seasons WHERE id = ?");
        $stmt->execute([(int)$_GET['season_id']]);
    } elseif (isset($_GET['episode_id'])) {
        $stmt = $pdo->prepare("DELETE FROM episodes WHERE id = ?");
        $stmt->execute([(int)$_GET['episode_id']]);
    } elseif (isset($_GET['user_id'])) {
        $user_id = (int)$_GET['user_id'];
        // جلوگیری از حذف کاربر فعلی
        if ($user_id == $_SESSION['user_id']) {
            die("خطا: نمی‌توانید حساب خود را حذف کنید.");
        }
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        header("Location: users.php");
        exit;
    } else {
        die("شناسه معتبر ارائه نشده است.");
    }
    header("Location: index.php");
    exit;
} catch (PDOException $e) {
    die("خطا در حذف: " . $e->getMessage());
}
?>