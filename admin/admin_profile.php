<?php
session_start();
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/db.php';
require_once __DIR__ . '/../utils/jwt.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$mock = isset($_GET['mock']) ? true : false;
$admin_id = $_GET['id'] ?? $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | Admin Dashboard</title>
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
<body class="bg-gray-50 min-h-screen">
    <div class="flex min-h-screen">
        <aside class="w-[240px] fixed h-full bg-white z-50 shadow-sm border-r border-border"></aside>

        <main class="flex-1 ml-[240px] p-8 max-w-[1200px] mx-auto w-full">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-gray-400 mb-6 font-medium">
                <span>Settings</span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span>Admin roles</span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-gray-900 font-semibold" id="breadcrumb-name">Loading...</span>
            </div>

            <!-- Header -->
            <div class="flex justify-between items-start mb-8">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h1 class="text-2xl font-bold text-gray-900" id="profile-name">Loading...</h1>
                        <span id="role-badge" class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-600 uppercase tracking-wide border border-blue-100">Super admin</span>
                    </div>
                    <div class="text-sm text-gray-500 font-mono" id="profile-id">ID - ???</div>
                </div>

                <div class="flex gap-3" id="action-buttons">
                    <!-- Buttons injected via JS -->
                </div>
            </div>

            <div class="grid grid-cols-12 gap-6">
                <!-- Left Column -->
                <div class="col-span-8 space-y-6">
                    <!-- Account Info -->
                    <div class="bg-white rounded-2xl border border-border p-6 shadow-sm">
                        <h3 class="font-bold text-gray-900 mb-6 text-base">Account information</h3>
                        
                        <div class="grid grid-cols-2 gap-y-6">
                            <div>
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Email address</div>
                                <div class="font-medium text-gray-900" id="info-email">Loading...</div>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Status</div>
                                <div class="font-bold text-green-600 inline-flex items-center gap-1.5 bg-green-50 px-2 py-0.5 rounded text-xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                </div>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Date Joined</div>
                                <div class="font-medium text-gray-900" id="info-joined">Jan 12, 2024</div>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Last login</div>
                                <div class="font-medium text-gray-900 text-sm">2 hours ago <span class="text-gray-400 font-normal">(IP: 192.168.1.1)</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions -->
                    <div class="bg-white rounded-2xl border border-border p-6 shadow-sm">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-bold text-gray-900 text-base">Activity permission</h3>
                            <button class="text-primary text-sm font-semibold hover:underline decoration-2 underline-offset-4">Manage permission</button>
                        </div>
        
                        <div class="grid grid-cols-2 gap-4" id="permissions-grid">
                            <!-- Injected via JS -->
                        </div>
                    </div>
                </div>

                <!-- Right Column (Audit Log) -->
                <div class="col-span-4">
                    <div class="bg-white rounded-2xl border border-border p-6 shadow-sm h-full">
                        <h3 class="font-bold text-gray-900 mb-6 text-base">Recent audit log</h3>
                        <div class="space-y-6 relative before:absolute before:left-[7px] before:top-2 before:bottom-2 before:w-[2px] before:bg-gray-100 before:z-0">
                            
                            <!-- Log Item -->
                            <div class="relative pl-6 z-10">
                                <div class="absolute left-0 top-1.5 w-4 h-4 rounded-full border-[3px] border-white bg-gray-300 ring-1 ring-gray-100"></div>
                                <div class="flex justify-between items-start mb-1">
                                    <div class="text-sm font-semibold text-gray-900">Modified role - Sarah Jenkins</div>
                                    <div class="text-[10px] text-gray-400 font-medium whitespace-nowrap ml-2">2 hours ago</div>
                                </div>
                                <div class="text-xs text-gray-500 leading-relaxed">Changed Sarah Jenkins role from Operations to Safety and Trust.</div>
                            </div>

                             <!-- Log Item -->
                             <div class="relative pl-6 z-10">
                                <div class="absolute left-0 top-1.5 w-4 h-4 rounded-full border-[3px] border-white bg-gray-300 ring-1 ring-gray-100"></div>
                                <div class="flex justify-between items-start mb-1">
                                    <div class="text-sm font-semibold text-gray-900">Admin login</div>
                                    <div class="text-[10px] text-gray-400 font-medium whitespace-nowrap ml-2">3 hours ago</div>
                                </div>
                                <div class="text-xs text-gray-500 leading-relaxed">Successfully login via Macbook pro intel i7</div>
                            </div>

                             <!-- Log Item -->
                             <div class="relative pl-6 z-10">
                                <div class="absolute left-0 top-1.5 w-4 h-4 rounded-full border-[3px] border-white bg-gray-300 ring-1 ring-gray-100"></div>
                                <div class="flex justify-between items-start mb-1">
                                    <div class="text-sm font-semibold text-gray-900">Security alert</div>
                                    <div class="text-[10px] text-gray-400 font-medium whitespace-nowrap ml-2">Yesterday, 13:45pm</div>
                                </div>
                                <div class="text-xs text-gray-500 leading-relaxed">Flagged suspicious trade ID: #TR-92837</div>
                            </div>
                            
                            <!-- Log Item -->
                             <div class="relative pl-6 z-10">
                                <div class="absolute left-0 top-1.5 w-4 h-4 rounded-full border-[3px] border-white bg-gray-300 ring-1 ring-gray-100"></div>
                                <div class="flex justify-between items-start mb-1">
                                    <div class="text-sm font-semibold text-gray-900">Report exported</div>
                                    <div class="text-[10px] text-gray-400 font-medium whitespace-nowrap ml-2">Feb 4, 12:30pm</div>
                                </div>
                                <div class="text-xs text-gray-500 leading-relaxed">Financial summary (Nov 23, 2024 to Jan 21, 2025) exported. <a href="#" class="text-primary hover:underline">View PDF</a></div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Update Role Modal -->
    <div id="role-modal" class="fixed inset-0 bg-black/50 z-[60] hidden flex items-center justify-center backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-2xl w-[600px] shadow-2xl scale-95 transition-transform duration-300" id="role-modal-content">
            <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 rounded-t-2xl">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Update role</h3>
                    <p class="text-sm text-gray-500 mt-1">Change and configure an admin role and access</p>
                </div>
                <button onclick="closeRoleModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="bi bi-x-lg text-lg"></i>
                </button>
            </div>
            
            <div class="p-8">
                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Full Name</label>
                        <input type="text" id="modal-name" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium text-gray-900 outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all" value="Sarah Jenkins" readonly>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Work Email</label>
                        <input type="email" id="modal-email" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium text-gray-500 outline-none" value="sarah.jenkins@36homes.com" readonly>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-3">Select administration role</label>
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Operations Role -->
                        <label class="relative border border-gray-200 rounded-xl p-4 cursor-pointer hover:border-primary hover:bg-slate-50 transition-all group has-[:checked]:border-primary has-[:checked]:bg-blue-50/30 has-[:checked]:ring-1 has-[:checked]:ring-primary">
                            <input type="radio" name="role" value="operations" class="peer sr-only" checked>
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-sm text-gray-900">Operations</span>
                                <div class="w-4 h-4 rounded-full border border-gray-300 peer-checked:border-primary peer-checked:bg-primary transition-colors flex items-center justify-center">
                                    <div class="w-1.5 h-1.5 rounded-full bg-white opacity-0 peer-checked:opacity-100"></div>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-500 leading-relaxed group-hover:text-gray-600">Manage listings, bookings and respond to customer support requests.</p>
                        </label>
                        
                        <!-- Finance Role -->
                        <label class="relative border border-gray-200 rounded-xl p-4 cursor-pointer hover:border-primary hover:bg-slate-50 transition-all group has-[:checked]:border-primary has-[:checked]:bg-blue-50/30 has-[:checked]:ring-1 has-[:checked]:ring-primary">
                            <input type="radio" name="role" value="finance" class="peer sr-only">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-sm text-gray-900">Finance</span>
                                <div class="w-4 h-4 rounded-full border border-gray-300 peer-checked:border-primary peer-checked:bg-primary transition-colors flex items-center justify-center">
                                    <div class="w-1.5 h-1.5 rounded-full bg-white opacity-0 peer-checked:opacity-100"></div>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-500 leading-relaxed group-hover:text-gray-600">Access to payout reports, revenue dashboards and tax documents.</p>
                        </label>

                         <!-- Trust Role -->
                         <label class="relative border border-gray-200 rounded-xl p-4 cursor-pointer hover:border-primary hover:bg-slate-50 transition-all group has-[:checked]:border-primary has-[:checked]:bg-blue-50/30 has-[:checked]:ring-1 has-[:checked]:ring-primary">
                            <input type="radio" name="role" value="trust" class="peer sr-only">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-sm text-gray-900">Trust and Safety</span>
                                <div class="w-4 h-4 rounded-full border border-gray-300 peer-checked:border-primary peer-checked:bg-primary transition-colors flex items-center justify-center">
                                    <div class="w-1.5 h-1.5 rounded-full bg-white opacity-0 peer-checked:opacity-100"></div>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-500 leading-relaxed group-hover:text-gray-600">Handle dispute resolutions, user verification, customer support and security flags.</p>
                        </label>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-8 py-5 rounded-b-2xl border-t border-gray-100 flex justify-end gap-3">
                <button onclick="closeRoleModal()" class="px-6 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-200 transition-colors">Cancel</button>
                <button onclick="updateRole()" class="px-6 py-2.5 rounded-xl text-sm font-semibold bg-primary text-white hover:bg-[#004a7a] transition-colors shadow-sm">Update admin role</button>
            </div>
        </div>
    </div>


    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        const urlParams = new URLSearchParams(window.location.search);
        let adminId = urlParams.get('id') || 'current';
        let currentAdminData = null;

        async function fetchAdminProfile() {
            try {
                // If using 'current', the backend handles it or we can pass nothing
                let url = `../api/admin/detail.php${adminId !== 'current' ? '?id=' + adminId : ''}`;
                
                const res = await fetch(url, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await res.json();

                if (data.success) {
                    currentAdminData = data.data.admin;
                    // If fetching 'current', update adminId for future reference
                    if (adminId === 'current') adminId = currentAdminData.id;
                    
                    renderProfile(currentAdminData);
                    renderAuditLogs(data.data.logs);
                } else {
                    alert('Failed to load admin profile: ' + data.message);
                }
            } catch (err) {
                console.error(err);
                alert('Error loading profile');
            }
        }

        function renderProfile(admin) {
            document.getElementById('breadcrumb-name').textContent = `${admin.first_name} ${admin.last_name}`;
            document.getElementById('profile-name').innerHTML = `${admin.first_name} <span class="font-normal text-gray-500">${admin.last_name}</span>`;
            document.getElementById('profile-id').textContent = `ID - ADM-${admin.id.toString().padStart(5, '0')}`;
            document.getElementById('info-email').textContent = admin.email;
            
            const joinedDate = new Date(admin.created_at);
            document.getElementById('info-joined').textContent = joinedDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

            // Role Badge
            const badge = document.getElementById('role-badge');
            let roleDisplay = admin.role.replace('_', ' ');
            if (admin.role === 'super_admin') {
                badge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-600 uppercase tracking-wide border border-blue-100';
                badge.innerHTML = '<i class="bi bi-patch-check-fill mr-1"></i> Super admin';
            } else {
                badge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-50 text-purple-600 uppercase tracking-wide border border-purple-100';
                badge.innerHTML = `<i class="bi bi-circle-fill text-[6px] mr-1.5 relative -top-[1px]"></i> ${roleDisplay}`;
            }

            // Buttons - Check if viewing self or another admin
            // Assuming current logged in user ID is stored in token payload -> but we don't have it easily here without decoding
            // We can infer from role permissions or API could tell us 'is_self'
            // For now, show buttons unless super_admin (logic from before)
            const buttonsContainer = document.getElementById('action-buttons');
            // Check if viewing super admin (can't change super admin role usually)
            if (admin.role !== 'super_admin') {
                buttonsContainer.innerHTML = `
                    <button onclick="openRoleModal()" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300 px-4 py-2 rounded-xl text-sm font-semibold transition-all shadow-sm">
                        Change role
                    </button>
                    <button class="bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 px-4 py-2 rounded-xl text-sm font-semibold transition-all">
                        Suspend account
                    </button>
                `;
            } else {
                buttonsContainer.innerHTML = `
                   <!-- No actions for super admin unless self -->
                   <button onclick="logout()" class="border border-red-200 text-red-600 hover:bg-red-50 px-4 py-2 rounded-xl text-sm font-semibold transition-all">
                        Sign out
                    </button>
                `;
            }

            // Permissions Grid
            renderPermissions(admin.role);

            // Update Modal Inputs
            document.getElementById('modal-name').value = `${admin.first_name} ${admin.last_name}`;
            document.getElementById('modal-email').value = admin.email;
             // Select current role ratio
            const radios = document.getElementsByName('role');
            for(let r of radios) {
                if(r.value === admin.role) r.checked = true;
            }
        }

        function renderAuditLogs(logs) {
            const container = document.querySelector('.col-span-4 .space-y-6');
            if (logs.length === 0) {
                 container.innerHTML = '<div class="text-sm text-gray-500 pl-6">No recent activity.</div>';
                 return;
            }

            container.innerHTML = logs.map(log => {
                const timeLabel = formatTime(log.created_at);
                return `
                 <div class="relative pl-6 z-10">
                    <div class="absolute left-0 top-1.5 w-4 h-4 rounded-full border-[3px] border-white bg-gray-300 ring-1 ring-gray-100"></div>
                    <div class="flex justify-between items-start mb-1">
                        <div class="text-sm font-semibold text-gray-900">${log.action}</div>
                        <div class="text-[10px] text-gray-400 font-medium whitespace-nowrap ml-2">${timeLabel}</div>
                    </div>
                    <div class="text-xs text-gray-500 leading-relaxed">${log.details || ''}</div>
                </div>
                `;
            }).join('');
        }

        function formatTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffHrs = Math.floor(diffMs / (1000 * 60 * 60));
            const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

            if (diffHrs < 24 && date.getDate() === now.getDate()) {
                if (diffHrs === 0) {
                    const mins = Math.floor(diffMs / (1000 * 60));
                    return mins < 1 ? 'Just now' : `${mins} mins ago`;
                }
                return `${diffHrs} hours ago`;
            }
            
            if (diffDays === 1 || (diffDays === 0 && date.getDate() !== now.getDate())) {
                return `Yesterday, ${date.getHours()}:${date.getMinutes().toString().padStart(2, '0')}`;
            }

            if (diffDays < 7) {
                return `This week`; 
            }

            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute:'numeric' });
        }

        function renderPermissions(role) {
            // Simplified permissions map
            const permissions = [
                {
                    title: 'Super admin',
                    desc: 'Full system access, billing management, and users permission control.',
                    checks: ['super_admin']
                },
                {
                    title: 'Operations',
                    desc: 'Manage listings, bookings and respond to customer support requests.',
                    checks: ['super_admin', 'operations', 'admin']
                },
                {
                    title: 'Finance',
                    desc: 'Access to payout reports, revenue dashboards and tax documents.',
                    checks: ['super_admin', 'finance']
                },
                {
                    title: 'Trust and safety',
                    desc: 'Handle dispute resolutions, user verification, customer support and security flags.',
                    checks: ['super_admin', 'trust']
                }
            ];

            const grid = document.getElementById('permissions-grid');
            grid.innerHTML = permissions.map(p => {
                const checked = p.checks.includes(role);
                const icon = checked 
                    ? '<i class="bi bi-check-circle-fill text-primary text-lg"></i>' 
                    : '<i class="bi bi-x-circle-fill text-red-400 text-lg"></i>';
                
                return `
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-100 hover:border-gray-200 transition-colors group">
                    <div class="flex justify-between items-start mb-3">
                        <h4 class="font-bold text-gray-900 text-sm">${p.title}</h4>
                        ${icon}
                    </div>
                    <p class="text-[12px] text-gray-500 leading-relaxed group-hover:text-gray-600">
                        ${p.desc}
                    </p>
                </div>
                `;
            }).join('');
        }

        // Modal Logic
        function openRoleModal() {
            const modal = document.getElementById('role-modal');
            modal.classList.remove('hidden');
            // Trigger reflow
            void modal.offsetWidth;
            modal.classList.remove('opacity-0');
            document.getElementById('role-modal-content').classList.remove('scale-95');
            document.getElementById('role-modal-content').classList.add('scale-100');
        }

        function closeRoleModal() {
             const modal = document.getElementById('role-modal');
            modal.classList.add('opacity-0');
            document.getElementById('role-modal-content').classList.remove('scale-100');
            document.getElementById('role-modal-content').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        async function updateRole() {
            const selected = document.querySelector('input[name="role"]:checked').value;
            
            try {
                const res = await fetch('../api/admin/update_role.php', {
                    method: 'POST',
                    headers: { 
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: currentAdminData.id, role: selected })
                });

                const data = await res.json();
                if (data.success) {
                    alert(`Role updated to: ${selected}`);
                    closeRoleModal();
                    fetchAdminProfile(); // Reload data
                } else {
                    alert('Failed to update role');
                }
            } catch (err) {
                console.error(err);
                alert('Error updating role');
            }
        }
        
        function logout() {
            if(confirm('Logout?')) window.location.href = 'logout.php';
        }

        // Initialize
        fetchAdminProfile();
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
