<?php
require 'auth.php';
require 'db.php';

// محدود کردن دسترسی به مدیر یا ادیتور با مجوز ویرایش
if (!canEdit($pdo)) {
    header("Location: index.php");
    exit;
}

$error = null;
$item_type = null;
$item = null;
$languages = [];
$genres = [];
$selected_languages = [];
$selected_genres = [];

try {
    // دریافت زبان‌ها و ژانرها
    $stmt = $pdo->query("SELECT id, name FROM languages ORDER BY name");
    $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->query("SELECT id, name FROM genres ORDER BY name");
    $genres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // بررسی پارامترهای GET
    if (isset($_GET['movie_id'])) {
        $item_type = 'movie';
        $movie_id = (int)$_GET['movie_id'];
        $stmt = $pdo->prepare("SELECT id, title, imdb_id, tmdb_id, rotten_tomatoes_id FROM movies WHERE id = ?");
        $stmt->execute([$movie_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            throw new Exception("فیلم یافت نشد.");
        }
        // دریافت زبان‌های انتخاب‌شده
        $stmt = $pdo->prepare("SELECT language_id FROM movie_languages WHERE movie_id = ?");
        $stmt->execute([$movie_id]);
        $selected_languages = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'language_id');
        // دریافت ژانرهای انتخاب‌شده
        $stmt = $pdo->prepare("SELECT genre_id FROM movie_genres WHERE movie_id = ?");
        $stmt->execute([$movie_id]);
        $selected_genres = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'genre_id');
    } elseif (isset($_GET['series_id'])) {
        $item_type = 'series';
        $series_id = (int)$_GET['series_id'];
        $stmt = $pdo->prepare("SELECT id, title, imdb_id, tmdb_id, rotten_tomatoes_id FROM series WHERE id = ?");
        $stmt->execute([$series_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            throw new Exception("سریال یافت نشد.");
        }
        // دریافت زبان‌های انتخاب‌شده
        $stmt = $pdo->prepare("SELECT language_id FROM series_languages WHERE series_id = ?");
        $stmt->execute([$series_id]);
        $selected_languages = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'language_id');
        // دریافت ژانرهای انتخاب‌شده
        $stmt = $pdo->prepare("SELECT genre_id FROM series_genres WHERE series_id = ?");
        $stmt->execute([$series_id]);
        $selected_genres = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'genre_id');
    } elseif (isset($_GET['season_id'])) {
        $item_type = 'season';
        $season_id = (int)$_GET['season_id'];
        $stmt = $pdo->prepare("SELECT s.id, s.series_id, s.season_number, s.imdb_id, s.tmdb_id, s.rotten_tomatoes_id, se.title as series_title 
                               FROM seasons s JOIN series se ON s.series_id = se.id WHERE s.id = ?");
        $stmt->execute([$season_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            throw new Exception("فصل یافت نشد.");
        }
        // دریافت زبان‌های انتخاب‌شده
        $stmt = $pdo->prepare("SELECT language_id FROM season_languages WHERE season_id = ?");
        $stmt->execute([$season_id]);
        $selected_languages = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'language_id');
        // دریافت ژانرهای انتخاب‌شده
        $stmt = $pdo->prepare("SELECT genre_id FROM season_genres WHERE season_id = ?");
        $stmt->execute([$season_id]);
        $selected_genres = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'genre_id');
    } elseif (isset($_GET['episode_id'])) {
        $item_type = 'episode';
        $episode_id = (int)$_GET['episode_id'];
        $stmt = $pdo->prepare("SELECT e.id, e.season_id, e.episode_number, e.title, e.imdb_id, e.tmdb_id, e.rotten_tomatoes_id, s.series_id, se.title as series_title 
                               FROM episodes e JOIN seasons s ON e.season_id = s.id JOIN series se ON s.series_id = se.id WHERE e.id = ?");
        $stmt->execute([$episode_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            throw new Exception("اپیزود یافت نشد.");
        }
        // دریافت زبان‌های انتخاب‌شده
        $stmt = $pdo->prepare("SELECT language_id FROM episode_languages WHERE episode_id = ?");
        $stmt->execute([$episode_id]);
        $selected_languages = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'language_id');
        // دریافت ژانرهای انتخاب‌شده
        $stmt = $pdo->prepare("SELECT genre_id FROM episode_genres WHERE episode_id = ?");
        $stmt->execute([$episode_id]);
        $selected_genres = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'genre_id');
    } else {
        throw new Exception("شناسه نامعتبر است.");
    }
} catch (Exception $e) {
    $error = "خطا: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();
        if ($item_type == 'movie') {
            $title = trim($_POST['title']);
            $imdb_id = trim($_POST['imdb_id']);
            $tmdb_id = trim($_POST['tmdb_id']);
            $rotten_tomatoes_id = trim($_POST['rotten_tomatoes_id']);
            $selected_languages = isset($_POST['languages']) ? $_POST['languages'] : [];
            $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];
            if (empty($title)) {
                throw new Exception("عنوان نمی‌تواند خالی باشد.");
            }
            $stmt = $pdo->prepare("UPDATE movies SET title = ?, imdb_id = ?, tmdb_id = ?, rotten_tomatoes_id = ? WHERE id = ?");
            $stmt->execute([$title, $imdb_id ?: null, $tmdb_id ?: null, $rotten_tomatoes_id ?: null, $movie_id]);
            // حذف زبان‌های قبلی
            $stmt = $pdo->prepare("DELETE FROM movie_languages WHERE movie_id = ?");
            $stmt->execute([$movie_id]);
            // افزودن زبان‌های جدید
            foreach ($selected_languages as $lang_id) {
                $stmt = $pdo->prepare("INSERT INTO movie_languages (movie_id, language_id) VALUES (?, ?)");
                $stmt->execute([$movie_id, (int)$lang_id]);
            }
            // حذف ژانرهای قبلی
            $stmt = $pdo->prepare("DELETE FROM movie_genres WHERE movie_id = ?");
            $stmt->execute([$movie_id]);
            // افزودن ژانرهای جدید
            foreach ($selected_genres as $genre_id) {
                $stmt = $pdo->prepare("INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)");
                $stmt->execute([$movie_id, (int)$genre_id]);
            }
        } elseif ($item_type == 'series') {
            $title = trim($_POST['title']);
            $imdb_id = trim($_POST['imdb_id']);
            $tmdb_id = trim($_POST['tmdb_id']);
            $rotten_tomatoes_id = trim($_POST['rotten_tomatoes_id']);
            $selected_languages = isset($_POST['languages']) ? $_POST['languages'] : [];
            $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];
            if (empty($title)) {
                throw new Exception("عنوان نمی‌تواند خالی باشد.");
            }
            $stmt = $pdo->prepare("UPDATE series SET title = ?, imdb_id = ?, tmdb_id = ?, rotten_tomatoes_id = ? WHERE id = ?");
            $stmt->execute([$title, $imdb_id ?: null, $tmdb_id ?: null, $rotten_tomatoes_id ?: null, $series_id]);
            // حذف زبان‌های قبلی
            $stmt = $pdo->prepare("DELETE FROM series_languages WHERE series_id = ?");
            $stmt->execute([$series_id]);
            // افزودن زبان‌های جدید
            foreach ($selected_languages as $lang_id) {
                $stmt = $pdo->prepare("INSERT INTO series_languages (series_id, language_id) VALUES (?, ?)");
                $stmt->execute([$series_id, (int)$lang_id]);
            }
            // حذف ژانرهای قبلی
            $stmt = $pdo->prepare("DELETE FROM series_genres WHERE series_id = ?");
            $stmt->execute([$series_id]);
            // افزودن ژانرهای جدید
            foreach ($selected_genres as $genre_id) {
                $stmt = $pdo->prepare("INSERT INTO series_genres (series_id, genre_id) VALUES (?, ?)");
                $stmt->execute([$series_id, (int)$genre_id]);
            }
        } elseif ($item_type == 'season') {
            $imdb_id = trim($_POST['imdb_id']);
            $tmdb_id = trim($_POST['tmdb_id']);
            $rotten_tomatoes_id = trim($_POST['rotten_tomatoes_id']);
            $selected_languages = isset($_POST['languages']) ? $_POST['languages'] : [];
            $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];
            $stmt = $pdo->prepare("UPDATE seasons SET imdb_id = ?, tmdb_id = ?, rotten_tomatoes_id = ? WHERE id = ?");
            $stmt->execute([$imdb_id ?: null, $tmdb_id ?: null, $rotten_tomatoes_id ?: null, $season_id]);
            // حذف زبان‌های قبلی
            $stmt = $pdo->prepare("DELETE FROM season_languages WHERE season_id = ?");
            $stmt->execute([$season_id]);
            // افزودن زبان‌های جدید
            foreach ($selected_languages as $lang_id) {
                $stmt = $pdo->prepare("INSERT INTO season_languages (season_id, language_id) VALUES (?, ?)");
                $stmt->execute([$season_id, (int)$lang_id]);
            }
            // حذف ژانرهای قبلی
            $stmt = $pdo->prepare("DELETE FROM season_genres WHERE season_id = ?");
            $stmt->execute([$season_id]);
            // افزودن ژانرهای جدید
            foreach ($selected_genres as $genre_id) {
                $stmt = $pdo->prepare("INSERT INTO season_genres (season_id, genre_id) VALUES (?, ?)");
                $stmt->execute([$season_id, (int)$genre_id]);
            }
        } elseif ($item_type == 'episode') {
            $title = trim($_POST['title']);
            $imdb_id = trim($_POST['imdb_id']);
            $tmdb_id = trim($_POST['tmdb_id']);
            $rotten_tomatoes_id = trim($_POST['rotten_tomatoes_id']);
            $selected_languages = isset($_POST['languages']) ? $_POST['languages'] : [];
            $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];
            if (empty($title)) {
                throw new Exception("عنوان اپیزود نمی‌تواند خالی باشد.");
            }
            $stmt = $pdo->prepare("UPDATE episodes SET title = ?, imdb_id = ?, tmdb_id = ?, rotten_tomatoes_id = ? WHERE id = ?");
            $stmt->execute([$title, $imdb_id ?: null, $tmdb_id ?: null, $rotten_tomatoes_id ?: null, $episode_id]);
            // حذف زبان‌های قبلی
            $stmt = $pdo->prepare("DELETE FROM episode_languages WHERE episode_id = ?");
            $stmt->execute([$episode_id]);
            // افزودن زبان‌های جدید
            foreach ($selected_languages as $lang_id) {
                $stmt = $pdo->prepare("INSERT INTO episode_languages (episode_id, language_id) VALUES (?, ?)");
                $stmt->execute([$episode_id, (int)$lang_id]);
            }
            // حذف ژانرهای قبلی
            $stmt = $pdo->prepare("DELETE FROM episode_genres WHERE episode_id = ?");
            $stmt->execute([$episode_id]);
            // افزودن ژانرهای جدید
            foreach ($selected_genres as $genre_id) {
                $stmt = $pdo->prepare("INSERT INTO episode_genres (episode_id, genre_id) VALUES (?, ?)");
                $stmt->execute([$episode_id, (int)$genre_id]);
            }
        }
        $pdo->commit();
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "خطا: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ویرایش <?php echo $item_type == 'movie' ? 'فیلم' : ($item_type == 'series' ? 'سریال' : ($item_type == 'season' ? 'فصل' : 'اپیزود')); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">ویرایش <?php echo $item_type == 'movie' ? 'فیلم' : ($item_type == 'series' ? 'سریال' : ($item_type == 'season' ? 'فصل' : 'اپیزود')); ?></h1>
            <div>
                <span class="text-gray-700">خوش آمدید، <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo $_SESSION['role'] == 'admin' ? 'مدیر' : 'ادیتور'; ?>)</span>
                <a href="change_password.php" class="text-blue-500 hover:underline ml-4">تغییر رمز عبور</a>
                <a href="logout.php" class="text-red-500 hover:underline ml-4">خروج</a>
            </div>
        </div>
        <?php if ($error): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($item): ?>
            <form method="POST" class="bg-white p-4 rounded shadow">
                <?php if ($item_type == 'movie' || $item_type == 'series' || $item_type == 'episode'): ?>
                    <div class="mb-4">
                        <label class="block text-gray-700">عنوان</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                <?php endif; ?>
                <?php if ($item_type == 'season' || $item_type == 'episode'): ?>
                    <div class="mb-4">
                        <label class="block text-gray-700">سریال</label>
                        <input type="text" value="<?php echo htmlspecialchars($item['series_title']); ?>" class="w-full p-2 border rounded bg-gray-100" readonly>
                    </div>
                <?php endif; ?>
                <?php if ($item_type == 'season' || $item_type == 'episode'): ?>
                    <div class="mb-4">
                        <label class="block text-gray-700">شماره فصل</label>
                        <input type="text" value="<?php echo htmlspecialchars($item['season_number']); ?>" class="w-full p-2 border rounded bg-gray-100" readonly>
                    </div>
                <?php endif; ?>
                <?php if ($item_type == 'episode'): ?>
                    <div class="mb-4">
                        <label class="block text-gray-700">شماره اپیزود</label>
                        <input type="text" value="<?php echo htmlspecialchars($item['episode_number']); ?>" class="w-full p-2 border rounded bg-gray-100" readonly>
                    </div>
                <?php endif; ?>
                <div class="mb-4">
                    <label class="block text-gray-700">زبان‌ها (اختیاری)</label>
                    <div class="flex flex-col space-y-2 max-h-40 overflow-y-auto p-2 border rounded">
                        <?php foreach ($languages as $lang): ?>
                            <label><input type="checkbox" name="languages[]" value="<?php echo $lang['id']; ?>" <?php echo in_array($lang['id'], $selected_languages) ? 'checked' : ''; ?> class="mr-2"><?php echo htmlspecialchars($lang['name']); ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">ژانرها (اختیاری)</label>
                    <div class="flex flex-col space-y-2 max-h-40 overflow-y-auto p-2 border rounded">
                        <?php foreach ($genres as $genre): ?>
                            <label><input type="checkbox" name="genres[]" value="<?php echo $genre['id']; ?>" <?php echo in_array($genre['id'], $selected_genres) ? 'checked' : ''; ?> class="mr-2"><?php echo htmlspecialchars($genre['name']); ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">IMDb ID (اختیاری)</label>
                    <input type="text" name="imdb_id" value="<?php echo htmlspecialchars($item['imdb_id'] ?? ''); ?>" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">TMDb ID (اختیاری)</label>
                    <input type="text" name="tmdb_id" value="<?php echo htmlspecialchars($item['tmdb_id'] ?? ''); ?>" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Rotten Tomatoes ID (اختیاری)</label>
                    <input type="text" name="rotten_tomatoes_id" value="<?php echo htmlspecialchars($item['rotten_tomatoes_id'] ?? ''); ?>" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">ذخیره تغییرات</button>
            </form>
        <?php endif; ?>
        <a href="index.php" class="text-blue-500 mt-4 inline-block hover:underline">بازگشت</a>
    </div>
</body>
</html>