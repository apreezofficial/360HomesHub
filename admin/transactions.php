<?php
session_start();
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/db.php';
require_once __DIR__ . '/../utils/jwt.php';

// Simple admin check - redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Generate JWT token if not already in session
if (!isset($_SESSION['jwt_token'])) {
    $_SESSION['jwt_token'] = JWTManager::generateToken([
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions | Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#005a92',
                        'bg-light': '#f8fafc',
                        border: '#eef1f4',
                        'text-secondary': '#667085',
                    },
                    fontFamily: {
                        outfit: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-white min-h-screen">
    <div class="flex min-h-screen">
        <aside class="w-[240px] fixed h-full bg-white z-50"></aside>

        <main class="flex-1 ml-[240px] bg-gray-50 min-h-screen p-6">
            <!-- Top Nav -->
            <div class="flex justify-between items-center mb-8">
                <div class="text-[13px] text-gray-400 font-medium">
                    Finance / <span class="text-gray-900">Transactions</span>
                </div>
                <!-- Search & Notification -->
                <div class="flex items-center gap-4">
                     <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center cursor-pointer">
                        <i class="bi bi-bell text-xl text-gray-600"></i>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Financial Transactions</h1>
                 <div class="relative inline-block">
                    <select class="appearance-none bg-white border border-border px-4 py-2 rounded-lg text-sm font-medium focus:outline-none pr-10 cursor-pointer">
                        <option>All transactions</option>
                        <option>Successful</option>
                        <option>Failed</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-border overflow-hidden min-h-[500px]">
                <table class="w-full text-left">
                    <thead class="bg-white border-b border-border">
                        <tr class="text-[13px] text-text-secondary">
                            <th class="py-4 px-6 font-medium">Reference</th>
                            <th class="py-4 px-6 font-medium">User</th>
                            <th class="py-4 px-6 font-medium">Amount</th>
                            <th class="py-4 px-6 font-medium">Gateway</th>
                            <th class="py-4 px-6 font-medium">Status</th>
                            <th class="py-4 px-6 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody id="trans-table" class="divide-y divide-border">
                        <!-- Loaded via JS -->
                    </tbody>
                </table>
                <div id="empty-state" class="hidden flex flex-col items-center justify-center py-20">
                     <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                        <i class="bi bi-receipt text-2xl text-gray-300"></i>
                    </div>
                    <p class="text-gray-500 text-sm">No transactions found</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        async function fetchTransactions() {
            try {
                const res = await fetch('../api/admin/transactions.php', { headers: { 'Authorization': `Bearer ${token}` } });
                const data = await res.json();
                
                if (data.success) {
                    const tbody = document.getElementById('trans-table');
                    const emptyState = document.getElementById('empty-state');
                    tbody.innerHTML = '';

                    if (data.data.transactions.length === 0) {
                        emptyState.classList.remove('hidden');
                        return;
                    }
                    emptyState.classList.add('hidden');

                    data.data.transactions.forEach(t => {
                        const tr = document.createElement('tr');
                        tr.className = 'hover:bg-slate-50 transition-colors text-[14px]';
                        
                        let statusColor = 'bg-red-50 text-red-600';
                        if(t.status === 'success' || t.status === 'successful') statusColor = 'bg-green-50 text-green-600';
                        if(t.status === 'pending') statusColor = 'bg-yellow-50 text-yellow-600';

                        const fName = t.first_name && t.first_name !== 'null' ? t.first_name : 'N/A';
                        const lName = t.last_name && t.last_name !== 'null' ? t.last_name : '';

                        tr.innerHTML = `
                            <td class="py-4 px-6">
                                <span class="font-mono text-xs bg-slate-100 px-2 py-1 rounded text-primary font-semibold">${t.reference}</span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-gray-900">${fName} ${lName}</div>
                                <div class="text-xs text-gray-500">${t.email}</div>
                            </td>
                            <td class="py-4 px-6 font-bold text-gray-900">₦${parseFloat(t.amount).toLocaleString()}</td>
                            <td class="py-4 px-6 capitalize text-gray-600">${t.gateway}</td>
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-1 rounded-md text-[12px] font-bold uppercase ${statusColor}">${t.status}</span>
                            </td>
                            <td class="py-4 px-6 text-gray-500 text-[13px]">${new Date(t.created_at).toLocaleString()}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            } catch (err) {
                console.error(err);
            }
        }
        fetchTransactions();
    </script>
    <script>
        // Inject JWT token from session into localStorage for API calls
        <?php if (isset($_SESSION['jwt_token'])): ?>
            localStorage.setItem('jwt_token', '<?= $_SESSION['jwt_token'] ?>');
        <?php endif; ?>
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>

