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
    header("Location: ../project/dashboard.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reactions WHERE user_id = :user_id AND post_id = :post_id");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        $insert_stmt = $pdo->prepare("INSERT INTO reactions (user_id, post_id) VALUES (:user_id, :post_id)");
        $insert_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $insert_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $insert_stmt->execute();
    }
} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage();
    exit();
}

header("Location: ../project/dashboard.php");
exit();
?>