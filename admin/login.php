<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | 360HomesHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        body {
            justify-content: center;
            align-items: center;
            background: radial-gradient(circle at top right, #1e1b4b, #05070a);
        }
        .login-card {
            width: 100%;
            max-width: 450px;
            padding: 3rem;
            animation: fadeIn 1s ease-out;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2.5rem;
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(to right, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>
    <div class="content-card login-card">
        <div class="login-logo">360Admin</div>
        <h2 style="text-align:center; margin-bottom: 0.5rem;">Welcome Back</h2>
        <p style="text-align:center; color: var(--text-dim); margin-bottom: 2.5rem;">Secure access to management portal</p>
        
        <form id="login-form">
            <label>Admin Email</label>
            <input type="email" id="email" placeholder="admin@360homeshub.com" required>
            
            <label>Password</label>
            <input type="password" id="password" placeholder="••••••••" required>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                Sign In to Dashboard
            </button>
            <p id="msg" style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem; min-height: 1.25rem;"></p>
        </form>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const msg = document.getElementById('msg');
            msg.textContent = 'Authenticating...';
            msg.style.color = 'var(--text-dim)';

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            try {
                const res = await fetch('../api/admin/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                const response = await res.json();

                if (response.success) {
                    localStorage.setItem('jwt_token', response.data.token);
                    msg.style.color = 'var(--success)';
                    msg.textContent = 'Login successful! Redirecting...';
                    setTimeout(() => window.location.href = 'index.php', 1000);
                } else {
                    msg.style.color = 'var(--danger)';
                    msg.textContent = response.message;
                }
            } catch (err) {
                msg.style.color = 'var(--danger)';
                msg.textContent = 'Connection error. Please try again.';
            }
        });
    </script>
</body>
</html>
