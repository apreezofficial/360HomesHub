<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communications | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <aside></aside>
    <main>
        <header style="margin-bottom: 2rem;">
            <h1>Communication Center</h1>
            <p style="color: var(--text-dim)">Blast notifications or send direct messages</p>
        </header>

        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 2rem;">
            <!-- Notifications -->
            <div class="content-card">
                <h3><i class="bi bi-megaphone"></i> Send Notification</h3>
                <p style="color: var(--text-dim); font-size: 0.875rem; margin-bottom: 1.5rem;">Targeted or Global In-App alerts</p>
                
                <form id="notif-form">
                    <label>Recipient</label>
                    <select id="notif-target">
                        <option value="all">All Users</option>
                        <!-- Dynamic options can be added here -->
                    </select>

                    <label>Message</label>
                    <textarea id="notif-msg" rows="4" placeholder="Type your notification here..." required></textarea>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Send Alert</button>
                    <p id="notif-status" style="margin-top: 1rem; text-align: center; font-size: 0.875rem;"></p>
                </form>
            </div>

            <!-- Direct Message -->
            <div class="content-card">
                <h3><i class="bi bi-chat-left-dots"></i> Direct Message</h3>
                <p style="color: var(--text-dim); font-size: 0.875rem; margin-bottom: 1.5rem;">Send a private message to a specific user</p>

                <form id="msg-form">
                    <label>User ID / Receiver</label>
                    <input type="number" id="msg-receiver" placeholder="User ID" required>
                    
                    <label>Message Content</label>
                    <textarea id="msg-content" rows="4" placeholder="Enter private message..." required></textarea>

                    <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="bi bi-send"></i> Send Message</button>
                    <p id="msg-status" style="margin-top: 1rem; text-align: center; font-size: 0.875rem;"></p>
                </form>
            </div>
        </div>
    </main>

    <script>
        const token = localStorage.getItem('jwt_token');
        if (!token) window.location.href = 'login.php';

        const params = new URLSearchParams(window.location.search);
        const prefillId = params.get('user_id');

        if (prefillId) {
            document.getElementById('msg-receiver').value = prefillId;
            const targetSelect = document.getElementById('notif-target');
            const opt = document.createElement('option');
            opt.value = prefillId;
            opt.textContent = `User ID: ${prefillId}`;
            opt.selected = true;
            targetSelect.appendChild(opt);
        }

        // Handle Notification
        document.getElementById('notif-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const status = document.getElementById('notif-status');
            status.textContent = 'Sending...';

            const res = await fetch('../api/admin/send_notification.php', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                body: JSON.stringify({ target: document.getElementById('notif-target').value, message: document.getElementById('notif-msg').value })
            });
            const response = await res.json();
            status.textContent = response.message;
            status.style.color = response.success ? 'var(--success)' : 'var(--danger)';
            if (response.success) document.getElementById('notif-msg').value = '';
        });

        // Handle Message
        document.getElementById('msg-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const status = document.getElementById('msg-status');
            status.textContent = 'Sending...';

            const res = await fetch('../api/admin/send_message.php', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                body: JSON.stringify({ receiver_id: document.getElementById('msg-receiver').value, message: document.getElementById('msg-content').value })
            });
            const response = await res.json();
            status.textContent = response.message;
            status.style.color = response.success ? 'var(--success)' : 'var(--danger)';
            if (response.success) document.getElementById('msg-content').value = '';
        });
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
