<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Details | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #6366f1;
            --sidebar-bg: #0f172a;
            --main-bg: #020617;
            --glass-bg: rgba(30, 41, 59, 0.7);
            --border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --success: #10b981;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--main-bg);
            color: var(--text-main);
            padding: 3rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-dim);
            text-decoration: none;
            margin-bottom: 2rem;
            transition: color 0.3s;
        }

        .back-btn:hover { color: var(--primary); }

        .property-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .main-info {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 1.5rem;
            padding: 2.5rem;
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .gallery-item {
            border-radius: 1rem;
            width: 100%;
            height: 150px;
            object-fit: cover;
            border: 1px solid var(--border);
        }

        .host-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 1.5rem;
            padding: 2rem;
            height: fit-content;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item label {
            display: block;
            color: var(--text-dim);
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .info-item p {
            font-weight: 600;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 600;
            background: var(--primary);
            color: white;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-btn"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    
    <div id="loading" style="text-align: center; padding: 5rem;">Loading property data...</div>
    
    <div id="content" class="property-container" style="display: none;">
        <div class="main-info">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
                <div>
                    <h1 id="p-name" style="font-size: 2.5rem; margin-bottom: 0.5rem;">-</h1>
                    <p id="p-location" style="color: var(--text-dim)"><i class="bi bi-geo-alt"></i> -</p>
                </div>
                <div id="p-status" class="badge">-</div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <label>Type</label>
                    <p id="p-type">-</p>
                </div>
                <div class="info-item">
                    <label>Price</label>
                    <p id="p-price" style="color: var(--success); font-size: 1.25rem;">-</p>
                </div>
                <div class="info-item">
                    <label>Guests</label>
                    <p id="p-guests">-</p>
                </div>
                <div class="info-item">
                    <label>Bedrooms/Beds</label>
                    <p id="p-rooms">-</p>
                </div>
            </div>

            <div class="section-title">Description</div>
            <p id="p-desc" style="line-height: 1.6; color: var(--text-dim); margin-bottom: 2rem;">-</p>

            <div class="section-title">Amenities</div>
            <div id="p-amenities" style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 2rem;">
                <!-- Amenities -->
            </div>

            <div class="section-title">Admin Actions</div>
            <div id="admin-actions" style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                <button class="badge" style="background: var(--success); cursor: pointer; border: none;" onclick="updatePropertyStatus('published')">Approve & Publish</button>
                <button class="badge" style="background: var(--danger); cursor: pointer; border: none;" onclick="updatePropertyStatus('archived')">Reject & Archive</button>
            </div>

            <div class="section-title">Media Gallery</div>
            <div class="gallery" id="p-gallery">
                <!-- Images/Videos -->
            </div>
        </div>

        <div class="host-card">
            <div class="section-title"><i class="bi bi-person-badge"></i> Host Information</div>
            <div style="text-align: center; margin-bottom: 2rem;">
                <div id="h-avatar" style="width: 80px; height: 80px; background: var(--primary); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700;">-</div>
                <h3 id="h-name">-</h3>
                <p id="h-email" style="color: var(--text-dim);">-</p>
            </div>
            <div class="info-item">
                <label>Phone Number</label>
                <p id="h-phone">-</p>
            </div>
        </div>
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
            }
        }

        function displayProperty(p) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('content').style.display = 'grid';

            document.getElementById('p-name').textContent = p.name || 'Draft Property';
            document.getElementById('p-location').innerHTML = `<i class="bi bi-geo-alt"></i> ${p.address}, ${p.city}, ${p.state}, ${p.country}`;
            document.getElementById('p-status').textContent = p.status;
            document.getElementById('p-type').textContent = `${p.space_type} ${p.type}`;
            document.getElementById('p-price').textContent = `₦${parseFloat(p.price).toLocaleString()} /${p.price_type}`;
            document.getElementById('p-guests').textContent = `${p.guests_max} Max Guests`;
            document.getElementById('p-rooms').textContent = `${p.bedrooms} Bedrooms, ${p.beds} Beds`;
            document.getElementById('p-desc').textContent = p.description || 'No description provided.';

            // Host
            document.getElementById('h-name').textContent = `${p.first_name} ${p.last_name}`;
            document.getElementById('h-email').textContent = p.host_email;
            document.getElementById('h-phone').textContent = p.host_phone || 'Not provided';
            document.getElementById('h-avatar').textContent = p.first_name ? p.first_name[0] : '?';

            // Amenities
            const amDiv = document.getElementById('p-amenities');
            const amenities = JSON.parse(p.amenities || '[]');
            amenities.forEach(a => {
                const span = document.createElement('span');
                span.className = 'badge';
                span.style.background = 'rgba(255,255,255,0.05)';
                span.style.color = 'var(--text-main)';
                span.style.border = '1px solid var(--border)';
                span.textContent = a;
                amDiv.appendChild(span);
            });

            // Gallery
            const gallery = document.getElementById('p-gallery');
            p.images.forEach(img => {
                if (img.media_type === 'image') {
                    const el = document.createElement('img');
                    el.src = `${API_BASE}/360HomesHub/${img.media_url}`;
                    el.className = 'gallery-item';
                    el.onerror = () => el.src = 'https://via.placeholder.com/300';
                    gallery.appendChild(el);
                } else if (img.media_type === 'video') {
                    const v = document.createElement('video');
                    v.src = `${API_BASE}/360HomesHub/${img.media_url}`;
                    v.className = 'gallery-item';
                    v.controls = true;
                    gallery.appendChild(v);
                }
            });
        }

        async function updatePropertyStatus(status) {
            const token = localStorage.getItem('jwt_token');
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
</body>
</html>
