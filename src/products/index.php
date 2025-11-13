<?php
require_once '../../config/connect.php';

// === PRODUCT CRUD ===
$products = [];
$editProduct = null;

if (isset($_POST['submit_product'])) {
    $productName = $_POST['product_name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $productId = $_POST['product_id'] ?? null;

    if ($productId) {
        $stmt = $conn->prepare("UPDATE products SET product_name=?, category=?, price=?, stock=?, updated_at=NOW() WHERE product_id=?");
        $stmt->bind_param("ssdis", $productName, $category, $price, $stock, $productId);
    } else {
        $stmt = $conn->prepare("INSERT INTO products (product_name, category, price, stock, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("ssdi", $productName, $category, $price, $stock);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit();
}

if (isset($_GET['delete_id'])) {
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id=?");
    $stmt->bind_param("i", $_GET['delete_id']);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit();
}

if (isset($_GET['edit_id'])) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id=?");
    $stmt->bind_param("i", $_GET['edit_id']);
    $stmt->execute();
    $editProduct = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// === VOUCHER CRUD ===
$vouchers = [];
$editVoucher = null;

if (isset($_POST['submit_voucher'])) {
    $voucherCode = strtoupper(trim($_POST['voucher_code']));
    $discountType = $_POST['discount_type'];
    $discountValue = $_POST['discount_value'];
    $expirationDate = $_POST['expiration_date'];
    $voucherId = $_POST['voucher_id'] ?? null;

    if ($voucherId) {
        $stmt = $conn->prepare("UPDATE vouchers SET voucher_code=?, discount_type=?, discount_value=?, expiration_date=?, updated_at=NOW() WHERE voucher_id=?");
        $stmt->bind_param("ssdsi", $voucherCode, $discountType, $discountValue, $expirationDate, $voucherId);
    } else {
        $stmt = $conn->prepare("INSERT INTO vouchers (voucher_code, discount_type, discount_value, expiration_date, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("ssds", $voucherCode, $discountType, $discountValue, $expirationDate);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit();
}

if (isset($_GET['delete_voucher'])) {
    $stmt = $conn->prepare("UPDATE vouchers SET deleted_at=NOW() WHERE voucher_id=?");
    $stmt->bind_param("i", $_GET['delete_voucher']);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit();
}

if (isset($_GET['edit_voucher'])) {
    $stmt = $conn->prepare("SELECT * FROM vouchers WHERE voucher_id=?");
    $stmt->bind_param("i", $_GET['edit_voucher']);
    $stmt->execute();
    $editVoucher = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$products = $conn->query("SELECT * FROM products ORDER BY product_id DESC")->fetch_all(MYSQLI_ASSOC);
$vouchers = $conn->query("SELECT * FROM vouchers WHERE deleted_at IS NULL ORDER BY voucher_id DESC")->fetch_all(MYSQLI_ASSOC);

$conn->close();

function idr($value) { return 'Rp ' . number_format($value, 0, ',', '.'); }
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indomaret POS - Products & Vouchers</title>
    <link href="../../output.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
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
                <p class="text-sm text-gray-500">Products</p>
            </div>
            </a>

            <nav class="space-y-2 text-sm">
            <a href="../../index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                </svg>
                <span>Dashboard</span>
            </a>
            <a href="../cashier/index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2v-7a2 2 0 00-2-2h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                </svg>
                <span>Cashier</span>
            </a>
            <a href="../products/index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-blue-500 text-white font-medium transition-all">
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
                <h2 class="text-2xl font-bold text-gray-900">Product & Voucher Management</h2>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-sm text-gray-400">Operator</p>
                    <p class="font-medium text-gray-700">Admin Toko</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold">A</div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 overflow-auto p-6 bg-gray-50 space-y-8">

            <!-- PRODUCT SECTION -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Product Form -->
                <section class="lg:col-span-1 bg-white p-6 rounded-xl shadow-md border border-gray-200 h-fit">
                    <h3 class="font-semibold text-gray-700 text-lg mb-4 border-b pb-2">
                        <?= $editProduct ? 'Edit Product' : 'Add New Product' ?>
                    </h3>
                    <form action="" method="POST" class="space-y-4">
                        <?php if ($editProduct): ?>
                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($editProduct['product_id']) ?>">
                        <?php endif; ?>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Product Name</label>
                            <input type="text" name="product_name" value="<?= htmlspecialchars($editProduct['product_name'] ?? '') ?>" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <input type="text" name="category" value="<?= htmlspecialchars($editProduct['category'] ?? '') ?>" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Price</label>
                            <input type="number" name="price" value="<?= htmlspecialchars($editProduct['price'] ?? '') ?>" step="0.01" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Stock</label>
                            <input type="number" name="stock" value="<?= htmlspecialchars($editProduct['stock'] ?? '') ?>" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>

                        <button type="submit" name="submit_product" class="w-full bg-blue-600 text-white font-semibold py-3 rounded-lg hover:bg-blue-700 transition-all">
                            <?= $editProduct ? 'Update Product' : 'Add Product' ?>
                        </button>
                    </form>
                </section>

                <!-- Product List -->
                <section class="lg:col-span-2 bg-white p-6 rounded-xl shadow-md border border-gray-200">
                    <h3 class="font-semibold text-gray-700 text-lg mb-4">📦 Product List</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                                <tr>
                                    <th class="p-3 text-left">ID</th>
                                    <th class="p-3 text-left">Product Name</th>
                                    <th class="p-3 text-left">Category</th>
                                    <th class="p-3 text-right">Price</th>
                                    <th class="p-3 text-right">Stock</th>
                                    <th class="p-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr><td colspan="6" class="p-3 text-center text-gray-500">No products found.</td></tr>
                                <?php else: foreach ($products as $product): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-all">
                                        <td class="p-3 text-xs text-gray-700"><?= $product['product_id'] ?></td>
                                        <td class="p-3 text-gray-700"><?= htmlspecialchars($product['product_name']) ?></td>
                                        <td class="p-3 text-gray-600"><?= htmlspecialchars($product['category']) ?></td>
                                        <td class="p-3 text-right text-gray-600"><?= idr($product['price']) ?></td>
                                        <td class="p-3 text-right text-gray-600"><?= htmlspecialchars($product['stock']) ?></td>
                                        <td class="p-3 text-center">
                                            <a href="?edit_id=<?= $product['product_id'] ?>" class="bg-yellow-500 text-white px-3 py-1 rounded-md text-xs hover:bg-yellow-600 mr-2">Edit</a>
                                            <a href="?delete_id=<?= $product['product_id'] ?>" onclick="return confirm('Delete this product?');" class="bg-red-500 text-white px-3 py-1 rounded-md text-xs hover:bg-red-600">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <!-- VOUCHER SECTION -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Voucher Form -->
                <section class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-lg border border-gray-100 h-fit">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6 border-b pb-2">
                        <?= $editVoucher ? 'Edit Voucher' : 'Add New Voucher' ?>
                    </h3>
                    <form method="POST" class="grid grid-cols-1 gap-5">
                        <?php if ($editVoucher): ?>
                            <input type="hidden" name="voucher_id" value="<?= $editVoucher['voucher_id'] ?>">
                        <?php endif; ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Voucher Code</label>
                            <input type="text" name="voucher_code" value="<?= htmlspecialchars($editVoucher['voucher_code'] ?? '') ?>" placeholder="e.g. DISKON10" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type</label>
                            <select name="discount_type" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="percentage" <?= isset($editVoucher) && $editVoucher['discount_type']=='percentage' ? 'selected' : '' ?>>(%) Persen</option>
                                <option value="fixed" <?= isset($editVoucher) && $editVoucher['discount_type']=='amount' ? 'selected' : '' ?>>(Rp) Rupiah</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Discount Value</label>
                            <input type="number" step="0.01" name="discount_value" value="<?= htmlspecialchars($editVoucher['discount_value'] ?? '') ?>" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Expiration Date</label>
                            <input type="date" name="expiration_date" value="<?= htmlspecialchars($editVoucher['expiration_date'] ?? '') ?>" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <button type="submit" name="submit_voucher" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold py-3 rounded-lg shadow-md hover:from-blue-700 hover:to-blue-800 transition-all">
                            <?= $editVoucher ? 'Update Voucher' : 'Add Voucher' ?>
                        </button>
                    </form>
                </section>

                <!-- Voucher List -->
                <section class="lg:col-span-2 bg-white p-6 rounded-xl shadow-md border border-gray-200">
                    <h3 class="font-semibold text-gray-700 text-lg mb-4">🎟️ Voucher List</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                                <tr>
                                    <th class="p-3 text-left">ID</th>
                                    <th class="p-3 text-left">Code</th>
                                    <th class="p-3 text-center">Type</th>
                                    <th class="p-3 text-right">Value</th>
                                    <th class="p-3 text-center">Expiration</th>
                                    <th class="p-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($vouchers)): ?>
                                    <tr><td colspan="6" class="p-3 text-center text-gray-500">No vouchers available.</td></tr>
                                <?php else: foreach ($vouchers as $v): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-all">
                                        <td class="p-3 text-xs text-gray-700"><?= $v['voucher_id'] ?></td>
                                        <td class="p-3 font-medium text-gray-700"><?= htmlspecialchars($v['voucher_code']) ?></td>
                                        <td class="p-3 text-center"><?= strtoupper($v['discount_type']) ?></td>
                                        <td class="p-3 text-right"><?= $v['discount_type']=='percentage' ? $v['discount_value'].'%' : idr($v['discount_value']) ?></td>
                                        <td class="p-3 text-center"><?= htmlspecialchars($v['expiration_date']) ?></td>
                                        <td class="p-3 text-center">
                                            <a href="?edit_voucher=<?= $v['voucher_id'] ?>" class="bg-yellow-500 text-white px-3 py-1 rounded-md text-xs hover:bg-yellow-600 mr-2">Edit</a>
                                            <a href="?delete_voucher=<?= $v['voucher_id'] ?>" onclick="return confirm('Delete this voucher?');" class="bg-red-500 text-white px-3 py-1 rounded-md text-xs hover:bg-red-600">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
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
