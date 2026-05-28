<?php
// データベース接続ファイルを読み込む
require("../dbconnect.php");
session_start();

// ログインしていない場合はログインページにリダイレクト
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$errors = [];

// フォームが送信されたときの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // フォームからデータを受け取る
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);
    $topic = trim($_POST['topic']);
    $user_id = $_SESSION['user_id'];

    // バリデーション
    if (empty($title)) {
        $errors[] = 'タイトルを入力してください。';
    }
    if (empty($body)) {
        $errors[] = '本文を入力してください。';
    }
    if (empty($topic)) {
        $errors[] = 'トピックを選択してください。';
    }

    $photo_path = null;

    // 写真がアップロードされた場合の処理
    if (isset($_FILES['images']) && $_FILES['images']['error'][0] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = uniqid('photo_') . '_' . basename($_FILES['images']['name'][0]);
        $photo_path = $upload_dir . $file_name;

        if (!move_uploaded_file($_FILES['images']['tmp_name'][0], $photo_path)) {
            $errors[] = '写真のアップロードに失敗しました。';
        }
    }

    // エラーがない場合のみデータベースに保存
    if (empty($errors)) {
        try {
            // トピックIDを取得
            $topic_sql = "SELECT id FROM topics WHERE name = :name";
            $topic_stmt = $pdo->prepare($topic_sql);
            $topic_stmt->bindValue(':name', $topic, PDO::PARAM_STR);
            $topic_stmt->execute();
            $topic_data = $topic_stmt->fetch(PDO::FETCH_ASSOC);
            $topic_id = $topic_data['id'] ?? null;

            if ($topic_id) {
                $stmt = $pdo->prepare('INSERT INTO posts (user_id, title, content, photo_path, topic_id) VALUES (:user_id, :title, :content, :photo_path, :topic_id)');
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':title', $title, PDO::PARAM_STR);
                $stmt->bindValue(':content', $body, PDO::PARAM_STR);
                $stmt->bindValue(':photo_path', $photo_path, PDO::PARAM_STR);
                $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
                $stmt->execute();

                // 投稿成功後、タイムラインにリダイレクト
                header('Location: dashboard.php');
                exit();
            } else {
                $errors[] = '選択されたトピックは無効です。';
            }

        } catch (PDOException $e) {
            $errors[] = 'データベースエラー: ' . $e->getMessage();
        }
    }
}

// トピック一覧を取得（フォーム用）
$topics_data = $pdo->query("SELECT name FROM topics ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$topics = array_column($topics_data, 'name');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新しい投稿</title>
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
        <a href="../project/create_post.php" class="kiroku"><img src="../assets/img/S__43532302_0.jpg" alt="">記録する</a>
        <a href="../mypage/my_page.php" class="mypage"><img src="../assets/img/S__43532304_0 copy.jpg" alt="マイページアイコン">マイページ</a>
        <a href="../howto.html" class="howto"><img src="../assets/img/アカウントのアイコン2.png" alt="使い方アイコン">使い方</a>
        <a href="../auth/logout.php" class="logout"><img src="../assets/img/logout.png" alt="">ログアウト</a>
    </nav>
    </nav>
</header>

<main>
    <form class="card" action="" method="post" enctype="multipart/form-data">
        <div class="zenbu">  
            <p class="oomidasi">Create Post</p>
            <p class="sub-text">タイトル・本文・<b>画像</b>を選んで送信してください</p>
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
            <input class="titleip" id="title" type="text" name="title" placeholder="例：Gitの使い方を教えた" required />
        </div>
        
        <div class="row-items">
            <div class="rowtwo">
                <label for="body" class="label-honbun">本文</label>
                <textarea class="Honbunip" id="body" name="body" placeholder="今日教えたこと：&#10;相手の反応：😊 / 😲 / 👍&#10;次に教えるなら：" required></textarea>
            </div>
            <div class="topicselect">
                <label for="topic topicpull">トピック</label>
                <select id="topic" name="topic" class="topiclist">
                    <option value="">選択してください</option>
                    <?php foreach ($topics as $topic_name): ?>
                        <option value="<?php echo htmlspecialchars($topic_name); ?>"><?php echo htmlspecialchars($topic_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row imgupload">
            <div class="upload">
                <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                <p style="margin:0">ここに <strong>画像を追加</strong>（クリックで選択）</p>
                <label class="fake-btn" for="images">画像ファイルを選ぶ</label>
                <input id="images" type="file" name="images[]" accept="image/*" multiple style="display: none;"/>
                <p class="hint">JPG/PNG、複数可</p>
            </div>
        </div>

        <div class="actionbutton">
            <button class="btnx-primary" type="submit">投稿する</button>
            <a class="btnx-ghost" href="./dashboard.php">キャンセル</a>  
        </div>
    </form>
</main>

<a href="#top" class="to-top">↑ TOP</a>
</body>
</html>