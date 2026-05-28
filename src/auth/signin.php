<?php
// DB接続ファイルを読み込む
require("../dbconnect.php");

// リクエストがPOSTメソッドかどうかをチェック
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // フォームから値を取得し、余分な空白を削除
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);
  $username = trim($_POST['username']);

  // エラーメッセージを格納する配列
  $errors = [];

  // バリデーション: 全てのフィールドが入力されているかチェック
  if (empty($email) || empty($password) || empty($username)) {
    $errors[] = "メール、ユーザー名、パスワードをすべて入力してください。";
  }

  // バリデーション: 正しいメール形式かチェック
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "正しいメールアドレスを入力してください。";
  }

  // パスワードの長さチェック（最低6文字以上）
  if (strlen($password) < 6) {
    $errors[] = "パスワードは6文字以上にしてください。";
  }

  // エラーがない場合のみDB処理
  if (empty($errors)) {
    // パスワードをハッシュ化
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // メールアドレスの重複チェック
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
      $errors[] = "このメールアドレスは既に登録されています。";
    } else {
      // SQL命令を準備
      $stmt = $pdo->prepare('INSERT INTO users(email, password, name) VALUES(:email, :password, :name)');
      // 値をバインド
      $stmt->bindValue(':email', $email, PDO::PARAM_STR);
      $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
      $stmt->bindValue(':name', $username, PDO::PARAM_STR);

      // SQL命令を実行
      if ($stmt->execute()) {
        // 成功したらログインページへリダイレクト
        header('Location: login.php');
        exit();
      } else {
        $errors[] = "登録に失敗しました。";
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>新規登録 - Teach & Learn</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

    body {
      font-family: 'Inter', sans-serif;
    }
  </style>
</head>

<body class="flex justify-center items-center min-h-screen bg-[#f0f1f7] text-gray-800">
  <div class="p-8 sm:p-10 border shadow-2xl bg-white w-full max-w-xs sm:max-w-sm md:max-w-md lg:max-w-lg xl:max-w-xl rounded-2xl flex flex-col items-center">
    <h1 class="text-3xl font-bold mb-6 text-[#284682]">新規登録</h1>
    <?php if (!empty($errors)): ?>
      <div class="mb-4 w-full p-4 bg-red-100 text-red-700 rounded-lg shadow-inner">
        <?php foreach ($errors as $error): ?>
          <p class="text-sm font-medium"><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <form action="" method="post" class="w-full" novalidate>
      <!-- メール入力 -->
      <div class="mb-4">
        <label for="email" class="block font-semibold text-gray-500 text-sm mb-1">メール</label>
        <input type="email" name="email" id="email" class="border w-full p-3 rounded-full focus:outline-none focus:ring-2 focus:ring-[#3A96D0] bg-gray-100 placeholder-gray-400" placeholder="メールアドレス" required>
      </div>
      <!-- ユーザー名入力 -->
      <div class="mb-4">
        <label for="username" class="block font-semibold text-gray-500 text-sm mb-1">ユーザー名</label>
        <input type="text" name="username" id="username" class="border w-full p-3 rounded-full focus:outline-none focus:ring-2 focus:ring-[#3A96D0] bg-gray-100 placeholder-gray-400" placeholder="ユーザー名" required>
      </div>
      <!-- 所属グループ選択 -->
      <div class="mb-4">
        <label for="user_group" class="block font-semibold text-gray-500 text-sm mb-1">所属グループ</label>
        <select name="user_group" id="user_group" class="border w-full p-3 rounded-full focus:outline-none focus:ring-2 focus:ring-[#3A96D0] bg-gray-100" required>
          <option value="" disabled selected>選択してください</option>
          <option value="user_group_1">user_group①</option>
          <option value="user_group_2">user_group②</option>
          <option value="user_group_3">user_group③</option>
        </select>
      </div>
      <!-- パスワード入力 -->
      <div class="mb-6">
        <label for="password" class="block font-semibold text-gray-500 text-sm mb-1">パスワード</label>
        <input type="password" name="password" id="password" class="border w-full p-3 rounded-full focus:outline-none focus:ring-2 focus:ring-[#3A96D0] bg-gray-100 placeholder-gray-400" placeholder="6文字以上" required>
      </div>
      <!-- 登録ボタン -->
      <div class="flex flex-col space-y-4 w-full">
        <button type="submit" class="bg-[#284682] text-white py-3 rounded-full w-full text-lg font-bold hover:bg-[#F7C32E] transition duration-300 transform hover:scale-105">登録</button>
        <a href="./login.php" class="text-center text-[#3A96D0] hover:underline text-sm font-semibold">ログインはこちら</a>
      </div>
    </form>
  </div>
</body>

</html>