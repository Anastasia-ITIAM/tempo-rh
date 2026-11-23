/**
 * Éléments d'interface partagés par les scripts timesheet :
 * notifications (style EasyAdmin) et modales.
 */

const FLASH_KEY = 'timesheet-flash';

// ==================== NOTIFICATIONS (style EasyAdmin) ====================

/**
 * Affiche une barre de notification en haut de la page, comme les messages flash d'EasyAdmin.
 * type : 'success' | 'danger' (ou 'error') | 'warning' | 'info'
 */
export function showNotification(message, type = 'info') {
    const bsType = {
        success: 'success',
        error: 'danger',
        danger: 'danger',
        warning: 'warning',
        info: 'info'
    }[type] || 'info';

    let container = document.getElementById('flash-messages');
    if (!container) {
        container = document.createElement('div');
        container.id = 'flash-messages';
        document.body.prepend(container);
    }
    container.innerHTML = '';

    const alert = document.createElement('div');
    alert.className = `alert alert-${bsType} flash-alert`;
    alert.setAttribute('role', 'alert');

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'flash-close';
    closeBtn.setAttribute('aria-label', 'Fermer');
    closeBtn.innerHTML = '&times;';
    closeBtn.addEventListener('click', () => alert.remove());

    alert.append(document.createTextNode(message), closeBtn);
    container.appendChild(alert);

    setTimeout(() => alert.remove(), 5000);
}

/** Garde le message de côté, recharge la page, puis l'affiche après le rechargement */
export function reloadWithFlash(message, type = 'info') {
    try {
        sessionStorage.setItem(FLASH_KEY, JSON.stringify({ message, type }));
    } catch (e) {}
    window.location.reload();
}

/** À appeler au chargement de la page : affiche le message gardé par reloadWithFlash() */
export function showPendingFlash() {
    try {
        const raw = sessionStorage.getItem(FLASH_KEY);
        if (!raw) return;
        sessionStorage.removeItem(FLASH_KEY);
        const { message, type } = JSON.parse(raw);
        showNotification(message, type);
    } catch (e) {}
}

// ==================== MODALES ====================

export function openModal(modalId) {
    document.getElementById(modalId)?.classList.add('is-open');
}

export function closeModal(modalId) {
    document.getElementById(modalId)?.classList.remove('is-open');
}

/**
 * Branche la fermeture de toutes les modales .ts-modal de la page
 */
export function initModals() {
    document.querySelectorAll('.ts-modal-close').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('.ts-modal')?.classList.remove('is-open'));
    });

    document.querySelectorAll('.ts-modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('is-open');
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.ts-modal.is-open').forEach(m => m.classList.remove('is-open'));
        }
    });
}
