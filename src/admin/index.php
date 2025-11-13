<?php
require_once '../../config/connect.php'; // koneksi database

$editCashier = null;

// Handle Create / Update
if (isset($_POST['submit_cashier'])) {
    $cashierId = $_POST['cashier_id'] ?? null;
    $cashierName = $_POST['cashier_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($cashierId) {
        // Update data
        $stmt = $conn->prepare("UPDATE cashiers SET cashier_name=?, username=?, password=?, updated_at=NOW() WHERE cashier_id=?");
        $stmt->bind_param("sssi", $cashierName, $username, $password, $cashierId);
    } else {
        // Insert baru
        $stmt = $conn->prepare("INSERT INTO cashiers (cashier_name, username, password, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("sss", $cashierName, $username, $password);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $cashierId = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM cashiers WHERE cashier_id=?");
    $stmt->bind_param("i", $cashierId);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit();
}

// Handle Edit
if (isset($_GET['edit_id'])) {
    $cashierId = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM cashiers WHERE cashier_id=?");
    $stmt->bind_param("i", $cashierId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editCashier = $result->fetch_assoc();
    $stmt->close();
}

// Ambil data semua kasir
$result = $conn->query("SELECT * FROM cashiers ORDER BY cashier_id DESC");
$cashiers = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Indomaret POS - Cashiers</title>
  <link href="../../output.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>body{font-family:'Poppins',sans-serif;}</style>
</head>
<body class="bg-gray-100 min-h-screen text-gray-800">
<div class="flex h-screen">

  <!-- Sidebar -->
  <aside class="w-64 bg-white p-6 hidden md:flex flex-col justify-between shadow-lg border-r border-gray-200">
    <div>
      <a href="#" class="flex items-center gap-3 mb-10">
        <div class="w-12 h-12 rounded-full bg-blue-600 text-white font-bold text-lg flex items-center justify-center shadow-md">IM</div>
        <div>
          <h1 class="text-xl font-bold text-gray-900">Indomaret POS</h1>
          <p class="text-sm text-gray-500">Cashier</p>
        </div>
      </a>

      <nav class="space-y-2 text-sm">
        <a href="../../index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
          </svg>
          <span>Dashboard</span>
        </a>
        <a href="../cashier/index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-600 font-medium transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2v-7a2 2 0 00-2-2h-1V6a4 4 0 00-4-4z" clip-rule="evenodd"/>
          </svg>
          <span>Cashier</span>
        </a>
        <a href="../products/index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-600  hover:bg-gray-100 hover:bg-gray-100 transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2h-2zM11 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2h-2z"/>
          </svg>
          <span>Product</span>
        </a>
        <a href="../transactions/index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
          </svg>
          <span>Transactions</span>
        </a>
        <a href="../admin/index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg  bg-blue-500 text-white transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 2a6 6 0 016 6v1a1 1 0 01-1 1h-1v1a4 4 0 11-8 0V9H5a1 1 0 01-1-1V8a6 6 0 016-6zm0 14a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
          </svg>
          <span>Admin</span>
        </a>
      </nav>
    </div>
    <div class="text-xs text-center text-gray-400 mt-10">© <?= date('Y') ?> Indomaret POS</div>
  </aside>

  <!-- Main -->
  <div class="flex-1 flex flex-col">
    <!-- Header -->
    <header class="flex items-center justify-between bg-white p-5 shadow-sm border-b border-gray-200">
      <h2 class="text-2xl font-bold text-gray-900">Cashier Management</h2>
      <div class="flex items-center gap-3">
        <div class="text-right hidden sm:block">
          <div class="text-sm text-gray-400">Operator</div>
          <div class="font-medium text-gray-700">Admin Toko</div>
        </div>
        <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold">A</div>
      </div>
    </header>

    <!-- Content -->
    <main class="flex-1 overflow-y-auto p-6 space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <section class="lg:col-span-1 bg-white p-6 rounded-xl shadow-md border border-gray-200 h-fit">
          <h3 class="font-semibold text-gray-700 text-lg mb-4">
            <?= $editCashier ? 'Edit Cashier' : 'Add New Cashier' ?>
          </h3>
          <form method="POST" class="space-y-4">
            <?php if ($editCashier): ?>
              <input type="hidden" name="cashier_id" value="<?= $editCashier['cashier_id'] ?>">
            <?php endif; ?>

            <div>
              <label class="text-sm font-medium text-gray-700">Cashier Name</label>
              <input type="text" name="cashier_name" value="<?= htmlspecialchars($editCashier['cashier_name'] ?? '') ?>" required class="w-full mt-1 p-2 border border-gray-300 rounded-md">
            </div>

            <div>
              <label class="text-sm font-medium text-gray-700">Username</label>
              <input type="text" name="username" value="<?= htmlspecialchars($editCashier['username'] ?? '') ?>" required class="w-full mt-1 p-2 border border-gray-300 rounded-md">
            </div>

            <div>
              <label class="text-sm font-medium text-gray-700">Password</label>
              <input type="password" name="password" value="<?= htmlspecialchars($editCashier['password'] ?? '') ?>" required class="w-full mt-1 p-2 border border-gray-300 rounded-md">
            </div>

            <button type="submit" name="submit_cashier" class="bg-blue-500 text-white w-full py-2 rounded-md font-semibold hover:bg-blue-600 transition-all">
              <?= $editCashier ? 'Update Cashier' : 'Add Cashier' ?>
            </button>
          </form>
        </section>

<!-- Cashier Table Section -->
<section class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-lg border border-gray-100 transition-all duration-300 hover:shadow-xl">
  <div class="flex items-center justify-between mb-4">
    <h3 class="font-semibold text-gray-800 text-xl flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18m-7 5h7" />
      </svg>
      Cashier List
    </h3>
    <a href="add_cashier.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg font-medium text-sm hover:bg-blue-600 transition-all flex items-center gap-2 shadow-md">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
      Add Cashier
    </a>
  </div>

  <div class="overflow-x-auto rounded-xl border border-gray-200">
    <table class="min-w-full text-sm text-gray-700">
      <thead>
        <tr class=" bg-blue-400 text-white uppercase text-xs tracking-wider">
          <th class="px-4 py-3 text-left">ID</th>
          <th class="px-4 py-3 text-left">Name</th>
          <th class="px-4 py-3 text-left">Username</th>
          <th class="px-4 py-3 text-left">Created At</th>
          <th class="px-4 py-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <?php foreach ($cashiers as $c): ?>
          <tr class="hover:bg-blue-50 transition-all duration-200">
            <td class="px-4 py-3 text-gray-600 font-medium"><?= $c['cashier_id'] ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($c['cashier_name']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($c['username']) ?></td>
            <td class="px-4 py-3"><?= $c['created_at'] ?></td>
            <td class="px-4 py-3 text-center">
              <div class="flex items-center justify-center gap-3">
                <a href="?edit_id=<?= $c['cashier_id'] ?>" class="text-blue-500 hover:text-blue-600 transition-all flex items-center gap-1">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M17.414 2.586a2 2 0 010 2.828l-9.9 9.9a1 1 0 01-.39.24l-4 1a1 1 0 01-1.23-1.23l1-4a1 1 0 01.24-.39l9.9-9.9a2 2 0 012.828 0z" />
                  </svg>
                  Edit
                </a>
                <a href="?delete_id=<?= $c['cashier_id'] ?>" class="text-red-500 hover:text-red-600 transition-all flex items-center gap-1" onclick="return confirm('Yakin ingin menghapus kasir ini?')">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2h1v10a2 2 0 002 2h6a2 2 0 002-2V6h1a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 012 0v6a1 1 0 11-2 0V8z" clip-rule="evenodd" />
                  </svg>
                  Delete
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

      </div>
    </main>
  </div>
</div>
</body>
</html>
