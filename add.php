<?php
require 'auth.php';
require 'db.php';

// محدود کردن دسترسی به مدیر یا ادیتور با مجوز افزودن
if (!canAdd($pdo)) {
    header("Location: index.php");
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, name FROM languages ORDER BY name");
    $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->query("SELECT id, name FROM genres ORDER BY name");
    $genres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطا در دریافت زبان‌ها یا ژانرها: " . $e->getMessage());
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();
        if (isset($_POST['movie'])) {
            $title = trim($_POST['title']);
            $imdb_id = trim($_POST['imdb_id']);
            $tmdb_id = trim($_POST['tmdb_id']);
            $rotten_tomatoes_id = trim($_POST['rotten_tomatoes_id']);
            $selected_languages = isset($_POST['languages']) ? $_POST['languages'] : [];
            $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];
            $type = $_POST['type'];
            if (empty($title)) {
                throw new Exception("عنوان نمی‌تواند خالی باشد.");
            }
            if ($type == 'movie') {
                $stmt = $pdo->prepare("INSERT INTO movies (title, imdb_id, tmdb_id, rotten_tomatoes_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $imdb_id ?: null, $tmdb_id ?: null, $rotten_tomatoes_id ?: null]);
                $movie_id = $pdo->lastInsertId();
                foreach ($selected_languages as $lang_id) {
                    $stmt = $pdo->prepare("INSERT INTO movie_languages (movie_id, language_id) VALUES (?, ?)");
                    $stmt->execute([$movie_id, (int)$lang_id]);
                }
                foreach ($selected_genres as $genre_id) {
                    $stmt = $pdo->prepare("INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)");
                    $stmt->execute([$movie_id, (int)$genre_id]);
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO series (title, imdb_id, tmdb_id, rotten_tomatoes_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $imdb_id ?: null, $tmdb_id ?: null, $rotten_tomatoes_id ?: null]);
                $series_id = $pdo->lastInsertId();
                foreach ($selected_languages as $lang_id) {
                    $stmt = $pdo->prepare("INSERT INTO series_languages (series_id, language_id) VALUES (?, ?)");
                    $stmt->execute([$series_id, (int)$lang_id]);
                }
                foreach ($selected_genres as $genre_id) {
                    $stmt = $pdo->prepare("INSERT INTO series_genres (series_id, genre_id) VALUES (?, ?)");
                    $stmt->execute([$series_id, (int)$genre_id]);
                }
            }
        } elseif (isset($_POST['season'])) {
            $series_id = $_POST['series_id'];
            $imdb_id = trim($_POST['imdb_id']);
            $tmdb_id = trim($_POST['tmdb_id']);
            $rotten_tomatoes_id = trim($_POST['rotten_tomatoes_id']);
            $selected_languages = isset($_POST['languages']) ? $_POST['languages'] : [];
            $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];
            $stmt = $pdo->prepare("SELECT MAX(season_number) as max_season FROM seasons WHERE series_id = ?");
            $stmt->execute([$series_id]);
            $max_season = $stmt->fetch(PDO::FETCH_ASSOC)['max_season'];
            $season_number = $max_season ? $max_season + 1 : 1;
            
            $stmt = $pdo->prepare("INSERT INTO seasons (series_id, season_number, imdb_id, tmdb_id, rotten_tomatoes_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$series_id, $season_number, $imdb_id ?: null, $tmdb_id ?: null, $rotten_tomatoes_id ?: null]);
            $season_id = $pdo->lastInsertId();
            foreach ($selected_languages as $lang_id) {
                $stmt = $pdo->prepare("INSERT INTO season_languages (season_id, language_id) VALUES (?, ?)");
                $stmt->execute([$season_id, (int)$lang_id]);
            }
            foreach ($selected_genres as $genre_id) {
                $stmt = $pdo->prepare("INSERT INTO season_genres (season_id, genre_id) VALUES (?, ?)");
                $stmt->execute([$season_id, (int)$genre_id]);
            }
        } elseif (isset($_POST['episode'])) {
            $season_id = $_POST['season_id'];
            $title = trim($_POST['title']);
            $imdb_id = trim($_POST['imdb_id']);
            $tmdb_id = trim($_POST['tmdb_id']);
            $rotten_tomatoes_id = trim($_POST['rotten_tomatoes_id']);
            $selected_languages = isset($_POST['languages']) ? $_POST['languages'] : [];
            $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];
            if (empty($title)) {
                throw new Exception("عنوان اپیزود نمی‌تواند خالی باشد.");
            }
            $stmt = $pdo->prepare("SELECT MAX(episode_number) as max_episode FROM episodes WHERE season_id = ?");
            $stmt->execute([$season_id]);
            $max_episode = $stmt->fetch(PDO::FETCH_ASSOC)['max_episode'];
            $episode_number = $max_episode ? $max_episode + 1 : 1;
            
            $stmt = $pdo->prepare("INSERT INTO episodes (season_id, episode_number, title, imdb_id, tmdb_id, rotten_tomatoes_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$season_id, $episode_number, $title, $imdb_id ?: null, $tmdb_id ?: null, $rotten_tomatoes_id ?: null]);
            $episode_id = $pdo->lastInsertId();
            foreach ($selected_languages as $lang_id) {
                $stmt = $pdo->prepare("INSERT INTO episode_languages (episode_id, language_id) VALUES (?, ?)");
                $stmt->execute([$episode_id, (int)$lang_id]);
            }
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

$series_id = isset($_GET['series_id']) ? (int)$_GET['series_id'] : null;
$season_id = isset($_GET['season_id']) ? (int)$_GET['season_id'] : null;
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- ریسپانسیو بودن -->
    <title>افزودن مورد جدید</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6 sm:p-8"> <!-- افزایش فاصله برای دسکتاپ و موبایل -->
        <header class="flex flex-col sm:flex-row justify-between items-center mb-6">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-800">افزودن مورد جدید</h1>
            <nav class="mt-4 sm:mt-0 flex items-center space-x-4 sm:space-x-6">
                <span class="text-gray-600 text-sm sm:text-base">خوش آمدید، <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo $_SESSION['role'] == 'admin' ? 'مدیر' : 'ادیتور'; ?>)</span>
                <a href="change_password.php" class="text-blue-500 hover:text-blue-700 text-sm sm:text-base transition-colors">تغییر رمز عبور</a>
                <a href="logout.php" class="text-red-500 hover:text-red-700 text-sm sm:text-base transition-colors">خروج</a>
            </nav>
        </header>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6 text-sm"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($season_id): ?>
            <section>
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">افزودن اپیزود</h2>
                <form method="POST" class="bg-white p-6 rounded-lg shadow-sm">
                    <input type="hidden" name="season_id" value="<?php echo htmlspecialchars($season_id); ?>">
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">عنوان اپیزود</label>
                        <input type="text" name="title" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">زبان‌ها (اختیاری)</label>
                        <div class="checkbox-container p-4 border rounded-lg">
                            <?php foreach ($languages as $lang): ?>
                                <label class="block mb-2 text-sm"><input type="checkbox" name="languages[]" value="<?php echo $lang['id']; ?>" class="mr-2"><?php echo htmlspecialchars($lang['name']); ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">ژانرها (اختیاری)</label>
                        <div class="checkbox-container p-4 border rounded-lg">
                            <?php foreach ($genres as $genre): ?>
                                <label class="block mb-2 text-sm"><input type="checkbox" name="genres[]" value="<?php echo $genre['id']; ?>" class="mr-2"><?php echo htmlspecialchars($genre['name']); ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">IMDb ID (اختیاری)</label>
                        <input type="text" name="imdb_id" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">TMDb ID (اختیاری)</label>
                        <input type="text" name="tmdb_id" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">Rotten Tomatoes ID (اختیاری)</label>
                        <input type="text" name="rotten_tomatoes_id" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" name="episode" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors w-full sm:w-auto">افزودن</button>
                </form>
            </section>
        <?php elseif ($series_id): ?>
            <section>
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">افزودن فصل</h2>
                <form method="POST" class="bg-white p-6 rounded-lg shadow-sm">
                    <input type="hidden" name="series_id" value="<?php echo htmlspecialchars($series_id); ?>">
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">زبان‌ها (اختیاری)</label>
                        <div class="checkbox-container p-4 border rounded-lg">
                            <?php foreach ($languages as $lang): ?>
                                <label class="block mb-2 text-sm"><input type="checkbox" name="languages[]" value="<?php echo $lang['id']; ?>" class="mr-2"><?php echo htmlspecialchars($lang['name']); ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">ژانرها (اختیاری)</label>
                        <div class="checkbox-container p-4 border rounded-lg">
                            <?php foreach ($genres as $genre): ?>
                                <label class="block mb-2 text-sm"><input type="checkbox" name="genres[]" value="<?php echo $genre['id']; ?>" class="mr-2"><?php echo htmlspecialchars($genre['name']); ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">IMDb ID (اختیاری)</label>
                        <input type="text" name="imdb_id" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">TMDb ID (اختیاری)</label>
                        <input type="text" name="tmdb_id" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">Rotten Tomatoes ID (اختیاری)</label>
                        <input type="text" name="rotten_tomatoes_id" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" name="season" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors w-full sm:w-auto">افزودن فصل جدید</button>
                </form>
            </section>
        <?php else: ?>
            <section>
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">افزودن فیلم یا سریال</h2>
                <form method="POST" class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">عنوان</label>
                        <input type="text" name="title" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">زبان‌ها (اختیاری)</label>
                        <div class="checkbox-container p-4 border rounded-lg">
                            <?php foreach ($languages as $lang): ?>
                                <label class="block mb-2 text-sm"><input type="checkbox" name="languages[]" value="<?php echo $lang['id']; ?>" class="mr-2"><?php echo htmlspecialchars($lang['name']); ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">ژانرها (اختیاری)</label>
                        <div class="checkbox-container p-4 border rounded-lg">
                            <?php foreach ($genres as $genre): ?>
                                <label class="block mb-2 text-sm"><input type="checkbox" name="genres[]" value="<?php echo $genre['id']; ?>" class="mr-2"><?php echo htmlspecialchars($genre['name']); ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">IMDb ID (اختیاری)</label>
                        <input type="text" name="imdb_id" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">TMDb ID (اختیاری)</label>
                        <input type="text" name="tmdb_id" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">Rotten Tomatoes ID (اختیاری)</label>
                        <input type="text" name="rotten_tomatoes_id" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-5">
                        <label class="block text-gray-600 text-sm font-medium mb-2">نوع</label>
                        <select name="type" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="movie">فیلم</option>
                            <option value="series">سریال</option>
                        </select>
                    </div>
                    <button type="submit" name="movie" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors w-full sm:w-auto">افزودن</button>
                </form>
            </section>
        <?php endif; ?>
        <a href="index.php" class="text-blue-500 hover:text-blue-700 text-sm block mt-6 transition-colors">بازگشت</a>
    </div>
</body>
</html>