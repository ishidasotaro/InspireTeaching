<?php
// データベース接続ファイルを読み込む
require("../dbconnect.php");
session_start();

// ログインしていない場合はログインページにリダイレクト
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// URLから投稿IDを取得
$post_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$from_page = isset($_GET['from']) && $_GET['from'] === 'mypage' ? '../mypage/my_page.php' : '../project/dashboard.php';

// 投稿IDが空ではないかチェック
if (empty($post_id)) {
    header("Location: ../project/dashboard.php");
    exit();
}

// 投稿情報を取得
$post_sql = "SELECT p.*, u.name AS user_name, t.name AS topic_name, u.user_group
             FROM posts p
             JOIN users u ON p.user_id = u.id
             LEFT JOIN topics t ON p.topic_id = t.id
             WHERE p.id = :post_id";
$post_stmt = $pdo->prepare($post_sql);
$post_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
$post_stmt->execute();
$post = $post_stmt->fetch(PDO::FETCH_ASSOC);

// 投稿が存在しない場合はタイムラインにリダイレクト
if (!$post) {
    header("Location: ../project/dashboard.php");
    exit();
}

// 投稿に対するいいね！数を取得
$reaction_count_sql = "SELECT COUNT(*) FROM reactions WHERE post_id = :post_id";
$reaction_count_stmt = $pdo->prepare($reaction_count_sql);
$reaction_count_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
$reaction_count_stmt->execute();
$reaction_count = $reaction_count_stmt->fetchColumn();

// ログイン中のユーザーがいいね！済みか確認
$is_reacted_sql = "SELECT COUNT(*) FROM reactions WHERE user_id = :user_id AND post_id = :post_id";
$is_reacted_stmt = $pdo->prepare($is_reacted_sql);
$is_reacted_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$is_reacted_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
$is_reacted_stmt->execute();
$is_reacted = ($is_reacted_stmt->fetchColumn() > 0);

// コメントを取得
$comments_sql = "SELECT c.*, u.name AS user_name
                 FROM comments c
                 JOIN users u ON c.user_id = u.id
                 WHERE c.post_id = :post_id
                 ORDER BY c.created_at ASC";
$comments_stmt = $pdo->prepare($comments_sql);
$comments_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
$comments_stmt->execute();
$comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

// コメント数を取得
$comment_count_sql = "SELECT COUNT(*) FROM comments WHERE post_id = :post_id";
$comment_count_stmt = $pdo->prepare($comment_count_sql);
$comment_count_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
$comment_count_stmt->execute();
$comment_count = $comment_count_stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> | Teach & Learn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }

        .grid-container {
            display: grid;
            gap: 24px;
            grid-template-columns: 1fr;
        }

        @media (min-width: 768px) {
            .grid-container {
                grid-template-columns: 1.5fr 1fr;
            }
        }

        .post-main {
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            padding: 16px;
        }

        .comment-section {
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            padding: 16px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #0284c7;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .post-meta {
            color: #075985;
            font-size: 14px;
        }

        .post-title {
            font-size: 24px;
            font-weight: 900;
            color: #0c4a6e;
            margin: 0 0 8px;
        }

          .post-content-body {
            line-height: 1.6;
            margin-top: 16px;
        }
        
        
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.3rem 0.55rem;
            border: 1px solid #bae6fd;
            border-radius: 999px;
            font-size: 12px;
            background: #e0f2fe;
            color: #0284c7;
            font-weight: 700;
        }

        .chip-likes {
            color: #ef4444;
            border-color: #fca5a5;
            background: #fee2e2;
        }

        .chip-comments {
            color: #0284c7;
            border-color: #bae6fd;
            background: #e0f2fe;
        }
    </style>
</head>
<body class="bg-[#e0f2fe] text-[#0c4a6e]">

    <main class="container">
        <div class="grid-container">
            <!-- 左側のカラム -->
            <div class="post-main">
                <a href="<?php echo htmlspecialchars($from_page); ?>" class="back-link">
                    &larr; 戻る
                </a>
                
                <?php if (!empty($post['photo_path'])): ?>
                    <img src="../project/<?php echo htmlspecialchars($post['photo_path']); ?>" alt="投稿画像" class="w-full h-auto rounded-xl mb-4">
                <?php endif; ?>
            </div>

            <!-- 右側のカラム -->
            <div class="comment-section">
                <div class="post-header">
                    <div>
                        <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                        <div class="post-meta">
                            <span><?php echo date('Y/m/d', strtotime($post['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="tags">
                        <span class="chip"><?php echo htmlspecialchars($post['topic_name']); ?></span>
                    </div>
                </div>

                <div class="foot">
                    <a href="../interaction/add_reaction.php?id=<?php echo htmlspecialchars($post['id']); ?>&from=mypage" class="chip chip-likes">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="<?php echo $is_reacted ? '#ef4444' : 'none'; ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-heart"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                        <span><?php echo htmlspecialchars($reaction_count); ?></span>
                    </a>
                </div>

                <div class="post-content-body">
                    <h2 class="font-bold">内容:</h2>
                    <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                </div>


                <h2 class="font-bold post-content-body">コメント</h2>
                <div class="space-y-4">
                    <?php if ($comments && count($comments) > 0): ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="border-b border-gray-100 pb-4">
                                <div class="flex items-center mb-1">
                                    <span class="text-xs text-gray-500"><?php echo date('Y/m/d H:i', strtotime($comment['created_at'])); ?></span>
                                </div>
                                <p class="text-gray-700 text-sm"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-sm">まだコメントがありません。</p>
                    <?php endif; ?>
                </div>

                <form action="../interaction/add_comment.php" method="post" class="mt-6">
                    <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post['id']); ?>">
                    <textarea name="comment" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#3A96D0] bg-gray-100" rows="3" placeholder="コメントを入力..."></textarea>
                    <div class="text-right mt-2">
                        <button type="submit" class="bg-[#284682] text-white py-2 px-4 rounded-full font-bold hover:bg-[#F7C32E] transition">コメントを投稿</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

</body>
</html>
