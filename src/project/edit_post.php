<?php
// データベース接続ファイルを読み込む
require("../dbconnect.php");
session_start();

// ログインしていない場合はログインページにリダイレクト
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// URLに投稿IDが存在しない場合はタイムラインにリダイレクト
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../project/dashboard.php");
    exit();
}

$post_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$errors = [];
$message = '';

// 投稿情報を取得
$post_sql = "SELECT * FROM posts WHERE id = :post_id AND user_id = :user_id";
$post_stmt = $pdo->prepare($post_sql);
$post_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
$post_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$post_stmt->execute();
$post = $post_stmt->fetch(PDO::FETCH_ASSOC);

// 投稿が存在しない、または投稿者とログインユーザーが一致しない場合はエラー
if (!$post) {
    header("Location: ../project/dashboard.php");
    exit();
}

// POSTリクエストの場合、更新処理を実行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $topic_id = $_POST['topic_id'] ?? null;
    
    // バリデーション
    if (empty($title) || empty($content) || empty($topic_id)) {
        $errors[] = '全ての必須項目を入力してください。';
    }

    // 写真がアップロードされた場合の処理
    $photo_path = $post['photo_path']; // 既存のパスを保持
    if (isset($_FILES['images']) && $_FILES['images']['error'][0] === UPLOAD_ERR_OK) {
        $upload_dir = '../project/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = uniqid('photo_') . '_' . basename($_FILES['images']['name'][0]);
        $new_photo_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['images']['tmp_name'][0], $new_photo_path)) {
            $photo_path = $new_photo_path; // 新しいパスに更新
        } else {
            $errors[] = '写真のアップロードに失敗しました。';
        }
    }

    if (empty($errors)) {
        try {
            $update_sql = "UPDATE posts SET title = :title, content = :content, topic_id = :topic_id, photo_path = :photo_path WHERE id = :post_id";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->bindValue(':title', $title, PDO::PARAM_STR);
            $update_stmt->bindValue(':content', $content, PDO::PARAM_STR);
            $update_stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
            $update_stmt->bindValue(':photo_path', $photo_path, PDO::PARAM_STR);
            $update_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
            $update_stmt->execute();

            // 更新成功後、ダッシュボードにリダイレクト
            header("Location: ../project/dashboard.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = 'データベースエラー: ' . $e->getMessage();
        }
    }
}

// トピック一覧を取得（フォーム用）
$topics = $pdo->query("SELECT id, name FROM topics ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>投稿を編集</title>
    <link rel="stylesheet" href="../assets/css/create_post.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Italiana&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Noto+Sans+JP:wght@100..900&family=Nunito:ital,wght@0,200..1000;1,200..1000&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
</head>
<body id="top" class="page-new">
<header class="header">
    <nav class="lan">
        <h1 class="timeline">Teach Loop</h1>
    <nav class="headermenu">  
        <a href="" class="kiroku"><img src="../assets/img/S__43532302_0.jpg" alt="">記録する</a>
        <a href="" class="mypage"><img src="../assets/img/S__43532304_0 copy.jpg" alt="マイページアイコン">マイページ</a>
        <a href="" class="howto"><img src="../assets/img/アカウントのアイコン2.png" alt="使い方アイコン">使い方</a>
        <a href="" class="logout"><img src="../assets/img/logout.png" alt="">ログアウト</a>
    </nav>
    </nav>
</header>

<main>
    <form class="card" action="edit_post.php?id=<?php echo htmlspecialchars($post_id); ?>" method="post" enctype="multipart/form-data">
        <div class="zenbu">  
            <p class="oomidasi">Edit Post</p>
            <p class="sub-text">タイトル・本文・<b>画像</b>を編集してください</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div style="color: red; margin-bottom: 1rem;">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="row rowone">
            <label for="title" class="up-title">タイトル</label>
            <input class="titleip" id="title" type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required />
        </div>
        
        <div class="row-items">
            <div class="rowtwo">
                <label for="content" class="label-honbun">本文</label>
                <textarea class="Honbunip" id="content" name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>
            </div>
            <div class="topicselect">
                <label for="topic topicpull">トピック</label>
                <select id="topic" name="topic_id" class="topiclist">
                    <?php foreach ($topics as $topic): ?>
                        <option value="<?php echo htmlspecialchars($topic['id']); ?>" <?php echo ($post['topic_id'] == $topic['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($topic['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row imgupload">
            <div class="upload">
                <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                <p style="margin:0">ここに <strong>画像を追加</strong>（クリックで選択）</p>
                <label class="fake-btn" for="images">画像ファイルを選ぶ</label>
                <input id="images" type="file" name="images[]" accept="image/*" multiple style="display: none;" />
                <p class="hint">JPG/PNG、複数可</p>
            </div>
            <?php if (!empty($post['photo_path'])): ?>
                <div class="current-image" style="margin-top: 1rem;">
                    <p style="margin:0">現在の画像:</p>
                    <img src="../project/<?php echo htmlspecialchars($post['photo_path']); ?>" alt="現在の投稿画像" style="width: 150px; height: auto; margin-top: 8px;">
                </div>
            <?php endif; ?>
        </div>

        <div class="actionbutton">
            <button class="btnx-primary" type="submit">投稿を更新</button>
            <a class="btnx-ghost" href="./dashboard.php">キャンセル</a>  
        </div>
    </form>
</main>

<a href="#top" class="to-top">↑ TOP</a>
</body>
</html>
