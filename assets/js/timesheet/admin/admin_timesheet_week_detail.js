import { escapeHtml } from '../shared/utils.js';
import { showNotification, reloadWithFlash, showPendingFlash, openModal, closeModal, initModals } from '../shared/ui.js';

/**
 * Admin Timesheet - Page détail (/admin/timesheet/week/{id})
 * Gestion des modales d'approbation / rejet uniquement.
 */

const state = {
    selectedTimesheetId: null
};

document.addEventListener('DOMContentLoaded', () => {
    initEventListeners();
    showPendingFlash();
});

function initEventListeners() {
    document.getElementById('approveBtnDetail')?.addEventListener('click', (e) => {
        const { timesheetId, userName, startDate, totalHours } = e.currentTarget.dataset;
        prepareApproveDetail(Number(timesheetId), userName, startDate, totalHours);
    });

    document.getElementById('rejectBtnDetail')?.addEventListener('click', (e) => {
        prepareRejectDetail(Number(e.currentTarget.dataset.timesheetId));
    });

    document.getElementById('confirmApproveBtnDetail')?.addEventListener('click', confirmApproveDetail);
    document.getElementById('confirmRejectBtnDetail')?.addEventListener('click', confirmRejectDetail);

    // Modales : Annuler, clic sur le fond, touche Échap
    initModals();
}

function prepareApproveDetail(timesheetId, userName, startDate, totalHours) {
    const summary = document.getElementById('approveSummary');
    summary.innerHTML = `
    <div class="approve-summary-box">
        <p><strong>${escapeHtml(userName)}</strong></p>
        <p>Semaine du ${escapeHtml(startDate)}</p>
        <p><strong>${escapeHtml(totalHours)}</strong> de travail</p>
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

        const response = await fetch(`/api/admin/timesheet/${state.selectedTimesheetId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            showNotification(errorData.message || 'Erreur lors de l\'approbation', 'danger');
            return;
        }

        reloadWithFlash('Feuille approuvée.', 'success');

    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de l\'approbation', 'danger');
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
            showNotification(errorData.message || 'Erreur lors du rejet', 'danger');
            return;
        }

        reloadWithFlash('Feuille rejetée.', 'danger');

    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors du rejet', 'danger');
    }
}
