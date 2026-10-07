/**
 * Apex Portal - Vanilla JS Logic
 * Theme Switcher (Light/Dark/System), Sidebar, Modals, AJAX Scraper Runner, Toasts
 */

(function () {
    'use strict';

    // 1. Theme Switcher System
    const THEME_KEY = 'apex_portal_theme';

    function getSystemTheme() {
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function applyTheme(themeMode) {
        const root = document.documentElement;
        let effectiveTheme = themeMode;

        if (themeMode === 'system') {
            effectiveTheme = getSystemTheme();
        }

        root.setAttribute('data-theme', effectiveTheme);
        root.setAttribute('data-theme-setting', themeMode);

        // Update Theme Switcher UI active states
        document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
            const mode = btn.getAttribute('data-theme-set');
            btn.classList.toggle('active', mode === themeMode);
        });

        // Update Radio Cards if present on profile page
        document.querySelectorAll('.theme-card-option').forEach(card => {
            const radio = card.querySelector('input[type="radio"]');
            if (radio) {
                if (radio.value === themeMode) {
                    radio.checked = true;
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            }
        });

        // Update quick toggle icon
        const quickIcon = document.getElementById('theme-quick-icon');
        if (quickIcon) {
            if (effectiveTheme === 'dark') {
                quickIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />`;
            } else {
                quickIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />`;
            }
        }
    }

    window.setPortalTheme = function (themeMode) {
        localStorage.setItem(THEME_KEY, themeMode);
        applyTheme(themeMode);

        // Notify user via subtle toast
        showToast(`Theme switched to ${themeMode.charAt(0).toUpperCase() + themeMode.slice(1)} mode`, 'info');

        // Sync with backend if user has session
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            fetch('/portal/update-theme', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ theme: themeMode })
            }).catch(() => {
                // Ignore silent background sync errors
            });
        }
    };

    // Toggle quick mode (switches back and forth between light and dark)
    window.toggleQuickTheme = function () {
        const currentEffective = document.documentElement.getAttribute('data-theme') || 'light';
        const nextMode = currentEffective === 'dark' ? 'light' : 'dark';
        window.setPortalTheme(nextMode);
    };

    // Initialize Theme
    const savedTheme = localStorage.getItem(THEME_KEY) || document.documentElement.getAttribute('data-user-theme') || 'dark';
    applyTheme(savedTheme);

    // Watch for OS system theme changes
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            const currentSetting = localStorage.getItem(THEME_KEY) || 'system';
            if (currentSetting === 'system') {
                applyTheme('system');
            }
        });
    }

    // 2. Mobile Sidebar Toggle
    document.addEventListener('DOMContentLoaded', () => {
        const menuBtn = document.getElementById('menu-toggle-btn');
        const sidebar = document.getElementById('portal-sidebar');

        if (menuBtn && sidebar) {
            menuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                sidebar.classList.toggle('open');
            });

            document.addEventListener('click', (e) => {
                if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            });
        }

        // Attach theme card click handlers on profile page
        document.querySelectorAll('.theme-card-option').forEach(card => {
            card.addEventListener('click', () => {
                const radio = card.querySelector('input[type="radio"]');
                if (radio) {
                    window.setPortalTheme(radio.value);
                }
            });
        });
    });

    // 3. Modals Management
    window.openModal = function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('open');
            document.body.style.overflow = '';
        }
    };

    // Close on backdrop or Esc key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.open').forEach(modal => {
                modal.classList.remove('open');
            });
            document.body.style.overflow = '';
        }
    });

    // 4. Toast Notifications
    window.showToast = function (message, type = 'info') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast`;

        let iconColor = '#6366f1';
        if (type === 'success') iconColor = '#10b981';
        if (type === 'danger') iconColor = '#ef4444';

        toast.innerHTML = `
            <div style="width: 10px; height: 10px; border-radius: 50%; background: ${iconColor}; flex-shrink: 0;"></div>
            <div style="font-size: 0.875rem; font-weight: 500; color: var(--text-primary); flex: 1;">${message}</div>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    };

    // 5. AJAX Scraper Trigger
    window.triggerScraperAjax = function (button, scraperId) {
        const originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `
            <svg class="animate-spin" style="width:16px;height:16px;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="12" cy="12" r="10" stroke-width="4" stroke="currentColor" stroke-dasharray="32" stroke-linecap="round"></circle>
            </svg>
            <span>Processing...</span>
        `;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(`/portal/scrapers/${scraperId}/trigger`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            button.disabled = false;
            button.innerHTML = originalHtml;

            if (data.success) {
                showToast(data.message, 'success');

                // Update badge and count on page
                const row = document.getElementById(`scraper-row-${scraperId}`);
                if (row) {
                    const badge = row.querySelector('.badge');
                    if (badge) {
                        badge.className = `badge status-${data.status}`;
                        badge.innerHTML = `<span class="badge-dot"></span> ${data.status.charAt(0).toUpperCase() + data.status.slice(1)}`;
                    }

                    const countEl = row.querySelector('.scraped-count');
                    if (countEl) countEl.innerText = Number(data.items_scraped).toLocaleString();

                    const lastRunEl = row.querySelector('.last-run-time');
                    if (lastRunEl) lastRunEl.innerText = data.last_run;
                }
            } else {
                showToast('Unable to execute scraper.', 'danger');
            }
        })
        .catch(err => {
            button.disabled = false;
            button.innerHTML = originalHtml;
            showToast('Network error while running scraper.', 'danger');
        });
    };

    // 6. Autofill Demo Credentials
    window.fillCredentials = function (role) {
        const emailInput = document.getElementById('email');
        const passInput = document.getElementById('password');

        if (!emailInput || !passInput) return;

        if (role === 'admin') {
            emailInput.value = 'admin@portal.test';
            passInput.value = 'password123';
        } else if (role === 'sarah') {
            emailInput.value = 'sarah@portal.test';
            passInput.value = 'password123';
        }

        showToast(`Filled ${role.toUpperCase()} credentials`, 'info');
    };

    // 7. Password Visibility Toggle
    window.togglePasswordVisibility = function (inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;

        if (input.type === 'password') {
            input.type = 'text';
            btn.innerHTML = `
                <svg style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                </svg>
            `;
        } else {
            input.type = 'password';
            btn.innerHTML = `
                <svg style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            `;
        }
    };
})();
