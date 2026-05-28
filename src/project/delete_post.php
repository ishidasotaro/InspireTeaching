<?php
require("../dbconnect.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$post_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

if (empty($post_id)) {
    header("Location: dashboard.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = :post_id");
    $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
    $stmt->execute();
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post && $post['user_id'] == $user_id) {
        $delete_stmt = $pdo->prepare("DELETE FROM posts WHERE id = :post_id");
        $delete_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $delete_stmt->execute();
    }
} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage();
    exit();
}

// 削除完了後、タイムラインにリダイレクト
header("Location: dashboard.php");
exit();
?>
