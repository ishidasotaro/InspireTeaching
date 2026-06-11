<?php
// データベース接続
require("../dbconnect.php");
date_default_timezone_set('Asia/Tokyo');
$pdo->exec("SET time_zone = '+09:00'");
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}
$user_id = $_SESSION['user_id'];
$topic_id = $_GET['topic_id'] ?? null;
$sort_order = $_GET['sort'] ?? 'new';
$user_group = $_SESSION['user_group'] ?? null;

// 投稿一覧SQL
$sql = "SELECT p.id, p.user_id, p.title, p.content, p.photo_path, p.topic_id, p.created_at,
               u.name AS user_name,
               t.name AS topic_name,
               COUNT(r.id) AS reaction_count,
               MAX(CASE WHEN r.user_id = :user_id THEN 1 ELSE 0 END) AS is_reacted
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN topics t ON p.topic_id = t.id
        LEFT JOIN reactions r ON p.id = r.post_id";

        // 条件式を格納する配列
$where_clauses = [];

// 1. ログインユーザーと同じグループの投稿に限定する（基本条件）
if ($user_group) {
    $where_clauses[] = "u.user_group = :user_group"; // usersテーブル、またはpostsテーブルのgroup_id
}

// 2. もしトピックの選択（ITやHTMLなど）があれば、それも条件に加える
if ($topic_id) {
    $where_clauses[] = "p.topic_id = :topic_id";
}

// 条件があれば WHERE 句としてSQLに結合する
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " GROUP BY p.id, p.user_id, p.title, p.content, p.photo_path, p.topic_id, p.created_at, u.name, t.name";

if ($sort_order === 'likes') {
  $sql .= " ORDER BY reaction_count DESC";
} else if ($sort_order === 'old') {
  $sql .= " ORDER BY p.created_at ASC";
} else {
  $sql .= " ORDER BY p.created_at DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
if ($topic_id) {
  $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
}
if ($user_group) {
  $stmt->bindValue(':user_group', $user_group, PDO::PARAM_STR);
}
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$topics = $pdo->query("SELECT id, name FROM topics ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>タイムライン</title>
  <link rel="stylesheet" href="../assets/css/dashuboard.css">
</head>

<body>
    <header class="header">
        <nav class="lan">
            <!-- ロゴ画像 -->
            <img class="logo-IT" src="../assets/img/logo-final.jpg" alt="ロゴ">

            <div class="menu-container">
                <!-- メニューボタン -->
                <button class="menu-toggle" onclick="toggleMenu()">☰</button>
                <!-- メニューリスト -->
                <ul class="menu-list">
                    <li><a href="./create_post.php" class="record"><img src="../assets/img/S__43532304_0.jpg" alt="記録アイコン">記録する</a></li>
                    <li><a href="../mypage/my_page.php" class="mypage"><img src="../assets/img/アカウントのアイコン2.png" alt="マイページアイコン">マイページ</a></li>
                    <li><a href="../howto.html" class="usage"><img src="../assets/img/S__43532302_0.jpg" alt="使い方アイコン">使い方</a></li>
                    <li><a href="../auth/logout.php" class="logout"><img src="../assets/img/logout.png" alt="ログアウト">ログアウト</a></li>
                </ul>
            </div>
        </nav>
    </header>
  <section class="pagehead">
    <div class="container">
      <div>
        <h1 class="title">🧩 タイムライン</h1>
      </div>
      <p class="sub">みんなの「教えた！」を眺めてインスピレーションを得よう。</p>
    </div>
  </section>

  <section class="toolbar">
    <form class="bar" action="dashboard.php" method="get">
      <label for="topic"></label>
      <select name="topic_id" id="topic" class="input">
        <option value="">すべて</option>
        <?php foreach ($topics as $topic): ?>
          <option value="<?php echo htmlspecialchars($topic['id']); ?>" <?php echo ($topic['id'] == $topic_id) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($topic['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="input-user_group">
        <label for="sort"></label>
        <select name="sort" id="sort" class="input">
          <option value="new" <?php echo ($sort_order === 'new') ? 'selected' : ''; ?>>新しい順</option>
          <option value="old" <?php echo ($sort_order === 'old') ? 'selected' : ''; ?>>古い順</option>
          <option value="likes" <?php echo ($sort_order === 'likes') ? 'selected' : ''; ?>>いいね順</option>
        </select>
      </div>
      <button class="submit" type="submit">絞り込む</button>
    </form>
  </section>

  <main class="wrap" style="display:block">
    <section class="masonry">
      <?php if ($posts && count($posts) > 0): ?>
        <?php foreach ($posts as $post): ?>
          <div class="card">
            <a href="../interaction/post_detail.php?id=<?php echo htmlspecialchars($post['id']); ?>" class="thumb">
              <?php if (!empty($post['photo_path'])): ?>
                <img src="../project/<?php echo htmlspecialchars($post['photo_path']); ?>" alt="投稿画像" loading="lazy">
              <?php endif; ?>
              <span class="caption"><?php echo htmlspecialchars($post['topic_name'] ?? ''); ?></span>
            </a>
            <div class="body">
              <a href="../interaction/post_detail.php?id=<?php echo htmlspecialchars($post['id']); ?>" class="title">
                <?php echo htmlspecialchars($post['title']); ?>
              </a>
              <div class="meta">
                <?php echo htmlspecialchars($post['user_name']); ?>
                <?php echo date('Y/m/d', strtotime($post['created_at'])); ?>
              </div>
            </div>
            <div class="foot">
              <!-- いいね -->
              <a href="../interaction/add_reaction.php?id=<?php echo htmlspecialchars($post['id']); ?>&from=dashboard" class="chip">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="<?php echo $post['is_reacted'] ? '#f87171' : 'currentColor'; ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-heart">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                <span><?php echo htmlspecialchars($post['reaction_count']); ?></span>
              </a>

              <!-- コメントボタン -->
              <button type="button" class="chip comment-btn" data-post-id="<?php echo htmlspecialchars($post['id']); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="feather feather-message-circle">
                  <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8A8.5 8.5 0 0 1 18.2 17L22 22l-1.8-4.5A9.43 9.43 0 0 1 12 21c-4.63 0-8.5-3.87-8.5-8.5S7.37 4 12 4s8.5 3.87 8.5 8.5z"></path>
                </svg>
                <span>コメント</span>
              </button>

              <!-- ドロップダウン（投稿者本人のみ） -->
              <?php if ($_SESSION['user_id'] == $post['user_id']): ?>
                <div class="dropdown ml-auto">
                  <button class="dropdown-toggle no-style-btn">
                    <svg xmlns="http://www.w3.org/2000/svg"
                      width="24" height="24" viewBox="0 0 24 24"
                      fill="none" stroke="currentColor" stroke-width="2"
                      stroke-linecap="round" stroke-linejoin="round"
                      class="feather feather-more-vertical">
                      <circle cx="12" cy="5" r="1"></circle>
                      <circle cx="12" cy="12" r="1"></circle>
                      <circle cx="12" cy="19" r="1"></circle>
                    </svg>
                  </button>
                  <div class="dropdown-menu" style="display: none;">
                    <a href="edit_post.php?id=<?php echo htmlspecialchars($post['id']); ?>">編集</a>
                    <a href="delete_post.php?id=<?php echo htmlspecialchars($post['id']); ?>" onclick="return confirm('本当に削除しますか？');">削除</a>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>まだ投稿がありません。</p>
      <?php endif; ?>
    </section>
  </main>

  <div id="commentModal" class="modal">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h2 class="text-lg font-bold mb-2">コメントを書く</h2>
      <form id="commentForm" method="post" action="../interaction/add_comment.php">
        <input type="hidden" name="post_id" id="modalPostId">
        <textarea name="comment" rows="4" class="w-full border rounded p-2"
          placeholder="コメントを入力してください..."></textarea>
        <div class="text-right mt-2">
          <button type="submit" class="btn">送信</button>
        </div>
      </form>
    </div>
  </div>

  <a href="#top" class="to-top">↑ TOP</a>

  <script>
    document.querySelectorAll('.dropdown-toggle').forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        const menu = this.nextElementSibling;
        const card = this.closest('.card');

        // 他のカードのz-indexを戻す
        document.querySelectorAll('.card').forEach(c => c.style.zIndex = 1);

        // このカードだけ最前面に
        card.style.zIndex = 10000;

        // 他のメニューを閉じる
        document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');

        // 表示
        menu.style.display = 'block';
      });
    });

    window.addEventListener('click', function(e) {
      if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
        document.querySelectorAll('.card').forEach(c => c.style.zIndex = 1);
      }
    });

    const commentBtns = document.querySelectorAll('.comment-btn');
    const modal = document.getElementById('commentModal');
    const closeBtn = document.querySelector('.close-btn');
    const modalPostIdInput = document.getElementById('modalPostId');

    commentBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        modal.style.display = 'flex'; // ← ここでflexにする
        modalPostIdInput.value = btn.getAttribute('data-post-id');
      });
    });



    closeBtn.addEventListener('click', () => {
      modal.style.display = 'none';
    });
    window.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.style.display = 'none';
      }
    });
  </script>
</body>

</html>