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
    <title>Communications | Admin Dashboard</title>
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
                    Communications / <span class="text-gray-900">Notifications</span>
                </div>
                <div class="flex items-center gap-4">
                     <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center cursor-pointer">
                        <i class="bi bi-bell text-xl text-gray-600"></i>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Communication Center</h1>
                <p class="text-text-secondary text-sm mt-1">Blast notifications or send direct messages</p>
            </div>

            <div class="grid grid-cols-2 gap-8">
                <!-- Notifications -->
                <div class="bg-white rounded-2xl border border-border p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-primary">
                            <i class="bi bi-megaphone text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-gray-900">Send Notification</h3>
                            <p class="text-xs text-text-secondary">Targeted or Global In-App alerts</p>
                        </div>
                    </div>
                    
                    <form id="notif-form" class="space-y-5">
                        <div>
                            <label class="block mb-2 font-medium text-[14px]">Recipient</label>
                            <div class="relative">
                                <select id="notif-target" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary appearance-none cursor-pointer">
                                    <option value="all">All Users</option>
                                    <!-- Dynamic options can be added here -->
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-2 font-medium text-[14px]">Message</label>
                            <textarea id="notif-msg" rows="4" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary resize-none" placeholder="Type your notification here..." required></textarea>
                        </div>

                        <button type="submit" class="w-full py-3.5 bg-primary text-white rounded-xl font-bold hover:bg-[#004a7a] transition-colors shadow-sm">Send Alert</button>
                        <p id="notif-status" class="text-center text-sm font-medium h-5"></p>
                    </form>
                </div>

                <!-- Direct Message -->
                <div class="bg-white rounded-2xl border border-border p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-full bg-purple-50 flex items-center justify-center text-purple-600">
                            <i class="bi bi-chat-left-dots text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-gray-900">Direct Message</h3>
                            <p class="text-xs text-text-secondary">Send a private message to a specific user</p>
                        </div>
                    </div>

                    <form id="msg-form" class="space-y-5">
                        <div>
                            <label class="block mb-2 font-medium text-[14px]">User ID / Receiver</label>
                            <input type="number" id="msg-receiver" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary" placeholder="Enter User ID" required>
                        </div>
                        
                        <div>
                            <label class="block mb-2 font-medium text-[14px]">Message Content</label>
                            <textarea id="msg-content" rows="4" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary resize-none" placeholder="Enter private message..." required></textarea>
                        </div>

                        <button type="submit" class="w-full py-3.5 bg-gray-900 text-white rounded-xl font-bold hover:bg-gray-800 transition-colors shadow-sm"><i class="bi bi-send mr-2"></i> Send Message</button>
                        <p id="msg-status" class="text-center text-sm font-medium h-5"></p>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        const params = new URLSearchParams(window.location.search);
        const prefillId = params.get('user_id');

        if (prefillId) {
            document.getElementById('msg-receiver').value = prefillId;
            const targetSelect = document.getElementById('notif-target');
            const opt = document.createElement('option');
            opt.value = prefillId;
            opt.textContent = `User ID: ${prefillId}`;
            opt.selected = true;
            targetSelect.appendChild(opt);
        }

        // Handle Notification
        document.getElementById('notif-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const status = document.getElementById('notif-status');
            status.textContent = 'Sending...';
            status.className = 'text-center text-sm font-medium text-gray-500';

            // Placeholder for API call
            try {
                // To be implemented or connected if API exists
                const res = await fetch('../api/admin/send_notification.php', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ target: document.getElementById('notif-target').value, message: document.getElementById('notif-msg').value })
                });
                const response = await res.json();
                status.textContent = response.message;
                status.className = `text-center text-sm font-medium ${response.success ? 'text-green-600' : 'text-red-500'}`;
                if (response.success) document.getElementById('notif-msg').value = '';
            } catch (err) {
                status.textContent = 'Failed to send notification.';
                status.className = 'text-center text-sm font-medium text-red-500';
            }
        });

        // Handle Message
        document.getElementById('msg-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const status = document.getElementById('msg-status');
            status.textContent = 'Sending...';
            status.className = 'text-center text-sm font-medium text-gray-500';

            try {
                const res = await fetch('../api/admin/send_message.php', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ receiver_id: document.getElementById('msg-receiver').value, message: document.getElementById('msg-content').value })
                });
                const response = await res.json();
                status.textContent = response.message;
                status.className = `text-center text-sm font-medium ${response.success ? 'text-green-600' : 'text-red-500'}`;
                if (response.success) document.getElementById('msg-content').value = '';
            } catch (err) {
                status.textContent = 'Failed to send message.';
                status.className = 'text-center text-sm font-medium text-red-500';
            }
        });
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

