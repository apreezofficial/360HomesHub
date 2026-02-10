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
    <title>KYC Management | Admin Dashboard</title>
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
                    Protection / <span class="text-gray-900">Verification</span>
                </div>
                <!-- Search & Notification (Reusable component ideally) -->
                <div class="flex items-center gap-4">
                     <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center cursor-pointer">
                        <i class="bi bi-bell text-xl text-gray-600"></i>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">KYC Verification</h1>
                 <div class="relative inline-block">
                    <select class="appearance-none bg-white border border-border px-4 py-2 rounded-lg text-sm font-medium focus:outline-none pr-10 cursor-pointer">
                        <option>All requests</option>
                        <option>Pending</option>
                        <option>Approved</option>
                        <option>Rejected</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-border overflow-hidden min-h-[500px]">
                <table class="w-full text-left">
                    <thead class="bg-white border-b border-border">
                        <tr class="text-[13px] text-text-secondary">
                            <th class="py-4 px-6 font-medium">User</th>
                            <th class="py-4 px-6 font-medium">Type</th>
                            <th class="py-4 px-6 font-medium">Documents</th>
                            <th class="py-4 px-6 font-medium">Status</th>
                            <th class="py-4 px-6 font-medium text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="kyc-table" class="divide-y divide-border">
                        <!-- Loaded via JS -->
                    </tbody>
                </table>
                <div id="empty-state" class="hidden flex flex-col items-center justify-center py-20">
                     <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                        <i class="bi bi-shield-check text-2xl text-gray-300"></i>
                    </div>
                    <p class="text-gray-500 text-sm">No verification requests found</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        const API_BASE = window.location.origin;

        async function fetchKYC() {
             try {
                const res = await fetch('../api/admin/kyc_list.php', { headers: { 'Authorization': `Bearer ${token}` } });
                const data = await res.json();
                
                if (data.success) {
                    const tbody = document.getElementById('kyc-table');
                    const emptyState = document.getElementById('empty-state');
                    tbody.innerHTML = '';
                    
                    if (data.data.kyc.length === 0) {
                        emptyState.classList.remove('hidden');
                        return;
                    }
                    emptyState.classList.add('hidden');

                    data.data.kyc.forEach(k => {
                        const tr = document.createElement('tr');
                        tr.className = 'hover:bg-slate-50 transition-colors text-[14px]';
                        
                        let statusColor = 'bg-yellow-50 text-yellow-600';
                        if(k.status === 'approved') statusColor = 'bg-green-50 text-green-600';
                        if(k.status === 'rejected') statusColor = 'bg-red-50 text-red-600';

                        tr.innerHTML = `
                            <td class="py-4 px-6">
                                <div class="font-bold text-gray-900">${k.first_name} ${k.last_name}</div>
                                <div class="text-xs text-gray-500">${k.email}</div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-900">${k.country}</div>
                                <div class="text-xs text-gray-400 uppercase tracking-wider">${k.identity_type}</div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2">
                                     <a href="${API_BASE}/360HomesHub/${k.id_front}" target="_blank" class="block w-12 h-8 rounded bg-gray-200 border border-gray-300 overflow-hidden hover:ring-2 ring-primary transition-all">
                                        <img src="${API_BASE}/360HomesHub/${k.id_front}" class="w-full h-full object-cover">
                                    </a>
                                    <a href="${API_BASE}/360HomesHub/${k.id_back}" target="_blank" class="block w-12 h-8 rounded bg-gray-200 border border-gray-300 overflow-hidden hover:ring-2 ring-primary transition-all">
                                        <img src="${API_BASE}/360HomesHub/${k.id_back}" class="w-full h-full object-cover">
                                    </a>
                                     <a href="${API_BASE}/360HomesHub/${k.selfie}" target="_blank" class="block w-12 h-8 rounded bg-gray-200 border border-gray-300 overflow-hidden hover:ring-2 ring-primary transition-all">
                                        <img src="${API_BASE}/360HomesHub/${k.selfie}" class="w-full h-full object-cover">
                                    </a>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-1 rounded-md text-[12px] font-bold uppercase ${statusColor}">${k.status}</span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                ${k.status === 'pending' ? `
                                    <div class="flex justify-end gap-2">
                                        <button onclick="updateKYC(${k.id}, 'reject')" class="px-3 py-1.5 rounded-lg border border-red-200 text-red-600 text-[12px] font-bold hover:bg-red-50 transition-colors">Reject</button>
                                        <button onclick="updateKYC(${k.id}, 'approve')" class="px-3 py-1.5 rounded-lg bg-green-600 text-white text-[12px] font-bold hover:bg-green-700 transition-colors">Approve</button>
                                    </div>
                                ` : '<i class="bi bi-three-dots-vertical text-gray-300"></i>'}
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            } catch (err) {
                console.error(err);
            }
        }

        async function updateKYC(kycId, action) {
            // No change to logic, just styling triggers
            // ... (keep existing logic but maybe add toast notification in future)
             const token = localStorage.getItem('jwt_token');
            const path = action === 'approve' ? '../../api/admin/approve_kyc.php' : '../../api/admin/reject_kyc.php'; // Adjusted path as this is in admin dir
            // Wait, previous file had '../api/admin/...' which is correct from 'admin/kyc.php'.
            // wait, my replace block above used ../api
            
             const res = await fetch(`../api/admin/${action === 'approve' ? 'approve_kyc.php' : 'reject_kyc.php'}`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                body: JSON.stringify({ kyc_id: kycId, admin_note: action === 'reject' ? 'Rejected by admin' : '' })
            });
            const data = await res.json();
            if (data.success) {
                // simple alert for now
                fetchKYC();
            } else {
                alert(data.message);
            }
        }

        fetchKYC();
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
