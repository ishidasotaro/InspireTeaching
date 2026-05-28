<?php
// データベース接続ファイルを読み込む
require("../dbconnect.php");
session_start();

// ログインしていない場合はログインページにリダイレクト
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ユーザー情報を取得
$user_sql = "SELECT name, user_group FROM users WHERE id = :user_id";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$user_stmt->execute();
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// ユーザーの投稿数を取得
$post_count_sql = "SELECT COUNT(*) FROM posts WHERE user_id = :user_id";
$post_count_stmt = $pdo->prepare($post_count_sql);
$post_count_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$post_count_stmt->execute();
$post_count = $post_count_stmt->fetchColumn();

// ユーザーが獲得したいいねの総数を取得
$reaction_count_sql = "SELECT COUNT(r.id) FROM reactions r JOIN posts p ON r.post_id = p.id WHERE p.user_id = :user_id";
$reaction_count_stmt = $pdo->prepare($reaction_count_sql);
$reaction_count_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$reaction_count_stmt->execute();
$reaction_count = $reaction_count_stmt->fetchColumn();

// ユーザーの過去の投稿を取得
$posts_sql = "SELECT p.*, t.name AS topic_name, COUNT(r.id) AS reaction_count
              FROM posts p
              LEFT JOIN topics t ON p.topic_id = t.id
              LEFT JOIN reactions r ON p.id = r.post_id
              WHERE p.user_id = :user_id
              user_group BY p.id
              ORDER BY p.created_at DESC";
$posts_stmt = $pdo->prepare($posts_sql);
$posts_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$posts_stmt->execute();
$posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マイページ - Teach & Learn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/mypage.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #e0f2fe;
            color: #333;
        }

        .post-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

    </style>
</head>

<body class="bg-[#e0f2fe] text-gray-800">
    <header class="header">
        <nav class="lan">
            <!-- ロゴ画像 -->
            <img class="logo-IT" src="../assets/img/logo-IT.png" alt="ロゴ">

            <div class="menu-container">
                <!-- メニューボタン -->
                <button class="menu-toggle" onclick="toggleMenu()">☰</button>
                <!-- メニューリスト -->
                <ul class="menu-list">
                    <li><a href="../project/create_post.php" class="record"><img src="../assets/img/record.jpeg" alt="記録アイコン">記録する</a></li>
                    <li><a href="../mypage/my_page.php" class="mypage"><img src="../assets/img/mypage.jpg" alt="マイページアイコン">マイページ</a></li>
                    <li><a href="../howto.html" class="usage"><img src="../assets/img/使い方.jpg" alt="使い方アイコン">使い方</a></li>
                    <li><a href="../auth/logout.php" class="logout"><img src="../assets/img/logout.png" alt="ログアウト">ログアウト</a></li>
                </ul>
            </div>
        </nav>
    </header>
    <div class="container mx-auto max-w-4xl p-4 sm:p-6 md:p-8">

        <!-- ユーザー情報と実績 -->
        <div class="bg-white p-6 rounded-2xl shadow-xl border border-gray-200 mb-6">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center text-xl font-bold text-gray-500">
                    <?php echo htmlspecialchars(mb_substr($user['name'], 0, 1)); ?>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-[#284682]"><?php echo htmlspecialchars($user['name']); ?></h2>
                    <span class="text-sm text-gray-500"><?php echo htmlspecialchars($user['user_group']); ?></span>
                </div>
            </div>
            <a href="profile_edit.php" class="bg-[#3A96D0] text-white py-2 px-4 rounded-full font-bold hover:bg-[#284682] transition duration-300">編集</a>
        </div>

        <div class="flex justify-around mt-8 text-center space-x-6 mb-6">
            <!-- 投稿数 -->
            <div class="bg-white rounded-2xl shadow-lg px-8 py-6 flex-1 hover:scale-105 transition">
                <p class="text-5xl font-extrabold text-[#284682] mb-2">
                    <?php echo htmlspecialchars($post_count); ?>
                </p>
                <p class="text-lg font-semibold text-gray-600">投稿数</p>
            </div>

            <!-- いいね数 -->
            <div class="bg-white rounded-2xl shadow-lg px-8 py-6 flex-1 hover:scale-105 transition">
                <p class="text-5xl font-extrabold text-red-500 mb-2">
                    <?php echo htmlspecialchars($reaction_count); ?>
                </p>
                <p class="text-lg font-semibold text-gray-600">いいね数</p>
            </div>
        </div>


        <!-- 過去の投稿一覧 -->
        <h2 class="text-2xl font-bold text-[#284682] mb-4">過去の投稿</h2>
        <div class="post-grid">
            <?php if ($posts && count($posts) > 0): ?>
                <?php foreach ($posts as $post): ?>
                    <a href="../interaction/post_detail.php?id=<?php echo htmlspecialchars($post['id']); ?>&from=mypage" class="block">
                        <div class="bg-white p-6 rounded-2xl shadow-xl border border-gray-200 flex flex-col">
                            <!-- 写真 -->
                            <?php if (!empty($post['photo_path'])): ?>
                                <div class="mb-4 -mt-6 -mx-6">
                                    <img src="../project/<?php echo htmlspecialchars($post['photo_path']); ?>" alt="投稿画像" class="w-full h-48 object-cover rounded-t-2xl border-b-2 border-[#3A96D0]">
                                </div>
                            <?php endif; ?>

                            <!-- トピック名 -->
                            <span class="bg-[#284682] text-white text-xs font-bold px-3 py-1 rounded-full w-fit mb-2">
                                <?php echo htmlspecialchars($post['topic_name']); ?>
                            </span>

                            <!-- 投稿タイトル -->
                            <div class="flex-grow">
                                <h2 class="text-xl font-bold text-[#4a4a4a] mb-2">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </h2>
                            </div>

                            <!-- 投稿者情報と日付 -->
                            <p class="text-sm text-gray-500 mb-2">
                                <span class="font-semibold text-[#284682]"><?php echo htmlspecialchars($user['name']); ?></span>
                                <span class="text-xs"><?php echo date('Y/m/d', strtotime($post['created_at'])); ?></span>
                            </p>

                            <!-- リアクション数 -->
                            <div class="flex items-center text-sm text-gray-500 mt-auto">
                                <span class="flex items-center text-sm font-medium mr-4 text-gray-500 hover:text-red-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                                    </svg>
                                    <span><?php echo htmlspecialchars($post['reaction_count']); ?></span>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-lg text-gray-500">まだ投稿がありません。</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>