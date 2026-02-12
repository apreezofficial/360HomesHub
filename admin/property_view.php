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
    <title>Property Details | Admin Dashboard</title>
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
<body class="bg-[#F9FAFB] min-h-screen font-outfit">
    <div class="flex min-h-screen">
        <aside class="w-[260px] fixed h-full bg-white z-50"></aside>

        <main class="flex-1 ml-[260px] min-h-screen p-8">
            <!-- Breadcrumbs -->
            <div class="text-[14px] text-gray-400 mb-8 flex items-center gap-2">
                <a href="listings.php" class="hover:text-gray-900 transition-colors">Listings</a>
                <span class="text-gray-300">/</span>
                <span class="text-gray-900 font-medium" id="breadcrumb-prop-name">Property Details</span>
            </div>

            <!-- Header -->
            <div class="flex justify-between items-start mb-10">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 rounded-2xl bg-primary/5 border border-primary/10 flex items-center justify-center text-primary shadow-sm">
                        <i class="bi bi-house-door text-[28px]"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h1 class="text-[28px] font-bold text-gray-900" id="header-p-name">Loading...</h1>
                            <span id="header-p-status" class="px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-wider"></span>
                        </div>
                        <div class="text-[14px] text-gray-400 flex items-center gap-2" id="header-p-location">
                            -
                        </div>
                    </div>
                </div>
                <div id="admin-actions" class="flex items-center gap-4 hidden">
                    <button onclick="updatePropertyStatus('archived')" class="px-6 py-3 border border-red-100 text-red-500 bg-white rounded-xl font-bold text-[14px] hover:bg-red-50 transition-all flex items-center gap-2">
                        <i class="bi bi-x-circle"></i> Reject Listing
                    </button>
                    <button onclick="updatePropertyStatus('published')" class="px-6 py-3 bg-[#005a92] text-white rounded-xl font-bold text-[14px] shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all flex items-center gap-2">
                        <i class="bi bi-check2-circle"></i> Approve Listing
                    </button>
                </div>
            </div>

            <div id="loading-state" class="py-32 flex flex-col items-center justify-center text-gray-400">
                <div class="w-12 h-12 border-4 border-gray-100 border-t-primary rounded-full animate-spin mb-4"></div>
                <p class="font-medium">Fetching listing metadata...</p>
            </div>

            <div id="content-grid" class="hidden grid grid-cols-12 gap-8">
                <!-- Main Content -->
                <div class="col-span-8 space-y-8">
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-[24px] border border-gray-100 shadow-sm">
                            <div class="text-[12px] font-bold text-gray-400 uppercase tracking-widest mb-1">Total Bookings</div>
                            <div class="text-[24px] font-bold text-gray-900" id="stat-bookings">0</div>
                        </div>
                        <div class="bg-white p-6 rounded-[24px] border border-gray-100 shadow-sm">
                            <div class="text-[12px] font-bold text-gray-400 uppercase tracking-widest mb-1">Revenue</div>
                            <div class="text-[24px] font-bold text-gray-900" id="stat-revenue">₦0</div>
                        </div>
                        <div class="bg-white p-6 rounded-[24px] border border-gray-100 shadow-sm">
                            <div class="text-[12px] font-bold text-gray-400 uppercase tracking-widest mb-1">Price / Night</div>
                            <div class="text-[24px] font-bold text-primary" id="stat-price">₦0</div>
                        </div>
                    </div>

                    <!-- Details Card -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-8">
                        <h3 class="text-[18px] font-bold text-gray-900 mb-6 flex items-center gap-3">
                            <i class="bi bi-journal-text text-gray-400"></i> Property Information
                        </h3>
                        <div class="space-y-8">
                            <div>
                                <h4 class="text-[14px] font-bold text-gray-400 uppercase tracking-wider mb-4">Description</h4>
                                <div id="p-desc" class="text-gray-600 leading-relaxed text-[15px] bg-[#F9FAFB] p-6 rounded-[20px]">-</div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-8">
                                <div>
                                    <h4 class="text-[14px] font-bold text-gray-400 uppercase tracking-wider mb-3">Key Details</h4>
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center text-[14px]">
                                            <span class="text-gray-400">Space Type</span>
                                            <span class="font-bold text-gray-900 capitalize" id="p-type">-</span>
                                        </div>
                                        <div class="flex justify-between items-center text-[14px]">
                                            <span class="text-gray-400">Maximum Guests</span>
                                            <span class="font-bold text-gray-900" id="p-guests">-</span>
                                        </div>
                                        <div class="flex justify-between items-center text-[14px]">
                                            <span class="text-gray-400">Bedrooms / Beds</span>
                                            <span class="font-bold text-gray-900" id="p-rooms">-</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-[14px] font-bold text-gray-400 uppercase tracking-wider mb-3">Amenities</h4>
                                    <div id="p-amenities" class="flex flex-wrap gap-2 text-[13px] font-bold text-gray-700">
                                        <!-- Amenities here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gallery Section -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-8">
                        <div class="flex justify-between items-center mb-8">
                            <h3 class="text-[18px] font-bold text-gray-900 flex items-center gap-3">
                                <i class="bi bi-images text-gray-400"></i> Listing Gallery
                            </h3>
                            <div class="text-[13px] text-gray-400 font-bold uppercase tracking-wider" id="gallery-count">0 items</div>
                        </div>
                        <div id="p-gallery" class="grid grid-cols-3 gap-6">
                            <!-- Injected JS -->
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar -->
                <div class="col-span-4 space-y-8">
                    <!-- Host Information -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-8">
                         <h3 class="text-[18px] font-bold text-gray-900 mb-6">Host Information</h3>
                         <div class="flex flex-col items-center mb-6">
                            <div id="h-avatar" class="w-20 h-20 rounded-full bg-gray-50 flex items-center justify-center text-[28px] font-bold text-gray-300 border border-gray-100 mb-4 overflow-hidden">
                                -
                            </div>
                            <h4 id="h-name" class="font-bold text-[18px] text-gray-900 mb-1">-</h4>
                            <p id="h-email" class="text-[13px] text-gray-400">-</p>
                        </div>
                        <div class="space-y-4 pt-4 border-t border-gray-50 mb-6">
                            <div class="flex justify-between items-center text-[13px]">
                                <span class="text-gray-400">Phone</span>
                                <span class="font-bold text-gray-900" id="h-phone">-</span>
                            </div>
                            <div class="flex justify-between items-center text-[13px]">
                                <span class="text-gray-400">Member Since</span>
                                <span class="font-bold text-gray-900">May 2023</span>
                            </div>
                        </div>
                        <a href="#" id="host-profile-link" class="block w-full text-center py-3.5 bg-[#F9FAFB] rounded-xl text-[13px] font-bold text-gray-900 hover:bg-gray-100 transition-all border border-gray-50 shadow-sm">
                            View Full Profile
                        </a>
                    </div>

                    <!-- Safety & Compliance -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-8">
                        <h3 class="text-[18px] font-bold text-gray-900 mb-4">Inspection Report</h3>
                        <p class="text-[13px] text-gray-400 leading-relaxed mb-6">Review the property details carefully against compliance rules before approval.</p>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3 p-4 bg-green-50 rounded-[16px] text-green-600">
                                <i class="bi bi-check-circle-fill"></i>
                                <span class="text-[13px] font-bold">Base requirements met</span>
                            </div>
                            <div class="flex items-center gap-3 p-4 bg-green-50 rounded-[16px] text-green-600">
                                <i class="bi bi-check-circle-fill"></i>
                                <span class="text-[13px] font-bold">Host verification valid</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        const API_BASE = window.location.origin;
        const params = new URLSearchParams(window.location.search);
        const propertyId = params.get('id');

        async function loadDetails() {
            if (!propertyId) { window.location.href = 'listings.php'; return; }

            try {
                const res = await fetch(`../api/admin/property_details.php?id=${propertyId}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const response = await res.json();

                if (response.success) {
                    displayProperty(response.data.property);
                }
            } catch (err) {
                console.error(err);
                document.getElementById('loading-state').innerHTML = '<p class="text-red-500 font-bold">Failed to load property data.</p>';
            }
        }

        function displayProperty(p) {
            document.getElementById('loading-state').classList.add('hidden');
            document.getElementById('content-grid').classList.remove('hidden');

            // Header & Breadcrumbs
            document.getElementById('header-p-name').textContent = p.name || 'Untitled Listing';
            document.getElementById('breadcrumb-prop-name').textContent = p.name || 'Property Details';
            document.getElementById('header-p-location').innerHTML = `<i class="bi bi-geo-alt text-gray-300"></i> ${p.address}, ${p.city}, ${p.state}`;
            
            const hs = document.getElementById('header-p-status');
            hs.textContent = p.status;
            const statusColors = { 'published': 'bg-green-50 text-green-500', 'active': 'bg-green-50 text-green-500', 'pending': 'bg-yellow-50 text-yellow-500', 'archived': 'bg-red-50 text-red-500' };
            hs.className = `px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-wider ${statusColors[p.status] || 'bg-gray-50 text-gray-500'}`;

            if (p.status === 'pending') document.getElementById('admin-actions').classList.remove('hidden');

            // Stats
            document.getElementById('stat-bookings').textContent = p.total_bookings || 0;
            document.getElementById('stat-revenue').textContent = `₦${parseFloat(p.total_earnings || 0).toLocaleString()}`;
            document.getElementById('stat-price').textContent = `₦${parseFloat(p.price).toLocaleString()}`;

            // Details
            document.getElementById('p-desc').textContent = p.description || 'No description provided.';
            document.getElementById('p-type').textContent = `${p.space_type} ${p.type}`;
            document.getElementById('p-guests').textContent = `${p.guests_max} People`;
            document.getElementById('p-rooms').textContent = `${p.bedrooms} BR / ${p.beds} Beds`;

            // Host
            document.getElementById('h-name').textContent = `${p.first_name} ${p.last_name}`;
            document.getElementById('h-email').textContent = p.host_email;
            document.getElementById('h-phone').textContent = p.host_phone || 'N/A';
            document.getElementById('host-profile-link').href = `user_profile.php?id=${p.host_id}`;
            const hAvatar = document.getElementById('h-avatar');
            if (p.host_pic) {
                hAvatar.innerHTML = `<img src="../${p.host_pic}" class="w-full h-full object-cover">`;
            } else {
                hAvatar.textContent = p.first_name[0].toUpperCase();
            }

            // Amenities
            const amDiv = document.getElementById('p-amenities');
            const amenities = JSON.parse(p.amenities || '[]');
            amDiv.innerHTML = amenities.map(a => `
                <span class="px-3 py-1.5 bg-gray-50 rounded-lg border border-gray-100">${a}</span>
            `).join('');

            // Gallery
            const gallery = document.getElementById('p-gallery');
            const items = p.images || [];
            document.getElementById('gallery-count').textContent = `${items.length} items`;
            gallery.innerHTML = items.map(img => `
                <div class="rounded-[20px] overflow-hidden border border-gray-100 aspect-video group bg-gray-50 relative">
                     ${img.media_type === 'video' 
                        ? `<video src="../${img.media_url}" class="w-full h-full object-cover" controls></video>` 
                        : `<img src="../${img.media_url}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 shadow-sm" onerror="this.src='https://via.placeholder.com/400x300'">`}
                </div>
            `).join('');
        }

        async function updatePropertyStatus(status) {
            if(!confirm(`Are you sure you want to mark this as ${status}?`)) return;
            try {
                const res = await fetch('../api/admin/update_property.php', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ property_id: propertyId, status: status })
                });
                const data = await res.json();
                if (data.success) { alert(data.message); location.reload(); }
                else { alert(data.message); }
            } catch (err) { console.error(err); }
        }

        loadDetails();
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>

    <script>
        // Inject JWT token from session into localStorage for API calls
        <?php if (isset($_SESSION['jwt_token'])): ?>
            localStorage.setItem('jwt_token', '<?= $_SESSION['jwt_token'] ?>');
        <?php endif; ?>
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>

