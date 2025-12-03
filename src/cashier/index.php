<?php
session_start();
require_once '../../config/connect.php';

// Inisialisasi keranjang
$cart = $_SESSION['cart'] ?? [];
$cartTotal = 0;

// --- Fungsi format rupiah ---
function idr($value) {
    return 'Rp ' . number_format($value, 0, ',', '.');
}

// --- Cari Produk ---
$products = [];
$searchTerm = $_GET['search_product'] ?? '';
if ($searchTerm !== '') {
    $searchTerm = '%' . $searchTerm . '%';
    $stmt = $conn->prepare("SELECT product_id, product_name, price, stock FROM products WHERE product_name LIKE ? AND stock > 0 LIMIT 15");
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query("SELECT product_id, product_name, price, stock FROM products WHERE stock > 0 ORDER BY product_name ASC LIMIT 15");
    $products = $result->fetch_all(MYSQLI_ASSOC);
}

// --- Tambah ke Cart ---
if (isset($_POST['add_to_cart'])) {
    $productId = (int)$_POST['product_id'];
    $productName = $_POST['product_name'];
    $productPrice = (float)$_POST['product_price'];

    $stmt = $conn->prepare("SELECT stock FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $stmt->bind_result($stock);
    $stmt->fetch();
    $stmt->close();

    if ($stock <= 0) {
        echo "<script>alert('Produk habis stok!');</script>";
    } else {
        $found = false;
        foreach ($cart as &$item) {
            if ($item['product_id'] == $productId) {
                if ($item['quantity'] < $stock) {
                    $item['quantity']++;
                    $item['subtotal'] = $item['quantity'] * $item['price'];
                } else {
                    echo "<script>alert('Jumlah melebihi stok tersedia!');</script>";
                }
                $found = true;
                break;
            }
        }
        unset($item);
        if (!$found) {
            $cart[] = [
                'product_id' => $productId,
                'product_name' => $productName,
                'price' => $productPrice,
                'quantity' => 1,
                'subtotal' => $productPrice
            ];
        }
        $_SESSION['cart'] = $cart;
    }
}

// --- Update Quantity ---
if (isset($_POST['update_quantity'])) {
    $productId = (int)$_POST['product_id'];
    $newQuantity = max(0, (int)$_POST['quantity']);

    $stmt = $conn->prepare("SELECT stock FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $stmt->bind_result($stock);
    $stmt->fetch();
    $stmt->close();

    foreach ($cart as &$item) {
        if ($item['product_id'] == $productId) {
            if ($newQuantity == 0) {
                $cart = array_filter($cart, fn($i) => $i['product_id'] != $productId);
            } elseif ($newQuantity > $stock) {
                echo "<script>alert('Jumlah melebihi stok tersedia ($stock)!');</script>";
            } else {
                $item['quantity'] = $newQuantity;
                $item['subtotal'] = $item['quantity'] * $item['price'];
            }
            break;
        }
    }
    unset($item);
    $_SESSION['cart'] = $cart;
}

// --- Hitung Total Keranjang ---
$cartTotal = array_sum(array_column($cart, 'subtotal'));

// --- Terapkan Voucher ---
$voucherUsed = null;
$discountAmount = 0;
$finalTotal = $cartTotal;

if (isset($_POST['apply_voucher'])) {
    $voucherCode = trim($_POST['voucher_code']);
    $stmt = $conn->prepare("SELECT * FROM vouchers WHERE voucher_code = ? AND expiration_date >= CURDATE()");
    $stmt->bind_param("s", $voucherCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $voucher = $result->fetch_assoc();
    $stmt->close();

    if ($voucher) {
        $_SESSION['voucher'] = $voucher;
        echo "<script>alert('Voucher berhasil diterapkan!');</script>";
    } else {
        unset($_SESSION['voucher']);
        echo "<script>alert('Voucher tidak valid atau sudah kadaluarsa!');</script>";
    }
}

// Jika ada voucher aktif di session
if (isset($_SESSION['voucher'])) {
    $voucherUsed = $_SESSION['voucher'];
    if ($voucherUsed['discount_type'] === 'percentage') {
        $discountAmount = $cartTotal * ($voucherUsed['discount_value'] / 100);
    } elseif ($voucherUsed['discount_type'] === 'fixed') {
        $discountAmount = $voucherUsed['discount_value'];
    }
    $finalTotal = max(0, $cartTotal - $discountAmount);
}

// --- Proses Transaksi ---
if (isset($_POST['process_transaction']) && !empty($cart)) {
    $conn->begin_transaction();
    try {
        $cashierId = 1; // Ganti dengan session user login
        $transactionDate = date('Y-m-d H:i:s');
        $totalAmount = $cartTotal;
        $finalAmount = $finalTotal;
        $voucherId = $voucherUsed['voucher_id'] ?? null;
        $paymentMethod = 'cash';

        $stmt = $conn->prepare("
            INSERT INTO transactions (cashier_id, voucher_id, transaction_date, total_amount, final_amount, payment_method, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iisdds", $cashierId, $voucherId, $transactionDate, $totalAmount, $finalAmount, $paymentMethod);
        $stmt->execute();
        $transactionId = $stmt->insert_id;
        $stmt->close();

        // Detail transaksi + update stok
        foreach ($cart as $item) {
            $stmt = $conn->prepare("
                INSERT INTO transaction_details (transaction_id, product_id, quantity, subtotal, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iiid", $transactionId, $item['product_id'], $item['quantity'], $item['subtotal']);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        $_SESSION['cart'] = [];
        unset($_SESSION['voucher']);
        echo "<script>alert('✅ Transaksi berhasil disimpan!');window.location.href='index.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('❌ Gagal: " . addslashes($e->getMessage()) . "');</script>";
    }
}

$conn->close();
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Indomaret POS - Cashier</title>
  <link href="../../output.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>body { font-family: 'Poppins', sans-serif; }</style>
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
              <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
            </svg>
            <span>Dashboard</span>
          </a>
          <a href="./index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-blue-500 text-white font-medium transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2v-7a2 2 0 00-2-2h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
            </svg>
            <span>Cashier</span>
          </a>
          <a href="../products/index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2h-2zM11 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2h-2z" />
            </svg>
            <span>Product</span>
          </a>
          <a href="../transactions/index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
            </svg>
            <span>Transactions</span>
          </a>
          <a href="../admin/index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-all">
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
                <h2 class="text-2xl font-bold text-gray-900">Cashier Management</h2>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden sm:block text-right">
                    <div class="text-sm text-gray-400">Operator</div>
                    <div class="font-medium text-gray-700">Admin Toko</div>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold">A</div>
            </div>
        </header>

      <!-- Content -->
      <main class="flex-1 overflow-auto p-6 bg-gray-50">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Product Search -->
          <section class="lg:col-span-2 bg-white p-6 rounded-xl shadow-md border border-gray-200">
            <h3 class="font-semibold text-gray-700 text-lg mb-4">🔍 Product Search</h3>
            <form action="" method="GET" class="mb-4">
              <input type="text" name="search_product" placeholder="Cari produk..." class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </form>

            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                  <tr>
                    <th class="p-3 text-left">Product</th>
                    <th class="p-3 text-right">Price</th>
                    <th class="p-3 text-right">Stock</th>
                    <th class="p-3 text-center">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($products)): ?>
                    <tr><td colspan="4" class="p-3 text-center text-gray-500">No products found.</td></tr>
                  <?php else: foreach ($products as $p): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                      <td class="p-3"><?= htmlspecialchars($p['product_name']) ?></td>
                      <td class="p-3 text-right"><?= idr($p['price']) ?></td>
                      <td class="p-3 text-right"><?= $p['stock'] ?></td>
                      <td class="p-3 text-center">
                        <form action="" method="POST">
                          <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                          <input type="hidden" name="product_name" value="<?= $p['product_name'] ?>">
                          <input type="hidden" name="product_price" value="<?= $p['price'] ?>">
                          <button type="submit" name="add_to_cart" class="bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600">Add</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </section>

          <!-- Cart -->
          <aside class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
            <h3 class="font-semibold mb-4 text-gray-700 text-lg">🛒 Cart</h3>
            <div class="space-y-3 mb-6">
              <?php if (empty($cart)): ?>
                <p class="text-gray-500 text-center">Cart is empty.</p>
              <?php else: foreach ($cart as $item): ?>
                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                  <div>
                    <div class="text-sm font-medium text-gray-800"><?= htmlspecialchars($item['product_name']) ?></div>
                    <div class="text-xs text-gray-500"><?= idr($item['price']) ?></div>
                  </div>
                  <form action="" method="POST" class="flex items-center gap-2">
                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="0" class="w-16 p-1 border border-gray-300 rounded-lg text-center text-sm">
                    <button type="submit" name="update_quantity" class="bg-gray-200 px-2 py-1 rounded text-xs hover:bg-gray-300">Upd</button>
                  </form>
                  <div class="text-sm font-bold text-gray-700"><?= idr($item['subtotal']) ?></div>
                </div>
              <?php endforeach; endif; ?>
            </div>

            <!-- Voucher -->
            <form method="POST" class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Voucher Code</label>
              <div class="flex gap-2">
                <input type="text" name="voucher_code" placeholder="Masukkan kode voucher" value="<?= htmlspecialchars($voucherUsed['voucher_code'] ?? '') ?>" class="flex-1 p-2 border border-gray-300 rounded-lg">
                <button type="submit" name="apply_voucher" class="bg-blue-500 text-white px-3 rounded-lg hover:bg-blue-600">Apply</button>
              </div>
            </form>

            <!-- Total -->
            <div class="border-t border-gray-200 pt-4">
              <div class="flex justify-between text-sm text-gray-600">
                <span>Subtotal:</span><span><?= idr($cartTotal) ?></span>
              </div>
              <?php if ($voucherUsed): ?>
              <div class="flex justify-between text-sm text-green-600">
                <span>Discount (<?= $voucherUsed['voucher_code'] ?>):</span><span>-<?= idr($discountAmount) ?></span>
              </div>
              <?php endif; ?>
              <div class="flex justify-between text-lg font-semibold text-gray-800 mt-2">
                <span>Total:</span><span class="text-blue-600"><?= idr($finalTotal) ?></span>
              </div>
            </div>

            <form action="" method="POST">
              <button type="submit" name="process_transaction" class="w-full bg-green-500 text-white p-3 rounded-lg mt-6 text-lg font-semibold hover:bg-green-600 transition-all">Process Transaction</button>
            </form>
          </aside>
        </div>
      </main>
    </div>
  </div>
</body>
</html>
