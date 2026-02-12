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
    <title>User Management | Admin Dashboard</title>
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
                    Administration / <span class="text-gray-900 font-medium">User Directory</span>
                </div>
                <div class="flex-1 max-w-[500px] mx-8">
                    <div class="relative">
                        <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="user-search" placeholder="Search by name, user ID or email..." 
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
                    <h1 class="text-[32px] font-bold text-gray-900 mb-2">User directory</h1>
                    <p class="text-gray-400 text-[15px]">Comprehensive view of all users and their platform metrics.</p>
                </div>
                <button class="bg-[#005a92] text-white px-6 py-3 rounded-xl font-bold text-[14px] flex items-center gap-2 shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all">
                    <i class="bi bi-download"></i> Export CSV
                </button>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="bg-white p-8 rounded-[24px] border border-gray-100 shadow-sm">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-primary/5 flex items-center justify-center text-primary shadow-inner">
                             <i class="bi bi-person text-[20px]"></i>
                        </div>
                        <span class="text-[15px] font-bold text-gray-500 uppercase tracking-widest">Total users</span>
                    </div>
                    <div class="text-[36px] font-bold text-gray-900 mb-1" id="stat-total">0</div>
                    <div class="text-[13px] font-bold text-green-500 flex items-center gap-1">
                        <i class="bi bi-graph-up"></i> +12% this month
                    </div>
                </div>
                <div class="bg-white p-8 rounded-[24px] border border-gray-100 shadow-sm">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center text-orange-500 shadow-inner">
                             <i class="bi bi-person-badge text-[20px]"></i>
                        </div>
                        <span class="text-[15px] font-bold text-gray-500 uppercase tracking-widest">Active Hosts</span>
                    </div>
                    <div class="text-[36px] font-bold text-gray-900 mb-1" id="stat-hosts">0</div>
                    <div class="text-[13px] font-bold text-green-500 flex items-center gap-1">
                        <i class="bi bi-graph-up"></i> +8% growth
                    </div>
                </div>
                <div class="bg-white p-8 rounded-[24px] border border-gray-100 shadow-sm">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-500 shadow-inner">
                             <i class="bi bi-people text-[20px]"></i>
                        </div>
                        <span class="text-[15px] font-bold text-gray-500 uppercase tracking-widest">Total Guests</span>
                    </div>
                    <div class="text-[36px] font-bold text-gray-900 mb-1" id="stat-guests">0</div>
                    <div class="text-[13px] font-bold text-green-500 flex items-center gap-1">
                        <i class="bi bi-graph-up"></i> +15% bookings
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-2 bg-gray-100 p-1 rounded-xl">
                    <button class="px-5 py-2.5 rounded-lg text-[13px] font-bold filter-btn active bg-white shadow-sm text-gray-900" data-filter="all">All Users</button>
                    <button class="px-5 py-2.5 rounded-lg text-[13px] font-bold filter-btn text-gray-400 hover:text-gray-600" data-filter="host">Hosts</button>
                    <button class="px-5 py-2.5 rounded-lg text-[13px] font-bold filter-btn text-gray-400 hover:text-gray-600" data-filter="guest">Guests</button>
                    <button class="px-5 py-2.5 rounded-lg text-[13px] font-bold filter-btn text-gray-400 hover:text-gray-600" data-filter="verified">Verified</button>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-[14px] text-gray-400">Sort by:</span>
                    <select id="user-sort" class="bg-transparent border-none text-[14px] font-bold text-gray-900 focus:ring-0 cursor-pointer">
                        <option value="newest">Recently Joined</option>
                        <option value="oldest">Oldest First</option>
                        <option value="name">Alphabetical</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 bg-gray-50/30">
                            <th class="py-5 px-8">User Instance</th>
                            <th class="py-5 px-8">Account Details</th>
                            <th class="py-5 px-8">Platform Role</th>
                            <th class="py-5 px-8">Activity Metrics</th>
                            <th class="py-5 px-8">Verification</th>
                            <th class="py-5 px-8 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="user-table" class="divide-y divide-gray-50 text-[13px]">
                        <!-- Users injected here -->
                    </tbody>
                </table>
                <div id="empty-state" class="hidden flex flex-col items-center justify-center py-32">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6 text-gray-200">
                        <i class="bi bi-people text-[40px]"></i>
                    </div>
                    <p class="text-gray-400 font-medium">No users found matching your search.</p>
                </div>
                
                <!-- Pagination -->
                <div class="p-6 border-t border-gray-50 flex justify-between items-center text-[13px] text-gray-500 font-medium font-outfit">
                    <div id="pagination-info">Showing 0 users</div>
                    <div class="flex items-center gap-4">
                        <button class="flex items-center gap-2 grayscale hover:grayscale-0 transition-all font-bold text-gray-900 border border-gray-100 px-4 py-2 rounded-xl"><i class="bi bi-chevron-left"></i> Previous</button>
                        <div class="flex items-center gap-2">
                            <span class="bg-gray-100 px-3.5 py-1.5 rounded-lg text-gray-900 border border-gray-100 font-bold">1</span>
                            <span class="text-gray-400 font-medium">of 1</span>
                        </div>
                        <button class="flex items-center gap-2 grayscale hover:grayscale-0 transition-all font-bold text-gray-900 border border-gray-100 px-4 py-2 rounded-xl">Next <i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        let allUsers = [];
        let currentFilter = 'all';

        async function fetchUsers() {
            try {
                const res = await fetch('../api/admin/users.php', { headers: { 'Authorization': `Bearer ${token}` } });
                const data = await res.json();
                
                if (data.success) {
                    allUsers = data.data.users || [];
                    
                    // Update stats
                    document.getElementById('stat-total').textContent = data.data.stats.total_users;
                    document.getElementById('stat-hosts').textContent = data.data.stats.host_count;
                    document.getElementById('stat-guests').textContent = data.data.stats.guest_count;
                    
                    renderUsers();
                }
            } catch (err) {
                console.error(err);
            }
        }

        function renderUsers() {
            const tbody = document.getElementById('user-table');
            const emptyState = document.getElementById('empty-state');
            const searchVal = document.getElementById('user-search').value.toLowerCase();
            
            tbody.innerHTML = '';
            
            let filtered = allUsers.filter(u => {
                const name = `${u.first_name} ${u.last_name}`.toLowerCase();
                const matchesSearch = !searchVal || name.includes(searchVal) || u.email.toLowerCase().includes(searchVal) || u.id.toString().includes(searchVal);
                
                let matchesFilter = true;
                if (currentFilter === 'host') matchesFilter = u.role === 'host';
                else if (currentFilter === 'guest') matchesFilter = u.role === 'guest';
                else if (currentFilter === 'verified') matchesFilter = u.is_verified == 1;
                
                return matchesSearch && matchesFilter;
            });

            document.getElementById('pagination-info').textContent = `Showing ${filtered.length} of ${allUsers.length} platform users`;

            if (filtered.length === 0) {
                emptyState.classList.remove('hidden');
                return;
            }
            emptyState.classList.add('hidden');

            filtered.forEach(u => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50/50 transition-colors group';
                
                const name = `${u.first_name || ''} ${u.last_name || ''}`.trim() || 'N/A';
                const avatar = u.avatar ? 
                    `<img src="${u.avatar}" class="w-full h-full object-cover">` :
                    `<div class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-400 font-bold uppercase">${(u.first_name || 'U')[0]}</div>`;

                const roleBadge = u.role === 'host' ? 'bg-orange-50 text-orange-600' : 'bg-blue-50 text-blue-600';
                
                let kycStatus = 'Not Started';
                let kycClass = 'bg-gray-50 text-gray-400';
                if (u.is_verified == 1) {
                    kycStatus = 'Verified';
                    kycClass = 'bg-green-50 text-green-500';
                } else if (u.onboarding_step > 1) {
                    kycStatus = 'Pending';
                    kycClass = 'bg-yellow-50 text-yellow-600';
                }

                tr.innerHTML = `
                    <td class="py-5 px-8">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full overflow-hidden border border-gray-100 shadow-sm flex-shrink-0">
                                ${avatar}
                            </div>
                            <div>
                                <div class="font-bold text-gray-900 group-hover:text-primary transition-colors">${name}</div>
                                <div class="text-[11px] text-gray-400 font-bold tracking-widest uppercase">ID: ${u.id.toString().padStart(6, '0')}</div>
                            </div>
                        </div>
                    </td>
                    <td class="py-5 px-8">
                        <div class="text-gray-900 font-bold">${u.email}</div>
                        <div class="text-[11px] text-gray-400 font-medium">${u.phone || 'No phone added'}</div>
                    </td>
                    <td class="py-5 px-8">
                        <span class="px-3 py-1.5 rounded-lg font-bold uppercase text-[11px] ${roleBadge}">${u.role}</span>
                    </td>
                    <td class="py-5 px-8">
                        <div class="font-bold text-gray-900">${u.role === 'host' ? u.listing_count : u.booking_count} ${u.role === 'host' ? 'Properties' : 'Reservations'}</div>
                        <div class="text-[11px] text-gray-400 font-medium uppercase tracking-tight">Active Activity</div>
                    </td>
                    <td class="py-5 px-8">
                        <span class="px-3 py-1.5 rounded-full text-[11px] font-bold uppercase ${kycClass}">${kycStatus}</span>
                    </td>
                    <td class="py-5 px-8 text-right relative">
                        <button onclick="toggleDropdown(${u.id})" class="p-2 rounded-lg text-gray-300 hover:text-gray-900 transition-colors">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <div id="dropdown-${u.id}" class="hidden absolute right-16 top-10 w-48 rounded-xl shadow-xl bg-white border border-gray-100 z-50 overflow-hidden text-left">
                            <div class="py-1">
                                <a href="user_profile.php?id=${u.id}" class="flex items-center px-4 py-3 text-[13px] text-gray-700 hover:bg-gray-50 font-bold">
                                    <i class="bi bi-eye mr-3 text-gray-400"></i> View Profile
                                </a>
                                <button class="w-full text-left flex items-center px-4 py-3 text-[13px] text-red-500 hover:bg-red-50 font-bold border-t border-gray-50">
                                    <i class="bi bi-slash-circle mr-3"></i> Suspend Access
                                </button>
                            </div>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Search listener
        document.getElementById('user-search').addEventListener('input', renderUsers);

        // Filter listeners
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(b => {
                    b.classList.remove('active', 'bg-white', 'shadow-sm', 'text-gray-900');
                    b.classList.add('text-gray-400');
                });
                btn.classList.add('active', 'bg-white', 'shadow-sm', 'text-gray-900');
                btn.classList.remove('text-gray-400');
                currentFilter = btn.dataset.filter;
                renderUsers();
            });
        });

        function toggleDropdown(id) {
            document.querySelectorAll('[id^="dropdown-"]').forEach(el => {
                if (el.id !== `dropdown-${id}`) el.classList.add('hidden');
            });
            const d = document.getElementById(`dropdown-${id}`);
            d.classList.toggle('hidden');
            window.event.stopPropagation();
        }

        document.addEventListener('click', () => {
            document.querySelectorAll('[id^="dropdown-"]').forEach(el => el.classList.add('hidden'));
        });

        fetchUsers();
    </script>
    <?php if (isset($_SESSION['jwt_token'])): ?>
    <script>
        localStorage.setItem('jwt_token', '<?= $_SESSION['jwt_token'] ?>');
    </script>
    <?php endif; ?>
    <script src="js/sidebar.js"></script>
</body>
</html>
