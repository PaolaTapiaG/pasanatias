import './bootstrap';
import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

/**
 * ==========================================
 * DARK MODE TOGGLE
 * ==========================================
 */
const setupTheme = () => {
    const root = document.documentElement;
    const toggle = document.getElementById('themeToggle');
    const storageKey = 'epsas-theme';
    
    // Determine initial theme
    const savedTheme = localStorage.getItem(storageKey);
    const fallbackTheme = document.body?.dataset.themeDefault || 'light';
    const initialTheme = savedTheme || fallbackTheme;
    
    // Apply initial theme
    const applyTheme = (theme) => {
        if (theme === 'dark') {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }
        localStorage.setItem(storageKey, theme);
    };
    
    applyTheme(initialTheme);
    
    // Toggle theme on button click
    if (toggle) {
        toggle.addEventListener('click', () => {
            const isDark = root.classList.contains('dark');
            applyTheme(isDark ? 'light' : 'dark');
        });
    }
    
    window.addEventListener('storage', (event) => {
        if (event.key === storageKey && event.newValue) {
            applyTheme(event.newValue);
        }
    });
};

/**
 * ==========================================
 * NEWS CAROUSEL (SWIPER)
 * ==========================================
 */
const setupNewsCarousel = () => {
    const newsCarousel = document.querySelector('.newsSwiper');
    if (!newsCarousel) return;
    
    new Swiper(newsCarousel, {
        modules: [Navigation, Pagination, Autoplay],
        slidesPerView: 1.2,
        spaceBetween: 30,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        breakpoints: {
            640: {
                slidesPerView: 1.5,
                spaceBetween: 20,
            },
            1024: {
                slidesPerView: 2.2,
                spaceBetween: 30,
            },
            1280: {
                slidesPerView: 3,
                spaceBetween: 30,
            },
        },
    });
};

/**
 * ==========================================
 * MOBILE MENU TOGGLE
 * ==========================================
 */
const setupMobileMenu = () => {
    const toggle = document.querySelector('[data-mobile-menu-toggle]');
    const menu = document.querySelector('[data-mobile-menu]');
    
    if (!toggle || !menu) return;
    
    toggle.addEventListener('click', () => {
        menu.classList.toggle('is-open');
    });
    
    // Close menu when a link is clicked
    const links = menu.querySelectorAll('a');
    links.forEach(link => {
        link.addEventListener('click', () => {
            menu.classList.remove('is-open');
        });
    });
};

/**
 * ==========================================
 * SCROLL ANIMATIONS
 * ==========================================
 */
const setupScrollAnimations = () => {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('[data-animate]').forEach(el => {
        observer.observe(el);
    });
};

/**
 * ==========================================
 * INITIALIZE ALL
 * ==========================================
 */
const setupSidebar = () => {
    const sidebar = document.querySelector('[data-admin-sidebar], [data-tech-sidebar], [data-secretaria-sidebar]');
    const main = document.querySelector('[data-sidebar-main], [data-admin-main], [data-tech-main]');
    const labels = sidebar?.querySelectorAll('[data-sidebar-label]') ?? [];
    const desktopToggles = document.querySelectorAll('[data-sidebar-toggle]');
    const openers = document.querySelectorAll('[data-sidebar-open]');
    const closers = document.querySelectorAll('[data-sidebar-close], [data-sidebar-overlay]');
    const icons = document.querySelectorAll('[data-sidebar-toggle-icon]');
    const overlay = document.querySelector('[data-sidebar-overlay]');

    if (!sidebar) {
        return;
    }

    const collapsedWidth = 'md:w-28';
    const expandedWidth = 'md:w-72';
    const collapsedPadding = 'md:pl-28';
    const expandedPadding = 'md:pl-72';
    const storageKey = sidebar.hasAttribute('data-tech-sidebar')
        ? 'epsas-tech-sidebar-collapsed'
        : sidebar.hasAttribute('data-secretaria-sidebar')
            ? 'epsas-secretaria-sidebar-collapsed'
        : 'epsas-admin-sidebar-collapsed';

    const applyDesktopState = (collapsed) => {
        if (window.innerWidth < 768) {
            return;
        }

        sidebar.dataset.collapsed = collapsed ? 'true' : 'false';
        sidebar.classList.toggle(collapsedWidth, collapsed);
        sidebar.classList.toggle(expandedWidth, !collapsed);

        if (main) {
            main.classList.toggle(collapsedPadding, collapsed);
            main.classList.toggle(expandedPadding, !collapsed);
        }

        labels.forEach((label) => {
            const persistent = label.hasAttribute('data-sidebar-persistent');
            label.dataset.collapsed = collapsed ? 'true' : 'false';
            label.classList.toggle('hidden', collapsed && !persistent);
        });

        icons.forEach((icon) => {
            icon.classList.toggle('rotate-180', collapsed);
        });

        window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
    };

    const openMobileSidebar = () => {
        sidebar.classList.remove('-translate-x-full');
        overlay?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeMobileSidebar = () => {
        if (window.innerWidth >= 768) {
            overlay?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            return;
        }

        sidebar.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    const syncByViewport = () => {
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('-translate-x-full');
            overlay?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            applyDesktopState(window.localStorage.getItem(storageKey) === '1');
        } else {
            sidebar.classList.add('-translate-x-full');
            labels.forEach((label) => {
                label.classList.remove('hidden');
                label.dataset.collapsed = 'false';
            });
            sidebar.dataset.collapsed = 'false';
        }
    };

    applyDesktopState(window.localStorage.getItem(storageKey) === '1');
    syncByViewport();

    desktopToggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            if (window.innerWidth < 768) {
                openMobileSidebar();
                return;
            }

            const nextCollapsed = sidebar.dataset.collapsed !== 'true';
            applyDesktopState(nextCollapsed);
        });
    });

    openers.forEach((opener) => opener.addEventListener('click', openMobileSidebar));
    closers.forEach((closer) => closer.addEventListener('click', closeMobileSidebar));
    window.addEventListener('resize', syncByViewport);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMobileSidebar();
        }
    });
};

/**
 * ==========================================
 * DOCUMENT READY
 * ==========================================
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setupTheme();
        setupMobileMenu();
        setupNewsCarousel();
        setupScrollAnimations();
        setupSidebar();
    });
} else {
    setupTheme();
    setupMobileMenu();
    setupNewsCarousel();
    setupScrollAnimations();
    setupSidebar();
}
