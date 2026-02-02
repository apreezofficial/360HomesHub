document.addEventListener('DOMContentLoaded', () => {
    const aside = document.querySelector('aside');
    if (!aside) return;

    const currentPage = window.location.pathname.split('/').pop() || 'index.php';

    const menuItems = [
        { name: 'Dashboard', icon: 'bi-grid-1x2-fill', link: 'index.php' },
        { name: 'Properties', icon: 'bi-building-fill', link: 'index.php' },
        { name: 'Users', icon: 'bi-people-fill', link: 'users.php' },
        { name: 'Transactions', icon: 'bi-wallet2', link: 'transactions.php' },
        { name: 'KYC Requests', icon: 'bi-shield-check', link: 'kyc.php' },
        { name: 'Add User', icon: 'bi-person-plus-fill', link: 'add_user.php' },
        { name: 'Communications', icon: 'bi-chat-dots-fill', link: 'communications.php' }
    ];

    aside.innerHTML = `
        <div class="logo">
            <i class="bi bi-houses-fill"></i>
            360Admin
        </div>
        <nav>
            <ul>
                ${menuItems.map(item => `
                    <li>
                        <a href="${item.link}" class="${currentPage === item.link ? 'active' : ''}">
                            <i class="bi ${item.icon}"></i> ${item.name}
                        </a>
                    </li>
                `).join('')}
            </ul>
        </nav>
        <div style="margin-top: auto;">
            <a href="login.php" onclick="localStorage.removeItem('jwt_token')" style="color: var(--text-dim); text-decoration: none; font-size: 0.875rem;">
                <i class="bi bi-box-arrow-left"></i> Logout
            </a>
        </div>
    `;
});
