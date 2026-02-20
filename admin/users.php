<?php
session_start();
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/db.php';
require_once __DIR__ . '/../utils/jwt.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['jwt_token'])) {
    $_SESSION['jwt_token'] = JWTManager::generateToken([
        'user_id' => $_SESSION['user_id'],
        'email'   => $_SESSION['email'],
        'role'    => $_SESSION['role']
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | 360HomesHub Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#005a92',
                    },
                    fontFamily: {
                        outfit: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 0px; }
        
        /* Toast */
        #toast {
            transition: opacity 0.4s ease, transform 0.4s ease;
        }

        /* Stat card shimmer */
        @keyframes shimmer { 0%{background-position:-400px 0} 100%{background-position:400px 0} }
        .shimmer { background: linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%); background-size:400px 100%; animation:shimmer 1.2s infinite; border-radius:6px; }

        /* Row fade-in */
        @keyframes fadeUp { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
        .fade-up { animation: fadeUp 0.25s ease forwards; }

        /* Modal backdrop */
        .modal-backdrop {
            backdrop-filter: blur(4px);
        }
    </style>
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
                    <input type="text" id="user-search" placeholder="Search by name, ID or email..."
                           class="w-full bg-white border border-gray-100 rounded-xl py-3 pl-12 pr-4 text-[14px] focus:outline-none focus:ring-2 focus:ring-primary/10 shadow-sm transition-all">
                    <kbd class="absolute right-4 top-1/2 -translate-y-1/2 bg-gray-100 text-gray-400 text-[10px] font-bold px-2 py-0.5 rounded">⌘K</kbd>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white border border-gray-100 flex items-center justify-center cursor-pointer shadow-sm relative">
                    <i class="bi bi-bell text-[18px] text-gray-400"></i>
                    <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                </div>
            </div>
        </div>

        <!-- Page Header -->
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-[30px] font-bold text-gray-900 mb-1">User Directory</h1>
                <p class="text-gray-400 text-[14px]">Manage all platform users, their roles, activity and verification status.</p>
            </div>
            <div class="flex gap-3">
                <button onclick="exportCSV()" class="bg-white border border-gray-100 text-gray-700 px-5 py-2.5 rounded-xl font-bold text-[13px] flex items-center gap-2 shadow-sm hover:bg-gray-50 transition-all">
                    <i class="bi bi-download"></i> Export CSV
                </button>
                <a href="add_user.php" class="bg-[#005a92] text-white px-5 py-2.5 rounded-xl font-bold text-[13px] flex items-center gap-2 shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all">
                    <i class="bi bi-person-plus-fill"></i> Add User
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8" id="stats-grid">
            <!-- Injected by JS -->
            <?php for($i=0;$i<4;$i++): ?>
            <div class="bg-white p-6 rounded-[20px] border border-gray-100 shadow-sm">
                <div class="shimmer h-3 w-24 mb-4"></div>
                <div class="shimmer h-8 w-16 mb-2"></div>
                <div class="shimmer h-3 w-20"></div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Filters & Controls -->
        <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm overflow-hidden mb-0">
            <div class="flex items-center justify-between p-5 border-b border-gray-50">
                <!-- Role Tabs -->
                <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl">
                    <button class="filter-tab px-4 py-2 rounded-lg text-[13px] font-bold bg-white shadow-sm text-gray-900 transition-all" data-filter="all">All</button>
                    <button class="filter-tab px-4 py-2 rounded-lg text-[13px] font-bold text-gray-400 hover:text-gray-600 transition-all" data-filter="host">Hosts</button>
                    <button class="filter-tab px-4 py-2 rounded-lg text-[13px] font-bold text-gray-400 hover:text-gray-600 transition-all" data-filter="guest">Guests</button>
                    <button class="filter-tab px-4 py-2 rounded-lg text-[13px] font-bold text-gray-400 hover:text-gray-600 transition-all" data-filter="verified">Verified</button>
                    <button class="filter-tab px-4 py-2 rounded-lg text-[13px] font-bold text-gray-400 hover:text-gray-600 transition-all" data-filter="suspended">Suspended</button>
                </div>

                <!-- Sort + Per page -->
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-[13px] text-gray-400">Sort:</span>
                        <select id="user-sort" class="bg-transparent border-none text-[13px] font-bold text-gray-900 focus:ring-0 cursor-pointer">
                            <option value="newest">Recently Joined</option>
                            <option value="oldest">Oldest First</option>
                            <option value="name">Alphabetical</option>
                            <option value="activity">Most Active</option>
                        </select>
                    </div>
                    <div class="h-4 w-px bg-gray-200"></div>
                    <div id="pagination-info" class="text-[13px] text-gray-400 font-medium"></div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-widest bg-gray-50/40 border-b border-gray-50">
                            <th class="py-4 px-6">User</th>
                            <th class="py-4 px-6">Role</th>
                            <th class="py-4 px-6">Activity</th>
                            <th class="py-4 px-6">KYC Status</th>
                            <th class="py-4 px-6">Account Status</th>
                            <th class="py-4 px-6">Joined</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="user-table" class="divide-y divide-gray-50 text-[13px]">
                        <!-- Users injected here -->
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div id="empty-state" class="hidden flex flex-col items-center justify-center py-24">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-5 text-gray-200">
                    <i class="bi bi-people text-[40px]"></i>
                </div>
                <p class="text-gray-400 font-medium">No users found matching your criteria.</p>
                <button onclick="resetFilters()" class="mt-4 text-primary font-bold text-[13px] hover:underline">Clear filters</button>
            </div>

            <!-- Loading State -->
            <div id="loading-state" class="flex flex-col items-center justify-center py-24">
                <div class="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin mb-4"></div>
                <p class="text-gray-400 font-medium text-[13px]">Loading users...</p>
            </div>

            <!-- Pagination -->
            <div class="p-5 border-t border-gray-50 flex justify-between items-center text-[13px] text-gray-500 font-medium">
                <div id="page-info">—</div>
                <div class="flex items-center gap-2">
                    <button id="btn-prev" onclick="changePage(-1)" class="flex items-center gap-2 font-bold text-gray-700 border border-gray-100 px-4 py-2 rounded-xl hover:bg-gray-50 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                        <i class="bi bi-chevron-left"></i> Previous
                    </button>
                    <div id="page-numbers" class="flex items-center gap-1"></div>
                    <button id="btn-next" onclick="changePage(1)" class="flex items-center gap-2 font-bold text-gray-700 border border-gray-100 px-4 py-2 rounded-xl hover:bg-gray-50 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                        Next <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Suspend Confirm Modal -->
<div id="suspend-modal" class="fixed inset-0 z-[100] hidden items-center justify-center modal-backdrop bg-black/20">
    <div class="bg-white rounded-[24px] shadow-2xl p-8 w-full max-w-[420px] mx-4 transform transition-all">
        <div class="w-14 h-14 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-5">
            <i class="bi bi-exclamation-triangle-fill text-red-500 text-[24px]"></i>
        </div>
        <h3 id="modal-title" class="text-[20px] font-bold text-gray-900 text-center mb-2">Suspend User?</h3>
        <p id="modal-desc" class="text-gray-400 text-[14px] text-center mb-8 leading-relaxed">
            This user will be suspended and lose access to the platform immediately.
        </p>
        <div class="flex gap-3">
            <button onclick="closeModal()" class="flex-1 py-3 border border-gray-100 text-gray-600 rounded-xl font-bold text-[14px] hover:bg-gray-50 transition-all">
                Cancel
            </button>
            <button id="modal-confirm-btn" class="flex-1 py-3 bg-red-500 text-white rounded-xl font-bold text-[14px] hover:bg-red-600 transition-all" onclick="confirmSuspend()">
                Confirm
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="fixed bottom-6 right-6 z-[200] opacity-0 translate-y-4 pointer-events-none">
    <div id="toast-inner" class="flex items-center gap-3 px-5 py-4 rounded-2xl shadow-2xl text-[14px] font-bold text-white min-w-[280px]">
        <i id="toast-icon" class="text-[18px]"></i>
        <span id="toast-msg"></span>
    </div>
</div>

<script>
    const token = localStorage.getItem('jwt_token');
    if (!token) window.location.href = 'login.php';

    // ─── State ────────────────────────────────────────────────────────────────
    let allUsers    = [];
    let currentPage = 1;
    let totalPages  = 1;
    let currentFilter = 'all';
    let currentSort   = 'newest';
    let pendingSuspendUser = null;

    const LIMIT = 20;

    // ─── Fetch ────────────────────────────────────────────────────────────────
    async function fetchUsers(page = 1) {
        showLoading(true);
        try {
            const search = document.getElementById('user-search').value.trim();
            const sort   = document.getElementById('user-sort').value;

            // Build query params
            const params = new URLSearchParams({ page, limit: LIMIT, sort });
            if (search)  params.set('search', search);

            if (currentFilter === 'host' || currentFilter === 'guest') params.set('role', currentFilter);
            if (currentFilter === 'suspended') params.set('status', 'suspended');
            if (currentFilter === 'verified')  params.set('kyc', 'verified');

            const res  = await fetch(`../api/admin/users.php?${params}`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await res.json();

            if (data.success) {
                allUsers    = data.data.users || [];
                currentPage = data.data.pagination.page;
                totalPages  = data.data.pagination.pages;
                const total = data.data.pagination.total;

                renderStats(data.data.stats);
                renderUsers(total);
                renderPagination(total);
            } else {
                showToast(data.message || 'Failed to load users', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Network error – check your connection.', 'error');
        } finally {
            showLoading(false);
        }
    }

    function showLoading(show) {
        document.getElementById('loading-state').classList.toggle('hidden', !show);
        document.getElementById('user-table').classList.toggle('hidden', show);
        document.getElementById('empty-state').classList.add('hidden');
    }

    // ─── Stats ────────────────────────────────────────────────────────────────
    function renderStats(s) {
        const grid = document.getElementById('stats-grid');
        grid.innerHTML = `
            ${statCard('Total Users',   s.total_users,   s.new_this_week + ' new this week', 'bi-people-fill',      '#005a92', '#eaf2fa')}
            ${statCard('Active Hosts',  s.host_count,    s.host_count + ' listed properties', 'bi-person-badge-fill','#7c3aed', '#f5f3ff')}
            ${statCard('Guests',        s.guest_count,   s.guest_count + ' total platform guests', 'bi-person-check','#059669', '#ecfdf5')}
            ${statCard('Suspended',     s.suspended,     s.pending_kyc + ' pending KYC',    'bi-slash-circle-fill', '#dc2626', '#fef2f2')}
        `;
    }

    function statCard(label, value, sub, icon, color, bg) {
        return `
        <div class="bg-white p-6 rounded-[20px] border border-gray-100 shadow-sm hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-4">
                <span class="text-[12px] font-bold text-gray-400 uppercase tracking-widest">${label}</span>
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:${bg}">
                    <i class="bi ${icon} text-[17px]" style="color:${color}"></i>
                </div>
            </div>
            <div class="text-[32px] font-bold text-gray-900 mb-1">${Number(value).toLocaleString()}</div>
            <div class="text-[12px] text-gray-400 font-medium">${sub}</div>
        </div>`;
    }

    // ─── Render Users ─────────────────────────────────────────────────────────
    function renderUsers(total) {
        const tbody      = document.getElementById('user-table');
        const emptyState = document.getElementById('empty-state');
        tbody.innerHTML  = '';

        document.getElementById('pagination-info').textContent = `${total.toLocaleString()} user${total !== 1 ? 's' : ''}`;

        if (allUsers.length === 0) {
            emptyState.classList.remove('hidden');
            return;
        }
        emptyState.classList.add('hidden');

        allUsers.forEach((u, i) => {
            const tr   = document.createElement('tr');
            tr.className = 'hover:bg-gray-50/60 transition-colors group fade-up';
            tr.style.animationDelay = (i * 30) + 'ms';

            const name   = `${u.first_name || ''} ${u.last_name || ''}`.trim() || 'Unnamed User';
            const initials = (u.first_name ? u.first_name[0] : 'U').toUpperCase();
            const avatar = u.avatar
                ? `<img src="${u.avatar.startsWith('http') ? u.avatar : '../' + u.avatar}" class="w-full h-full object-cover">`
                : `<div class="w-full h-full flex items-center justify-center font-bold text-white text-[14px]" style="background:${stringToColor(name)}">${initials}</div>`;

            // Role badge
            const roleMeta = {
                'host':  { bg: 'bg-orange-50',  text: 'text-orange-600',  label: 'Host'  },
                'guest': { bg: 'bg-blue-50',     text: 'text-blue-600',    label: 'Guest' },
                'admin': { bg: 'bg-purple-50',   text: 'text-purple-700',  label: 'Admin' },
            };
            const rm = roleMeta[u.role] || { bg: 'bg-gray-50', text: 'text-gray-600', label: u.role };

            // KYC badge — from kyc table's latest status
            let kycBg, kycText, kycLabel, kycIcon;
            if (u.status === 'verified') {
                kycBg = 'bg-green-50'; kycText = 'text-green-600'; kycLabel = 'Verified'; kycIcon = 'bi-patch-check-fill';
            } else if (u.kyc_status === 'pending') {
                kycBg = 'bg-yellow-50'; kycText = 'text-yellow-600'; kycLabel = 'Pending'; kycIcon = 'bi-hourglass-split';
            } else if (u.kyc_status === 'rejected') {
                kycBg = 'bg-red-50'; kycText = 'text-red-500'; kycLabel = 'Rejected'; kycIcon = 'bi-x-circle-fill';
            } else {
                kycBg = 'bg-gray-50'; kycText = 'text-gray-400'; kycLabel = 'Not Started'; kycIcon = 'bi-dash-circle';
            }

            // Account status badge
            const isSuspended = u.status === 'suspended';
            const accBg    = isSuspended ? 'bg-red-50'    : 'bg-emerald-50';
            const accText  = isSuspended ? 'text-red-500'  : 'text-emerald-600';
            const accLabel = isSuspended ? 'Suspended'    : 'Active';


            // Activity
            const activity = u.role === 'host'
                ? `${u.listing_count} listing${u.listing_count != 1 ? 's' : ''}`
                : `${u.booking_count} booking${u.booking_count != 1 ? 's' : ''}`;

            const activitySub = u.role === 'host'
                ? `${u.bookings_received} reservations received`
                : `₦${parseFloat(u.total_spent || 0).toLocaleString()} total spent`;

            // Joined date
            const joined = u.created_at ? new Date(u.created_at).toLocaleDateString('en-GB', {day:'numeric', month:'short', year:'numeric'}) : '—';

            tr.innerHTML = `
                <td class="py-4 px-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0 shadow-sm">${avatar}</div>
                        <div>
                            <div class="font-bold text-gray-900 group-hover:text-primary transition-colors">${escHtml(name)}</div>
                            <div class="text-[11px] text-gray-400 font-medium">${escHtml(u.email)}</div>
                            <div class="text-[10px] text-gray-300 font-bold tracking-widest uppercase mt-0.5">UID-${String(u.id).padStart(6,'0')}</div>
                        </div>
                    </div>
                </td>
                <td class="py-4 px-6">
                    <span class="px-3 py-1.5 rounded-lg font-bold text-[11px] uppercase ${rm.bg} ${rm.text}">${rm.label}</span>
                    ${u.phone ? `<div class="text-[11px] text-gray-400 mt-1 font-medium">${escHtml(u.phone)}</div>` : ''}
                </td>
                <td class="py-4 px-6">
                    <div class="font-bold text-gray-900 text-[13px]">${activity}</div>
                    <div class="text-[11px] text-gray-400 mt-0.5">${activitySub}</div>
                </td>
                <td class="py-4 px-6">
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full w-fit ${kycBg}">
                        <i class="bi ${kycIcon} text-[12px] ${kycText}"></i>
                        <span class="font-bold text-[11px] uppercase ${kycText}">${kycLabel}</span>
                    </div>
                </td>
                <td class="py-4 px-6">
                    <span class="px-3 py-1.5 rounded-full font-bold text-[11px] uppercase ${accBg} ${accText}">${accLabel}</span>
                </td>
                <td class="py-4 px-6">
                    <div class="text-[13px] text-gray-700 font-medium">${joined}</div>
                </td>
                <td class="py-4 px-6 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <a href="user_profile.php?id=${u.id}" 
                           title="View Profile"
                           class="w-9 h-9 rounded-xl flex items-center justify-center text-gray-400 hover:bg-primary/5 hover:text-primary transition-all">
                            <i class="bi bi-eye-fill text-[15px]"></i>
                        </a>
                        <button onclick="openSuspendModal(${u.id}, ${escJson(name)}, ${u.status === 'suspended' ? 'true' : 'false'})"
                                title="${isSuspended ? 'Unsuspend' : 'Suspend'} User"
                                class="w-9 h-9 rounded-xl flex items-center justify-center transition-all ${isSuspended ? 'text-emerald-500 hover:bg-emerald-50' : 'text-red-400 hover:bg-red-50'}">
                            <i class="bi ${isSuspended ? 'bi-unlock-fill' : 'bi-slash-circle-fill'} text-[15px]"></i>
                        </button>
                        <a href="user_profile.php?id=${u.id}" title="Edit User"
                           class="w-9 h-9 rounded-xl flex items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition-all">
                            <i class="bi bi-pencil-fill text-[14px]"></i>
                        </a>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // ─── Pagination ───────────────────────────────────────────────────────────
    function renderPagination(total) {
        const start = ((currentPage - 1) * LIMIT) + 1;
        const end   = Math.min(currentPage * LIMIT, total);
        document.getElementById('page-info').textContent = total > 0
            ? `Showing ${start}–${end} of ${total.toLocaleString()} users`
            : 'No results';

        document.getElementById('btn-prev').disabled = currentPage <= 1;
        document.getElementById('btn-next').disabled = currentPage >= totalPages;

        // Page numbers
        const nums = document.getElementById('page-numbers');
        nums.innerHTML = '';
        const range = pageRange(currentPage, totalPages);
        range.forEach(p => {
            if (p === '...') {
                const span = document.createElement('span');
                span.textContent = '…';
                span.className = 'text-gray-400 px-1';
                nums.appendChild(span);
            } else {
                const btn = document.createElement('button');
                btn.textContent = p;
                btn.className = `w-9 h-9 rounded-xl text-[13px] font-bold transition-all ${p === currentPage ? 'bg-primary text-white shadow-md' : 'text-gray-600 hover:bg-gray-100'}`;
                btn.onclick = () => { currentPage = p; fetchUsers(p); };
                nums.appendChild(btn);
            }
        });
    }

    function pageRange(current, total) {
        if (total <= 7) return Array.from({length: total}, (_, i) => i + 1);
        if (current <= 4) return [1, 2, 3, 4, 5, '...', total];
        if (current >= total - 3) return [1, '...', total-4, total-3, total-2, total-1, total];
        return [1, '...', current-1, current, current+1, '...', total];
    }

    function changePage(delta) {
        const next = currentPage + delta;
        if (next < 1 || next > totalPages) return;
        currentPage = next;
        fetchUsers(next);
    }

    // ─── Suspend Modal ────────────────────────────────────────────────────────
    function openSuspendModal(userId, name, isSuspended) {
        pendingSuspendUser = { id: userId, isSuspended };
        const modal = document.getElementById('suspend-modal');
        document.getElementById('modal-title').textContent = isSuspended ? 'Unsuspend User?' : 'Suspend User?';
        document.getElementById('modal-desc').textContent  = isSuspended
            ? `${name} will regain full access to the platform.`
            : `${name} will be suspended and lose access immediately.`;
        const btn = document.getElementById('modal-confirm-btn');
        btn.textContent = isSuspended ? 'Unsuspend' : 'Suspend';
        btn.className   = `flex-1 py-3 rounded-xl font-bold text-[14px] transition-all text-white ${isSuspended ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-red-500 hover:bg-red-600'}`;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        const modal = document.getElementById('suspend-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        pendingSuspendUser = null;
    }

    async function confirmSuspend() {
        if (!pendingSuspendUser) return;
        const btn = document.getElementById('modal-confirm-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></span>Processing...';

        try {
            const action = pendingSuspendUser.isSuspended ? 'unsuspend' : 'suspend';
            const res = await fetch('../api/admin/suspend_user.php', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: pendingSuspendUser.id, action })
            });
            const data = await res.json();

            if (data.success) {
                showToast(data.message, 'success');
                closeModal();
                fetchUsers(currentPage);
            } else {
                showToast(data.message || 'Action failed', 'error');
                btn.disabled = false;
                btn.textContent = pendingSuspendUser.isSuspended ? 'Unsuspend' : 'Suspend';
            }
        } catch (err) {
            showToast('Network error', 'error');
            btn.disabled = false;
            btn.textContent = pendingSuspendUser.isSuspended ? 'Unsuspend' : 'Suspend';
        }
    }

    // Close modal on backdrop click
    document.getElementById('suspend-modal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // ─── Filters ──────────────────────────────────────────────────────────────
    document.querySelectorAll('.filter-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-tab').forEach(b => {
                b.classList.remove('bg-white', 'shadow-sm', 'text-gray-900');
                b.classList.add('text-gray-400');
            });
            btn.classList.add('bg-white', 'shadow-sm', 'text-gray-900');
            btn.classList.remove('text-gray-400');
            currentFilter = btn.dataset.filter;
            currentPage   = 1;
            fetchUsers(1);
        });
    });

    document.getElementById('user-sort').addEventListener('change', () => {
        currentPage = 1;
        fetchUsers(1);
    });

    let searchTimer;
    document.getElementById('user-search').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { currentPage = 1; fetchUsers(1); }, 350);
    });

    function resetFilters() {
        document.getElementById('user-search').value = '';
        currentFilter = 'all';
        currentPage   = 1;
        document.querySelectorAll('.filter-tab')[0].click();
    }

    // ─── CSV Export ───────────────────────────────────────────────────────────
    function exportCSV() {
        if (!allUsers.length) { showToast('No data to export', 'error'); return; }
        const headers = ['ID', 'Name', 'Email', 'Phone', 'Role', 'KYC Status', 'Account Status', 'Listings', 'Bookings', 'Joined'];
        const rows = allUsers.map(u => [
            u.id,
            `${u.first_name || ''} ${u.last_name || ''}`.trim(),
            u.email,
            u.phone || '',
            u.role,
            u.kyc_status,
            u.status,
            u.listing_count,
            u.booking_count,
            u.created_at ? new Date(u.created_at).toLocaleDateString() : ''
        ]);
        const csv  = [headers, ...rows].map(r => r.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a'); a.href = url;
        a.download = `users_${new Date().toISOString().slice(0,10)}.csv`;
        a.click(); URL.revokeObjectURL(url);
        showToast('CSV exported successfully!', 'success');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────
    function escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function escJson(str) {
        return JSON.stringify(str);
    }
    function stringToColor(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) hash = str.charCodeAt(i) + ((hash << 5) - hash);
        const colors = ['#005a92','#7c3aed','#059669','#d97706','#db2777','#0891b2','#65a30d'];
        return colors[Math.abs(hash) % colors.length];
    }

    let toastTimer;
    function showToast(msg, type = 'success') {
        const toast    = document.getElementById('toast');
        const inner    = document.getElementById('toast-inner');
        const icon     = document.getElementById('toast-icon');
        const msgEl    = document.getElementById('toast-msg');

        msgEl.textContent  = msg;
        if (type === 'success') {
            inner.className  = 'flex items-center gap-3 px-5 py-4 rounded-2xl shadow-2xl text-[14px] font-bold text-white min-w-[280px] bg-emerald-500';
            icon.className   = 'bi bi-check-circle-fill text-[18px]';
        } else {
            inner.className  = 'flex items-center gap-3 px-5 py-4 rounded-2xl shadow-2xl text-[14px] font-bold text-white min-w-[280px] bg-red-500';
            icon.className   = 'bi bi-exclamation-circle-fill text-[18px]';
        }
        toast.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
        toast.classList.add('opacity-100', 'translate-y-0');

        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
            toast.classList.remove('opacity-100', 'translate-y-0');
        }, 4000);
    }

    // ─── Init ─────────────────────────────────────────────────────────────────
    fetchUsers(1);
</script>
<?php if (isset($_SESSION['jwt_token'])): ?>
<script>localStorage.setItem('jwt_token', '<?= $_SESSION['jwt_token'] ?>');</script>
<?php endif; ?>
<script src="js/sidebar.js"></script>
</body>
</html>
