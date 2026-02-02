<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <aside></aside>
    <main>
        <header style="margin-bottom: 2rem;"><h1>Financial Transactions</h1></header>
        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Gateway</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="trans-table"></tbody>
            </table>
        </div>
    </main>
    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        async function fetchTransactions() {
            const token = localStorage.getItem('jwt_token');
            const res = await fetch('../api/admin/transactions.php', { headers: { 'Authorization': `Bearer ${token}` } });
            const data = await res.json();
            if (data.success) {
                const tbody = document.getElementById('trans-table');
                data.data.transactions.forEach(t => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><code style="color: var(--primary)">${t.reference}</code></td>
                        <td><strong>${t.first_name} ${t.last_name}</strong><br><small>${t.email}</small></td>
                        <td>₦${parseFloat(t.amount).toLocaleString()}</td>
                        <td style="text-transform: capitalize;">${t.gateway}</td>
                        <td><span class="badge badge-${t.status === 'success' ? 'published' : 'danger'}">${t.status}</span></td>
                        <td>${new Date(t.created_at).toLocaleString()}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        }
        fetchTransactions();
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
