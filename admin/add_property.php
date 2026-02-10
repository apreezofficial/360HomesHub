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
    <title>Admin - Add Property | 36HomesHub</title>
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
            <div class="mb-8 flex items-center gap-3">
                <a href="users.php" class="text-gray-400 hover:text-primary transition-colors"><i class="bi bi-arrow-left text-xl"></i></a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Add Property for Host</h1>
                    <p id="host-info" class="text-text-secondary text-sm mt-1">Loading host info...</p>
                </div>
            </div>

            <div class="bg-white max-w-4xl rounded-2xl border border-border p-8 mx-auto">
                <form id="add-property-form" class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block mb-2 font-medium text-[14px]">Property Name</label>
                            <input type="text" id="name" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary" placeholder="Luxury Studio" required>
                        </div>
                        <div>
                            <label class="block mb-2 font-medium text-[14px]">Property Type</label>
                            <div class="relative">
                                <select id="type" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary appearance-none cursor-pointer">
                                    <option value="apartment">Apartment</option>
                                    <option value="house">House</option>
                                    <option value="studio">Studio</option>
                                    <option value="duplex">Duplex</option>
                                    <option value="hotel">Hotel</option>
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block mb-2 font-medium text-[14px]">Address</label>
                        <input type="text" id="address" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary" placeholder="123 Main St" required>
                    </div>

                    <div class="grid grid-cols-3 gap-6">
                        <div>
                            <label class="block mb-2 font-medium text-[14px]">City</label>
                            <input type="text" id="city" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary" required>
                        </div>
                        <div>
                            <label class="block mb-2 font-medium text-[14px]">State</label>
                            <input type="text" id="state" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary" required>
                        </div>
                        <div>
                            <label class="block mb-2 font-medium text-[14px]">Country</label>
                            <input type="text" id="country" value="Nigeria" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-6">
                        <div>
                            <label class="block mb-2 font-medium text-[14px]">Price (₦)</label>
                            <input type="number" id="price" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary" required>
                        </div>
                        <div>
                            <label class="block mb-2 font-medium text-[14px]">Max Guests</label>
                            <input type="number" id="guests_max" value="1" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary" required>
                        </div>
                        <div>
                            <label class="block mb-2 font-medium text-[14px]">Bedrooms</label>
                            <input type="number" id="bedrooms" value="1" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary" required>
                        </div>
                    </div>

                    <div>
                        <label class="block mb-2 font-medium text-[14px]">Description</label>
                        <textarea id="description" rows="4" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-primary resize-none" placeholder="Describe the property..."></textarea>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full py-4 bg-primary text-white rounded-xl font-bold text-[16px] hover:bg-[#004a7a] transition-colors shadow-lg shadow-blue-100">Create & Publish Property</button>
                        <p id="msg" class="text-center mt-4 text-sm font-medium h-5"></p>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        const params = new URLSearchParams(window.location.search);
        const hostId = params.get('host_id');

        if (!hostId) {
            alert('Host ID is required');
            window.location.href = 'users.php';
        }

        document.getElementById('host-info').textContent = `Assigning property to Host ID: ${hostId}`;

        document.getElementById('add-property-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const msg = document.getElementById('msg');
            msg.textContent = 'Creating property...';
            msg.className = 'text-center mt-4 text-sm font-medium text-gray-500';

            const data = {
                host_id: hostId,
                name: document.getElementById('name').value,
                type: document.getElementById('type').value,
                address: document.getElementById('address').value,
                city: document.getElementById('city').value,
                state: document.getElementById('state').value,
                country: document.getElementById('country').value,
                price: document.getElementById('price').value,
                guests_max: document.getElementById('guests_max').value,
                bedrooms: document.getElementById('bedrooms').value,
                description: document.getElementById('description').value
            };

            try {
                const res = await fetch('../api/admin/create_property.php', {
                    method: 'POST',
                    headers: { 
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                const response = await res.json();
                if (response.success) {
                    msg.className = 'text-center mt-4 text-sm font-medium text-green-600';
                    msg.textContent = 'Property created and published successfully!';
                    setTimeout(() => window.location.href = 'index.php', 1500);
                } else {
                    msg.className = 'text-center mt-4 text-sm font-medium text-red-500';
                    msg.textContent = response.message;
                }
            } catch (err) {
                msg.className = 'text-center mt-4 text-sm font-medium text-red-500';
                msg.textContent = 'Error: ' + err.message;
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
