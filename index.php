<?php
require 'auth.php';
require 'db.php';

try {
    // دریافت لیست فیلم‌ها
    $stmt_movies = $pdo->query("SELECT * FROM movies ORDER BY title");
    $movies = $stmt_movies->fetchAll(PDO::FETCH_ASSOC);
    foreach ($movies as $index => $movie) {
        $stmt = $pdo->prepare("SELECT l.name FROM languages l JOIN movie_languages ml ON l.id = ml.language_id WHERE ml.movie_id = ?");
        $stmt->execute([$movie['id']]);
        $movies[$index]['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $pdo->prepare("SELECT g.name FROM genres g JOIN movie_genres mg ON g.id = mg.genre_id WHERE mg.movie_id = ?");
        $stmt->execute([$movie['id']]);
        $movies[$index]['genres'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // دریافت لیست سریال‌ها
    $stmt_series = $pdo->query("SELECT * FROM series ORDER BY title");
    $series = $stmt_series->fetchAll(PDO::FETCH_ASSOC);
    foreach ($series as $index => $serie) {
        $stmt = $pdo->prepare("SELECT l.name FROM languages l JOIN series_languages sl ON l.id = sl.language_id WHERE sl.series_id = ?");
        $stmt->execute([$serie['id']]);
        $series[$index]['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $pdo->prepare("SELECT g.name FROM genres g JOIN series_genres sg ON g.id = sg.genre_id WHERE sg.series_id = ?");
        $stmt->execute([$serie['id']]);
        $series[$index]['genres'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // دریافت فصل‌ها برای هر سریال
        $stmt = $pdo->prepare("SELECT * FROM seasons WHERE series_id = ? ORDER BY season_number");
        $stmt->execute([$serie['id']]);
        $series[$index]['seasons'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($series[$index]['seasons'] as $season_index => $season) {
            $stmt = $pdo->prepare("SELECT l.name FROM languages l JOIN season_languages sl ON l.id = sl.language_id WHERE sl.season_id = ?");
            $stmt->execute([$season['id']]);
            $series[$index]['seasons'][$season_index]['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $stmt = $pdo->prepare("SELECT g.name FROM genres g JOIN season_genres sg ON g.id = sg.genre_id WHERE sg.season_id = ?");
            $stmt->execute([$season['id']]);
            $series[$index]['seasons'][$season_index]['genres'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // دریافت اپیزودها برای هر فصل
            $stmt = $pdo->prepare("SELECT * FROM episodes WHERE season_id = ? ORDER BY episode_number");
            $stmt->execute([$season['id']]);
            $series[$index]['seasons'][$season_index]['episodes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($series[$index]['seasons'][$season_index]['episodes'] as $episode_index => $episode) {
                $stmt = $pdo->prepare("SELECT l.name FROM languages l JOIN episode_languages el ON l.id = el.language_id WHERE el.episode_id = ?");
                $stmt->execute([$episode['id']]);
                $series[$index]['seasons'][$season_index]['episodes'][$episode_index]['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $stmt = $pdo->prepare("SELECT g.name FROM genres g JOIN episode_genres eg ON g.id = eg.genre_id WHERE eg.episode_id = ?");
                $stmt->execute([$episode['id']]);
                $series[$index]['seasons'][$season_index]['episodes'][$episode_index]['genres'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
        }
    }
} catch (PDOException $e) {
    die("خطا در دریافت داده‌ها: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مدیریت فیلم‌ها و سریال‌ها</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-3xl font-bold">مدیریت فیلم‌ها و سریال‌ها</h1>
            <div>
                <?php if (isLoggedIn()): ?>
                    <span class="text-gray-700">خوش آمدید، <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo $_SESSION['role'] == 'admin' ? 'مدیر' : ($_SESSION['role'] == 'editor' ? 'ادیتور' : 'کاربر'); ?>)</span>
                    <?php if (isAdmin()): ?>
                        <a href="users.php" class="text-blue-500 hover:underline ml-4">مدیریت کاربران</a>
                        <a href="manage_languages_genres.php" class="text-blue-500 hover:underline ml-4">مدیریت زبان‌ها و ژانرها</a>
                    <?php endif; ?>
                    <a href="change_password.php" class="text-blue-500 hover:underline ml-4">تغییر رمز عبور</a>
                    <a href="logout.php" class="text-red-500 hover:underline ml-4">خروج</a>
                <?php else: ?>
                    <a href="login.php" class="text-blue-500 hover:underline">ورود</a>
                    <a href="register.php" class="text-blue-500 hover:underline ml-4">ثبت‌نام</a>
                <?php endif; ?>
            </div>
        </div>
        <?php if (canAdd($pdo)): ?>
            <a href="add.php" class="bg-blue-500 text-white px-4 py-2 rounded mb-4 inline-block hover:bg-blue-600">افزودن مورد جدید</a>
        <?php endif; ?>

        <!-- جدول فیلم‌ها -->
        <h2 class="text-2xl font-bold mb-2">فیلم‌ها</h2>
        <?php if (empty($movies)): ?>
            <p class="text-gray-500 mb-4">هیچ فیلمی یافت نشد.</p>
        <?php else: ?>
            <table class="w-full bg-white shadow rounded mb-8">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2">عنوان</th>
                        <th class="p-2">زبان‌ها</th>
                        <th class="p-2">ژانرها</th>
                        <th class="p-2">IMDb ID</th>
                        <th class="p-2">TMDb ID</th>
                        <th class="p-2">Rotten Tomatoes ID</th>
                        <?php if (canEdit($pdo) || canDelete($pdo)): ?>
                            <th class="p-2">عملیات</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movies as $movie): ?>
                        <tr>
                            <td class="p-2"><?php echo htmlspecialchars($movie['title']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars(implode(', ', $movie['languages']) ?: '-'); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars(implode(', ', $movie['genres']) ?: '-'); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($movie['imdb_id'] ?: '-'); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($movie['tmdb_id'] ?: '-'); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($movie['rotten_tomatoes_id'] ?: '-'); ?></td>
                            <?php if (canEdit($pdo) || canDelete($pdo)): ?>
                                <td class="p-2">
                                    <?php if (canEdit($pdo)): ?>
                                        <a href="edit.php?movie_id=<?php echo $movie['id']; ?>" class="text-yellow-500 hover:underline">ویرایش</a>
                                    <?php endif; ?>
                                    <?php if (canEdit($pdo) && canDelete($pdo)): ?> | <?php endif; ?>
                                    <?php if (canDelete($pdo)): ?>
                                        <a href="delete.php?movie_id=<?php echo $movie['id']; ?>" class="text-red-500 hover:underline" onclick="return confirm('آیا مطمئن هستید؟');">حذف</a>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- جدول سریال‌ها -->
        <h2 class="text-2xl font-bold mb-2">سریال‌ها</h2>
        <?php if (empty($series)): ?>
            <p class="text-gray-500 mb-4">هیچ سریالی یافت نشد.</p>
        <?php else: ?>
            <table class="w-full bg-white shadow rounded">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2">عنوان</th>
                        <th class="p-2">زبان‌ها</th>
                        <th class="p-2">ژانرها</th>
                        <th class="p-2">IMDb ID</th>
                        <th class="p-2">TMDb ID</th>
                        <th class="p-2">Rotten Tomatoes ID</th>
                        <?php if (canEdit($pdo) || canDelete($pdo) || canAdd($pdo)): ?>
                            <th class="p-2">عملیات</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($series as $serie): ?>
                        <tr class="series-row" data-series-id="<?php echo $serie['id']; ?>">
                            <td class="p-2">
                                <button class="toggle-series text-blue-500 hover:underline" data-series-id="<?php echo $serie['id']; ?>">
                                    <?php echo htmlspecialchars($serie['title']); ?>
                                    <span class="arrow">▼</span>
                                </button>
                            </td>
                            <td class="p-2"><?php echo htmlspecialchars(implode(', ', $serie['languages']) ?: '-'); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars(implode(', ', $serie['genres']) ?: '-'); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($serie['imdb_id'] ?: '-'); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($serie['tmdb_id'] ?: '-'); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($serie['rotten_tomatoes_id'] ?: '-'); ?></td>
                            <?php if (canEdit($pdo) || canDelete($pdo) || canAdd($pdo)): ?>
                                <td class="p-2">
                                    <?php if (canAdd($pdo)): ?>
                                        <a href="add.php?series_id=<?php echo $serie['id']; ?>" class="text-blue-500 hover:underline">افزودن فصل</a>
                                    <?php endif; ?>
                                    <?php if (canAdd($pdo) && (canEdit($pdo) || canDelete($pdo))): ?> | <?php endif; ?>
                                    <?php if (canEdit($pdo)): ?>
                                        <a href="edit.php?series_id=<?php echo $serie['id']; ?>" class="text-yellow-500 hover:underline">ویرایش</a>
                                    <?php endif; ?>
                                    <?php if (canEdit($pdo) && canDelete($pdo)): ?> | <?php endif; ?>
                                    <?php if (canDelete($pdo)): ?>
                                        <a href="delete.php?series_id=<?php echo $serie['id']; ?>" class="text-red-500 hover:underline" onclick="return confirm('آیا مطمئن هستید؟');">حذف</a>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <tr class="seasons-row hidden" data-series-id="<?php echo $serie['id']; ?>">
                            <td colspan="<?php echo (canEdit($pdo) || canDelete($pdo) || canAdd($pdo)) ? 7 : 6; ?>" class="p-0">
                                <div class="season-list bg-gray-50 p-2">
                                    <?php if (empty($serie['seasons'])): ?>
                                        <p class="text-gray-500 p-2">هیچ فصلی یافت نشد.</p>
                                    <?php else: ?>
                                        <?php foreach ($serie['seasons'] as $season): ?>
                                            <div class="season-item" data-season-id="<?php echo $season['id']; ?>">
                                                <div class="flex justify-between items-center p-2 border-b">
                                                    <button class="toggle-season text-blue-500 hover:underline" data-season-id="<?php echo $season['id']; ?>">
                                                        فصل <?php echo htmlspecialchars($season['season_number']); ?>
                                                        <span class="arrow">▼</span>
                                                    </button>
                                                    <span class="text-gray-600"><?php echo htmlspecialchars(implode(', ', $season['languages']) ?: '-'); ?></span>
                                                    <span class="text-gray-600"><?php echo htmlspecialchars(implode(', ', $season['genres']) ?: '-'); ?></span>
                                                    <span class="text-gray-600"><?php echo htmlspecialchars($season['imdb_id'] ?: '-'); ?></span>
                                                    <span class="text-gray-600"><?php echo htmlspecialchars($season['tmdb_id'] ?: '-'); ?></span>
                                                    <span class="text-gray-600"><?php echo htmlspecialchars($season['rotten_tomatoes_id'] ?: '-'); ?></span>
                                                    <?php if (canEdit($pdo) || canDelete($pdo) || canAdd($pdo)): ?>
                                                        <div>
                                                            <?php if (canAdd($pdo)): ?>
                                                                <a href="add.php?season_id=<?php echo $season['id']; ?>" class="text-blue-500 hover:underline">افزودن اپیزود</a>
                                                            <?php endif; ?>
                                                            <?php if (canAdd($pdo) && (canEdit($pdo) || canDelete($pdo))): ?> | <?php endif; ?>
                                                            <?php if (canEdit($pdo)): ?>
                                                                <a href="edit.php?season_id=<?php echo $season['id']; ?>" class="text-yellow-500 hover:underline">ویرایش</a>
                                                            <?php endif; ?>
                                                            <?php if (canEdit($pdo) && canDelete($pdo)): ?> | <?php endif; ?>
                                                            <?php if (canDelete($pdo)): ?>
                                                                <a href="delete.php?season_id=<?php echo $season['id']; ?>" class="text-red-500 hover:underline" onclick="return confirm('آیا مطمئن هستید؟');">حذف</a>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="episode-list hidden" data-season-id="<?php echo $season['id']; ?>">
                                                    <?php if (empty($season['episodes'])): ?>
                                                        <p class="text-gray-500 p-2 pl-8">هیچ اپیزودی یافت نشد.</p>
                                                    <?php else: ?>
                                                        <?php foreach ($season['episodes'] as $episode): ?>
                                                            <div class="flex justify-between items-center p-2 pl-8 bg-gray-100 border-b">
                                                                <span>اپیزود <?php echo htmlspecialchars($episode['episode_number']); ?>: <?php echo htmlspecialchars($episode['title']); ?></span>
                                                                <span class="text-gray-600"><?php echo htmlspecialchars(implode(', ', $episode['languages']) ?: '-'); ?></span>
                                                                <span class="text-gray-600"><?php echo htmlspecialchars(implode(', ', $episode['genres']) ?: '-'); ?></span>
                                                                <span class="text-gray-600"><?php echo htmlspecialchars($episode['imdb_id'] ?: '-'); ?></span>
                                                                <span class="text-gray-600"><?php echo htmlspecialchars($episode['tmdb_id'] ?: '-'); ?></span>
                                                                <span class="text-gray-600"><?php echo htmlspecialchars($episode['rotten_tomatoes_id'] ?: '-'); ?></span>
                                                                <?php if (canEdit($pdo) || canDelete($pdo)): ?>
                                                                    <div>
                                                                        <?php if (canEdit($pdo)): ?>
                                                                            <a href="edit.php?episode_id=<?php echo $episode['id']; ?>" class="text-yellow-500 hover:underline">ویرایش</a>
                                                                        <?php endif; ?>
                                                                        <?php if (canEdit($pdo) && canDelete($pdo)): ?> | <?php endif; ?>
                                                                        <?php if (canDelete($pdo)): ?>
                                                                            <a href="delete.php?episode_id=<?php echo $episode['id']; ?>" class="text-red-500 hover:underline" onclick="return confirm('آیا مطمئن هستید؟');">حذف</a>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <script src="script.js"></script>
</body>
</html>