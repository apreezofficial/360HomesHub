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
<body class="bg-[#F9FAFB] min-h-screen font-outfit">
    <div class="flex min-h-screen">
        <aside class="w-[260px] fixed h-full bg-white z-50"></aside>

        <main class="flex-1 ml-[260px] min-h-screen p-8">
            <!-- Top Nav -->
            <div class="flex justify-between items-center mb-10">
                <div class="text-[14px] text-gray-400">
                    Protection / <span class="text-gray-900 font-medium">Verification</span>
                </div>
                <div class="flex-1 max-w-[500px] mx-8">
                    <div class="relative">
                        <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="search-input" placeholder="Search for user name or email..." 
                               class="w-full bg-white border border-gray-100 rounded-xl py-3 pl-12 pr-4 text-[14px] focus:outline-none focus:ring-2 focus:ring-primary/5 shadow-sm transition-all">
                    </div>
                </div>
                <div class="flex items-center gap-4">
                     <div class="w-10 h-10 rounded-full bg-white border border-gray-100 flex items-center justify-center cursor-pointer shadow-sm relative">
                        <i class="bi bi-bell text-[18px] text-gray-400"></i>
                         <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-[32px] font-bold text-gray-900 mb-2">KYC Verification</h1>
                    <p class="text-gray-400 text-[15px]">Review and verify user identity documents for platform safety.</p>
                </div>
                <div class="flex gap-2 bg-gray-100 p-1 rounded-xl">
                    <button class="px-6 py-2.5 text-[13px] font-bold rounded-lg bg-white shadow-sm text-gray-900 filter-btn active" data-status="all">All requests</button>
                    <button class="px-6 py-2.5 text-[13px] font-bold text-gray-400 hover:text-gray-600 filter-btn" data-status="pending">Pending</button>
                    <button class="px-6 py-2.5 text-[13px] font-bold text-gray-400 hover:text-gray-600 filter-btn" data-status="approved">Approved</button>
                    <button class="px-6 py-2.5 text-[13px] font-bold text-gray-400 hover:text-gray-600 filter-btn" data-status="rejected">Rejected</button>
                </div>
            </div>

            <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm overflow-hidden min-h-[500px]">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 bg-gray-50/30">
                            <th class="py-4 px-8">User Instance</th>
                            <th class="py-4 px-8">Identity Type</th>
                            <th class="py-4 px-8">Documents</th>
                            <th class="py-4 px-8 text-center">Status</th>
                            <th class="py-4 px-8 text-right">Review Action</th>
                        </tr>
                    </thead>
                    <tbody id="kyc-table" class="divide-y divide-gray-50 text-[13px]">
                        <!-- Loaded via JS -->
                    </tbody>
                </table>
                <div id="empty-state" class="hidden flex flex-col items-center justify-center py-32">
                     <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                        <i class="bi bi-shield-check text-[32px] text-gray-200"></i>
                    </div>
                    <p class="text-gray-400 font-medium">No verification requests match your selection.</p>
                </div>
                
                <div class="p-6 border-t border-gray-50 flex justify-between items-center text-[13px] text-gray-500 font-medium">
                    <div id="pagination-info">Showing 0 out of 0 list</div>
                    <div class="flex items-center gap-4 text-gray-900">
                        <button class="flex items-center gap-2 grayscale hover:grayscale-0"><i class="bi bi-chevron-left"></i> Prev</button>
                        <div class="flex items-center gap-3">
                            <span class="bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-100">1</span>
                            <span class="text-gray-400">of 1</span>
                        </div>
                        <button class="flex items-center gap-2 grayscale hover:grayscale-0">Next <i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        let allKYC = [];
        let currentStatusFilter = 'all';

        async function fetchKYC() {
             try {
                const res = await fetch('../api/admin/kyc_list.php', { 
                    headers: { 'Authorization': `Bearer ${token}` } 
                });
                const data = await res.json();
                
                if (data.success) {
                    allKYC = data.data.kyc || [];
                    renderKYC();
                }
            } catch (err) {
                console.error(err);
            }
        }

        function renderKYC() {
            const tbody = document.getElementById('kyc-table');
            const emptyState = document.getElementById('empty-state');
            tbody.innerHTML = '';

            const filtered = allKYC.filter(k => {
                const matchesStatus = currentStatusFilter === 'all' || k.status === currentStatusFilter;
                const search = document.getElementById('search-input').value.toLowerCase();
                const userName = `${k.first_name} ${k.last_name}`.toLowerCase();
                return matchesStatus && (!search || userName.includes(search) || k.email.toLowerCase().includes(search));
            });

            if (filtered.length === 0) {
                emptyState.classList.remove('hidden');
                document.getElementById('pagination-info').textContent = 'Showing 0 instances';
                return;
            }

            emptyState.classList.add('hidden');
            filtered.forEach(k => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50/50 transition-colors group';
                
                let statusBadge = 'bg-yellow-50 text-yellow-500';
                if(k.status === 'approved') statusBadge = 'bg-green-50 text-green-500';
                if(k.status === 'rejected') statusBadge = 'bg-red-50 text-red-500';

                tr.innerHTML = `
                    <td class="py-5 px-8">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center font-bold text-gray-400 overflow-hidden">
                                ${k.avatar ? `<img src="${k.avatar}" class="w-full h-full object-cover">` : k.first_name[0]}
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">${k.first_name} ${k.last_name}</div>
                                <div class="text-[12px] text-gray-400">${k.email}</div>
                            </div>
                        </div>
                    </td>
                    <td class="py-5 px-8">
                        <div class="font-bold text-gray-900">${k.country}</div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-tighter">${k.identity_type}</div>
                    </td>
                    <td class="py-5 px-8">
                        <div class="flex items-center gap-2">
                             <a href="../${k.id_front}" target="_blank" class="w-10 h-10 rounded-lg bg-gray-50 border border-gray-100 overflow-hidden block hover:ring-2 ring-primary transition-all">
                                <img src="../${k.id_front}" class="w-full h-full object-cover">
                            </a>
                            <a href="../${k.id_back}" target="_blank" class="w-10 h-10 rounded-lg bg-gray-50 border border-gray-100 overflow-hidden block hover:ring-2 ring-primary transition-all">
                                <img src="../${k.id_back}" class="w-full h-full object-cover">
                            </a>
                            <a href="../${k.selfie}" target="_blank" class="w-10 h-10 rounded-lg bg-gray-50 border border-gray-100 overflow-hidden block hover:ring-2 ring-primary transition-all">
                                <img src="../${k.selfie}" class="w-full h-full object-cover">
                            </a>
                        </div>
                    </td>
                    <td class="py-5 px-8 text-center uppercase">
                        <span class="px-3 py-1.5 rounded-full text-[11px] font-bold ${statusBadge}">${k.status}</span>
                    </td>
                    <td class="py-5 px-8 text-right">
                        ${k.status === 'pending' ? `
                            <div class="flex justify-end gap-2">
                                <button onclick="updateKYC(${k.id}, 'reject', this)" class="w-9 h-9 flex items-center justify-center rounded-xl border border-red-50 text-red-400 hover:bg-red-50 transition-colors"><i class="bi bi-x-lg"></i></button>
                                <button onclick="updateKYC(${k.id}, 'approve', this)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-green-500 text-white hover:bg-green-600 shadow-sm transition-colors"><i class="bi bi-check-lg"></i></button>
                            </div>
                        ` : `
                            <button class="p-2 text-gray-300 hover:text-gray-900"><i class="bi bi-three-dots-vertical"></i></button>
                        `}
                    </td>
                `;
                tbody.appendChild(tr);
            });
            document.getElementById('pagination-info').textContent = `Showing ${filtered.length} out of ${allKYC.length} instances`;
        }

        // Filtering logic
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(b => {
                    b.classList.remove('bg-white', 'shadow-sm', 'text-gray-900', 'active');
                    b.classList.add('text-gray-400');
                });
                btn.classList.add('bg-white', 'shadow-sm', 'text-gray-900', 'active');
                btn.classList.remove('text-gray-400');
                currentStatusFilter = btn.dataset.status;
                renderKYC();
            });
        });

        document.getElementById('search-input').addEventListener('input', renderKYC);

        async function updateKYC(kycId, action, btn) {
            if(!confirm(`Are you sure you want to ${action} this request?`)) return;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>';
            btn.disabled = true;

            try {
                 const res = await fetch(`../api/admin/${action === 'approve' ? 'approve_kyc.php' : 'reject_kyc.php'}`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ kyc_id: kycId, admin_note: action === 'reject' ? 'Rejected by admin review' : 'Verified by admin' })
                });
                const data = await res.json();
                if (data.success) { fetchKYC(); }
                else { alert(data.message); btn.innerHTML = originalHTML; btn.disabled = false; }
            } catch (err) {
                console.error(err);
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        }

        fetchKYC();
    </script>
    <?php if (isset($_SESSION['jwt_token'])): ?>
    <script>
        localStorage.setItem('jwt_token', '<?= $_SESSION['jwt_token'] ?>');
    </script>
    <?php endif; ?>
    <script src="js/sidebar.js"></script>
</body>
</html>
