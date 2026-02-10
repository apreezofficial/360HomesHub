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
<body class="bg-white min-h-screen">
    <div class="flex min-h-screen">
        <aside class="w-[240px] fixed h-full bg-white z-50"></aside>

        <main class="flex-1 ml-[240px] bg-gray-50 min-h-screen p-6">
            <!-- Top Nav -->
            <div class="flex justify-between items-center mb-8">
                <div class="text-[13px] text-gray-400 font-medium">
                    Users / <span class="text-gray-900" id="page-breadcrumb">Details</span>
                </div>
                <div class="flex items-center gap-3 w-full max-w-[400px]">
                    <div class="flex items-center gap-3 bg-white px-4 py-2.5 rounded-xl border border-border w-full">
                        <i class="bi bi-search text-gray-400"></i>
                        <input type="text" placeholder="Search by users, Id..." class="w-full outline-none text-[15px] bg-transparent">
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900" id="page-title">User Management</h1>
                <div class="relative">
                     <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center cursor-pointer">
                        <i class="bi bi-bell text-xl text-gray-600"></i>
                    </div>
                </div>
            </div>

            <div class="mb-8 flex gap-4">
                <div class="relative inline-block">
                    <select id="role-filter" class="appearance-none bg-white border border-border px-4 py-2 rounded-lg text-sm font-medium focus:outline-none pr-10 cursor-pointer text-gray-900 min-w-[150px]">
                        <option value="all">All Users</option>
                        <option value="guest">Guests</option>
                        <option value="host">Hosts</option>
                        <option value="admin">Admins</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 pointer-events-none text-xs"></i>
                </div>
                
                <div class="relative inline-block">
                    <select id="date-filter" class="appearance-none bg-white border border-border px-4 py-2 rounded-lg text-sm font-medium focus:outline-none pr-10 cursor-pointer text-gray-900">
                        <option value="30">Last 30 days</option>
                        <option value="7">Last 7 days</option>
                        <option value="all">All time</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 pointer-events-none text-xs"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-border overflow-hidden min-h-[500px]">
                <table class="w-full text-left">
                    <thead class="bg-white border-b border-border">
                        <tr class="text-[13px] text-text-secondary">
                            <th class="py-4 px-6 font-medium">User profile</th>
                            <th class="py-4 px-6 font-medium">Role</th>
                            <th class="py-4 px-6 font-medium">Contact info</th>
                            <th class="py-4 px-6 font-medium">Status</th>
                            <th class="py-4 px-6 font-medium">Joined date</th>
                            <th class="py-4 px-6 font-medium text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="user-table" class="divide-y divide-border">
                        <!-- Users injected here -->
                    </tbody>
                </table>
                <div id="empty-state" class="hidden flex flex-col items-center justify-center py-20">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                        <i class="bi bi-people text-2xl text-gray-300"></i>
                    </div>
                    <p class="text-gray-500 text-sm">No users found</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        const urlParams = new URLSearchParams(window.location.search);
        const initialType = urlParams.get('type'); 
        
        // Initialize role filter if type param exists (map guests->guest, hosts->host)
        const roleMap = { 'guests': 'guest', 'hosts': 'host', 'admins': 'admin' };
        if (initialType && roleMap[initialType]) {
             document.getElementById('role-filter').value = roleMap[initialType];
        }

        let allUsers = []; // Store all users to avoid re-fetching

        document.getElementById('date-filter').addEventListener('change', renderUsers);
        document.getElementById('role-filter').addEventListener('change', renderUsers);

        async function fetchUsers() {
            try {
                const res = await fetch('../api/admin/users.php', { headers: { 'Authorization': `Bearer ${token}` } });
                const data = await res.json();
                
                if (data.success) {
                    allUsers = data.data.users;
                    renderUsers();
                }
            } catch (err) {
                console.error(err);
            }
        }

        function renderUsers() {
            const tbody = document.getElementById('user-table');
            const emptyState = document.getElementById('empty-state');
            const dateFilter = document.getElementById('date-filter').value;
            const roleFilter = document.getElementById('role-filter').value;
            
            tbody.innerHTML = '';

            let users = [...allUsers];
            
            // Filter by role
            if (roleFilter !== 'all') {
                users = users.filter(u => u.role === roleFilter);
            }

            // Filter by date
            if (dateFilter !== 'all') {
                const now = new Date();
                const pastDate = new Date();
                pastDate.setDate(now.getDate() - parseInt(dateFilter));
                
                users = users.filter(u => {
                    const created = new Date(u.created_at.replace(' ', 'T'));
                    return created >= pastDate;
                });
            }

            if (users.length === 0) {
                emptyState.classList.remove('hidden');
                return;
            }
            emptyState.classList.add('hidden');

            users.forEach(u => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50 transition-colors text-[14px] group/row';
                
                // Profile Pic Logic
                let avatarHtml = '';
                if (u.profile_pic && u.profile_pic !== 'null') {
                    avatarHtml = `<img src="${u.profile_pic.startsWith('http') ? u.profile_pic : '../' + u.profile_pic}" class="w-10 h-10 rounded-full object-cover border border-gray-200" alt="${u.first_name}">`;
                } else {
                    avatarHtml = `<div class="w-10 h-10 rounded-full bg-slate-200 border border-white shadow-sm flex items-center justify-center text-gray-500 font-bold text-sm">
                                    ${u.first_name ? u.first_name[0].toUpperCase() : 'U'}
                                  </div>`;
                }

                tr.innerHTML = `
                    <td class="py-4 px-6">
                        <div class="flex items-center gap-3">
                            ${avatarHtml}
                            <div>
                                <div class="font-bold text-gray-900">${u.first_name || 'N/A'} ${u.last_name || ''}</div>
                                <div class="text-xs text-gray-400">ID: USR-${u.id}</div>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 px-6">
                        <span class="capitalize bg-slate-100 text-slate-600 px-2.5 py-1 rounded-md text-xs font-bold border border-slate-200">${u.role || 'User'}</span>
                    </td>
                    <td class="py-4 px-6 text-gray-600">
                        <div class="font-medium text-gray-700">${u.email}</div>
                        <div class="text-xs text-gray-400 mt-0.5">${u.phone || 'No phone'}</div>
                    </td>
                    <td class="py-4 px-6">
                        <span class="px-2.5 py-1 rounded-full text-[12px] font-bold border ${u.is_verified ? 'bg-green-50 text-green-600 border-green-100' : 'bg-yellow-50 text-yellow-600 border-yellow-100'}">
                            ${u.is_verified ? 'Verified' : 'Pending'}
                        </span>
                    </td>
                    <td class="py-4 px-6 text-gray-500 text-sm whitespace-nowrap">${new Date(u.created_at).toLocaleDateString()}</td>
                    <td class="py-4 px-6 text-right relative">
                        <div class="relative inline-block text-left">
                            <button onclick="toggleDropdown(${u.id})" class="p-2 rounded-full hover:bg-gray-100 transition-colors text-gray-400 hover:text-primary focus:outline-none">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <div id="dropdown-${u.id}" class="hidden absolute right-0 mt-2 w-40 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-20 overflow-hidden transform origin-top-right transition-all">
                                <div class="py-1">
                                    <a href="user_profile.php?id=${u.id}" class="group flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary transition-colors">
                                        <i class="bi bi-person mr-3 text-gray-400 group-hover:text-primary"></i>
                                        View Profile
                                    </a>
                                    <a href="#" class="group flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-orange-500 transition-colors">
                                        <i class="bi bi-pencil mr-3 text-gray-400 group-hover:text-orange-500"></i>
                                        Edit Details
                                    </a>
                                    <a href="#" onclick="alert('Suspend logic placeholder')" class="group flex items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-gray-100">
                                        <i class="bi bi-ban mr-3 text-red-400 group-hover:text-red-600"></i>
                                        Suspend User
                                    </a>
                                </div>
                            </div>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('td.text-right')) {
                    document.querySelectorAll('[id^="dropdown-"]').forEach(el => el.classList.add('hidden'));
                }
            });
        }

        function toggleDropdown(userId) {
            // Hide all other dropdowns
            document.querySelectorAll('[id^="dropdown-"]').forEach(el => {
                if (el.id !== `dropdown-${userId}`) el.classList.add('hidden');
            });
            
            const dropdown = document.getElementById(`dropdown-${userId}`);
            dropdown.classList.toggle('hidden');
            
            // Prevent event bubbling if needed, though click listener handles outside clicks
            window.event.stopPropagation();
        }
        
        // Initial fetch
        fetchUsers();
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
