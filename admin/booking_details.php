<?php
session_start();
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/db.php';
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
    <title>Booking Details | Admin</title>
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
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-[#F9FAFB] min-h-screen font-outfit">
    <div class="flex min-h-screen">
        <aside class="w-[260px] fixed h-full bg-white z-50"></aside>

        <main class="flex-1 ml-[260px] min-h-screen p-8">
            <!-- Breadcrumbs -->
            <div class="text-[14px] text-gray-400 mb-8 flex items-center gap-2">
                <a href="bookings.php" class="hover:text-gray-900 transition-colors">Bookings</a>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-medium" id="breadcrumb-id">Booking Details</span>
            </div>

            <!-- Header Section -->
            <div class="flex justify-between items-start mb-10">
                <div class="flex items-center gap-5">
                    <div id="booking-icon" class="w-16 h-16 rounded-2xl bg-[#005a92] flex items-center justify-center text-white shadow-lg shadow-primary/20">
                        <i class="bi bi-calendar-check text-[28px]"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h1 class="text-[28px] font-bold text-gray-900" id="header-id">Booking #...</h1>
                            <span id="status-badge" class="px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-wider"></span>
                        </div>
                        <div class="text-[14px] text-gray-400 flex items-center gap-2">
                            <span id="header-dates">...</span>
                            <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                            <span id="header-property">...</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="window.print()" class="px-6 py-3 border border-gray-100 text-gray-600 bg-white rounded-xl font-bold text-[14px] hover:bg-gray-50 transition-all flex items-center gap-2">
                        <i class="bi bi-printer"></i> Print Receipt
                    </button>
                    <button class="px-6 py-3 bg-[#005a92] text-white rounded-xl font-bold text-[14px] shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all">
                        Cancel Booking
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-8">
                <!-- Main Content -->
                <div class="col-span-8 space-y-8">
                    <!-- Property Details -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-8">
                        <h3 class="text-[18px] font-bold text-gray-900 mb-6 flex items-center gap-3">
                            <i class="bi bi-house text-gray-400"></i> Property Information
                        </h3>
                        <div class="flex gap-8">
                            <div id="property-image" class="w-48 h-32 rounded-2xl bg-gray-100 overflow-hidden shrink-0">
                                <!-- Image here -->
                            </div>
                            <div class="flex-1 space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <div class="text-[12px] font-bold text-gray-400 uppercase tracking-wider mb-1">Property Name</div>
                                        <div class="text-[15px] font-bold text-gray-900" id="prop-name">...</div>
                                    </div>
                                    <div>
                                        <div class="text-[12px] font-bold text-gray-400 uppercase tracking-wider mb-1">Location</div>
                                        <div class="text-[15px] font-bold text-gray-700" id="prop-location">...</div>
                                    </div>
                                    <div>
                                        <div class="text-[12px] font-bold text-gray-400 uppercase tracking-wider mb-1">Host Name</div>
                                        <div class="text-[15px] font-bold text-primary" id="prop-host">...</div>
                                    </div>
                                    <div>
                                        <div class="text-[12px] font-bold text-gray-400 uppercase tracking-wider mb-1">Price Per Night</div>
                                        <div class="text-[15px] font-bold text-gray-900" id="prop-price">...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm overflow-hidden">
                        <div class="p-8 border-b border-gray-50">
                            <h3 class="text-[18px] font-bold text-gray-900 flex items-center gap-3">
                                <i class="bi bi-wallet2 text-gray-400"></i> Payment Information
                            </h3>
                        </div>
                        <div class="p-8 space-y-6">
                            <div class="grid grid-cols-3 gap-8">
                                <div>
                                    <div class="text-[12px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Amount</div>
                                    <div class="text-[24px] font-bold text-gray-900" id="pay-total">...</div>
                                </div>
                                <div>
                                    <div class="text-[12px] font-bold text-gray-400 uppercase tracking-wider mb-1">Payment Status</div>
                                    <span id="pay-status" class="px-3 py-1 bg-yellow-50 text-yellow-600 rounded-full text-[11px] font-bold uppercase">Pending</span>
                                </div>
                                <div>
                                    <div class="text-[12px] font-bold text-gray-400 uppercase tracking-wider mb-1">Payment Reference</div>
                                    <div class="text-[14px] font-medium text-gray-500" id="pay-ref">-</div>
                                </div>
                            </div>

                            <div id="payment-link-section" class="bg-gray-50 rounded-2xl p-6 border border-gray-100 hidden">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="text-[13px] font-bold text-gray-900 mb-1">Payment Link</div>
                                        <div class="text-[12px] text-gray-400 break-all" id="pay-link-text">...</div>
                                    </div>
                                    <button id="copy-link-btn" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-[12px] font-bold text-gray-700 hover:bg-gray-50 transition-all shrink-0">
                                        Copy Link
                                    </button>
                                </div>
                            </div>
                            
                            <button id="gen-link-btn" class="w-full py-4 bg-[#005a92] text-white rounded-xl font-bold text-[14px] hover:bg-primary/90 transition-all flex items-center justify-center gap-3 hidden">
                                <i class="bi bi-link-45deg text-xl"></i> Generate Payment Link
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar -->
                <div class="col-span-4 space-y-8">
                    <!-- Guest Details -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-8">
                        <h3 class="text-[18px] font-bold text-gray-900 mb-6">Guest Details</h3>
                        <div class="flex items-center gap-4 mb-6">
                            <div id="guest-avatar" class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden">
                                <!-- Avatar -->
                            </div>
                            <div>
                                <div class="text-[16px] font-bold text-gray-900" id="guest-name">...</div>
                                <div class="text-[13px] text-gray-400" id="guest-email">...</div>
                            </div>
                        </div>
                        <div class="space-y-4 pt-4 border-t border-gray-50">
                            <div class="flex justify-between items-center text-[13px]">
                                <span class="text-gray-400">Phone</span>
                                <span class="font-bold text-gray-900" id="guest-phone">...</span>
                            </div>
                            <div class="flex justify-between items-center text-[13px]">
                                <span class="text-gray-400">Verified</span>
                                <span id="guest-verified" class="px-2 py-0.5 bg-green-50 text-green-500 rounded text-[10px] font-bold uppercase">No</span>
                            </div>
                            <a href="#" id="guest-profile-link" class="block w-full text-center py-3 border border-gray-100 rounded-xl text-[13px] font-bold text-gray-600 hover:bg-gray-50 transition-all">
                                View Full Profile
                            </a>
                        </div>
                    </div>

                    <!-- Booking Log -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-8">
                        <h3 class="text-[18px] font-bold text-gray-900 mb-6">Booking Log</h3>
                        <div id="log-container" class="space-y-6 relative before:absolute before:left-5 before:top-2 before:bottom-2 before:w-[2px] before:bg-gray-50">
                            <!-- Logs here -->
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        const urlParams = new URLSearchParams(window.location.search);
        const bookingId = urlParams.get('id');

        if (!bookingId) window.location.href = 'bookings.php';

        async function fetchBookingDetails() {
            try {
                const res = await fetch(`../api/admin/booking_detail.php?id=${bookingId}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await res.json();
                
                if (data.success) {
                    const b = data.data.booking;
                    
                    document.getElementById('breadcrumb-id').textContent = `Booking #BK-${b.id.toString().padStart(6, '0')}`;
                    document.getElementById('header-id').textContent = `Booking #BK-${b.id.toString().padStart(6, '0')}`;
                    
                    // Status
                    const sb = document.getElementById('status-badge');
                    sb.textContent = b.status;
                    const statusColors = { 'confirmed': 'bg-green-50 text-green-500', 'pending': 'bg-yellow-50 text-yellow-500', 'cancelled': 'bg-red-50 text-red-500' };
                    sb.className = `px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-wider ${statusColors[b.status] || 'bg-gray-50 text-gray-500'}`;

                    // Dates
                    const start = new Date(b.check_in).toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
                    const end = new Date(b.check_out).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
                    document.getElementById('header-dates').textContent = `${start} - ${end}`;
                    document.getElementById('header-property').textContent = b.property_name;

                    // Property
                    document.getElementById('prop-name').textContent = b.property_name;
                    document.getElementById('prop-location').textContent = `${b.property_city}, ${b.property_state}`;
                    document.getElementById('prop-host').textContent = b.host_name;
                    document.getElementById('prop-price').textContent = `₦${parseFloat(b.price_per_night || 0).toLocaleString()} / night`;
                    if (b.property_image) {
                         document.getElementById('property-image').innerHTML = `<img src="../${b.property_image}" class="w-full h-full object-cover">`;
                    }

                    // Payment
                    document.getElementById('pay-total').textContent = `₦${parseFloat(b.total_amount).toLocaleString()}`;
                    const ps = document.getElementById('pay-status');
                    ps.textContent = b.payment_status || 'Pending';
                    ps.className = `px-3 py-1 rounded-full text-[11px] font-bold uppercase ${b.payment_status === 'paid' ? 'bg-green-50 text-green-500' : 'bg-yellow-50 text-yellow-600'}`;
                    
                    if (b.payment_ref) document.getElementById('pay-ref').textContent = b.payment_ref;

                    const genBtn = document.getElementById('gen-link-btn');
                    const linkSec = document.getElementById('payment-link-section');
                    
                    if (b.payment_link) {
                        linkSec.classList.remove('hidden');
                        document.getElementById('pay-link-text').textContent = b.payment_link;
                        genBtn.classList.add('hidden');
                    } else if (b.status !== 'cancelled' && b.payment_status !== 'paid') {
                        genBtn.classList.remove('hidden');
                    }

                    // Guest
                    document.getElementById('guest-name').textContent = b.guest_name;
                    document.getElementById('guest-email').textContent = b.guest_email;
                    document.getElementById('guest-phone').textContent = b.guest_phone || 'N/A';
                    document.getElementById('guest-verified').textContent = b.guest_is_verified ? 'Yes' : 'No';
                    document.getElementById('guest-profile-link').href = `user_profile.php?id=${b.guest_id}`;
                    
                    const gAvatar = document.getElementById('guest-avatar');
                    if (b.guest_pic) {
                        gAvatar.innerHTML = `<img src="${b.guest_pic.startsWith('http') ? b.guest_pic : '../' + b.guest_pic}" class="w-full h-full object-cover">`;
                    } else {
                        gAvatar.innerHTML = `<span class="text-xl font-bold text-gray-300">${b.guest_name[0].toUpperCase()}</span>`;
                    }

                    // Logs (Mock)
                    renderLogs(b);
                }
            } catch (err) { console.error(err); }
        }

        function renderLogs(b) {
            const container = document.getElementById('log-container');
            const logs = [
                { title: 'Booking confirmed', time: b.created_at, desc: 'Booking was successfully verified and confirmed.' },
                { title: 'Payment link shared', time: b.created_at, desc: 'A payment link was generated for the guest.' },
                { title: 'Requested booking', time: b.created_at, desc: 'Guest initiated a booking request for this property.' }
            ];
            container.innerHTML = logs.map(l => `
                <div class="relative pl-10">
                    <div class="absolute left-0 w-10 flex justify-center">
                        <div class="w-2.5 h-2.5 rounded-full bg-primary border-4 border-white ring-4 ring-primary/5"></div>
                    </div>
                    <div class="flex justify-between items-center mb-1">
                        <div class="text-[14px] font-bold text-gray-900">${l.title}</div>
                        <div class="text-[11px] text-gray-400">${new Date(l.time).toLocaleDateString(undefined, { hour: '2-digit', minute: '2-digit' })}</div>
                    </div>
                    <div class="text-[12px] text-gray-400 leading-tight">${l.desc}</div>
                </div>
            `).join('');
        }

        document.getElementById('gen-link-btn').addEventListener('click', async () => {
            if(!confirm('Generate and store payment link for this booking?')) return;
            try {
                const res = await fetch(`../api/bookings/generate_payment_link.php`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ booking_id: bookingId })
                });
                const data = await res.json();
                if (data.success) {
                    fetchBookingDetails();
                } else { alert(data.message); }
            } catch (err) { console.error(err); }
        });

        document.getElementById('copy-link-btn').addEventListener('click', () => {
             const link = document.getElementById('pay-link-text').textContent;
             navigator.clipboard.writeText(link);
             alert('Link copied to clipboard!');
        });

        fetchBookingDetails();
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
