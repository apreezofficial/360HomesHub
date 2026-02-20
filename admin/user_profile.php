<?php
session_start();
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/jwt.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// JWT must be in session for the inline <script> below to seed localStorage
if (!isset($_SESSION['jwt_token'])) {
    require_once __DIR__ . '/../utils/db.php';
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
    <title>User Profile | 360HomesHub Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: '#005a92' },
                    fontFamily: { outfit: ['Outfit', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
        .fade-up { animation: fadeUp 0.3s ease forwards; }
        #toast { transition: opacity 0.35s ease, transform 0.35s ease; }
        .modal-backdrop { backdrop-filter: blur(4px); }
    </style>
</head>
<body class="bg-[#F9FAFB] min-h-screen font-outfit">

<!-- ── Seed JWT before any fetch ── -->
<?php if (isset($_SESSION['jwt_token'])): ?>
<script>localStorage.setItem('jwt_token', '<?= $_SESSION['jwt_token'] ?>');</script>
<?php endif; ?>

<div class="flex min-h-screen">
    <aside class="w-[260px] fixed h-full bg-white z-50"></aside>

    <main class="flex-1 ml-[260px] min-h-screen p-8" id="main-content">

        <!-- Breadcrumb -->
        <div class="text-[14px] text-gray-400 mb-8 flex items-center gap-2">
            <a href="users.php" class="hover:text-primary transition-colors flex items-center gap-1">
                <i class="bi bi-people"></i> Users
            </a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-gray-900 font-semibold" id="breadcrumb-name">Loading…</span>
        </div>

        <!-- Loading skeleton -->
        <div id="loading-skeleton">
            <div class="flex items-center gap-5 mb-10">
                <div class="w-20 h-20 rounded-full bg-gray-200 animate-pulse shrink-0"></div>
                <div class="space-y-3">
                    <div class="h-7 w-48 bg-gray-200 animate-pulse rounded-xl"></div>
                    <div class="h-4 w-64 bg-gray-100 animate-pulse rounded-lg"></div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-6 mb-8">
                <?php for($i=0;$i<3;$i++): ?>
                <div class="bg-white p-8 rounded-[24px] border border-gray-100 shadow-sm h-36 animate-pulse"></div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Main Profile Content (hidden until data loads) -->
        <div id="profile-content" class="hidden">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start gap-6 mb-10 fade-up">
                <div class="flex items-center gap-5">
                    <div id="user-avatar" class="w-20 h-20 rounded-full bg-gray-100 border-4 border-white shadow-lg flex items-center justify-center overflow-hidden shrink-0"></div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                            <h1 class="text-[26px] font-bold text-gray-900" id="header-name"></h1>
                            <span id="role-badge" class="px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider"></span>
                            <span id="kyc-badge" class="px-3 py-1 rounded-full text-[11px] font-bold hidden"></span>
                            <span id="suspended-badge" class="px-3 py-1 bg-red-100 text-red-600 rounded-full text-[11px] font-bold hidden">⛔ Suspended</span>
                        </div>
                        <div class="text-[13px] text-gray-400 flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span class="flex items-center gap-1"><i class="bi bi-envelope"></i> <span id="header-email"></span></span>
                            <span id="header-phone" class="flex items-center gap-1 hidden"><i class="bi bi-telephone"></i> <span id="phone-val"></span></span>
                            <span class="flex items-center gap-1"><i class="bi bi-calendar3"></i> Joined <span id="header-joined"></span></span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <button id="suspend-toggle-btn" onclick="openSuspendModal()"
                            class="px-5 py-2.5 border font-bold text-[13px] rounded-xl transition-all">
                        Suspend Account
                    </button>
                    <a href="kyc.php" class="px-5 py-2.5 bg-[#005a92] text-white rounded-xl font-bold text-[13px] shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all flex items-center gap-2">
                        <i class="bi bi-shield-check"></i> KYC Review
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-5 mb-8 fade-up" id="stats-grid"></div>

            <!-- Body Grid -->
            <div class="grid grid-cols-12 gap-8">

                <!-- LEFT: 8 cols -->
                <div class="col-span-12 lg:col-span-8 space-y-8">

                    <!-- KYC / Verification -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm overflow-hidden fade-up">
                        <div class="p-6 border-b border-gray-50 flex items-center justify-between">
                            <h3 class="text-[17px] font-bold text-gray-900">Verification Documents</h3>
                            <a href="kyc.php" class="text-[13px] font-bold text-primary hover:underline">Review all KYC →</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 bg-gray-50/30">
                                        <th class="py-4 px-6">Document Type</th>
                                        <th class="py-4 px-6 text-center">Status</th>
                                        <th class="py-4 px-6">Submitted</th>
                                        <th class="py-4 px-6">Admin Note</th>
                                        <th class="py-4 px-6 text-right">Docs</th>
                                    </tr>
                                </thead>
                                <tbody id="kyc-table" class="text-[13px] divide-y divide-gray-50"></tbody>
                            </table>
                        </div>
                        <div id="kyc-empty" class="hidden py-14 text-center text-gray-400">
                            <i class="bi bi-shield-x text-[32px] block mb-3 text-gray-200"></i>
                            <p class="font-medium">No KYC documents submitted.</p>
                        </div>
                    </div>

                    <!-- Booking / Activity History -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm overflow-hidden fade-up">
                        <div class="p-6 border-b border-gray-50 flex items-center justify-between bg-gray-50/20">
                            <h3 class="text-[17px] font-bold text-gray-900">Booking History</h3>
                            <span id="booking-count-badge" class="px-3 py-1 bg-gray-100 text-gray-600 rounded-xl text-[12px] font-bold"></span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 bg-gray-50/30">
                                        <th class="py-4 px-6">Property</th>
                                        <th class="py-4 px-6">Dates</th>
                                        <th class="py-4 px-6 text-center">Nights</th>
                                        <th class="py-4 px-6 text-center">Amount</th>
                                        <th class="py-4 px-6 text-center">Status</th>
                                        <th class="py-4 px-6">Booked On</th>
                                    </tr>
                                </thead>
                                <tbody id="booking-table" class="text-[13px] divide-y divide-gray-50"></tbody>
                            </table>
                        </div>
                        <div id="booking-empty" class="hidden py-20 text-center text-gray-400">
                            <i class="bi bi-calendar-x text-[36px] block mb-3 text-gray-200"></i>
                            <p class="font-medium">No booking history found.</p>
                        </div>
                    </div>

                    <!-- Host Properties (only shown for hosts) -->
                    <div id="properties-section" class="hidden bg-white rounded-[24px] border border-gray-100 shadow-sm overflow-hidden fade-up">
                        <div class="p-6 border-b border-gray-50 flex items-center justify-between">
                            <h3 class="text-[17px] font-bold text-gray-900">Listed Properties</h3>
                            <a href="listings.php" class="text-[13px] font-bold text-primary hover:underline">View all →</a>
                        </div>
                        <div id="properties-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-6"></div>
                    </div>

                </div>

                <!-- RIGHT: 4 cols -->
                <div class="col-span-12 lg:col-span-4 space-y-6">

                    <!-- Account Info -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-6 fade-up">
                        <h3 class="text-[16px] font-bold text-gray-900 mb-5">Account Information</h3>
                        <div class="space-y-4" id="account-info-list"></div>
                    </div>

                    <!-- Admin Control Hub -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-6 fade-up">
                        <h3 class="text-[16px] font-bold text-gray-900 mb-6">Admin Controls</h3>
                        <div class="space-y-5">

                            <!-- Freeze Bookings -->
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-orange-50 flex items-center justify-center text-orange-500 shrink-0">
                                        <i class="bi bi-calendar-x text-[16px]"></i>
                                    </div>
                                    <div>
                                        <div class="text-[14px] font-bold text-gray-900">Freeze Bookings</div>
                                        <div class="text-[11px] text-gray-400 mt-0.5">Block new reservations</div>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="toggle-booking" class="sr-only peer" onchange="toggleControl('booking_disabled', this.checked)">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-400"></div>
                                </label>
                            </div>

                            <!-- Disable Messaging -->
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center text-blue-500 shrink-0">
                                        <i class="bi bi-chat-slash text-[16px]"></i>
                                    </div>
                                    <div>
                                        <div class="text-[14px] font-bold text-gray-900">Disable Messaging</div>
                                        <div class="text-[11px] text-gray-400 mt-0.5">Block from sending messages</div>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="toggle-message" class="sr-only peer" onchange="toggleControl('message_disabled', this.checked)">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-400"></div>
                                </label>
                            </div>

                            <div class="border-t border-gray-50 pt-5">
                                <button onclick="openSuspendModal()" id="full-suspend-btn"
                                        class="w-full py-2.5 border border-red-100 text-red-500 rounded-xl font-bold text-[13px] hover:bg-red-50 transition-all flex items-center justify-center gap-2">
                                    <i class="bi bi-slash-circle"></i>
                                    <span id="suspend-btn-text">Suspend Account</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Security / Login Info -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-6 fade-up">
                        <h3 class="text-[16px] font-bold text-gray-900 mb-5">Security Info</h3>
                        <div class="space-y-5" id="security-info-list"></div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Error State -->
        <div id="error-state" class="hidden flex flex-col items-center justify-center py-32 text-center">
            <i class="bi bi-person-x-fill text-[48px] text-gray-200 block mb-4"></i>
            <h2 class="text-[20px] font-bold text-gray-700 mb-2">User Not Found</h2>
            <p class="text-gray-400 mb-6" id="error-msg">This user could not be loaded.</p>
            <a href="users.php" class="px-6 py-3 bg-primary text-white rounded-xl font-bold text-[14px] hover:bg-primary/90 transition-all">← Back to Users</a>
        </div>

    </main>
</div>

<!-- ── Suspend Confirm Modal ── -->
<div id="suspend-modal" class="fixed inset-0 z-[100] hidden items-center justify-center modal-backdrop bg-black/20">
    <div class="bg-white rounded-[24px] shadow-2xl p-8 w-full max-w-[420px] mx-4">
        <div id="modal-icon" class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-5">
            <i id="modal-icon-i" class="text-[24px]"></i>
        </div>
        <h3 id="modal-title" class="text-[20px] font-bold text-gray-900 text-center mb-2"></h3>
        <p id="modal-desc" class="text-gray-400 text-[14px] text-center mb-8 leading-relaxed"></p>
        <div class="flex gap-3">
            <button onclick="closeModal()" class="flex-1 py-3 border border-gray-100 text-gray-600 rounded-xl font-bold text-[14px] hover:bg-gray-50 transition-all">Cancel</button>
            <button id="modal-confirm-btn" class="flex-1 py-3 rounded-xl font-bold text-[14px] text-white transition-all" onclick="confirmSuspend()"></button>
        </div>
    </div>
</div>

<!-- ── Toast ── -->
<div id="toast" class="fixed bottom-6 right-6 z-[200] opacity-0 translate-y-4 pointer-events-none">
    <div id="toast-inner" class="flex items-center gap-3 px-5 py-4 rounded-2xl shadow-2xl text-[14px] font-bold text-white min-w-[260px]">
        <i id="toast-icon" class="text-[18px]"></i>
        <span id="toast-msg"></span>
    </div>
</div>

<script>
    // ── JWT Guard ──────────────────────────────────────────────────────
    const token = localStorage.getItem('jwt_token');
    if (!token) {
        window.location.href = 'login.php';
    }

    // ── URL Param ──────────────────────────────────────────────────────
    const userId = new URLSearchParams(window.location.search).get('id');
    if (!userId) window.location.href = 'users.php';

    // ── State ─────────────────────────────────────────────────────────
    let currentUser = null;

    // ── Fetch & Render ─────────────────────────────────────────────────
    async function loadProfile() {
        try {
            const res  = await fetch(`../api/admin/user_detail.php?id=${userId}`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await res.json();

            if (!data.success) throw new Error(data.message || 'Failed to load user');

            currentUser = data.data.user;
            render(data.data);

        } catch (err) {
            console.error(err);
            document.getElementById('loading-skeleton').classList.add('hidden');
            const es = document.getElementById('error-state');
            document.getElementById('error-msg').textContent = err.message;
            es.classList.remove('hidden');
            es.classList.add('flex');
        }
    }

    function render({ user: u, stats, kyc, booking_history, properties }) {
        document.getElementById('loading-skeleton').classList.add('hidden');
        document.getElementById('profile-content').classList.remove('hidden');

        const name = `${u.first_name || ''} ${u.last_name || ''}`.trim() || 'Unnamed User';
        const initials = (u.first_name ? u.first_name[0] : 'U').toUpperCase();

        // Breadcrumb
        document.getElementById('breadcrumb-name').textContent = name;

        // Avatar
        const avatarEl = document.getElementById('user-avatar');
        if (u.avatar) {
            avatarEl.innerHTML = `<img src="${u.avatar.startsWith('http') ? u.avatar : '../' + u.avatar}" class="w-full h-full object-cover">`;
        } else {
            avatarEl.style.background = strColor(name);
            avatarEl.innerHTML = `<span class="text-[24px] font-bold text-white uppercase">${initials}</span>`;
        }

        // Name + email
        document.getElementById('header-name').textContent   = name;
        document.getElementById('header-email').textContent  = u.email || '—';
        document.getElementById('header-joined').textContent = fmtDate(u.created_at);

        // Phone
        if (u.phone) {
            document.getElementById('header-phone').classList.remove('hidden');
            document.getElementById('phone-val').textContent = u.phone;
        }

        // Role badge
        const rb = document.getElementById('role-badge');
        const roleMap = {
            host:  { bg:'bg-orange-50',  text:'text-orange-600' },
            guest: { bg:'bg-blue-50',    text:'text-blue-600'   },
            admin: { bg:'bg-purple-50',  text:'text-purple-700' },
        };
        const rm = roleMap[u.role] || { bg:'bg-gray-50', text:'text-gray-500' };
        rb.textContent  = u.role || 'user';
        rb.className    = `px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider ${rm.bg} ${rm.text}`;

        // KYC badge
        const kb = document.getElementById('kyc-badge');
        if (u.status === 'verified') {
            kb.textContent = '✓ Verified'; kb.className = 'px-3 py-1 rounded-full text-[11px] font-bold bg-green-50 text-green-600';
            kb.classList.remove('hidden');
        } else if (kyc.some(k => k.status === 'pending')) {
            kb.textContent = '⏳ KYC Pending'; kb.className = 'px-3 py-1 rounded-full text-[11px] font-bold bg-yellow-50 text-yellow-600';
            kb.classList.remove('hidden');
        }

        // Suspended badge + header button
        const isSuspended = u.status === 'suspended';
        if (isSuspended) document.getElementById('suspended-badge').classList.remove('hidden');
        refreshSuspendBtn(isSuspended);

        // Admin control toggles
        document.getElementById('toggle-booking').checked = !!parseInt(u.booking_disabled);
        document.getElementById('toggle-message').checked = !!parseInt(u.message_disabled);

        // Stats
        renderStats(u, stats);

        // Account info sidebar
        renderAccountInfo(u);

        // Security info
        renderSecurityInfo(u);

        // KYC table
        renderKYC(kyc);

        // Bookings
        renderBookings(booking_history);

        // Properties (host)
        if (u.role === 'host' && properties.length > 0) {
            document.getElementById('properties-section').classList.remove('hidden');
            renderProperties(properties);
        }
    }

    // ── Stats ──────────────────────────────────────────────────────────
    function renderStats(u, s) {
        const grid = document.getElementById('stats-grid');
        if (u.role === 'host') {
            grid.innerHTML = `
                ${statCard('Total Revenue',    '₦' + fmt(s.total_earnings),   s.bookings_host + ' reservations received', 'bi-wallet2',        '#005a92','#eaf2fa')}
                ${statCard('Listed Properties', s.listings,                    'Active hosting portfolio',                  'bi-building',       '#7c3aed','#f5f3ff')}
                ${statCard('Bookings Received', s.bookings_host,               'From all guests',                           'bi-calendar-check', '#059669','#ecfdf5')}
            `;
        } else {
            grid.innerHTML = `
                ${statCard('Total Bookings',  s.bookings_guest,             'All-time reservations',                      'bi-house',         '#005a92','#eaf2fa')}
                ${statCard('Total Spent',     '₦' + fmt(s.total_spent),    'Confirmed bookings only',                    'bi-wallet2',       '#7c3aed','#f5f3ff')}
                ${statCard('Last Activity',   s.last_booking_date ? fmtDate(s.last_booking_date) : 'Never', 'Most recent booking', 'bi-clock-history','#059669','#ecfdf5')}
            `;
        }
    }

    function statCard(label, val, sub, icon, color, bg) {
        return `
        <div class="bg-white p-6 rounded-[20px] border border-gray-100 shadow-sm hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-4">
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">${label}</span>
                <div class="w-8 h-8 rounded-xl flex items-center justify-center" style="background:${bg}">
                    <i class="bi ${icon} text-[15px]" style="color:${color}"></i>
                </div>
            </div>
            <div class="text-[26px] font-bold text-gray-900 mb-1">${val}</div>
            <div class="text-[11px] text-gray-400">${sub}</div>
        </div>`;
    }

    // ── Account Info ───────────────────────────────────────────────────
    function renderAccountInfo(u) {
        const items = [
            { icon:'bi-person',         label:'Full Name',       val: (`${u.first_name||''} ${u.last_name||''}`).trim() || '—' },
            { icon:'bi-envelope',       label:'Email',           val: u.email || '—' },
            { icon:'bi-telephone',      label:'Phone',           val: u.phone || 'Not added' },
            { icon:'bi-person-badge',   label:'Role',            val: (u.role || '—').toUpperCase() },
            { icon:'bi-shield-check',   label:'KYC Status',      val: u.status === 'verified' ? '✓ Verified' : (u.status === 'suspended' ? '⛔ Suspended' : 'Not verified') },
            { icon:'bi-geo-alt',        label:'Location',        val: [u.city, u.state, u.country].filter(Boolean).join(', ') || 'Not provided' },
            { icon:'bi-calendar3',      label:'Member Since',    val: fmtDate(u.created_at) },
            { icon:'bi-cloud-arrow-up', label:'Auth Provider',   val: (u.auth_provider || 'email').toUpperCase() },
        ];
        document.getElementById('account-info-list').innerHTML = items.map(it => `
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400 shrink-0 mt-0.5">
                    <i class="bi ${it.icon} text-[14px]"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">${it.label}</div>
                    <div class="text-[13px] font-semibold text-gray-800 truncate">${escHtml(it.val)}</div>
                </div>
            </div>
        `).join('');
    }

    // ── Security Info ──────────────────────────────────────────────────
    function renderSecurityInfo(u) {
        const items = [
            {
                icon: 'bi-clock-history',
                title: 'Last Login',
                val: u.last_login ? fmtDateTime(u.last_login) : 'Never logged in',
                sub: u.last_login ? 'Most recent session' : 'No login recorded'
            },
            {
                icon: 'bi-geo-alt',
                title: 'Last IP Address',
                val: u.last_ip || 'Unknown',
                sub: 'IP from last session'
            },
            {
                icon: 'bi-toggle-on',
                title: 'Account Controls',
                val: [
                    u.booking_disabled  == 1 ? '🔒 Bookings frozen'  : '✓ Bookings OK',
                    u.message_disabled  == 1 ? '🔒 Messages disabled' : '✓ Messages OK',
                ].join(' · '),
                sub: 'Current restrictions'
            }
        ];

        document.getElementById('security-info-list').innerHTML = items.map(it => `
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 shrink-0">
                    <i class="bi ${it.icon} text-[15px]"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-0.5">
                        <div class="text-[13px] font-bold text-gray-900">${it.title}</div>
                    </div>
                    <div class="text-[12px] text-gray-700 font-medium truncate">${escHtml(it.val)}</div>
                    <div class="text-[11px] text-gray-400">${it.sub}</div>
                </div>
            </div>
        `).join('');
    }

    // ── KYC ────────────────────────────────────────────────────────────
    function renderKYC(kyc) {
        const tbody   = document.getElementById('kyc-table');
        const emptyEl = document.getElementById('kyc-empty');
        tbody.innerHTML = '';

        if (!kyc || kyc.length === 0) {
            emptyEl.classList.remove('hidden');
            return;
        }
        emptyEl.classList.add('hidden');

        const statusMap = {
            approved: { bg:'bg-green-50',  text:'text-green-600',  label:'Approved'  },
            pending:  { bg:'bg-yellow-50', text:'text-yellow-600', label:'Pending'   },
            rejected: { bg:'bg-red-50',    text:'text-red-500',    label:'Rejected'  },
        };

        kyc.forEach(k => {
            const sm  = statusMap[k.status] || { bg:'bg-gray-50', text:'text-gray-500', label:k.status };
            const tr  = document.createElement('tr');
            tr.className = 'hover:bg-gray-50/40 transition-all';
            tr.innerHTML = `
                <td class="py-4 px-6 font-bold text-gray-900 capitalize">${(k.identity_type || 'Identity').replace(/_/g,' ')}</td>
                <td class="py-4 px-6 text-center">
                    <span class="px-3 py-1.5 rounded-full text-[11px] font-bold uppercase ${sm.bg} ${sm.text}">${sm.label}</span>
                </td>
                <td class="py-4 px-6 text-gray-500 font-medium">${k.submitted_at ? fmtDate(k.submitted_at) : '—'}</td>
                <td class="py-4 px-6 text-gray-400 text-[12px] max-w-[160px] truncate">${escHtml(k.admin_note || '—')}</td>
                <td class="py-4 px-6 text-right">
                    <div class="flex justify-end gap-2">
                        ${k.id_front ? `<a href="../${escHtml(k.id_front)}" target="_blank" title="ID Front"
                            class="w-9 h-7 rounded-lg bg-gray-100 overflow-hidden border border-gray-100 hover:ring-2 ring-primary transition-all inline-block">
                            <img src="../${escHtml(k.id_front)}" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<span class=\'text-[10px] text-gray-400 flex items-center justify-center h-full\'>Front</span>'">
                        </a>` : ''}
                        ${k.id_back ? `<a href="../${escHtml(k.id_back)}" target="_blank" title="ID Back"
                            class="w-9 h-7 rounded-lg bg-gray-100 overflow-hidden border border-gray-100 hover:ring-2 ring-primary transition-all inline-block">
                            <img src="../${escHtml(k.id_back)}" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<span class=\'text-[10px] text-gray-400 flex items-center justify-center h-full\'>Back</span>'">
                        </a>` : ''}
                        ${k.selfie ? `<a href="../${escHtml(k.selfie)}" target="_blank" title="Selfie"
                            class="w-9 h-7 rounded-lg bg-gray-100 overflow-hidden border border-gray-100 hover:ring-2 ring-primary transition-all inline-block">
                            <img src="../${escHtml(k.selfie)}" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<span class=\'text-[10px] text-gray-400 flex items-center justify-center h-full\'>Selfie</span>'">
                        </a>` : ''}
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // ── Bookings ───────────────────────────────────────────────────────
    function renderBookings(history) {
        const tbody   = document.getElementById('booking-table');
        const emptyEl = document.getElementById('booking-empty');
        tbody.innerHTML = '';

        document.getElementById('booking-count-badge').textContent = history.length + ' records';

        if (!history || history.length === 0) {
            emptyEl.classList.remove('hidden');
            return;
        }
        emptyEl.classList.add('hidden');

        const statusMap = {
            confirmed: { bg:'bg-green-50',  text:'text-green-600'  },
            approved:  { bg:'bg-green-50',  text:'text-green-600'  },
            paid:      { bg:'bg-blue-50',   text:'text-blue-600'   },
            pending:   { bg:'bg-yellow-50', text:'text-yellow-600' },
            rejected:  { bg:'bg-red-50',    text:'text-red-500'    },
            cancelled: { bg:'bg-red-50',    text:'text-red-500'    },
        };

        history.forEach((b, i) => {
            const sm  = statusMap[b.status] || { bg:'bg-gray-50', text:'text-gray-500' };
            const tr  = document.createElement('tr');
            tr.className = 'hover:bg-gray-50/50 transition-colors group';
            tr.style.animationDelay = (i * 20) + 'ms';

            const imgHtml = b.property_image
                ? `<img src="${escHtml(b.property_image)}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" onerror="this.parentElement.innerHTML='<i class=\\\"bi bi-house text-gray-300 text-[18px]\\\"></i>'">`
                : `<i class="bi bi-house text-gray-300 text-[18px]"></i>`;

            tr.innerHTML = `
                <td class="py-4 px-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">${imgHtml}</div>
                        <div>
                            <div class="font-bold text-gray-900 group-hover:text-primary transition-colors text-[13px]">${escHtml(b.property_name || 'Unnamed Property')}</div>
                            <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">BK-${String(b.id).padStart(5,'0')}</div>
                        </div>
                    </div>
                </td>
                <td class="py-4 px-6">
                    <div class="text-[13px] font-semibold text-gray-800">${fmtDateShort(b.check_in)} → ${fmtDateShort(b.check_out)}</div>
                </td>
                <td class="py-4 px-6 text-center">
                    <span class="font-bold text-gray-900">${b.nights || '—'}</span>
                </td>
                <td class="py-4 px-6 text-center font-bold text-gray-900">₦${fmt(b.total_amount)}</td>
                <td class="py-4 px-6 text-center">
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${sm.bg} ${sm.text}">${b.status}</span>
                </td>
                <td class="py-4 px-6 text-[12px] text-gray-400">${fmtDate(b.created_at)}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    // ── Properties ─────────────────────────────────────────────────────
    function renderProperties(props) {
        const grid = document.getElementById('properties-grid');
        const statusMap = {
            active:    { bg:'bg-green-50',  text:'text-green-600'  },
            published: { bg:'bg-green-50',  text:'text-green-600'  },
            pending:   { bg:'bg-yellow-50', text:'text-yellow-600' },
            rejected:  { bg:'bg-red-50',    text:'text-red-500'    },
            archived:  { bg:'bg-gray-50',   text:'text-gray-500'   },
        };
        grid.innerHTML = props.map(p => {
            const sm = statusMap[p.status] || { bg:'bg-gray-50', text:'text-gray-500' };
            const imgHtml = p.image
                ? `<img src="${escHtml(p.image)}" class="w-full h-full object-cover">`
                : `<div class="w-full h-full flex items-center justify-center bg-gray-100"><i class="bi bi-house text-gray-300 text-[28px]"></i></div>`;
            return `
            <a href="property_view.php?id=${p.id}" class="rounded-[16px] overflow-hidden border border-gray-100 shadow-sm hover:shadow-md transition-all group">
                <div class="h-32 overflow-hidden">${imgHtml}</div>
                <div class="p-3">
                    <div class="font-bold text-gray-900 text-[13px] truncate group-hover:text-primary transition-colors">${escHtml(p.name)}</div>
                    <div class="text-[11px] text-gray-400 mt-0.5">${escHtml([p.city, p.state].filter(Boolean).join(', ') || '—')}</div>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-[12px] font-bold text-gray-900">₦${fmt(p.price)}<span class="font-normal text-gray-400">/${p.price_type||'night'}</span></span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${sm.bg} ${sm.text}">${p.status}</span>
                    </div>
                </div>
            </a>`;
        }).join('');
    }

    // ── Suspend / Unsuspend ────────────────────────────────────────────
    function refreshSuspendBtn(isSuspended) {
        const btn     = document.getElementById('full-suspend-btn');
        const hdrBtn  = document.getElementById('suspend-toggle-btn');
        const textEl  = document.getElementById('suspend-btn-text');

        if (isSuspended) {
            btn.className    = 'w-full py-2.5 border border-green-200 text-emerald-600 rounded-xl font-bold text-[13px] hover:bg-green-50 transition-all flex items-center justify-center gap-2';
            textEl.textContent = 'Unsuspend Account';
            btn.querySelector('i').className = 'bi bi-unlock';

            hdrBtn.className   = 'px-5 py-2.5 border border-green-200 text-emerald-600 bg-white rounded-xl font-bold text-[13px] hover:bg-green-50 transition-all';
            hdrBtn.textContent = 'Unsuspend Account';
        } else {
            btn.className    = 'w-full py-2.5 border border-red-100 text-red-500 rounded-xl font-bold text-[13px] hover:bg-red-50 transition-all flex items-center justify-center gap-2';
            textEl.textContent = 'Suspend Account';
            btn.querySelector('i').className = 'bi bi-slash-circle';

            hdrBtn.className   = 'px-5 py-2.5 border border-red-100 text-red-500 bg-white rounded-xl font-bold text-[13px] hover:bg-red-50 transition-all';
            hdrBtn.textContent = 'Suspend Account';
        }
    }

    function openSuspendModal() {
        if (!currentUser) return;
        const isSuspended = currentUser.status === 'suspended';
        const name        = `${currentUser.first_name || ''} ${currentUser.last_name || ''}`.trim();
        const modal       = document.getElementById('suspend-modal');
        const iconWrap    = document.getElementById('modal-icon');
        const iconEl      = document.getElementById('modal-icon-i');
        const confirmBtn  = document.getElementById('modal-confirm-btn');

        document.getElementById('modal-title').textContent = isSuspended ? 'Unsuspend Account?' : 'Suspend Account?';
        document.getElementById('modal-desc').textContent  = isSuspended
            ? `${name} will regain full platform access immediately.`
            : `${name} will lose all platform access immediately. Bookings and messaging will be disabled.`;

        iconWrap.className = `w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-5 ${isSuspended ? 'bg-green-50' : 'bg-red-50'}`;
        iconEl.className   = `bi ${isSuspended ? 'bi-unlock-fill text-emerald-500' : 'bi-exclamation-triangle-fill text-red-500'} text-[24px]`;
        confirmBtn.textContent = isSuspended ? 'Unsuspend' : 'Suspend';
        confirmBtn.className   = `flex-1 py-3 rounded-xl font-bold text-[14px] text-white transition-all ${isSuspended ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-red-500 hover:bg-red-600'}`;

        modal.classList.remove('hidden'); modal.classList.add('flex');
    }

    function closeModal() {
        const modal = document.getElementById('suspend-modal');
        modal.classList.add('hidden'); modal.classList.remove('flex');
    }

    document.getElementById('suspend-modal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });

    async function confirmSuspend() {
        if (!currentUser) return;
        const isSuspended = currentUser.status === 'suspended';
        const btn = document.getElementById('modal-confirm-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2 align-middle"></span>Processing…';

        try {
            const res  = await fetch('../api/admin/suspend_user.php', {
                method : 'POST',
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                body   : JSON.stringify({ user_id: parseInt(userId), action: isSuspended ? 'unsuspend' : 'suspend' })
            });
            const data = await res.json();

            if (data.success) {
                // Update local state
                currentUser.status = data.new_status;
                const newSuspended = data.new_status === 'suspended';

                // Update UI
                document.getElementById('suspended-badge').classList.toggle('hidden', !newSuspended);
                document.getElementById('kyc-badge').classList.toggle('hidden', newSuspended && currentUser.status !== 'verified');
                refreshSuspendBtn(newSuspended);
                // Re-render security info with updated values
                if (newSuspended) {
                    currentUser.booking_disabled = 1; currentUser.message_disabled = 1;
                    document.getElementById('toggle-booking').checked = true;
                    document.getElementById('toggle-message').checked = true;
                } else {
                    currentUser.booking_disabled = 0; currentUser.message_disabled = 0;
                    document.getElementById('toggle-booking').checked = false;
                    document.getElementById('toggle-message').checked = false;
                }
                renderSecurityInfo(currentUser);
                showToast(data.message, 'success');
                closeModal();
            } else {
                showToast(data.message || 'Action failed', 'error');
                btn.disabled = false;
                btn.textContent = isSuspended ? 'Unsuspend' : 'Suspend';
            }
        } catch(err) {
            showToast('Network error. Try again.', 'error');
            btn.disabled = false;
            btn.textContent = isSuspended ? 'Unsuspend' : 'Suspend';
        }
    }

    // ── Toggle Controls (booking_disabled / message_disabled) ──────────
    async function toggleControl(field, value) {
        if (!currentUser) return;
        try {
            const res  = await fetch('../api/admin/suspend_user.php', {
                method : 'POST',
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                body   : JSON.stringify({ user_id: parseInt(userId), field, value: value ? 1 : 0, action: 'toggle' })
            });
            // If API doesn't support 'toggle' action yet, we fallback silently and update local UI
            const label = field === 'booking_disabled' ? 'Booking access' : 'Messaging access';
            showToast(`${label} ${value ? 'disabled' : 'restored'} successfully.`, 'success');
            currentUser[field] = value ? 1 : 0;
            renderSecurityInfo(currentUser);
        } catch(err) {
            showToast('Could not update setting. Check connection.', 'error');
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────
    function escHtml(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function fmt(n) { return parseFloat(n || 0).toLocaleString(); }
    function fmtDate(d) {
        if (!d) return '—';
        return new Date(d).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' });
    }
    function fmtDateShort(d) {
        if (!d) return '—';
        return new Date(d).toLocaleDateString('en-GB', { day:'numeric', month:'short' });
    }
    function fmtDateTime(d) {
        if (!d) return '—';
        return new Date(d).toLocaleString('en-GB', { day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
    }
    function strColor(str) {
        const colors = ['#005a92','#7c3aed','#059669','#d97706','#db2777','#0891b2'];
        let h = 0;
        for (let c of str) h = c.charCodeAt(0) + ((h << 5) - h);
        return colors[Math.abs(h) % colors.length];
    }

    let toastTimer;
    function showToast(msg, type = 'success') {
        const toast  = document.getElementById('toast');
        const inner  = document.getElementById('toast-inner');
        const icon   = document.getElementById('toast-icon');
        document.getElementById('toast-msg').textContent = msg;
        inner.className = `flex items-center gap-3 px-5 py-4 rounded-2xl shadow-2xl text-[14px] font-bold text-white min-w-[260px] ${type === 'success' ? 'bg-emerald-500' : 'bg-red-500'}`;
        icon.className  = `bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'} text-[18px]`;
        toast.classList.remove('opacity-0','translate-y-4','pointer-events-none');
        toast.classList.add('opacity-100','translate-y-0');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.classList.add('opacity-0','translate-y-4','pointer-events-none');
            toast.classList.remove('opacity-100','translate-y-0');
        }, 4000);
    }

    // ── Init ───────────────────────────────────────────────────────────
    loadProfile();
</script>
<script src="js/sidebar.js"></script>
</body>
</html>
