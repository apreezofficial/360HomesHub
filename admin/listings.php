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
<body class="bg-[#F9FAFB] min-h-screen font-outfit">
    <div class="flex min-h-screen">
        <aside class="w-[260px] fixed h-full bg-white z-50"></aside>

        <main class="flex-1 ml-[260px] min-h-screen p-8">
            <!-- Top Nav -->
            <div class="flex justify-between items-center mb-10">
                <div class="text-[14px] text-gray-400">
                    Management / <span class="text-gray-900 font-medium">Listings</span>
                </div>
                <div class="flex-1 max-w-[500px] mx-8">
                    <div class="relative">
                        <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="search-input" placeholder="Search for property name, host or location..." 
                               class="w-full bg-white border border-gray-100 rounded-xl py-3 pl-12 pr-4 text-[14px] focus:outline-none focus:ring-2 focus:ring-primary/5 shadow-sm transition-all">
                    </div>
                </div>
                <div class="flex items-center gap-4">
                     <div class="w-10 h-10 rounded-full bg-white border border-gray-100 flex items-center justify-center cursor-pointer shadow-sm">
                        <i class="bi bi-bell text-[18px] text-gray-400"></i>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-[32px] font-bold text-gray-900 mb-2">Property Listings</h1>
                    <p class="text-gray-400 text-[15px]">Manage and review all properties listed by hosts.</p>
                </div>
                <div class="flex gap-2 bg-gray-100 p-1 rounded-xl">
                    <button class="px-6 py-2.5 text-[13px] font-bold rounded-lg bg-white shadow-sm text-gray-900 filter-btn active" data-status="all">All listings</button>
                    <button class="px-6 py-2.5 text-[13px] font-bold text-gray-400 hover:text-gray-600 filter-btn" data-status="published">Published</button>
                    <button class="px-6 py-2.5 text-[13px] font-bold text-gray-400 hover:text-gray-600 filter-btn" data-status="pending">Pending</button>
                    <button class="px-6 py-2.5 text-[13px] font-bold text-gray-400 hover:text-gray-600 filter-btn" data-status="archived">Archived</button>
                </div>
            </div>

            <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 bg-gray-50/30">
                            <th class="py-4 px-8">Property</th>
                            <th class="py-4 px-8">Location</th>
                            <th class="py-4 px-8">Host</th>
                            <th class="py-4 px-8">Price</th>
                            <th class="py-4 px-8 text-center">Status</th>
                            <th class="py-4 px-8 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="listings-table" class="divide-y divide-gray-50 text-[13px]">
                        <!-- Loaded via JS -->
                    </tbody>
                </table>
                <div id="empty-state" class="hidden flex flex-col items-center justify-center py-32">
                     <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                        <i class="bi bi-houses text-[32px] text-gray-200"></i>
                    </div>
                    <p class="text-gray-400 font-medium">No properties match your current filter.</p>
                </div>
                
                <div class="p-6 border-t border-gray-50 flex justify-between items-center text-[13px] text-gray-500 font-medium">
                    <div id="pagination-info">Showing 0 out of 0 list</div>
                    <div class="flex items-center gap-4 text-gray-900">
                        <button class="flex items-center gap-2 grayscale hover:grayscale-0"><i class="bi bi-chevron-left"></i> Prev</button>
                        <div class="flex items-center gap-2">
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

        let allProperties = [];
        let currentStatusFilter = 'all';

        async function fetchListings() {
            try {
                const res = await fetch('../api/admin/properties.php', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await res.json();
                
                if (data.success) {
                    allProperties = data.data.properties || [];
                    renderListings();
                }
            } catch (err) {
                console.error(err);
            }
        }

        function renderListings() {
            const tbody = document.getElementById('listings-table');
            const emptyState = document.getElementById('empty-state');
            tbody.innerHTML = '';

            const filtered = allProperties.filter(p => {
                const matchesStatus = currentStatusFilter === 'all' || p.status === currentStatusFilter;
                const search = document.getElementById('search-input').value.toLowerCase();
                const matchesSearch = !search || p.name.toLowerCase().includes(search) || 
                                     p.city.toLowerCase().includes(search) || 
                                     `${p.first_name} ${p.last_name}`.toLowerCase().includes(search);
                return matchesStatus && matchesSearch;
            });

            if (filtered.length === 0) {
                emptyState.classList.remove('hidden');
                document.getElementById('pagination-info').textContent = 'Showing 0 listings';
                return;
            }

            emptyState.classList.add('hidden');
            filtered.forEach(p => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50/50 transition-colors group';

                let statusBadge = 'bg-gray-50 text-gray-600';
                if (p.status === 'published' || p.status === 'active') statusBadge = 'bg-green-50 text-green-500';
                if (p.status === 'archived' || p.status === 'rejected') statusBadge = 'bg-red-50 text-red-500';
                if (p.status === 'draft' || p.status === 'pending') statusBadge = 'bg-yellow-50 text-yellow-500';

                tr.innerHTML = `
                    <td class="py-5 px-8">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 overflow-hidden shrink-0">
                                <img src="${p.image_url ? '../' + p.image_url : 'https://via.placeholder.com/100'}" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">${p.name || 'Untitled Property'}</div>
                                <div class="text-[11px] text-gray-400 mt-0.5 uppercase tracking-tighter">ID: LST-${p.id.toString().padStart(6, '0')}</div>
                            </div>
                        </div>
                    </td>
                    <td class="py-5 px-8 text-gray-500 font-medium">
                        <div>${p.city}, ${p.state}</div>
                    </td>
                    <td class="py-5 px-8">
                        <div class="font-bold text-gray-900">${p.first_name} ${p.last_name}</div>
                        <div class="text-[12px] text-gray-400">${p.host_email}</div>
                    </td>
                    <td class="py-5 px-8 font-bold text-gray-900">₦${parseFloat(p.price).toLocaleString()}</td>
                    <td class="py-5 px-8 text-center">
                        <span class="px-3 py-1.5 rounded-full text-[11px] font-bold uppercase ${statusBadge}">${p.status}</span>
                    </td>
                    <td class="py-5 px-8 text-right">
                        <a href="property_view.php?id=${p.id}" class="p-2 text-gray-300 hover:text-gray-900 transition-colors"><i class="bi bi-eye-fill"></i></a>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            document.getElementById('pagination-info').textContent = `Showing ${filtered.length} out of ${allProperties.length} listings`;
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
                renderListings();
            });
        });

        document.getElementById('search-input').addEventListener('input', renderListings);

        fetchListings();
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>

    <script>
        // Inject JWT token from session into localStorage for API calls
        <?php if (isset($_SESSION['jwt_token'])): ?>
            localStorage.setItem('jwt_token', '<?= $_SESSION['jwt_token'] ?>');
        <?php endif; ?>
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
