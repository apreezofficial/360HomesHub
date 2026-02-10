<?php
session_start();
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/jwt.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Admin Dashboard</title>
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

        <main class="flex-1 ml-[240px] p-8 max-w-[1000px] mx-auto w-full">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-1">Notifications</h1>
                    <p class="text-sm text-gray-500">System alerts and activity updates.</p>
                </div>
                <button onclick="markAllRead()" class="text-sm font-semibold text-primary hover:text-blue-700 transition-colors flex items-center gap-2">
                    <i class="bi bi-check-all text-lg"></i>
                    Mark all as read
                </button>
            </div>

            <!-- List -->
            <div class="space-y-4" id="notifications-list">
                <!-- Loading State -->
                <div class="animate-pulse space-y-4">
                    <div class="h-20 bg-gray-200 rounded-xl w-full"></div>
                    <div class="h-20 bg-gray-200 rounded-xl w-full"></div>
                </div>
            </div>

            <!-- Empty State (Hidden) -->
            <div id="empty-state" class="hidden flex flex-col items-center justify-center py-16 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-400">
                    <i class="bi bi-bell-slash text-2xl"></i>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">No notifications</h3>
                <p class="text-sm text-gray-500">You're all caught up!</p>
            </div>

        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        async function fetchNotifications() {
            try {
                const res = await fetch('../api/admin/notifications.php', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await res.json();
                
                const container = document.getElementById('notifications-list');
                const emptyState = document.getElementById('empty-state');
                container.innerHTML = '';

                if (!data.success || data.data.length === 0) {
                    emptyState.classList.remove('hidden');
                    return;
                }
                
                emptyState.classList.add('hidden');

                data.data.forEach(n => {
                    const isUnread = n.is_read == 0;
                    const timeLabel = formatTime(n.created_at);
                    
                    // Icon logic based on type
                    let iconClass = 'bi-info-circle text-blue-500 bg-blue-50';
                    if (n.type === 'success') iconClass = 'bi-check-circle text-green-500 bg-green-50';
                    if (n.type === 'warning') iconClass = 'bi-exclamation-triangle text-orange-500 bg-orange-50';
                    if (n.type === 'error') iconClass = 'bi-x-circle text-red-500 bg-red-50';
                    
                    const div = document.createElement('div');
                    div.className = `p-4 rounded-xl border transition-all flex items-start gap-4 ${isUnread ? 'bg-white border-primary/30 shadow-sm border-l-4 border-l-primary' : 'bg-gray-50 border-gray-100 opacity-75 hover:opacity-100'}`;
                    
                    div.innerHTML = `
                        <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center ${iconClass.split(' ').slice(1).join(' ')}">
                             <i class="bi ${iconClass.split(' ')[0]} text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start mb-1">
                                <h4 class="font-bold text-gray-900 text-sm ${isUnread ? '' : 'font-medium'}">${n.title}</h4>
                                <span class="text-[11px] text-gray-400 whitespace-nowrap ml-2">${timeLabel}</span>
                            </div>
                            <p class="text-sm text-gray-600 leading-relaxed truncate pr-4">${n.message}</p>
                        </div>
                        ${isUnread ? `
                        <button onclick="markRead(${n.id}, this)" class="w-8 h-8 flex items-center justify-center text-gray-300 hover:text-primary transition-colors" title="Mark as read">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        ` : ''}
                    `;
                    container.appendChild(div);
                });

            } catch (err) {
                console.error(err);
            }
        }

        async function markRead(id, btn) {
            try {
                await fetch('../api/admin/notifications.php', {
                    method: 'PATCH',
                    headers: { 
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id })
                });
                // Optimistic UI update
                // Reload list to update style properly or modify classes
                fetchNotifications();
            } catch (err) {
                console.error(err);
            }
        }
        
        async function markAllRead() {
             if(!confirm('Mark all as read?')) return;
             try {
                await fetch('../api/admin/notifications.php', {
                    method: 'PATCH',
                    headers: { 
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ all: true })
                });
                fetchNotifications();
            } catch (err) {
                console.error(err);
            }
        }

        function formatTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffHrs = Math.floor(diffMs / (1000 * 60 * 60));
            
            if (diffHrs < 24 && date.getDate() === now.getDate()) {
                if (diffHrs === 0) {
                    const mins = Math.floor(diffMs / (1000 * 60));
                    return mins < 1 ? 'Just now' : `${mins}m ago`;
                }
                return `${diffHrs}h ago`;
            }
            if (diffHrs < 48) return 'Yesterday';
            return date.toLocaleDateString();
        }

        fetchNotifications();
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
