import $ from 'jquery';
import 'bootstrap';
import 'admin-lte';
import DataTable from 'datatables.net-bs4';
import 'datatables.net-responsive-bs4';
import { Chart } from 'chart.js/auto';

window.$ = window.jQuery = $;
window.Chart = Chart;
DataTable(window, $);

document.addEventListener('DOMContentLoaded', () => {
    const currentPath = window.location.pathname.replace(/\/$/, '');
    document.querySelectorAll('.nav-sidebar .nav-link[href]').forEach((link) => {
        const linkPath = new URL(link.href, window.location.origin).pathname.replace(/\/$/, '');
        if (linkPath && linkPath !== '/' && (currentPath === linkPath || currentPath.startsWith(`${linkPath}/`))) {
            link.classList.add('active');
        }
    });

    $('.datatable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [],
    });

    document.querySelectorAll('form[data-prevent-double-submit="true"]').forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.disabled = true;
                button.classList.add('is-loading');
            });
        });
    });

    const timeoutMeta = document.querySelector('meta[name="session-timeout-seconds"]');
    const keepAliveMeta = document.querySelector('meta[name="session-keep-alive-url"]');
    const warning = document.getElementById('sessionTimeoutWarning');
    if (timeoutMeta && keepAliveMeta && warning) {
        const timeoutSeconds = Math.max(Number(timeoutMeta.content) || 120 * 60, 60);
        const warningSeconds = Math.min(30, timeoutSeconds);
        const clock = document.getElementById('sessionCountdownClock');
        const value = document.getElementById('sessionCountdownValue');
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        let lastActivity = Date.now();
        let lastKeepAlive = Date.now();
        let keepAlivePending = false;

        async function renewSession() {
            if (keepAlivePending) return;
            keepAlivePending = true;
            try {
                const response = await fetch(keepAliveMeta.content, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (response.status === 401) { window.location.reload(); return; }
                if (response.ok) lastKeepAlive = Date.now();
            } finally { keepAlivePending = false; }
        }

        function registerActivity() {
            const wasWarning = !warning.hidden;
            lastActivity = Date.now();
            warning.hidden = true;
            if (wasWarning || Date.now() - lastKeepAlive > Math.min(60000, timeoutSeconds * 500)) renewSession();
        }

        ['click', 'keydown', 'touchstart'].forEach(eventName => document.addEventListener(eventName, registerActivity, { passive: true }));
        setInterval(() => {
            const remaining = Math.ceil(timeoutSeconds - (Date.now() - lastActivity) / 1000);
            if (remaining <= 0) { window.location.reload(); return; }
            if (remaining <= warningSeconds) {
                warning.hidden = false;
                value.textContent = remaining;
                clock.style.setProperty('--countdown-progress', `${Math.max(remaining / warningSeconds * 360, 0)}deg`);
            } else {
                warning.hidden = true;
            }
        }, 500);
    }
});
