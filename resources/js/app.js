import './game/madrid-hub';
import './game/panaderia-recorder';
import './game/panaderia-dialogue';
import './content-studio/content-builder';
import './content-studio/content-preview';

const sidebar = document.querySelector('[data-studio-sidebar]');
const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
const sidebarToggles = document.querySelectorAll('[data-sidebar-toggle]');

const setSidebarOpen = (open) => {
    if (!sidebar || !sidebarOverlay) {
        return;
    }

    sidebar.classList.toggle('-translate-x-full', !open);
    sidebar.classList.toggle('translate-x-0', open);
    sidebarOverlay.classList.toggle('hidden', !open);
    document.body.classList.toggle('overflow-hidden', open && window.innerWidth < 1024);

    sidebarToggles.forEach((toggle) => toggle.setAttribute('aria-expanded', String(open)));
};

sidebarToggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const isOpen = sidebar?.classList.contains('translate-x-0') ?? false;
        setSidebarOpen(!isOpen);
    });
});

sidebarOverlay?.addEventListener('click', () => setSidebarOpen(false));

const userMenuButton = document.querySelector('[data-user-menu-button]');
const userMenu = document.querySelector('[data-user-menu]');

const setUserMenuOpen = (open) => {
    if (!userMenuButton || !userMenu) {
        return;
    }

    userMenu.classList.toggle('hidden', !open);
    userMenuButton.setAttribute('aria-expanded', String(open));
};

userMenuButton?.addEventListener('click', (event) => {
    event.stopPropagation();
    setUserMenuOpen(userMenu?.classList.contains('hidden') ?? true);
});

document.addEventListener('click', (event) => {
    if (userMenu && !userMenu.contains(event.target) && event.target !== userMenuButton) {
        setUserMenuOpen(false);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    setSidebarOpen(false);
    setUserMenuOpen(false);
});

window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        setSidebarOpen(false);
    }
});
