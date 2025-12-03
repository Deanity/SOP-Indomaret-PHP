<?php
require_once '../../config/connect.php';

// Fetch transaksi
$query = "
    SELECT t.*, c.cashier_name, v.voucher_code 
    FROM transactions t
    LEFT JOIN cashiers c ON t.cashier_id = c.cashier_id
    LEFT JOIN vouchers v ON t.voucher_id = v.voucher_id
    ORDER BY t.transaction_id DESC";
$result = $conn->query($query);
$transactions = $result->fetch_all(MYSQLI_ASSOC);

// Ambil detail tiap transaksi (1 query besar)
$detailQuery = "
    SELECT td.*, p.product_name 
    FROM transaction_details td
    JOIN products p ON td.product_id = p.product_id
";
$detailResult = $conn->query($detailQuery);
$details = [];

while ($row = $detailResult->fetch_assoc()) {
    $details[$row['transaction_id']][] = $row;
}

$conn->close();

function idr($val) {
    return 'Rp ' . number_format($val, 0, ',', '.');
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Indomaret POS - Transactions</title>
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
            <a href="../products/index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2h-2zM11 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2h-2z" />
                </svg>
                <span>Product</span>
            </a>
            <a href="../transactions/index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-blue-500 text-white font-medium transition-all">
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
            <h2 class="text-2xl font-bold text-gray-900">Transaction List</h2>
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
            <section class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 transition-all duration-300 hover:shadow-xl">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="font-semibold text-gray-800 text-xl flex items-center gap-2">
                        💳 Transaction History
                    </h3>
                    <p class="text-sm text-gray-500">Showing <?= count($transactions) ?> records</p>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="min-w-full text-sm text-gray-700">
                        <thead>
                            <tr class="bg-gradient-to-r from-blue-500 to-blue-600 text-white text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left">ID</th>
                                <th class="px-4 py-3 text-left">Cashier</th>
                                <th class="px-4 py-3 text-left">Voucher</th>
                                <th class="px-4 py-3 text-left">Method</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-right">Final</th>
                                <th class="px-4 py-3 text-center">Date</th>
                                <th class="px-4 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="8" class="p-5 text-center text-gray-500">No transactions found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $t): ?>
                                <tr class="hover:bg-blue-50 transition-all">
                                    <td class="px-4 py-3 font-medium"><?= $t['transaction_id'] ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars($t['cashier_name']) ?></td>
                                    <td class="px-4 py-3"><?= $t['voucher_code'] ?? '-' ?></td>
                                    <td class="px-4 py-3"><?= ucfirst($t['payment_method']) ?></td>
                                    <td class="px-4 py-3 text-right"><?= idr($t['total_amount']) ?></td>
                                    <td class="px-4 py-3 text-right"><?= idr($t['final_amount']) ?></td>
                                    <td class="px-4 py-3 text-center"><?= date('d/m/Y H:i', strtotime($t['transaction_date'])) ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <button 
                                            class="bg-blue-500 text-white px-3 py-1 rounded-md text-xs hover:bg-blue-600 transition-all"
                                            onclick='showDetail(<?= json_encode($t) ?>, <?= json_encode($details[$t["transaction_id"]] ?? []) ?>)'>
                                            Detail
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<!-- Detail Modal -->
<div id="detailModal" class="hidden fixed inset-0 bg-gray-900/30 backdrop-blur-md flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl p-6 relative">
        <button onclick="closeModal()" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Transaction Details</h3>
        <div id="detailContent" class="text-sm text-gray-700 space-y-2"></div>
        <div class="mt-6 text-right">
            <button onclick="closeModal()" class="bg-gray-200 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-300 transition-all">Close</button>
        </div>
    </div>
</div>

<script>
function showDetail(data, detailList) {
    const modal = document.getElementById('detailModal');
    const content = document.getElementById('detailContent');

    let productRows = "";
    if (detailList.length > 0) {
        productRows = detailList.map(d => `
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 border-b">${d.product_name}</td>
                <td class="px-3 py-2 border-b text-center">${d.quantity}</td>
                <td class="px-3 py-2 border-b text-right">Rp ${parseFloat(d.subtotal).toLocaleString('id-ID')}</td>
            </tr>
        `).join('');
    } else {
        productRows = `<tr><td colspan="3" class="text-center py-3 text-gray-400">No products found</td></tr>`;
    }

    content.innerHTML = `
        <div class="grid grid-cols-2 gap-4 mb-4">
            <p><strong>ID:</strong> ${data.transaction_id}</p>
            <p><strong>Cashier:</strong> ${data.cashier_name}</p>
            <p><strong>Payment:</strong> ${data.payment_method}</p>
            <p><strong>Date:</strong> ${new Date(data.transaction_date).toLocaleString('id-ID')}</p>
        </div>
        <div class="border-t border-gray-200 pt-3">
            <h4 class="font-medium text-gray-800 mb-2">Products</h4>
            <table class="min-w-full text-sm border border-gray-200 rounded-md overflow-hidden">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-center">Qty</th>
                        <th class="px-3 py-2 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>${productRows}</tbody>
            </table>
        </div>
        <div class="pt-3 border-t border-gray-200">
            <p><strong>Total:</strong> Rp ${parseFloat(data.total_amount).toLocaleString('id-ID')}</p>
            <p><strong>Final:</strong> Rp ${parseFloat(data.final_amount).toLocaleString('id-ID')}</p>
        </div>
    `;
    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('detailModal').classList.add('hidden');
}
</script>
</body>
</html>
