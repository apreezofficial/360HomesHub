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
    <title>User Profile | Admin Dashboard</title>
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
        <aside class="w-[240px] fixed h-full bg-white z-50 border-r border-border"></aside>

        <main class="flex-1 ml-[240px] p-8 max-w-[1200px] mx-auto w-full">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-gray-400 mb-6">
                <a href="users.php" class="hover:text-primary transition-colors">Users</a>
                <i class="bi bi-chevron-right text-xs"></i>
                <span class="text-gray-900 font-medium">Profile</span>
            </div>

            <!-- Header -->
            <div class="flex justify-between items-start mb-8">
                <div class="flex items-center gap-4">
                    <div id="header-avatar" class="w-16 h-16 rounded-full bg-gray-200 border-2 border-white shadow-md flex items-center justify-center text-xl font-bold text-gray-500 overflow-hidden">
                        <!-- Img injected here -->
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900" id="user-name">Loading...</h1>
                        <div class="flex items-center gap-2 mt-1">
                            <span id="user-role" class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider">USER</span>
                            <span class="text-gray-400 text-sm flex items-center gap-1"><i class="bi bi-geo-alt"></i> <span id="user-location">Unknown</span></span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium transition-colors">Edit Details</button>
                    <button class="px-4 py-2 bg-red-50 border border-red-100 text-red-600 rounded-lg hover:bg-red-100 text-sm font-medium transition-colors">Suspend User</button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left Column: Personal Info -->
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl p-6 border border-border shadow-sm">
                        <h3 class="font-bold text-gray-900 mb-4 text-lg">Contact Information</h3>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 shrink-0"><i class="bi bi-envelope"></i></div>
                                <div>
                                    <div class="text-xs text-gray-400 uppercase tracking-wide font-bold mb-0.5">Email Address</div>
                                    <div class="text-sm font-medium text-gray-900 break-all" id="info-email">...</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-green-50 flex items-center justify-center text-green-600 shrink-0"><i class="bi bi-telephone"></i></div>
                                <div>
                                    <div class="text-xs text-gray-400 uppercase tracking-wide font-bold mb-0.5">Phone Number</div>
                                    <div class="text-sm font-medium text-gray-900" id="info-phone">...</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center text-purple-600 shrink-0"><i class="bi bi-calendar3"></i></div>
                                <div>
                                    <div class="text-xs text-gray-400 uppercase tracking-wide font-bold mb-0.5">Joined Date</div>
                                    <div class="text-sm font-medium text-gray-900" id="info-joined">...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-border shadow-sm">
                        <h3 class="font-bold text-gray-900 mb-4 text-lg">Verification Status</h3>
                        
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl mb-3">
                            <div class="flex items-center gap-3">
                                <i class="bi bi-envelope-check text-green-500 text-lg"></i>
                                <span class="text-sm font-medium text-gray-700">Email Verified</span>
                            </div>
                            <i class="bi bi-check-circle-fill text-green-500"></i>
                        </div>
                         <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                            <div class="flex items-center gap-3">
                                <i class="bi bi-shield-check text-gray-400 text-lg"></i>
                                <span class="text-sm font-medium text-gray-700">Identity Verified</span>
                            </div>
                             <span id="kyc-badge"><i class="bi bi-dash-circle text-gray-300"></i></span>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Stats & Activity -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-white p-5 rounded-2xl border border-border shadow-sm">
                            <div class="text-gray-400 text-sm font-medium mb-1">Total Listings</div>
                            <div class="text-2xl font-bold text-gray-900" id="stat-listings">0</div>
                        </div>
                         <div class="bg-white p-5 rounded-2xl border border-border shadow-sm">
                            <div class="text-gray-400 text-sm font-medium mb-1">Total Bookings</div>
                            <div class="text-2xl font-bold text-gray-900" id="stat-bookings">0</div>
                        </div>
                         <div class="bg-white p-5 rounded-2xl border border-border shadow-sm">
                            <div class="text-gray-400 text-sm font-medium mb-1">Total Spent</div>
                            <div class="text-2xl font-bold text-gray-900" id="stat-spent">$0.00</div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white rounded-2xl border border-border shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-border flex justify-between items-center">
                            <h3 class="font-bold text-gray-900 text-lg">Recent Booking Activity</h3>
                            <button class="text-sm text-primary hover:underline">View All</button>
                        </div>
                        <div class="p-8 text-center" id="activity-container">
                             <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-400 mb-3"><i class="bi bi-clock-history text-xl"></i></div>
                             <p class="text-gray-500 text-sm">No recent activity found for this user.</p>
                        </div>
                    </div>

                </div>
            </div>

        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';
        
        const urlParams = new URLSearchParams(window.location.search);
        const userId = urlParams.get('id');

        if (!userId) {
            alert('User ID missing');
            window.location.href = 'users.php';
        }

        async function fetchUserDetails() {
            try {
                const res = await fetch(`../api/admin/user_detail.php?id=${userId}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await res.json();
                
                if (data.success) {
                    const u = data.data.user;
                    const stats = data.data.stats;

                    // Info
                    const fName = u.first_name && u.first_name !== 'null' ? u.first_name : 'N/A';
                    const lName = u.last_name && u.last_name !== 'null' ? u.last_name : '';
                    document.getElementById('user-name').textContent = `${fName} ${lName}`;
                    
                    document.getElementById('user-role').textContent = u.role;
                    document.getElementById('info-email').textContent = u.email;
                    document.getElementById('info-phone').textContent = u.phone || 'Not provided';
                    document.getElementById('info-joined').textContent = new Date(u.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
                    
                    // Avatar
                    const avatarEl = document.getElementById('header-avatar');
                    if (u.profile_pic && u.profile_pic !== 'null') {
                        avatarEl.innerHTML = `<img src="${u.profile_pic.startsWith('http') ? u.profile_pic : '../' + u.profile_pic}" class="w-full h-full object-cover">`;
                    } else {
                        avatarEl.textContent = (fName[0] || 'U').toUpperCase();
                    }

                    // KYC
                    if (u.is_verified) {
                         document.getElementById('kyc-badge').innerHTML = '<i class="bi bi-check-circle-fill text-green-500"></i>';
                    }

                    // Stats
                    document.getElementById('stat-listings').textContent = stats.listings;
                    document.getElementById('stat-bookings').textContent = stats.bookings;
                    
                    // Recent Activity
                    const acts = data.data.recent_activity;
                    if (acts && acts.length > 0) {
                        const container = document.getElementById('activity-container');
                        container.innerHTML = '';
                        container.className = 'p-0'; // Remove padding for list items
                        
                        const list = document.createElement('div');
                        list.className = 'divide-y divide-gray-100';
                        
                        acts.forEach(booking => {
                            const dateStr = new Date(booking.created_at).toLocaleDateString();
                            const statusColor = booking.status === 'confirmed' ? 'text-green-600 bg-green-50' : (booking.status === 'pending' ? 'text-yellow-600 bg-yellow-50' : 'text-gray-600 bg-gray-50');
                            
                            list.innerHTML += `
                                <div class="p-4 flex justify-between items-center hover:bg-gray-50 transition-colors">
                                    <div class="text-left">
                                        <div class="font-bold text-sm text-gray-800">${booking.property_name || 'Unknown Property'}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">Booking #${booking.id} • ${dateStr}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-sm text-gray-900">$${parseFloat(booking.total_amount).toLocaleString()}</div>
                                        <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded ${statusColor}">${booking.status}</span>
                                    </div>
                                </div>
                            `;
                        });
                        container.appendChild(list);
                    }
                } else {
                    alert('Error loading profile: ' + data.message);
                }

            } catch (err) {
                console.error(err);
                alert('Connection failed');
            }
        }

        fetchUserDetails();
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
