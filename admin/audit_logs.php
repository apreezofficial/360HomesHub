<?php
session_start();
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/jwt.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | Admin Dashboard</title>
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
            <!-- Header -->
             <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-1">Audit Logs</h1>
                    <p class="text-sm text-gray-500">Track all administrative actions and system events.</p>
                </div>
                 <div class="flex items-center gap-3">
                    <div class="relative w-[300px]">
                        <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="search-logs" placeholder="Search logs..." class="w-full pl-10 pr-4 py-2.5 bg-white border border-border rounded-xl text-sm focus:outline-none focus:border-primary transition-colors">
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-border overflow-hidden shadow-sm">
                <table class="w-full text-left">
                    <thead class="bg-white border-b border-border">
                        <tr class="text-[13px] text-text-secondary uppercase">
                            <th class="py-4 px-6 font-medium w-[25%]">Admin</th>
                            <th class="py-4 px-6 font-medium w-[20%]">Action</th>
                            <th class="py-4 px-6 font-medium w-[30%]">Details</th>
                             <th class="py-4 px-6 font-medium w-[15%]">IP Address</th>
                            <th class="py-4 px-6 font-medium text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody id="logs-table" class="divide-y divide-border">
                         <!-- Loading -->
                         <tr><td colspan="5" class="py-8 text-center text-gray-500">Loading logs...</td></tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination (Simple dummy for now) -->
            <div class="flex justify-between items-center mt-6 text-sm text-gray-500 px-2" id="pagination">
                <span>Showing recent 100 logs</span>
               <!-- <div class="flex gap-2">
                    <button class="px-3 py-1 bg-white border border-gray-200 rounded-lg hover:bg-gray-50" disabled>Previous</button>
                    <button class="px-3 py-1 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">Next</button>
                </div> -->
            </div>

        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        async function fetchLogs() {
            try {
                const res = await fetch('../api/admin/audit_logs.php', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await res.json();
                
                const table = document.getElementById('logs-table');
                table.innerHTML = '';

                if (!data.success || data.data.length === 0) {
                    table.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-gray-500">No logs found</td></tr>';
                    return;
                }

                // Filter logic
                const search = document.getElementById('search-logs').value.toLowerCase();
                const filtered = data.data.filter(l => 
                    (l.first_name || '').toLowerCase().includes(search) || 
                    (l.action || '').toLowerCase().includes(search) || 
                    (l.details || '').toLowerCase().includes(search)
                );

                if (filtered.length === 0) {
                     table.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-gray-500">No matching logs</td></tr>';
                     return;
                }

                filtered.forEach(l => {
                    const date = new Date(l.created_at);
                    const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50 transition-colors text-[14px]';
                    tr.innerHTML = `
                         <td class="py-4 px-6">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600 border border-gray-200">
                                    ${l.first_name ? l.first_name[0].toUpperCase() : 'A'}
                                </div>
                                <div class="font-medium text-gray-900 truncate max-w-[140px]">${l.first_name || 'System'} ${l.last_name || ''}</div>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-gray-900 font-medium">${l.action}</td>
                        <td class="py-4 px-6 text-gray-500 max-w-[300px] truncate" title="${l.details}">${l.details}</td>
                        <td class="py-4 px-6 text-gray-500 font-mono text-xs">${l.ip_address || '-'}</td>
                        <td class="py-4 px-6 text-right text-gray-400 text-xs whitespace-nowrap">${formattedDate}</td>
                    `;
                    table.appendChild(tr);
                });

            } catch (err) {
                console.error(err);
                document.getElementById('logs-table').innerHTML = '<tr><td colspan="5" class="py-8 text-center text-red-500">Error loading logs</td></tr>';

            }
        }
        
        document.getElementById('search-logs').addEventListener('input', fetchLogs);

        fetchLogs();
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
