<?php
// データベース接続ファイルを読み込む
require("../dbconnect.php");
session_start();

// ログインしていない場合はログインページにリダイレクト
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$message = '';

// ユーザー情報を取得
$user_sql = "SELECT name, email, user_group FROM users WHERE id = :user_id";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$user_stmt->execute();
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// POSTリクエストの場合、更新処理を実行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);
    $new_user_group = $_POST['user_group'];
    $new_password = trim($_POST['password']);
    
    // バリデーション
    if (empty($new_name) || empty($new_email) || empty($new_user_group)) {
        $errors[] = 'ユーザー名、メール、所属グループは必須項目です。';
    }
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '正しいメールアドレスを入力してください。';
    }
    
    // メールアドレスの重複チェック（自分自身のメールアドレスは除く）
    $email_check_sql = "SELECT COUNT(*) FROM users WHERE email = :email AND id != :user_id";
    $email_check_stmt = $pdo->prepare($email_check_sql);
    $email_check_stmt->bindValue(':email', $new_email, PDO::PARAM_STR);
    $email_check_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $email_check_stmt->execute();
    if ($email_check_stmt->fetchColumn() > 0) {
        $errors[] = 'このメールアドレスは既に他のユーザーに登録されています。';
    }

    // パスワードが入力された場合のみバリデーションとハッシュ化
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = 'パスワードは6文字以上にしてください。';
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    } else {
        // パスワードが入力されていない場合は既存のパスワードを使用
        $hashed_password = null;
    }

    if (empty($errors)) {
        try {
            if ($hashed_password) {
                // パスワードも更新する場合
                $update_sql = "UPDATE users SET name = :name, email = :email, password = :password, user_group = :user_group WHERE id = :user_id";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->bindValue(':password', $hashed_password, PDO::PARAM_STR);
            } else {
                // パスワードを更新しない場合
                $update_sql = "UPDATE users SET name = :name, email = :email, user_group = :user_group WHERE id = :user_id";
                $update_stmt = $pdo->prepare($update_sql);
            }
            
            $update_stmt->bindValue(':name', $new_name, PDO::PARAM_STR);
            $update_stmt->bindValue(':email', $new_email, PDO::PARAM_STR);
            $update_stmt->bindValue(':user_group', $new_user_group, PDO::PARAM_STR);
            $update_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $update_stmt->execute();

            // 成功メッセージを表示
            $message = 'プロフィールが更新されました！';
            
            // ユーザー情報を再取得してフォームを更新
            $user_sql = "SELECT name, email, user_group FROM users WHERE id = :user_id";
            $user_stmt = $pdo->prepare($user_sql);
            $user_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $user_stmt->execute();
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

            header("Location: my_page.php");
            exit();

        } catch (PDOException $e) {
            $errors[] = 'データベースエラー: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プロフィール編集 - Teach & Learn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f1f7;
            color: #333;
        }
    </style>
</head>
<body class="flex justify-center items-center min-h-screen bg-[#f0f1f7] text-gray-800">
    <div class="container mx-auto max-w-lg p-8 sm:p-10 border shadow-2xl bg-white rounded-2xl">
        <h1 class="text-3xl font-bold mb-6 text-[#284682] text-center">プロフィール編集</h1>

        <?php if (!empty($errors)): ?>
            <div class="mb-4 w-full p-4 bg-red-100 text-red-700 rounded-lg shadow-inner">
                <?php foreach ($errors as $error): ?>
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($message)): ?>
            <div class="mb-4 w-full p-4 bg-green-100 text-green-700 rounded-lg shadow-inner">
                <p class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <form action="profile_edit.php" method="post" class="w-full">
            <!-- ユーザー名 -->
            <div class="mb-4">
                <label for="name" class="block font-semibold text-gray-500 text-sm mb-1">ユーザー名</label>
                <input type="text" id="name" name="name" class="border w-full p-3 rounded-full focus:outline-none focus:ring-2 focus:ring-[#3A96D0] bg-gray-100" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <!-- メールアドレス -->
            <div class="mb-4">
                <label for="email" class="block font-semibold text-gray-500 text-sm mb-1">メールアドレス</label>
                <input type="email" id="email" name="email" class="border w-full p-3 rounded-full focus:outline-none focus:ring-2 focus:ring-[#3A96D0] bg-gray-100" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <!-- 所属グループ -->
            <div class="mb-4">
                <label for="user_group" class="block font-semibold text-gray-500 text-sm mb-1">所属グループ</label>
                <select name="user_group" id="user_group" class="border w-full p-3 rounded-full focus:outline-none focus:ring-2 focus:ring-[#3A96D0] bg-gray-100" required>
                    <option value="user_group_1" <?php echo ($user['user_group'] == 'user_group_1') ? 'selected' : ''; ?>>user_group①</option>
                    <option value="user_group_2" <?php echo ($user['user_group'] == 'user_group_2') ? 'selected' : ''; ?>>user_group②</option>
                    <option value="user_group_3" <?php echo ($user['user_group'] == 'user_group_3') ? 'selected' : ''; ?>>user_group③</option>
                </select>
            </div>
            <!-- パスワード（任意） -->
            <div class="mb-6">
                <label for="password" class="block font-semibold text-gray-500 text-sm mb-1">新しいパスワード (変更する場合のみ)</label>
                <input type="password" id="password" name="password" class="border w-full p-3 rounded-full focus:outline-none focus:ring-2 focus:ring-[#3A96D0] bg-gray-100 placeholder-gray-400" placeholder="6文字以上">
            </div>
            <div class="flex justify-center space-x-4">
                <button type="submit" class="bg-[#284682] text-white py-3 px-6 rounded-full text-lg font-bold hover:bg-[#F7C32E] transition duration-300 transform hover:scale-105">更新する</button>
                <a href="my_page.php" class="bg-gray-400 text-white py-3 px-6 rounded-full text-lg font-bold hover:bg-gray-500 transition duration-300 transform hover:scale-105 inline-block">キャンセル</a>
            </div>
        </form>
    </div>
</body>
</html>
