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
    <title>Listings | Admin Dashboard</title>
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
                    Management / <span class="text-gray-900">Listings</span>
                </div>
                <div class="flex items-center gap-4">
                     <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center cursor-pointer">
                        <i class="bi bi-bell text-xl text-gray-600"></i>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Property Listings</h1>
                    <p class="text-sm text-text-secondary mt-1">Manage and approve properties</p>
                </div>
                <div class="flex gap-3">
                    <div class="relative inline-block">
                        <select id="status-filter" class="appearance-none bg-white border border-border px-4 py-2.5 rounded-xl text-sm font-medium focus:outline-none pr-10 cursor-pointer min-w-[150px]">
                            <option value="all">All listings</option>
                            <option value="published">Published</option>
                            <option value="draft">Drafts</option>
                            <option value="archived">Archived</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-border overflow-hidden min-h-[500px]">
                <table class="w-full text-left">
                    <thead class="bg-white border-b border-border">
                        <tr class="text-[13px] uppercase text-text-secondary">
                            <th class="py-4 px-6 font-medium">Property</th>
                            <th class="py-4 px-6 font-medium">Location</th>
                            <th class="py-4 px-6 font-medium">Host</th>
                            <th class="py-4 px-6 font-medium">Price</th>
                            <th class="py-4 px-6 font-medium">Status</th>
                            <th class="py-4 px-6 font-medium text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="listings-table" class="divide-y divide-border">
                        <!-- Loaded via JS -->
                    </tbody>
                </table>
                <div id="empty-state" class="hidden flex flex-col items-center justify-center py-20">
                     <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                        <i class="bi bi-houses text-2xl text-gray-300"></i>
                    </div>
                    <p class="text-gray-500 text-sm">No listings found</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        let allProperties = [];
        
        document.getElementById('status-filter').addEventListener('change', renderListings);

        async function fetchListings() {
            try {
                const res = await fetch('../api/admin/properties.php', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await res.json();
                
                if (data.success) {
                    allProperties = data.data.properties;
                    renderListings();
                }

            } catch (err) {
                console.error(err);
            }
        }

        function renderListings() {
            const tbody = document.getElementById('listings-table');
            const emptyState = document.getElementById('empty-state');
            const statusFilter = document.getElementById('status-filter').value;

            tbody.innerHTML = '';

            let properties = [...allProperties];
            
            if (statusFilter !== 'all') {
                properties = properties.filter(p => p.status === statusFilter);
            }

            if (properties.length === 0) {
                emptyState.classList.remove('hidden');
                return;
            }
            emptyState.classList.add('hidden');

            properties.forEach(p => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50 transition-colors text-[14px]';

                let statusBadge = 'bg-gray-50 text-gray-600 border-gray-200';
                if (p.status === 'published' || p.status === 'active') statusBadge = 'bg-green-50 text-green-600 border-green-200';
                if (p.status === 'archived' || p.status === 'rejected') statusBadge = 'bg-red-50 text-red-600 border-red-200';
                if (p.status === 'draft') statusBadge = 'bg-yellow-50 text-yellow-600 border-yellow-200';

                tr.innerHTML = `
                    <td class="py-4 px-6">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden flex-shrink-0">
                               <img src="../api/placeholder_image.php" class="w-full h-full object-cover" onerror="this.style.display='none'">
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">${p.name || 'Untitled Property'}</div>
                                <div class="text-xs text-gray-400 capitalize">${p.type}</div>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 px-6 text-gray-600">
                        <div>${p.city}, ${p.state}</div>
                        <div class="text-xs text-gray-400">${p.country}</div>
                    </td>
                    <td class="py-4 px-6">
                        <div class="font-medium text-gray-900">${p.first_name} ${p.last_name}</div>
                        <div class="text-xs text-gray-500">${p.host_email}</div>
                    </td>
                    <td class="py-4 px-6 font-bold text-gray-900">₦${parseFloat(p.price).toLocaleString()}</td>
                    <td class="py-4 px-6">
                        <span class="px-2.5 py-1 rounded-md text-[12px] font-bold uppercase border ${statusBadge}">${p.status}</span>
                    </td>
                    <td class="py-4 px-6 text-right">
                        <a href="property_view.php?id=${p.id}" class="text-primary hover:text-blue-700 font-medium text-sm">View</a>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
        
        fetchListings();
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
