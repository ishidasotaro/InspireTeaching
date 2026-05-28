<?php
session_start();
require("../dbconnect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // バリデーション
    if (empty($email) || empty($password)) {
        echo "メールアドレスとパスワードを入力してください。";
        exit;
    }

    // SQL命令の準備
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // メールアドレスの確認
    if (!$user) {
        echo 'メールアドレスまたはパスワードが正しくありません。';
        exit;
    }

    // パスワードの検証
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user["id"];
        header('Location: ../project/dashboard.php');
        exit;
    } else {
        echo 'メールアドレスまたはパスワードが正しくありません。';
    }
}
?>


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - HelpHive</title>
    <link rel="stylesheet" href="./dist/output.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex justify-center items-center min-h-screen bg-[#f0f1f7]">
    <div class="p-10 border shadow-xl bg-white w-full max-w-xs sm:max-w-sm md:max-w-md lg:max-w-lg xl:max-w-xl rounded-2xl flex flex-col items-center">
        <h1 class="text-xl font-bold mb-5 text-[#284682]">ログイン</h1>

        <form action="" method="post" class="w-full">
            <!-- メール入力 -->
            <div class="mb-4">
                <label for="email" class="block font-semibold text-gray-500 text-sm mb-1">メール</label>
                <input type="email" name="email" id="email" class="border w-full p-3 rounded-full focus:outline-none focus:ring-2 focus:ring-[#3A96D0] bg-gray-100" required>
            </div>

            <!-- パスワード入力 -->
            <div class="mb-4">
                <label for="password" class="block font-semibold text-gray-500 text-sm mb-1">パスワード</label>
                <input type="password" name="password" id="password" class="border w-full p-3 rounded-full focus:outline-none focus:ring-2 focus:ring-[#3A96D0] bg-gray-100" required>
            </div>

            <!-- ログインボタン -->
            <div class="flex flex-col space-y-4 w-full">
                <button type="submit" class="bg-[#284682] text-white py-3 rounded-full w-full text-lg font-bold hover:bg-[#F7C32E] transition">ログイン</button>
                <a href="./signin.php" class="text-center text-[#3A96D0] hover:underline text-sm">新規登録はこちら</a>
            </div>
        </form>
    </div>
</body>

</html>