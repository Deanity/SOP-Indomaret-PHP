<?php
session_start();
require_once '../config/connect.php';

// Jika sudah login, langsung redirect ke kasir
if (isset($_SESSION['cashier_id'])) {
  header("Location: ../kasir/index.php");
  exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);

  if (!empty($username) && !empty($password)) {
    $stmt = $conn->prepare("SELECT cashier_id, password FROM cashiers WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $cashier = $result->fetch_assoc();
      // Untuk testing awal tanpa hash, gunakan: if ($password == $cashier['password'])
      if (password_verify($password, $cashier['password']) || $password === $cashier['password']) {
        $_SESSION['cashier_id'] = $cashier['cashier_id'];
        header("Location: ../index.php");
        exit();
      } else {
        $error = 'Password salah.';
      }
    } else {
      $error = 'Username tidak ditemukan.';
    }
    $stmt->close();
  } else {
    $error = 'Harap isi semua kolom.';
  }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Kasir - Indomaret POS</title>
  <link href="../output.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Poppins', sans-serif; }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center min-h-screen">
  <div class="bg-white shadow-2xl rounded-2xl p-10 w-full max-w-md border border-gray-200">
    <div class="flex items-center justify-center mb-6">
      <div class="w-14 h-14 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-xl shadow-lg">IM</div>
    </div>
    <h1 class="text-center text-2xl font-bold text-gray-800 mb-2">Indomaret POS</h1>
    <p class="text-center text-gray-500 mb-8">Silakan login untuk masuk ke sistem kasir</p>

    <?php if (!empty($error)): ?>
      <div class="bg-red-100 text-red-600 p-3 rounded-lg text-sm text-center mb-4 border border-red-200">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">
      <div>
        <label class="block text-gray-700 font-medium mb-2 text-sm">Username</label>
        <input type="text" name="username" placeholder="Masukkan username" required
          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-2 text-sm">Password</label>
        <input type="password" name="password" placeholder="Masukkan password" required
          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
      </div>

      <button type="submit" class="w-full bg-blue-600 text-white font-semibold p-3 rounded-lg hover:bg-blue-700 transition-all">
        Masuk
      </button>
    </form>

    <div class="text-center text-sm text-gray-500 mt-6">
      © <?= date('Y') ?> Indomaret POS — <span class="text-blue-600 font-medium">Kasir Login</span>
    </div>
  </div>
</body>
</html>
