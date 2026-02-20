<?php
session_start();
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/jwt.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | Admin Dashboard</title>
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
            <!-- Breadcrumbs -->
            <div class="text-[14px] text-gray-400 mb-8 flex items-center gap-2">
                <a href="users.php" class="hover:text-gray-900 transition-colors">Users</a>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-medium" id="breadcrumb-name">...</span>
            </div>

            <!-- Header Section -->
            <div class="flex justify-between items-start mb-10">
                <div class="flex items-center gap-5">
                    <div id="user-avatar" class="w-20 h-20 rounded-full bg-gray-100 border-2 border-white shadow-sm flex items-center justify-center overflow-hidden">
                        <!-- Avatar injected here -->
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h1 class="text-[28px] font-bold text-gray-900" id="header-name">...</h1>
                            <span id="role-badge" class="px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-wider"></span>
                            <span id="verified-badge-top" class="px-3 py-1 bg-green-50 text-green-500 rounded-full text-[12px] font-bold hidden">Verified</span>
                        </div>
                        <div class="text-[14px] text-gray-400 flex items-center gap-2">
                            <span id="header-email">...</span>
                            <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                            <span>Joined <span id="header-joined">...</span></span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button class="px-6 py-3 border border-red-100 text-red-500 bg-white rounded-xl font-bold text-[14px] hover:bg-red-50 transition-all">Suspend Account</button>
                    <button class="px-6 py-3 bg-[#005a92] text-white rounded-xl font-bold text-[14px] shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all flex items-center gap-2">
                        Send message
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-8">
                <!-- Main Content (Left + Center) -->
                <div class="col-span-8 space-y-8">
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-3 gap-6" id="stats-grid">
                        <!-- Stats injected here based on role -->
                    </div>

                    <!-- Verification Status -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-8">
                        <h3 class="text-[18px] font-bold text-gray-900 mb-6">Verification details</h3>
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50">
                                    <th class="pb-4">Credential type</th>
                                    <th class="pb-4 text-center">Current Status</th>
                                    <th class="pb-4">Timeline</th>
                                    <th class="pb-4 text-right">Reference docs</th>
                                </tr>
                            </thead>
                            <tbody class="text-[14px]" id="kyc-table">
                                <!-- Documents injected here -->
                            </tbody>
                        </table>
                        <div id="kyc-empty" class="hidden py-10 text-center text-gray-400 font-medium">No KYC records on file.</div>
                    </div>

                    <!-- Booking History -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm overflow-hidden">
                        <div class="p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/20">
                            <h3 class="text-[18px] font-bold text-gray-900">Platform activity logs</h3>
                        </div>
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 bg-gray-50/30">
                                    <th class="py-4 px-8">Instance</th>
                                    <th class="py-4 px-8">Schedule</th>
                                    <th class="py-4 px-8 text-center">Amount</th>
                                    <th class="py-4 px-8 text-center">Ledger Status</th>
                                    <th class="py-4 px-8 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-[13px]" id="booking-history-table">
                                <!-- Bookings injected here -->
                            </tbody>
                        </table>
                        <div id="booking-empty" class="hidden py-24 text-center text-gray-400">
                            <i class="bi bi-calendar-x text-[40px] block mb-4"></i>
                            <p class="font-medium">No transaction or booking history found.</p>
                        </div>
                        <div class="p-6 border-t border-gray-50 flex justify-between items-center text-[13px] text-gray-500 font-medium">
                            <div id="pagination-info">Showing 0 activity logs</div>
                            <div class="flex items-center gap-4 text-gray-900">
                                <button class="flex items-center gap-2 grayscale hover:grayscale-0 font-bold border border-gray-100 px-4 py-2 rounded-xl"><i class="bi bi-chevron-left"></i> Previous</button>
                                <button class="flex items-center gap-2 grayscale hover:grayscale-0 font-bold border border-gray-100 px-4 py-2 rounded-xl">Next <i class="bi bi-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Sidebar) -->
                <div class="col-span-4 space-y-8">
                    <!-- Admin Action -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-8 text-outfit">
                        <h3 class="text-[18px] font-bold text-gray-900 mb-8">Admin Control Hub</h3>
                        <div class="space-y-8">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-red-500 shadow-sm">
                                        <i class="bi bi-slash-circle text-[18px]"></i>
                                    </div>
                                    <div>
                                        <div class="text-[15px] font-bold text-gray-900">Freeze Bookings</div>
                                        <div class="text-[12px] text-gray-400 mt-0.5 leading-tight">Restrict user from making new reservation</div>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#005a92]"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center text-orange-400 shadow-sm">
                                        <i class="bi bi-shield-lock text-[18px]"></i>
                                    </div>
                                    <div>
                                        <div class="text-[15px] font-bold text-gray-900">Flag for Review</div>
                                        <div class="text-[12px] text-gray-400 mt-0.5 leading-tight">Mark profile for compliance audit</div>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#005a92]"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Security Insights -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-8">
                        <h3 class="text-[18px] font-bold text-gray-900 mb-8">Security Insights</h3>
                        <div class="space-y-8">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-300">
                                    <i class="bi bi-hdd-network text-[18px]"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-1">
                                        <div class="text-[14px] font-bold text-gray-900">Password changed</div>
                                        <div class="text-[11px] text-gray-400 font-bold uppercase tracking-widest">2h ago</div>
                                    </div>
                                    <div class="text-[12px] text-gray-400 font-medium">Credential update from OS: Windows 11</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-300">
                                    <i class="bi bi-geo-alt text-[18px]"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-1">
                                        <div class="text-[14px] font-bold text-gray-900">New login location</div>
                                        <div class="text-[11px] text-gray-400 font-bold uppercase tracking-widest">Yesterday</div>
                                    </div>
                                    <div class="text-[12px] text-gray-400 font-medium">Lagos, Nigeria (IP: 105.112.5.187)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';
        
        const urlParams = new URLSearchParams(window.location.search);
        const userId = urlParams.get('id');

        if (!userId) window.location.href = 'users.php';

        async function fetchUserDetails() {
            try {
                const res = await fetch(`../api/admin/user_detail.php?id=${userId}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await res.json();
                
                if (data.success) {
                    const u = data.data.user;
                    const stats = data.data.stats;
                    const bookingHistory = data.data.booking_history;
                    const kycRecords = data.data.kyc;

                    const name = `${u.first_name || ''} ${u.last_name || ''}`.trim() || 'Anonymous User';
                    document.getElementById('breadcrumb-name').textContent = name;
                    document.getElementById('header-name').textContent = name;
                    document.getElementById('header-email').textContent = u.email;
                    document.getElementById('header-joined').textContent = new Date(u.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });

                    // Avatar
                    const avatarContainer = document.getElementById('user-avatar');
                    if (u.avatar) {
                        avatarContainer.innerHTML = `<img src="../${u.avatar}" class="w-full h-full object-cover">`;
                    } else {
                        avatarContainer.innerHTML = `<span class="text-[24px] font-bold text-gray-300 uppercase">${(u.first_name || 'U')[0]}</span>`;
                    }

                    // Role Badge
                    const rb = document.getElementById('role-badge');
                    rb.textContent = u.role;
                    rb.className = `px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-wider ${u.role === 'host' ? 'bg-orange-50 text-orange-500' : 'bg-blue-50 text-blue-500'}`;

                    if (u.is_verified == 1) document.getElementById('verified-badge-top').classList.remove('hidden');

                    // Stats Grid
                    const statsGrid = document.getElementById('stats-grid');
                    if (u.role === 'host') {
                        statsGrid.innerHTML = `
                            ${statCard('Host Revenue', '₦' + parseFloat(stats.total_earnings || 0).toLocaleString(), '+12.5% increment', 'bi-wallet2')}
                            ${statCard('Public Listings', stats.listings, 'Total active properties', 'bi-building')}
                            ${statCard('Bookings Gained', stats.bookings_host, 'Successful check-ins', 'bi-calendar-check')}
                        `;
                    } else {
                        statsGrid.innerHTML = `
                            ${statCard('Total Bookings', stats.bookings_guest, 'Total stay history', 'bi-house')}
                            ${statCard('Wallet Spending', '₦' + parseFloat(stats.total_spent || 0).toLocaleString(), 'Average NGN20k/stay', 'bi-wallet2')}
                            ${statCard('Engagement Status', 'Active', 'Last seen: ' + (stats.last_booking_date ? new Date(stats.last_booking_date).toLocaleDateString() : 'Never'), 'bi-person-check')}
                        `;
                    }

                    // KYC Table
                    const kycTbody = document.getElementById('kyc-table');
                    const kycEmpty = document.getElementById('kyc-empty');
                    kycTbody.innerHTML = '';
                    
                    if (kycRecords && kycRecords.length > 0) {
                        kycRecords.forEach(k => {
                            const tr = document.createElement('tr');
                            tr.className = 'border-b border-gray-50 hover:bg-gray-50/30 transition-all';
                            tr.innerHTML = `
                                <td class="py-5 font-bold text-gray-900 group-hover:text-primary capitalize">${k.identity_type || 'Identity Card'}</td>
                                <td class="py-5 text-center">
                                    <span class="px-3 py-1.5 rounded-full text-[11px] font-bold uppercase ${k.status === 'approved' ? 'bg-green-50 text-green-500' : 'bg-yellow-50 text-yellow-500'}">${k.status}</span>
                                </td>
                                <td class="py-5 text-gray-400 font-medium">
                                    ${new Date(k.created_at).toLocaleDateString()}
                                </td>
                                <td class="py-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="../${k.id_front}" target="_blank" class="w-10 h-7 rounded bg-gray-100 overflow-hidden border border-gray-100 hover:ring-2 ring-primary transition-all"><img src="../${k.id_front}" class="w-full h-full object-cover"></a>
                                        <a href="../${k.id_back}" target="_blank" class="w-10 h-7 rounded bg-gray-100 overflow-hidden border border-gray-100 hover:ring-2 ring-primary transition-all"><img src="../${k.id_back}" class="w-full h-full object-cover"></a>
                                    </div>
                                </td>
                            `;
                            kycTbody.appendChild(tr);
                        });
                    } else {
                        kycEmpty.classList.remove('hidden');
                    }

                    // Booking History
                    const historyTbody = document.getElementById('booking-history-table');
                    const historyEmpty = document.getElementById('booking-empty');
                    historyTbody.innerHTML = '';
                    
                    if (bookingHistory.length === 0) {
                        historyEmpty.classList.remove('hidden');
                    } else {
                        historyEmpty.classList.add('hidden');
                        bookingHistory.forEach(b => {
                            const tr = document.createElement('tr');
                            tr.className = 'border-b border-gray-50 hover:bg-gray-50/50 transition-colors group';
                            
                            const statusColor = b.status === 'confirmed' ? 'green' : (b.status === 'pending' ? 'yellow' : 'red');

                            tr.innerHTML = `
                                <td class="py-5 px-8">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gray-100 overflow-hidden shrink-0 border border-gray-100 shadow-sm">
                                            <img src="../${b.property_image}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" onerror="this.src='https://via.placeholder.com/100'">
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 group-hover:text-primary transition-colors">${b.property_name || 'Listing Instance'}</div>
                                            <div class="text-[11px] text-gray-400 mt-0.5 font-bold uppercase">BK-${b.id.toString().padStart(5, '0')}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-5 px-8">
                                    <div class="text-gray-900 font-bold">${new Date(b.check_in).toLocaleDateString(undefined, { day: 'numeric', month: 'short' })} - ${new Date(b.check_out).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })}</div>
                                    <div class="text-[11px] text-gray-400">Total duration stay</div>
                                </td>
                                <td class="py-5 px-8 font-bold text-gray-900 text-center">₦${parseFloat(b.total_amount).toLocaleString()}</td>
                                <td class="py-5 px-8 text-center uppercase">
                                    <span class="px-3 py-1.5 rounded-full text-[11px] font-bold bg-${statusColor}-50 text-${statusColor}-500">${b.status}</span>
                                </td>
                                <td class="py-5 px-8 text-right">
                                    <button class="p-2 text-gray-300 hover:text-gray-900"><i class="bi bi-three-dots-vertical"></i></button>
                                </td>
                            `;
                            historyTbody.appendChild(tr);
                        });
                    }
                    document.getElementById('pagination-info').textContent = `Showing ${bookingHistory.length} activity logs`;
                }

            } catch (err) {
                console.error(err);
            }
        }

        function statCard(label, val, sub, icon) {
            return `
                <div class="bg-white p-8 rounded-[24px] border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-primary/5 group-hover:text-primary transition-all">
                             <i class="bi ${icon} text-[20px]"></i>
                        </div>
                        <span class="text-[12px] font-bold text-gray-400 uppercase tracking-widest">${label}</span>
                    </div>
                    <div class="text-[32px] font-bold text-gray-900 mb-1">${val}</div>
                    ${sub ? `<div class="text-[13px] font-bold text-green-500 flex items-center gap-1"><i class="bi bi-graph-up-arrow"></i> ${sub}</div>` : ''}
                </div>
            `;
        }

        fetchUserDetails();
    </script>
    <?php if (isset($_SESSION['jwt_token'])): ?>
    <script>
        localStorage.setItem('jwt_token', '<?= $_SESSION['jwt_token'] ?>');
    </script>
    <?php endif; ?>
    <script src="js/sidebar.js"></script>
</body>
</html>
