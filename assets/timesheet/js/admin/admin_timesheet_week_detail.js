/**
 * Admin Timesheet - Page détail (/admin/timesheet/week/{id})
 * Gestion des modales d'approbation / rejet uniquement.
 */

const state = {
    selectedTimesheetId: null
};

document.addEventListener('DOMContentLoaded', () => {
    initEventListeners();
});

function initEventListeners() {
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.target.closest('.modal').classList.remove('show');
        });
    });

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('show');
            }
        });
    });
}

function prepareApproveDetail(timesheetId, userName, startDate, totalHours) {
    const summary = document.getElementById('approveSummary');
    summary.innerHTML = `
    <div class="approve-summary-box">
        <p><strong>${userName}</strong></p>
        <p>Semaine du ${startDate}</p>
        <p><strong>${totalHours}</strong> de travail</p>
    </div>
`;

    state.selectedTimesheetId = timesheetId;
    openModal('approveModal');
}

function prepareRejectDetail(timesheetId) {
    state.selectedTimesheetId = timesheetId;
    document.getElementById('rejectReason').value = '';
    openModal('rejectModal');
}

async function confirmApproveDetail() {
    try {
        closeModal('approveModal');
        showNotification('Approbation en cours...', 'info');

        const response = await fetch(`/api/admin/timesheet/${state.selectedTimesheetId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            showNotification(errorData.message || 'Erreur lors de l\'approbation', 'error');
            return;
        }

        showNotification('Feuille approuvée ✓', 'success');
        setTimeout(() => {
            window.location.reload();
        }, 1500);

    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de l\'approbation', 'error');
    }
}

async function confirmRejectDetail() {
    const textarea = document.getElementById('rejectReason');
    const reason = textarea.value.trim();

    if (!reason) {
        textarea.value = '';
        textarea.reportValidity();
        textarea.focus();
        return;
    }

    try {
        closeModal('rejectModal');
        showNotification('Rejet en cours...', 'info');

        const response = await fetch(`/api/admin/timesheet/${state.selectedTimesheetId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ reason })
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            showNotification(errorData.message || 'Erreur lors du rejet', 'error');
            return;
        }

        showNotification('Feuille rejetée ✓', 'success');
        setTimeout(() => {
            window.location.reload();
        }, 1500);

    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors du rejet', 'error');
    }
}

// ==================== MODALES ====================

function openModal(modalId) {
    document.getElementById(modalId).classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

// ==================== NOTIFICATIONS ====================

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 14px 18px;
        border-radius: 4px;
        font-size: 13px;
        z-index: 9999;
        animation: slideIn 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    `;

    const colors = {
        'success': { bg: '#d4edda', color: '#155724', border: '#c3e6cb' },
        'error': { bg: '#f8d7da', color: '#721c24', border: '#f5c6cb' },
        'info': { bg: '#d1ecf1', color: '#0c5460', border: '#bee5eb' }
    };

    const style = colors[type] || colors['info'];
    notification.style.background = style.bg;
    notification.style.color = style.color;
    notification.style.border = `1px solid ${style.border}`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => notification.remove(), 3000);
}

if (!document.getElementById('admin-timesheet-anim')) {
    const style = document.createElement('style');
    style.id = 'admin-timesheet-anim';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
}

// ==================== EXPOSITION GLOBALE ====================
window.prepareApproveDetail = prepareApproveDetail;
window.prepareRejectDetail = prepareRejectDetail;
window.confirmApproveDetail = confirmApproveDetail;
window.confirmRejectDetail = confirmRejectDetail;
window.openModal = openModal;
window.closeModal = closeModal;