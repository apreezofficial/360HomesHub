document.addEventListener('DOMContentLoaded', () => {
    const aside = document.querySelector('aside');
    if (!aside) return;

    const currentPage = window.location.pathname.split('/').pop() || 'index.php';

    const menuItems = [
        { name: 'Dashboard', icon: 'bi-grid', link: 'index.php' },
        {
            name: 'Users',
            icon: 'bi-person',
            link: 'users.php',
            children: [
                { name: 'All Users', link: 'users.php' },
                { name: 'Hosts', link: 'users.php?role=host' },
                { name: 'Guests', link: 'users.php?role=guest' }
            ]
        },
        { name: 'Verification', icon: 'bi-shield-check', link: 'kyc.php' },
        { name: 'Listings', icon: 'bi-house-door', link: 'listings.php' },
        { name: 'Bookings', icon: 'bi-calendar-event', link: 'bookings.php' },
        { name: 'Finance', icon: 'bi-wallet2', link: 'transactions.php' },
        { name: 'Chat monitoring', icon: 'bi-chat-dots', link: 'communications.php' },
        {
            name: 'Settings',
            icon: 'bi-gear',
            link: '#',
            isOpen: ['settings.php', 'admin_roles.php', 'notifications.php', 'policies.php', 'audit_logs.php', 'admin_profile.php'].some(p => window.location.pathname.includes(p)),
            children: [
                { name: 'Admin roles', link: 'admin_roles.php' },
                { name: 'Notification', link: 'notifications.php' },
                { name: 'Policy management', link: 'policies.php' },
                { name: 'Audit logs', link: 'audit_logs.php' }
            ]
        }
    ];

    aside.className = 'w-[260px] fixed h-full bg-white z-50 flex flex-col border-r border-gray-100 font-outfit';
    aside.innerHTML = `
        <div class="h-20 w-full flex items-center px-8">
            <div class="w-10 h-10 bg-gray-200 rounded-lg"></div>
        </div>
        
        <ul class="flex-1 py-4 overflow-y-auto custom-scrollbar space-y-1 px-4">
            ${menuItems.map(item => {
        const isCurrent = currentPage === item.link || (item.link === 'index.php' && currentPage === '');
        const hasChildren = item.children && item.children.length > 0;
        const isChildActive = hasChildren && item.children.some(c => currentPage === c.link);
        const isOpen = item.isOpen || isChildActive;

        const activeClass = 'bg-gray-100 text-gray-900 font-semibold';
        const inactiveClass = 'text-gray-500 hover:bg-gray-50';

        return `
                <li>
                    <a href="${hasChildren ? '#' : item.link}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all ${isCurrent && !hasChildren ? activeClass : inactiveClass} ${isOpen && hasChildren ? 'text-gray-900' : ''}"
                       ${hasChildren ? `onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.arrow').classList.toggle('rotate-180'); return false;"` : ''}
                    >
                        <i class="bi ${item.icon} text-[20px] ${isCurrent || isOpen ? 'text-gray-900' : 'text-gray-400'}"></i>
                        <span class="text-[15px] flex-1">${item.name}</span>
                        ${hasChildren ? `<i class="bi bi-chevron-right arrow text-[10px] text-gray-400 transition-transform ${isOpen ? 'rotate-90' : ''}"></i>` : ''}
                    </a>
                    ${hasChildren ? `
                        <ul class="pl-12 pr-2 space-y-1 mt-1 ${isOpen ? '' : 'hidden'}">
                            ${item.children.map(child => {
            const isChildCurrent = currentPage === child.link;
            return `
                                <li>
                                    <a href="${child.link}" class="block px-3 py-2 text-[14px] rounded-md transition-colors ${isChildCurrent ? 'text-gray-900 font-medium' : 'text-gray-500 hover:text-gray-900'}">
                                        ${child.name}
                                    </a>
                                </li>
                                `;
        }).join('')}
                        </ul>
                    ` : ''}
                </li>
                `;
    }).join('')}
        </ul>

        <div class="p-6 space-y-4">
            <div class="bg-white border border-gray-100 rounded-2xl p-4 flex items-center gap-3 shadow-sm cursor-pointer hover:bg-gray-50 transition-all" onclick="window.location.href='admin_profile.php?id=current'">
                <div id="sidebar-avatar" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-bold border border-gray-200">
                    A
                </div>
                <div class="flex-1 min-w-0">
                    <div id="sidebar-name" class="text-[14px] font-bold text-gray-900 truncate">Admin</div>
                    <div id="sidebar-role" class="text-[12px] text-gray-400 truncate">Super admin</div>
                </div>
                <i class="bi bi-chevron-right text-gray-300 text-xs text-right"></i>
            </div>
            <button onclick="logout()" class="w-full py-3 px-4 border border-red-100 text-red-500 rounded-xl text-[14px] font-bold hover:bg-red-50 transition-all">
                Sign out
            </button>
        </div>
    `;

    // Add scrollbar styles
    if (!document.getElementById('scrollbar-style')) {
        const style = document.createElement('style');
        style.id = 'scrollbar-style';
        style.innerHTML = `
            .custom-scrollbar::-webkit-scrollbar { width: 0px; }
            .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
            .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 3px; }
        `;
        document.head.appendChild(style);
    }

    loadSidebarProfile();
});

function logout() {
    if (confirm('Are you sure you want to log out?')) {
        localStorage.removeItem('jwt_token');
        window.location.href = 'login.php';
    }
}

async function loadSidebarProfile() {
    const token = localStorage.getItem('jwt_token');
    if (!token) return;

    try {
        const res = await fetch('../api/admin/detail.php?id=current', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await res.json();

        if (data.success && data.data.admin) {
            const admin = data.data.admin;
            const name = `${admin.first_name || ''} ${admin.last_name || ''}`.trim() || admin.email;
            document.getElementById('sidebar-name').textContent = name;
            document.getElementById('sidebar-role').textContent = admin.role.replace('_', ' ');
            if (admin.avatar) {
                document.getElementById('sidebar-avatar').innerHTML = `<img src="${admin.avatar.startsWith('http') ? admin.avatar : '../' + admin.avatar}" class="w-full h-full rounded-full object-cover">`;
            } else {
                document.getElementById('sidebar-avatar').textContent = admin.first_name ? admin.first_name[0].toUpperCase() : 'A';
            }
        }
    } catch (err) {
        console.error('Failed to load sidebar profile', err);
    }
}
