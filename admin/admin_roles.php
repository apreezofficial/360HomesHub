<?php
session_start();
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/db.php';
require_once __DIR__ . '/../utils/jwt.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Generate JWT token
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
    <title>Admin Roles | Admin Dashboard</title>
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
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div class="text-[13px] text-gray-400 font-medium">
                    Settings / <span class="text-gray-900">Admin roles</span>
                </div>
                <div class="flex items-center gap-4">
                    <div class="relative w-[300px]">
                        <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" placeholder="Search admin..." class="w-full pl-10 pr-4 py-2.5 bg-white border border-border rounded-xl text-sm focus:outline-none focus:border-primary transition-colors">
                    </div>
                    <button onclick="window.location.href='add_user.php?role=admin'" class="bg-primary hover:bg-[#004a7a] text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2">
                        <i class="bi bi-plus-lg"></i>
                        Add admin
                    </button>
                </div>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-6">Administrative roles</h1>

            <!-- Admins Table -->
            <div class="bg-white rounded-2xl border border-border overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-white border-b border-border">
                        <tr class="text-[13px] text-text-secondary uppercase">
                            <th class="py-4 px-6 font-medium">Name</th>
                            <th class="py-4 px-6 font-medium">Role</th>
                            <th class="py-4 px-6 font-medium">Date added</th>
                            <th class="py-4 px-6 font-medium">Last active</th>
                            <th class="py-4 px-6 font-medium">Status</th>
                            <th class="py-4 px-6 font-medium text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="admins-table" class="divide-y divide-border">
                        <!-- Loading State -->
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-500">Loading admins...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Success Modal (Hidden by default) -->
            <div id="success-modal" class="fixed inset-0 bg-black/50 z-[60] hidden flex items-center justify-center backdrop-blur-sm opacity-0 transition-opacity duration-300">
                <div class="bg-white rounded-2xl p-8 w-[400px] text-center transform scale-95 transition-transform duration-300" id="success-modal-content">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="bi bi-check-lg text-3xl text-green-600"></i>
                    </div>
                    
                    <h3 class="text-xl font-bold text-gray-900 mb-2">New admin added</h3>
                    <p class="text-sm text-gray-500 mb-6 px-4 leading-relaxed">
                        You have added <span class="font-bold text-gray-900" id="new-admin-name">Alex Rivera</span> as an <span id="new-admin-role">operations admin</span>. 
                        Admin access password has been sent to <a href="#" class="text-primary hover:underline" id="new-admin-email">alex@rivera.36homes.com</a>
                    </p>
                    
                    <div class="bg-gray-50 rounded-lg p-3 border border-dashed border-gray-200 mb-2 relative group text-left">
                         <p class="text-[10px] text-gray-400 font-medium mb-1 uppercase tracking-wider">Direct login link</p>
                         <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 truncate mr-2" id="login-link">https://admin.36homes.com/auth/login?invite=123</span>
                            <button onclick="copyLink()" class="text-gray-400 hover:text-gray-600">
                                <i class="bi bi-clipboard"></i>
                            </button>
                         </div>
                         <div class="absolute right-2 top-2 text-[10px] text-red-500 font-medium">expires in 48 hours</div>
                    </div>
                    <p class="text-[11px] text-gray-400 mb-6 italic">Send this link if the user didn't receive the email</p>

                    <button onclick="closeSuccessModal()" class="w-full bg-primary text-white font-bold py-3 rounded-xl hover:bg-[#004a7a] transition-colors">
                        Done
                    </button>
                </div>
            </div>

        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        // Check for URL params to show success modal
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('new_admin')) {
             const name = urlParams.get('name') || 'New Admin';
             const role = urlParams.get('role') || 'Admin';
             const email = urlParams.get('email') || 'email@example.com';
             
             document.getElementById('new-admin-name').textContent = name;
             document.getElementById('new-admin-role').textContent = role.toLowerCase(); // + ' admin'
             document.getElementById('new-admin-email').textContent = email;
             
             const modal = document.getElementById('success-modal');
             modal.classList.remove('hidden');
             // Trigger reflow
             void modal.offsetWidth;
             modal.classList.remove('opacity-0');
             document.getElementById('success-modal-content').classList.remove('scale-95');
             document.getElementById('success-modal-content').classList.add('scale-100');
        }

        function closeSuccessModal() {
            const modal = document.getElementById('success-modal');
            modal.classList.add('opacity-0');
            document.getElementById('success-modal-content').classList.remove('scale-100');
            document.getElementById('success-modal-content').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }, 300);
        }

        async function fetchAdmins() {
            try {
                // Reuse users endpoint filtering for admins
                // In production, should have dedicated endpoint
                const res = await fetch('../api/admin/users.php?type=admins', { 
                    headers: { 'Authorization': `Bearer ${token}` } 
                });
                const data = await res.json();
                
                const tbody = document.getElementById('admins-table');
                tbody.innerHTML = '';

                if (!data.success || !data.data.users.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-gray-500">No admins found</td></tr>';
                    return;
                }

                const admins = data.data.users.filter(u => u.role === 'admin' || u.role === 'super_admin'); // Ensure client side filter too

                admins.forEach(u => {
                    // Mock data for missing fields
                    const roleLabel = u.role === 'super_admin' ? 'Super admin' : 'Operations'; // Mock specific roles
                    const roleBadge = u.role === 'super_admin' 
                        ? 'bg-blue-50 text-blue-700' 
                        : 'bg-emerald-50 text-emerald-700';
                    
                    const status = u.is_verified ? 'Active' : 'Invited';
                    const statusColor = u.is_verified ? 'text-green-600' : 'text-orange-500';

                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50 transition-colors text-[14px] cursor-pointer';
                    tr.onclick = (e) => {
                        if(!e.target.closest('button')) window.location.href = `admin_profile.php?id=${u.id}`;
                    };

                    tr.innerHTML = `
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-gray-500 font-bold border-2 border-white shadow-sm">
                                    ${u.first_name ? u.first_name[0].toUpperCase() : 'A'}
                                </div>
                                <div class="font-bold text-gray-900">${u.first_name || 'Admin'} ${u.last_name || ''}</div>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold ${roleBadge}">
                                ${roleLabel}
                            </span>
                        </td>
                        <td class="py-4 px-6 text-gray-500">
                           ${new Date(u.created_at).toLocaleDateString('en-US', {month: 'long', day: 'numeric', year: 'numeric'})}
                        </td>
                        <td class="py-4 px-6 text-gray-500">
                            2 hours ago
                        </td>
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full ${u.is_verified ? 'bg-green-500' : 'bg-orange-500'}"></div>
                                <span class="font-medium ${statusColor}">${status}</span>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-right">
                             <button class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400 transition-colors">
                                <i class="bi bi-chevron-right text-xs"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

            } catch (err) {
                console.error(err);
                document.getElementById('admins-table').innerHTML = '<tr><td colspan="6" class="py-8 text-center text-red-500">Failed to load admins</td></tr>';
            }
        }

        fetchAdmins();
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
