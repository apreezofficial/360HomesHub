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
<body class="bg-white min-h-screen">
    <div class="flex min-h-screen">
        <aside class="w-[240px] fixed h-full bg-white z-50"></aside>

        <main class="flex-1 ml-[240px] bg-gray-50 min-h-screen p-6">
            <div class="mb-8 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="text-gray-400 hover:text-primary transition-colors"><i class="bi bi-arrow-left text-xl"></i></a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Property Details</h1>
                        <p class="text-text-secondary text-sm mt-1">Review and manage property listing</p>
                    </div>
                </div>
                <div id="admin-actions" class="flex gap-3">
                    <button onclick="updatePropertyStatus('archived')" class="px-5 py-2.5 rounded-xl border border-red-200 text-red-600 font-bold text-sm hover:bg-red-50 transition-colors">Reject & Archive</button>
                    <button onclick="updatePropertyStatus('published')" class="px-5 py-2.5 rounded-xl bg-green-600 text-white font-bold text-sm hover:bg-green-700 transition-colors shadow-sm">Approve & Publish</button>
                </div>
            </div>

            <div id="loading" class="text-center py-20 text-gray-500">Loading property data...</div>

            <div id="content" class="hidden grid-cols-12 gap-8">
                <!-- Main Content -->
                <div class="col-span-8 space-y-8">
                    <!-- Header Card -->
                    <div class="bg-white rounded-2xl border border-border p-8">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h2 id="p-name" class="text-3xl font-bold text-gray-900 mb-2">-</h2>
                                <p id="p-location" class="text-text-secondary"><i class="bi bi-geo-alt mr-2"></i> -</p>
                            </div>
                            <span id="p-status" class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-bold uppercase tracking-wider">-</span>
                        </div>

                        <div class="grid grid-cols-4 gap-6 py-6 border-y border-gray-100">
                             <div>
                                <label class="text-xs text-text-secondary font-medium uppercase tracking-wider">Type</label>
                                <p id="p-type" class="font-bold text-gray-900 mt-1 capitalize">-</p>
                            </div>
                            <div>
                                <label class="text-xs text-text-secondary font-medium uppercase tracking-wider">Price</label>
                                <p id="p-price" class="font-bold text-primary mt-1">-</p>
                            </div>
                            <div>
                                <label class="text-xs text-text-secondary font-medium uppercase tracking-wider">Guests</label>
                                <p id="p-guests" class="font-bold text-gray-900 mt-1">-</p>
                            </div>
                             <div>
                                <label class="text-xs text-text-secondary font-medium uppercase tracking-wider">Rooms/Beds</label>
                                <p id="p-rooms" class="font-bold text-gray-900 mt-1">-</p>
                            </div>
                        </div>

                        <div class="mt-8">
                            <h3 class="font-bold text-lg text-gray-900 mb-3">Description</h3>
                            <p id="p-desc" class="text-text-secondary leading-relaxed bg-slate-50 p-6 rounded-xl">-</p>
                        </div>
                    </div>

                    <!-- Amenities -->
                    <div class="bg-white rounded-2xl border border-border p-8">
                        <h3 class="font-bold text-lg text-gray-900 mb-4">Amenities</h3>
                        <div id="p-amenities" class="flex flex-wrap gap-3">
                            <!-- Injected JS -->
                        </div>
                    </div>

                     <!-- Gallery -->
                    <div class="bg-white rounded-2xl border border-border p-8">
                        <h3 class="font-bold text-lg text-gray-900 mb-4">Media Gallery</h3>
                        <div id="p-gallery" class="grid grid-cols-3 gap-4">
                            <!-- Injected JS -->
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-span-4 space-y-8">
                    <!-- Host Card -->
                    <div class="bg-white rounded-2xl border border-border p-8">
                        <h3 class="font-bold text-base text-gray-900 mb-6 flex items-center gap-2"><i class="bi bi-person-badge text-primary"></i> Host Information</h3>
                        
                        <div class="flex flex-col items-center mb-6">
                            <div id="h-avatar" class="w-20 h-20 rounded-full bg-slate-200 flex items-center justify-center text-2xl font-bold text-gray-500 mb-3 border-4 border-white shadow-sm">
                                -
                            </div>
                            <h4 id="h-name" class="font-bold text-lg text-gray-900">-</h4>
                            <p id="h-email" class="text-sm text-text-secondary">-</p>
                        </div>

                        <div class="space-y-4 pt-4 border-t border-gray-100">
                             <div class="flex justify-between items-center">
                                <span class="text-sm text-text-secondary">Phone</span>
                                <span id="h-phone" class="text-sm font-medium text-gray-900">-</span>
                            </div>
                             <div class="flex justify-between items-center">
                                <span class="text-sm text-text-secondary">Host ID</span>
                                <span class="text-sm font-medium text-gray-900">HST-001</span>
                            </div>
                        </div>
                        
                        <a href="#" class="block w-full text-center py-3 mt-6 bg-slate-50 text-primary font-bold rounded-xl text-sm hover:bg-slate-100 transition-colors">View full profile</a>
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
            if (!propertyId) return;

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
                document.getElementById('loading').textContent = 'Error loading property details.';
            }
        }

        function displayProperty(p) {
            document.getElementById('loading').classList.add('hidden');
            const content = document.getElementById('content');
            content.classList.remove('hidden');
            content.classList.add('grid');

            document.getElementById('p-name').textContent = p.name || 'Draft Property';
            document.getElementById('p-location').innerHTML = `<i class="bi bi-geo-alt mr-2 text-primary"></i>${p.address}, ${p.city}, ${p.state}, ${p.country}`;
            
            const pStatus = document.getElementById('p-status');
            pStatus.textContent = p.status;
            if(p.status === 'published') {
                pStatus.className = 'px-3 py-1 bg-green-50 text-green-700 rounded-full text-xs font-bold uppercase tracking-wider border border-green-200';
            } else if(p.status === 'archived') {
                 pStatus.className = 'px-3 py-1 bg-red-50 text-red-700 rounded-full text-xs font-bold uppercase tracking-wider border border-red-200';
            } else {
                 pStatus.className = 'px-3 py-1 bg-yellow-50 text-yellow-700 rounded-full text-xs font-bold uppercase tracking-wider border border-yellow-200';
            }

            document.getElementById('p-type').textContent = `${p.space_type} ${p.type}`;
            document.getElementById('p-price').textContent = `₦${parseFloat(p.price).toLocaleString()} /${p.price_type}`;
            document.getElementById('p-guests').textContent = `${p.guests_max} Max Guests`;
            document.getElementById('p-rooms').textContent = `${p.bedrooms} Bedrooms, ${p.beds} Beds`;
            document.getElementById('p-desc').textContent = p.description || 'No description provided.';

            // Host
            document.getElementById('h-name').textContent = `${p.first_name} ${p.last_name}`;
            document.getElementById('h-email').textContent = p.host_email;
            document.getElementById('h-phone').textContent = p.host_phone || 'Not provided';
            document.getElementById('h-avatar').textContent = p.first_name ? p.first_name[0].toUpperCase() : '?';

            // Amenities
            const amDiv = document.getElementById('p-amenities');
            const amenities = JSON.parse(p.amenities || '[]');
            amenities.forEach(a => {
                const span = document.createElement('span');
                span.className = 'px-4 py-2 bg-slate-50 text-gray-700 rounded-lg text-sm font-medium border border-slate-100';
                span.textContent = a;
                amDiv.appendChild(span);
            });

            // Gallery
            const gallery = document.getElementById('p-gallery');
            p.images.forEach(img => {
                const wrapper = document.createElement('div');
                wrapper.className = 'rounded-xl overflow-hidden border border-gray-100 aspect-video bg-gray-50 relative group';
                
                if (img.media_type === 'image') {
                    const el = document.createElement('img');
                    el.src = `${API_BASE}/360HomesHub/${img.media_url}`;
                    el.className = 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-110';
                    el.onerror = () => el.src = 'https://via.placeholder.com/300';
                    wrapper.appendChild(el);
                } else if (img.media_type === 'video') {
                    const v = document.createElement('video');
                    v.src = `${API_BASE}/360HomesHub/${img.media_url}`;
                    v.className = 'w-full h-full object-cover';
                    v.controls = true;
                    wrapper.appendChild(v);
                }
                gallery.appendChild(wrapper);
            });
        }

        async function updatePropertyStatus(status) {
            const token = localStorage.getItem('jwt_token');
            if(!confirm(`Are you sure you want to ${status === 'published' ? 'publish' : 'archive'} this property?`)) return;

            try {
                const res = await fetch('../api/admin/update_property.php', {
                    method: 'POST',
                    headers: { 
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ property_id: propertyId, status: status })
                });
                const response = await res.json();
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message);
                }
            } catch (err) {
                console.error(err);
            }
        }

        loadDetails();
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

