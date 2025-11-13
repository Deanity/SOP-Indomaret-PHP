<?php
session_start();
require_once 'config/connect.php';

// Redirect ke login jika belum login
if (!isset($_SESSION['cashier_id'])) {
  header("Location: ../login/index.php");
  exit();
}

$cashierId = $_SESSION['cashier_id'];
$cashierName = $_SESSION['cashier_name'] ?? 'Kasir';

$totalSales = 0;
$totalTransactions = 0;
$totalProducts = 0;
$totalCustomers = 0;
$recentTransactions = [];

// Fetch Total Sales (sum of final_amount from transactions for the current month)
$currentMonth = date('Y-m');
$stmt = $conn->prepare("SELECT SUM(final_amount) AS total_sales FROM transactions WHERE DATE_FORMAT(transaction_date, '%Y-%m') = ?");
$stmt->bind_param("s", $currentMonth);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $totalSales = $row['total_sales'] ?? 0;
}
$stmt->close();

// Fetch Total Transactions (for today)
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) AS total_transactions FROM transactions WHERE DATE(transaction_date) = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $totalTransactions = $row['total_transactions'] ?? 0;
}
$stmt->close();

// Fetch Total Products
$result = $conn->query("SELECT COUNT(*) AS total_products FROM products");
if ($row = $result->fetch_assoc()) {
    $totalProducts = $row['total_products'] ?? 0;
}

// Fetch Total Customers (assuming cashiers are customers for now, or if there's a separate customers table)
// Based on the ERD, there's no explicit 'customers' table. Let's assume 'cashiers' are the users of the system.
// If 'customers' refers to actual buyers, a dedicated table would be needed.
$result = $conn->query("SELECT COUNT(*) AS total_customers FROM cashiers");
if ($row = $result->fetch_assoc()) {
    $totalCustomers = $row['total_customers'] ?? 0;
}

// Fetch Recent Transactions
$stmt = $conn->prepare("SELECT t.transaction_id AS id, t.transaction_date AS time, c.cashier_name AS cashier, SUM(td.quantity) AS items, t.final_amount AS amount
                        FROM transactions t
                        JOIN cashiers c ON t.cashier_id = c.cashier_id
                        JOIN transaction_details td ON t.transaction_id = td.transaction_id
                        GROUP BY t.transaction_id, t.transaction_date, c.cashier_name, t.final_amount
                        ORDER BY t.transaction_date DESC
                        LIMIT 5");
$stmt->execute();
$recentTransactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

function idr($value) {
    return 'Rp ' . number_format($value, 0, ',', '.');
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Indomaret POS - Dashboard</title>
  <link href="./output.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Poppins', sans-serif; }
  </style>
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
            <p class="text-sm text-gray-500">Dashboard</p>
          </div>
        </a>

        <nav class="space-y-2 text-sm">
          <a href="./index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-blue-500 text-white font-medium transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
            </svg>
            <span>Dashboard</span>
          </a>
          <a href="src/cashier/index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2v-7a2 2 0 00-2-2h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
            </svg>
            <span>Cashier</span>
          </a>
          <a href="src/products/index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2h-2zM11 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2h-2z" />
            </svg>
            <span>Product</span>
          </a>
          <a href="src/transactions/index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
            </svg>
            <span>Transactions</span>
          </a>
          <a href="src/admin/index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 2a6 6 0 016 6v1a1 1 0 01-1 1h-1v1a4 4 0 11-8 0V9H5a1 1 0 01-1-1V8a6 6 0 016-6zm0 14a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
            </svg>
            <span>Admin</span>
          </a>
        </nav>
      </div>

      <div class="text-xs text-center text-gray-400 mt-10">
        © <?= date('Y') ?> Indomaret POS
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col">
      <!-- Header -->
      <header class="flex items-center justify-between bg-white p-5 shadow-sm border-b border-gray-200">
        <div class="flex items-center gap-3">
          <button class="md:hidden p-2 bg-gray-100 text-gray-600 rounded-lg">☰</button>
          <h2 class="text-2xl font-bold text-gray-900">Dashboard</h2>
          <span class="text-base text-gray-500">Point of Sales</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="hidden sm:block text-right">
              <div class="text-sm text-gray-400">Operator</div>
              <div class="font-medium text-gray-700"><?= htmlspecialchars($cashierName) ?></div>
            </div>
          <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold">A</div>
        </div>
      </header>

      <!-- Content -->
      <main class="flex-1 overflow-auto p-6 bg-gray-50">
        <!-- Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
          <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-all border-l-4 border-blue-500">
            <div class="text-sm text-gray-500">Total Penjualan</div>
            <div class="text-3xl font-bold text-blue-600 mt-2"><?= idr($totalSales) ?></div>
            <div class="text-xs text-gray-400 mt-1">Bulan ini</div>
          </div>

          <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-all border-l-4 border-yellow-500">
            <div class="text-sm text-gray-500">Transaksi</div>
            <div class="text-3xl font-bold text-yellow-600 mt-2"><?= $totalTransactions ?></div>
            <div class="text-xs text-gray-400 mt-1">Hari ini</div>
          </div>

          <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-all border-l-4 border-red-500">
            <div class="text-sm text-gray-500">Produk</div>
            <div class="text-3xl font-bold text-red-600 mt-2"><?= $totalProducts ?></div>
            <div class="text-xs text-gray-400 mt-1">Dalam katalog</div>
          </div>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Transactions -->
          <section class="lg:col-span-2 bg-white p-6 rounded-xl shadow-md border border-gray-200">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold text-gray-700 text-lg">🧾 Recent Transactions</h3>
              <a href="src/transactions/index.php" class="text-blue-600 text-sm hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                  <tr>
                    <th class="p-3 text-left">ID</th>
                    <th class="p-3 text-left">Time</th>
                    <th class="p-3 text-left">Cashier</th>
                    <th class="p-3 text-right">Items</th>
                    <th class="p-3 text-right">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentTransactions as $t): ?>
                  <tr class="border-b border-gray-100 hover:bg-gray-50 transition-all">
                    <td class="p-3 font-mono text-xs text-gray-700"><?= htmlspecialchars($t['id']) ?></td>
                    <td class="p-3 text-xs text-gray-600"><?= htmlspecialchars($t['time']) ?></td>
                    <td class="p-3 text-gray-700"><?= htmlspecialchars($t['cashier']) ?></td>
                    <td class="p-3 text-right text-gray-600"><?= htmlspecialchars($t['items']) ?></td>
                    <td class="p-3 text-right font-bold text-blue-600"><?= idr($t['amount']) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>

          <!-- Quick Actions -->
          <aside class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
            <h3 class="font-semibold mb-4 text-gray-700 text-lg">⚡ Quick Actions</h3>
            <div class="space-y-3">
              <a href="src/cashier/index.php" class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl hover:bg-blue-50 transition-all">
                <div class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center text-xl">＋</div>
                <div>
                  <div class="text-sm font-medium text-gray-800">New Transaction</div>
                  <div class="text-xs text-gray-500">Start POS</div>
                </div>
              </a>
              <a href="src/products/index.php" class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl hover:bg-yellow-50 transition-all">
                <div class="w-10 h-10 rounded-full bg-yellow-500 text-white flex items-center justify-center text-xl">📦</div>
                <div>
                  <div class="text-sm font-medium text-gray-800">Add Stock</div>
                  <div class="text-xs text-gray-500">Update inventory</div>
                </div>
              </a>
            </div>
            <p class="mt-6 text-xs text-gray-500">
              Demo version — integrate backend as needed (products, payments, printer, etc).
            </p>
          </aside>
        </div>
      </main>
    </div>
  </div>
</body>
</html>
