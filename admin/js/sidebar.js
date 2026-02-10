document.addEventListener('DOMContentLoaded', () => {
    const aside = document.querySelector('aside');
    if (!aside) return;

    const currentPage = window.location.pathname.split('/').pop() || 'index.php';

    const menuItems = [
        { name: 'Dashboard', icon: 'bi-speedometer2', link: 'index.php' },
        {
            name: 'Users',
            icon: 'bi-people',
            link: 'users.php',
            children: [
                { name: 'All Users', link: 'users.php' },
                { name: 'Hosts', link: 'users.php?role=host' },
                { name: 'Guests', link: 'users.php?role=guest' }
            ]
        },
        { name: 'Verification', icon: 'bi-shield-check', link: 'kyc.php' },
        { name: 'Listings', icon: 'bi-house-door', link: 'listings.php' },
        { name: 'Bookings', icon: 'bi-calendar-check', link: 'bookings.php' },
        { name: 'Finance', icon: 'bi-currency-dollar', link: 'transactions.php' },
        { name: 'Chat monitoring', icon: 'bi-chat-dots', link: 'communications.php' },
        {
            name: 'Settings',
            icon: 'bi-gear',
            link: '#', // Settings parent doesn't navigate itself usually if it has children, or defaults to first child
            isOpen: ['settings.php', 'admin_roles.php', 'notifications.php', 'policies.php', 'audit_logs.php', 'admin_profile.php'].some(p => window.location.pathname.includes(p)),
            children: [
                { name: 'Admin roles', link: 'admin_roles.php' },
                { name: 'Notification', link: 'notifications.php' },
                { name: 'Policy management', link: 'policies.php' },
                { name: 'Audit logs', link: 'audit_logs.php' }
            ]
        }
    ];

    aside.className = 'w-[240px] fixed h-full bg-white z-50 flex flex-col shadow-sm border-r border-gray-100';
    aside.innerHTML = `
        <div class="h-16 w-full flex items-center px-6 border-b border-gray-100">
            <h1 class="font-outfit text-xl font-bold text-gray-800 tracking-tight">36Admin</h1>
        </div>
        
        <ul class="flex-1 py-6 overflow-y-auto custom-scrollbar space-y-1 px-3">
            ${menuItems.map(item => {
        const isCurrent = currentPage === item.link || (item.link === 'index.php' && currentPage === '');
        const hasChildren = item.children && item.children.length > 0;
        // Check if any child is active
        const isChildActive = hasChildren && item.children.some(c => currentPage === c.link || (c.link === 'admin_profile.php' && item.name === 'Settings')); // admin_profile is part of admin roles

        const isOpen = item.isOpen || isChildActive;

        const activeClass = 'bg-primary/5 text-primary font-semibold';
        const inactiveClass = 'text-gray-600 hover:bg-gray-50';

        let html = `
                <li>
                    <a href="${hasChildren ? '#' : item.link}" 
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all ${isCurrent && !hasChildren ? activeClass : inactiveClass} ${isOpen ? 'text-gray-900' : ''}"
                       ${hasChildren ? `onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.arrow').classList.toggle('rotate-180'); return false;"` : ''}
                    >
                        <i class="bi ${item.icon} text-[18px] ${isCurrent || isOpen ? 'text-primary' : 'text-gray-400'}"></i>
                        <span class="text-[14px] flex-1">${item.name}</span>
                        ${hasChildren ? `<i class="bi bi-chevron-down arrow text-[10px] text-gray-400 transition-transform ${isOpen ? 'rotate-180' : ''}"></i>` : ''}
                    </a>
                    ${hasChildren ? `
                        <ul class="pl-11 pr-2 space-y-1 mt-1 ${isOpen ? '' : 'hidden'}">
                            ${item.children.map(child => {
            const isChildCurrent = currentPage === child.link || (child.link === 'admin_roles.php' && currentPage === 'admin_profile.php');
            return `
                                <li>
                                    <a href="${child.link}" class="block px-3 py-2 text-[13px] rounded-md transition-colors ${isChildCurrent ? 'text-primary font-medium bg-white shadow-sm border border-gray-100' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'}">
                                        ${child.name}
                                    </a>
                                </li>
                                `;
        }).join('')}
                        </ul>
                    ` : ''}
                </li>
                `;
        return html;
    }).join('')}
        </ul>

        <div class="border-t border-gray-100 p-4">
            <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-all mb-3" onclick="window.location.href='admin_profile.php?id=current'">
                <div id="sidebar-avatar" class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-semibold text-sm border border-gray-300">
                    A
                </div>
                <div class="flex-1 overflow-hidden min-w-0">
                    <div id="sidebar-name" class="text-[13px] font-semibold text-gray-800 truncate">Loading...</div>
                    <div id="sidebar-role" class="text-[11px] text-gray-500 truncate lowercase capitalize">...</div>
                </div>
                <i class="bi bi-chevron-right text-gray-400 text-xs"></i>
            </div>
            <button onclick="logout()" class="w-full flex items-center justify-center gap-2 py-2.5 px-4 border border-gray-200 text-gray-700 rounded-lg text-[13px] font-medium hover:bg-gray-50 transition-all">
                <span>Sign out</span>
            </button>
        </div>
    `;

    // Add CSS for scrollbar
    if (!document.getElementById('scrollbar-style')) {
        const style = document.createElement('style');
        style.id = 'scrollbar-style';
        style.innerHTML = `
            .custom-scrollbar::-webkit-scrollbar { width: 3px; }
            .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
            .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 3px; }
            .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
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
        // Use the detail endpoint with id=current
        const res = await fetch('../api/admin/detail.php?id=current', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await res.json();

        if (data.success && data.data.admin) {
            const admin = data.data.admin;
            const name = `${admin.first_name} ${admin.last_name}`;
            const role = admin.role.replace('_', ' ');

            document.getElementById('sidebar-name').textContent = name;
            document.getElementById('sidebar-role').textContent = role;
            document.getElementById('sidebar-avatar').textContent = admin.first_name ? admin.first_name[0].toUpperCase() : 'A';
        }
    } catch (err) {
        console.error('Failed to load sidebar profile', err);
    }
}
