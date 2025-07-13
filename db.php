<?php
$dsn = 'mysql:host=localhost;dbname=movie_db;charset=utf8';
$username = 'root';
$password = '';
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('اتصال به پایگاه داده ناموفق بود: ' . $e->getMessage());
}
?>