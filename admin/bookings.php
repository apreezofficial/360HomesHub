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
    <title>Bookings | Admin Dashboard</title>
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
                    Management / <span class="text-gray-900">Bookings</span>
                </div>
                <div class="flex items-center gap-4">
                     <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center cursor-pointer">
                        <i class="bi bi-bell text-xl text-gray-600"></i>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Booking Management</h1>
                 <div class="relative inline-block">
                    <select class="appearance-none bg-white border border-border px-4 py-2 rounded-lg text-sm font-medium focus:outline-none pr-10 cursor-pointer">
                        <option>All bookings</option>
                        <option>Confirmed</option>
                        <option>Pending</option>
                        <option>Cancelled</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-border overflow-hidden min-h-[500px]">
                <table class="w-full text-left">
                    <thead class="bg-white border-b border-border">
                        <tr class="text-[13px] text-text-secondary">
                            <th class="py-4 px-6 font-medium">Property</th>
                            <th class="py-4 px-6 font-medium">Guest</th>
                            <th class="py-4 px-6 font-medium">Dates</th>
                            <th class="py-4 px-6 font-medium">Total Price</th>
                            <th class="py-4 px-6 font-medium">Status</th>
                            <th class="py-4 px-6 font-medium text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="bookings-table" class="divide-y divide-border">
                        <!-- Loaded via JS -->
                    </tbody>
                </table>
                <div id="empty-state" class="hidden flex flex-col items-center justify-center py-20">
                     <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                        <i class="bi bi-calendar-x text-2xl text-gray-300"></i>
                    </div>
                    <p class="text-gray-500 text-sm">No bookings found</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        async function fetchBookings() {
            try {
                const res = await fetch('../api/admin/bookings.php', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await res.json();
                
                const tbody = document.getElementById('bookings-table');
                const emptyState = document.getElementById('empty-state');
                tbody.innerHTML = '';

                if (!data.success || !data.data.bookings || data.data.bookings.length === 0) {
                    emptyState.classList.remove('hidden');
                    return;
                }
                emptyState.classList.add('hidden');

                data.data.bookings.forEach(b => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50 transition-colors text-[14px] group/row';

                    // improved Status Logic
                    const statusMap = {
                        'pending': { text: 'Pending Approval', class: 'bg-yellow-50 text-yellow-700 border-yellow-200' },
                        'confirmed': { text: 'Confirmed', class: 'bg-green-50 text-green-700 border-green-200' },
                        'cancelled': { text: 'Cancelled', class: 'bg-red-50 text-red-700 border-red-200' },
                        'rejected': { text: 'Rejected', class: 'bg-red-50 text-red-700 border-red-200' },
                        'completed': { text: 'Completed', class: 'bg-blue-50 text-blue-700 border-blue-200' },
                        'awaiting_payment': { text: 'Awaiting Payment', class: 'bg-orange-50 text-orange-700 border-orange-200' }
                    };
                    const st = statusMap[b.status] || { text: b.status, class: 'bg-gray-100 text-gray-600 border-gray-200' };

                    const checkIn = new Date(b.check_in).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                    const checkOut = new Date(b.check_out).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                    
                    const propName = b.property_name || 'Unknown Property';
                    // Robust Name Handling
                    const gFirst = b.guest_first_name && b.guest_first_name !== 'null' ? b.guest_first_name : 'N/A';
                    const gLast = b.guest_last_name && b.guest_last_name !== 'null' ? b.guest_last_name : '';
                    const guestName = `${gFirst} ${gLast}`.trim();

                    tr.innerHTML = `
                        <td class="py-4 px-6">
                            <div class="font-bold text-gray-900">${propName}</div>
                            <div class="text-xs text-gray-500">${b.property_city || ''}, ${b.property_state || ''}</div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="font-medium text-gray-900">${guestName}</div>
                            <div class="text-xs text-gray-500">${b.guest_email || 'No email'}</div>
                        </td>
                        <td class="py-4 px-6 text-gray-600">
                            <div>From: <span class="font-medium text-gray-900">${checkIn}</span></div>
                            <div>To: <span class="font-medium text-gray-900">${checkOut}</span></div>
                        </td>
                        <td class="py-4 px-6 font-bold text-gray-900">₦${parseFloat(b.total_amount).toLocaleString()}</td>
                        <td class="py-4 px-6">
                            <span class="px-2.5 py-1 rounded-md text-[12px] font-bold uppercase border ${st.class}">${st.text}</span>
                        </td>
                        <td class="py-4 px-6 text-right relative">
                            <div class="relative inline-block text-left">
                                <button onclick="toggleDropdown(${b.id})" class="p-2 rounded-full hover:bg-gray-100 transition-colors text-gray-400 hover:text-primary focus:outline-none">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <div id="dropdown-${b.id}" class="hidden absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-20 overflow-hidden transform origin-top-right transition-all">
                                    <div class="py-1">
                                        <a href="booking_details.php?id=${b.id}" class="group flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary transition-colors">
                                            <i class="bi bi-eye mr-3 text-gray-400 group-hover:text-primary"></i>
                                            View Details
                                        </a>
                                        <a href="#" onclick="alert('Modify Logic Modal')" class="group flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors">
                                            <i class="bi bi-pencil mr-3 text-gray-400 group-hover:text-blue-600"></i>
                                            Modify Booking
                                        </a>
                                        <a href="#" onclick="generatePaymentLink(${b.id}, ${b.total_amount})" class="group flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-green-600 transition-colors border-t border-gray-100">
                                            <i class="bi bi-credit-card mr-3 text-gray-400 group-hover:text-green-600"></i>
                                            Generate Payment Link
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
                
            } catch (err) {
                console.error(err);
            }
        }

        // Global functions
        function toggleDropdown(id) {
            document.querySelectorAll('[id^="dropdown-"]').forEach(el => {
                if (el.id !== `dropdown-${id}`) el.classList.add('hidden');
            });
            const d = document.getElementById(`dropdown-${id}`);
            if(d) d.classList.toggle('hidden');
            window.event.stopPropagation();
        }

        async function generatePaymentLink(bookingId, amount) {
             if(!confirm('Generate payment link for ₦' + parseFloat(amount).toLocaleString() + '?')) return;
             
             // Placeholder for API call
             // In future, call api/admin/generate_payment_link.php
             alert('Payment link generated (placeholder): https://36homes.com/pay/' + btoa(bookingId));
        }
        
        // Close dropdowns logic
        document.addEventListener('click', function(event) {
            if (!event.target.closest('td.text-right')) {
                document.querySelectorAll('[id^="dropdown-"]').forEach(el => el.classList.add('hidden'));
            }
        });

        // Initialize
        fetchBookings();
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
