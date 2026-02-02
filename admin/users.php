<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <aside></aside>
    <main>
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1>User Management</h1>
            <a href="add_user.php" class="btn btn-primary" style="text-decoration: none;">+ Add User</a>
        </header>
        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email/Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="user-table"></tbody>
            </table>
        </div>
    </main>
    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        async function fetchUsers() {
            const token = localStorage.getItem('jwt_token');
            const res = await fetch('../api/admin/users.php', { headers: { 'Authorization': `Bearer ${token}` } });
            const data = await res.json();
            if (data.success) {
                const tbody = document.getElementById('user-table');
                data.data.users.forEach(u => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${u.first_name || 'N/A'} ${u.last_name || ''}</strong></td>
                        <td>${u.email || u.phone}</td>
                        <td style="text-transform: capitalize;">${u.role}</td>
                        <td><span class="badge badge-${u.is_verified ? 'published' : 'warning'}">${u.is_verified ? 'Verified' : 'Pending'}</span></td>
                        <td>${new Date(u.created_at).toLocaleDateString()}</td>
                        <td>
                            <a href="communications.php?user_id=${u.id}" style="color: var(--primary-light); text-decoration: none; font-size: 0.875rem; margin-right: 1rem;">Message</a>
                            ${u.role === 'host' ? `<a href="add_property.php?host_id=${u.id}" style="color: var(--success); text-decoration: none; font-size: 0.875rem;">+ Property</a>` : ''}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        }
        fetchUsers();
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
