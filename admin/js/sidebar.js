document.addEventListener('DOMContentLoaded', () => {
    const aside = document.querySelector('aside');
    if (!aside) return;

    // ── Mobile Toggle Setup ──
    const main = document.querySelector('main');
    const body = document.body;

    // Create Hamburger Button
    const mobileHeader = document.createElement('div');
    mobileHeader.className = 'lg:hidden fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-100 px-6 flex items-center justify-between z-[60] shadow-sm';
    mobileHeader.innerHTML = `
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center text-white font-bold text-xs">36</div>
            <span class="font-bold text-gray-900 text-sm">36HomesHub</span>
        </div>
        <button id="mobile-menu-toggle" class="p-2 text-gray-400 hover:text-gray-900 transition-colors">
            <i class="bi bi-list text-2xl"></i>
        </button>
    `;
    body.prepend(mobileHeader);

    // Create Backdrop
    const backdrop = document.createElement('div');
    backdrop.id = 'sidebar-backdrop';
    backdrop.className = 'fixed inset-0 bg-black/20 backdrop-blur-sm z-[45] hidden transition-opacity lg:hidden';
    body.appendChild(backdrop);

    const toggleSidebar = () => {
        const isOpen = !aside.classList.contains('-translate-x-full');
        if (isOpen) {
            aside.classList.add('-translate-x-full');
            backdrop.classList.add('hidden');
            body.style.overflow = '';
        } else {
            aside.classList.remove('-translate-x-full');
            backdrop.classList.remove('hidden');
            body.style.overflow = 'hidden';
        }
    };

    document.getElementById('mobile-menu-toggle')?.addEventListener('click', toggleSidebar);
    backdrop.addEventListener('click', toggleSidebar);

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

    aside.className = 'w-[280px] fixed h-full bg-white z-[55] flex flex-col border-r border-gray-100 font-outfit transition-transform lg:translate-x-0 -translate-x-full';
    aside.innerHTML = `
        <div class="h-20 w-full flex items-center px-8 border-b border-gray-50 mb-4">
             <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-primary/20">36</div>
                <div class="font-extrabold text-[#001D3D] text-[18px] tracking-tight">36HomesHub</div>
            </div>
        </div>
        
        <ul class="flex-1 py-2 overflow-y-auto custom-scrollbar space-y-1 px-4">
            ${menuItems.map(item => {
        const isCurrent = currentPage === item.link || (item.link === 'index.php' && currentPage === '');
        const hasChildren = item.children && item.children.length > 0;
        const isChildActive = hasChildren && item.children.some(c => currentPage === c.link);
        const isOpen = item.isOpen || isChildActive;

        const activeClass = 'bg-primary/5 text-primary font-bold shadow-sm ring-1 ring-primary/5';
        const inactiveClass = 'text-gray-500 hover:bg-gray-50 hover:text-gray-900';

        return `
                <li>
                    <a href="${hasChildren ? '#' : item.link}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all ${isCurrent && !hasChildren ? activeClass : inactiveClass} ${isOpen && hasChildren ? 'text-gray-900 font-bold bg-gray-50/50' : ''}"
                       ${hasChildren ? `onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.arrow').classList.toggle('rotate-90'); return false;"` : ''}
                    >
                        <i class="bi ${item.icon} text-[20px] ${isCurrent || isOpen ? 'text-primary' : 'text-gray-400'}"></i>
                        <span class="text-[14px] flex-1">${item.name}</span>
                        ${hasChildren ? `<i class="bi bi-chevron-right arrow text-[10px] text-gray-400 transition-transform ${isOpen ? 'rotate-90' : ''}"></i>` : ''}
                    </a>
                    ${hasChildren ? `
                        <ul class="pl-12 pr-2 space-y-1 mt-1 ${isOpen ? '' : 'hidden'}">
                            ${item.children.map(child => {
            const isChildCurrent = currentPage === child.link;
            return `
                                <li>
                                    <a href="${child.link}" class="block px-3 py-2 text-[13px] rounded-lg transition-colors ${isChildCurrent ? 'text-primary font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'}">
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

        <div class="px-6 py-6 border-t border-gray-50 bg-gray-50/20">
            <div class="bg-white border border-gray-100 rounded-2xl p-3 flex items-center gap-3 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] cursor-pointer hover:shadow-md transition-all mb-4" onclick="window.location.href='admin_profile.php?id=current'">
                <div id="sidebar-avatar" class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-bold border border-primary/5 overflow-hidden">
                    A
                </div>
                <div class="flex-1 min-w-0">
                    <div id="sidebar-name" class="text-[13px] font-bold text-gray-900 truncate">Admin</div>
                    <div id="sidebar-role" class="text-[11px] text-gray-400 truncate tracking-wide">Super Admin</div>
                </div>
                <i class="bi bi-chevron-right text-gray-300 text-[10px]"></i>
            </div>
            <button onclick="logout()" class="w-full py-3 px-4 border border-red-100 text-red-500 rounded-xl text-[13px] font-bold hover:bg-red-50 transition-all flex items-center justify-center gap-2">
                <i class="bi bi-box-arrow-right"></i> Sign out
            </button>
        </div>
    `;

    // Adjust main padding
    if (main) {
        main.classList.remove('ml-[260px]');
        main.className = 'flex-1 lg:ml-[280px] min-h-screen pt-20 lg:pt-8 px-4 sm:px-8 pb-12 transition-all';
    }


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
