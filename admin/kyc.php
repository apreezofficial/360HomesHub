<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Management | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <aside></aside>
    <main>
        <header style="margin-bottom: 2rem;"><h1>KYC Management</h1></header>
        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Country/Type</th>
                        <th>Documents</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="kyc-table"></tbody>
            </table>
        </div>
    </main>
    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        const API_BASE = window.location.origin;
        async function fetchKYC() {
            const token = localStorage.getItem('jwt_token');
            const res = await fetch('../api/admin/kyc_list.php', { headers: { 'Authorization': `Bearer ${token}` } });
            const data = await res.json();
            if (data.success) {
                const tbody = document.getElementById('kyc-table');
                tbody.innerHTML = '';
                data.data.kyc.forEach(k => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${k.first_name} ${k.last_name}</strong><br><small>${k.email}</small></td>
                        <td>${k.country}<br><small style="text-transform: uppercase;">${k.identity_type}</small></td>
                        <td style="display: flex; gap: 0.5rem;">
                            <img src="${API_BASE}/360HomesHub/${k.id_front}" style="width: 80px; height: 50px; object-fit: cover; border-radius: 0.5rem; cursor: pointer;" title="Front" onclick="window.open(this.src)">
                            <img src="${API_BASE}/360HomesHub/${k.id_back}" style="width: 80px; height: 50px; object-fit: cover; border-radius: 0.5rem; cursor: pointer;" title="Back" onclick="window.open(this.src)">
                            <img src="${API_BASE}/360HomesHub/${k.selfie}" style="width: 80px; height: 50px; object-fit: cover; border-radius: 0.5rem; cursor: pointer;" title="Selfie" onclick="window.open(this.src)">
                        </td>
                        <td><span class="badge badge-${k.status === 'approved' ? 'published' : 'warning'}" style="text-transform: capitalize;">${k.status}</span></td>
                        <td>
                            ${k.status === 'pending' ? `
                                <button class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; background: var(--success);" onclick="updateKYC(${k.id}, 'approve')">Approve</button>
                                <button class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; background: var(--danger);" onclick="updateKYC(${k.id}, 'reject')">Reject</button>
                            ` : '-'}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        }

        async function updateKYC(kycId, action) {
            const token = localStorage.getItem('jwt_token');
            const path = action === 'approve' ? 'approve_kyc.php' : 'reject_kyc.php';
            const body = action === 'approve' ? { kyc_id: kycId } : { kyc_id: kycId, admin_note: 'Rejected by admin' };

            const res = await fetch(`../api/admin/${path}`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            const data = await res.json();
            if (data.success) {
                alert(`KYC ${action}d successfully`);
                fetchKYC();
            } else {
                alert(data.message);
            }
        }

        fetchKYC();
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
