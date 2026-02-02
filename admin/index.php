<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | 360HomesHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <aside></aside>

    <main>
        <header>
            <div class="header-title">
                <h1>Property Overview</h1>
                <p>Manage and monitor all property listings</p>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(99, 102, 241, 0.1); color: var(--primary);">
                    <i class="bi bi-house"></i>
                </div>
                <div class="stat-info">
                    <h3 id="stat-total">-</h3>
                    <p>Total Listings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                    <i class="bi bi-patch-check"></i>
                </div>
                <div class="stat-info">
                    <h3 id="stat-published">-</h3>
                    <p>Published</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-info">
                    <h3 id="stat-draft">-</h3>
                    <p>Drafts</p>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3>Recent Properties</h3>
                <a href="add_property.php" class="btn btn-primary" style="text-decoration: none; font-size: 0.875rem;">+ Add Property</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Host</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Date Added</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="property-table">
                    <!-- Properties injected here -->
                </tbody>
            </table>
        </div>
    </main>

    <script>
        const API_BASE = window.location.origin;

        async function fetchDashboardData() {
            const token = localStorage.getItem('jwt_token');
            if (!token) {
                window.location.href = 'login.php';
                return;
            }

            try {
                // Fetch Stats
                const statsRes = await fetch('../api/admin/stats.php', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const statsData = await statsRes.json();
                if (statsData.success) {
                    updateStats(statsData.data);
                }

                // Fetch Properties
                const propsRes = await fetch('../api/admin/properties.php', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const propsData = await propsRes.json();
                if (propsData.success) {
                    renderProperties(propsData.data.properties);
                }
            } catch (err) {
                console.error('Error:', err);
            }
        }

        function updateStats(data) {
            document.getElementById('stat-total').textContent = data.properties.total;
            document.getElementById('stat-published').textContent = data.properties.published;
            document.getElementById('stat-draft').textContent = data.properties.draft;
        }

        function renderProperties(properties) {
            const tbody = document.getElementById('property-table');
            tbody.innerHTML = '';

            properties.slice(0, 10).forEach(p => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <img src="${API_BASE}/360HomesHub/${p.main_image || 'assets/placeholder.jpg'}" style="width: 48px; height: 48px; border-radius: 0.75rem; object-fit: cover;" onerror="this.src='https://via.placeholder.com/150'">
                            <div>
                                <strong>${p.name || 'Untitled'}</strong><br>
                                <small style="color: var(--text-dim)">${p.city}, ${p.state}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <strong>${p.first_name} ${p.last_name}</strong><br>
                        <small style="color: var(--text-dim)">${p.host_email}</small>
                    </td>
                    <td>₦${parseFloat(p.price).toLocaleString()} /${p.price_type}</td>
                    <td><span class="badge badge-${p.status}">${p.status}</span></td>
                    <td>${new Date(p.created_at).toLocaleDateString()}</td>
                    <td><a href="property_view.php?id=${p.id}" style="color: var(--primary-light); text-decoration: none; font-weight: 600; font-size: 0.875rem;">VIEW</a></td>
                `;
                tbody.appendChild(tr);
            });
        }

        fetchDashboardData();
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
