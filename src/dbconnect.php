<?php
$servername = "localhost";
$username = "root"; // MySQLのユーザー名
$password = "root"; // MySQLのパスワード
$dbname = "hackathon"; // 作成したデータベース名

try {
    // PDO接続
    $pdo = new PDO("mysql:host=db;dbname=hackathon;charset=utf8mb4", $username, $password);
    // エラーモードを例外に設定
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // フェッチモードをデフォルトの連想配列に設定
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
