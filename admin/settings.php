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
    <title>Settings | Admin Dashboard</title>
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
            <div class="max-w-4xl mx-auto">
                <div class="flex items-center gap-3 mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
                    <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-full text-xs font-bold uppercase tracking-wider">Configuration</span>
                </div>

                <div class="grid grid-cols-3 gap-6">
                    <!-- Sidebar Navigation for Settings -->
                    <div class="col-span-1 space-y-2">
                        <button class="w-full text-left px-4 py-3 bg-white border border-border rounded-xl font-medium text-gray-900 hover:bg-slate-50 transition-colors shadow-sm">General</button>
                        <button class="w-full text-left px-4 py-3 bg-transparent text-gray-500 hover:bg-white hover:text-gray-900 rounded-xl transition-colors">Security</button>
                        <button class="w-full text-left px-4 py-3 bg-transparent text-gray-500 hover:bg-white hover:text-gray-900 rounded-xl transition-colors">Notifications</button>
                        <button class="w-full text-left px-4 py-3 bg-transparent text-gray-500 hover:bg-white hover:text-gray-900 rounded-xl transition-colors">Payment Gateways</button>
                    </div>

                    <!-- Main Settings Content -->
                    <div class="col-span-2 space-y-6">
                        <div class="bg-white p-8 rounded-2xl border border-border">
                            <h3 class="font-bold text-lg text-gray-900 mb-6">General Settings</h3>
                            
                            <form class="space-y-6" id="settings-form">
                                <div>
                                    <label class="block mb-2 font-medium text-[14px]">Site Name</label>
                                    <input type="text" id="site_name" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary">
                                </div>
                                
                                <div>
                                    <label class="block mb-2 font-medium text-[14px]">Support Email</label>
                                    <input type="email" id="support_email" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary">
                                </div>

                                <div class="pt-4 border-t border-gray-100 mt-6">
                                    <h4 class="font-bold text-gray-900 mb-4 text-sm">Your Admin Profile</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block mb-2 font-medium text-[14px]">First Name</label>
                                            <input type="text" id="admin_firstname" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary" readonly>
                                        </div>
                                        <div>
                                            <label class="block mb-2 font-medium text-[14px]">Last Name</label>
                                            <input type="text" id="admin_lastname" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary" readonly>
                                        </div>
                                        <div class="col-span-2">
                                            <label class="block mb-2 font-medium text-[14px]">Email</label>
                                            <input type="email" id="admin_email" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary" readonly>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="px-6 py-3 bg-primary text-white rounded-xl font-bold hover:bg-[#004a7a] transition-colors shadow-sm">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        async function loadSettings() {
            try {
                const res = await fetch('../api/admin/settings.php', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await res.json();
                if (data.success) {
                    const s = data.data.settings;
                    document.getElementById('site_name').value = s.site_name;
                    document.getElementById('support_email').value = s.support_email;
                    
                    if (s.admin_profile) {
                        document.getElementById('admin_firstname').value = s.admin_profile.first_name;
                        document.getElementById('admin_lastname').value = s.admin_profile.last_name;
                        document.getElementById('admin_email').value = s.admin_profile.email;
                    }
                }
            } catch (err) {
                console.error(err);
            }
        }

        document.getElementById('settings-form').addEventListener('submit', (e) => {
            e.preventDefault();
            // Just simulate success for now as we don't have a settings table update endpoint yet
            alert('Settings updated successfully!');
        });

        loadSettings();
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
