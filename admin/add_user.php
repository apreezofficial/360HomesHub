<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <aside></aside>
    <main>
        <header style="margin-bottom: 2rem;">
            <h1>Create New Account</h1>
            <p style="color: var(--text-dim)">Directly add a guest or host to the platform</p>
        </header>

        <div class="content-card" style="max-width: 600px;">
            <form id="add-user-form">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label>First Name</label>
                        <input type="text" id="first_name" placeholder="John" required>
                    </div>
                    <div>
                        <label>Last Name</label>
                        <input type="text" id="last_name" placeholder="Doe" required>
                    </div>
                </div>
                
                <label>Email Address</label>
                <input type="email" id="email" placeholder="john@example.com">

                <label>Phone Number</label>
                <input type="text" id="phone" placeholder="+234...">

                <label>Password</label>
                <input type="password" id="password" placeholder="Minimum 8 characters" required>

                <label>Account Role</label>
                <select id="role">
                    <option value="guest">Guest (Traveler)</option>
                    <option value="host">Host (Property Owner)</option>
                </select>

                <div style="margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
                    <p id="msg" style="margin-top: 1rem; text-align: center; font-size: 0.875rem;"></p>
                </div>
            </form>
        </div>
    </main>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        document.getElementById('add-user-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const msg = document.getElementById('msg');
            msg.textContent = 'Processing...';

            const data = {
                first_name: document.getElementById('first_name').value,
                last_name: document.getElementById('last_name').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                password: document.getElementById('password').value,
                role: document.getElementById('role').value
            };

            const token = localStorage.getItem('jwt_token');
            try {
                const res = await fetch('../api/admin/create_user.php', {
                    method: 'POST',
                    headers: { 
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                const response = await res.json();
                if (response.success) {
                    msg.style.color = 'var(--success)';
                    msg.textContent = 'User account created successfully!';
                    setTimeout(() => window.location.href = 'users.php', 1500);
                } else {
                    msg.style.color = 'var(--danger)';
                    msg.textContent = response.message;
                }
            } catch (err) {
                msg.textContent = 'Error: ' + err.message;
            }
        });
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
