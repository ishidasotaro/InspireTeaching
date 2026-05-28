<?php
// データベース接続ファイルを読み込む
require("../dbconnect.php");
session_start();

// ログインしていない場合はログインページにリダイレクト
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// フォームがPOST送信されたときの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'];
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];

    // バリデーション：コメントが空でないかチェック
    if (empty($comment)) {
        header("Location: post_detail.php?id=" . $post_id);
        exit();
    }

    try {
        // SQL命令の準備と実行
        $stmt = $pdo->prepare('INSERT INTO comments (post_id, user_id, comment) VALUES (:post_id, :user_id, :comment)');
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':comment', $comment, PDO::PARAM_STR);
        $stmt->execute();
    } catch (PDOException $e) {
        // エラーハンドリング
        // 例: echo "データベースエラー: " . $e->getMessage();
    }

    // コメント投稿後、投稿詳細ページにリダイレクト
    header("Location: ./post_detail.php?id=" . $post_id);
    exit();
}
?>
