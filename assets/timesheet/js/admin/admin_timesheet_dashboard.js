const state = {
    timesheets: [],
    currentPage: 1,
    itemsPerPage: 10,
    filters: {
        dateFrom: null,
        dateTo: null,
        userId: null,
        status: null,
        clients: null
    },
    selectedTimesheetId: null,
    selectedAction: null
};

// ==================== INITIALISATION ====================

document.addEventListener('DOMContentLoaded', () => {
    initEventListeners();
    loadTimesheets();
});

function initEventListeners() {
    // Filtres
    document.getElementById('filterBtn')?.addEventListener('click', applyFilters);
    document.getElementById('resetBtn')?.addEventListener('click', resetFilters);

    // Pagination
    document.getElementById('prevPage')?.addEventListener('click', () => changePage(-1));
    document.getElementById('nextPage')?.addEventListener('click', () => changePage(1));

    // Modales
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

    // Actions modales
    document.getElementById('confirmApproveBtn')?.addEventListener('click', confirmApprove);
    document.getElementById('confirmRejectBtn')?.addEventListener('click', confirmReject);

    // Export
    document.getElementById('exportBtn')?.addEventListener('click', (e) => {
        e.preventDefault();

        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        const userId = document.getElementById('filterUser').value;
        const status = document.getElementById('filterStatus').value;
        const clientId = document.getElementById('filterClient').value;

        const params = new URLSearchParams();
        if (dateFrom) params.append('dateFrom', dateFrom);
        if (dateTo) params.append('dateTo', dateTo);
        if (userId) params.append('userId', userId);
        if (status) params.append('status', status);
        if (clientId) params.append('clientId', clientId);

        window.location.href = `/api/admin/timesheet/export?${params}`;
    });
}

// ==================== CHARGEMENT DES DONNÉES ====================

async function loadTimesheets() {
    try {
        const params = new URLSearchParams();
        params.append('page', state.currentPage);
        params.append('limit', state.itemsPerPage);

        if (state.filters.dateFrom) params.append('dateFrom', state.filters.dateFrom);
        if (state.filters.dateTo) params.append('dateTo', state.filters.dateTo);
        if (state.filters.userId) params.append('userId', state.filters.userId);
        if (state.filters.status) params.append('status', state.filters.status);
        if (state.filters.clients) params.append('client', state.filters.clients);

        const url = `/api/admin/timesheet/list?${params}`;
        console.log('Requête:', url);

        const response = await fetch(url);

        // Vérifier le status HTTP d'abord
        if (!response.ok) {
            const contentType = response.headers.get('content-type') || '';

            // Si c'est du HTML (erreur Symfony), afficher un message utile
            if (contentType.includes('text/html')) {
                console.error(`Erreur serveur ${response.status} (HTML réçu)`);
                showNotification(`Erreur ${response.status}. Vérifiez les logs serveur.`, 'error');

                // Afficher le HTML d'erreur en console pour debug
                const html = await response.text();
                console.error('Réponse HTML:', html.substring(0, 500));
                return;
            }

            // Sinon c'est du JSON
            try {
                const errorData = await response.json();
                showNotification(errorData.message || `Erreur ${response.status}`, 'error');
            } catch {
                showNotification(`Erreur ${response.status}`, 'error');
            }
            return;
        }

        //  Parser le JSON
        const data = await response.json();
        console.log('Réponse reçue:', data);

        // Vérifier que les données existent
        if (!data || !Array.isArray(data.timesheets)) {
            console.error('Réponse invalide, timesheets attendu:', data);
            showNotification('Réponse serveur invalide', 'error');
            return;
        }

        state.timesheets = data.timesheets;
        renderTable();
        updatePagination(data.total);

    } catch (error) {
        console.error('Erreur lors du chargement:', error);
        showNotification('Erreur: ' + error.message, 'error');
    }
}

const statusBadgeMap = {
    'brouillon': 'badge-draft',
    'soumis': 'badge-submitted',
    'approuvé': 'badge-approved',
    'rejeté': 'badge-rejected'
};

function renderTable() {
    const tbody = document.getElementById('timesheetsBody');

    if (state.timesheets.length === 0) {
        tbody.innerHTML = '<tr class="empty-row"><td colspan="7" class="text-center">Aucun résultat</td></tr>';
        return;
    }

    //  Calcule la date de fin pour chaque timesheet (startDate + 6 jours)
    state.timesheets.forEach(ts => {
        const start = new Date(ts.startDate);
        const end = new Date(start.getTime() + 6 * 24 * 60 * 60 * 1000);
        ts.endDate = end.toISOString().split('T')[0];
    });

    tbody.innerHTML = state.timesheets.map(ts => `
        <tr>
            <td><strong>${ts.user.firstName} ${ts.user.lastName}</strong></td>
                <td>
                    <div>${formatDate(ts.startDate)}</div>
                    au ${formatDate(ts.endDate)}
                </td>            
               <td>
                    <span class="badge ${statusBadgeMap[ts.status] || ''}">
                        ${ts.statusLabel}
                    </span>
                </td>
            <td><strong>${ts.totalHours.toFixed(2)}h</strong></td>
            <td>
                ${ts.clients.length === 1
        ? ts.clients[0]
        : ts.clients.join(', ')
    }
            </td>
            <td>${ts.submittedAt ? formatDateTime(ts.submittedAt) : '-'}</td>
            <td>
                <div class="action-buttons">
                    <a href="/admin/timesheet/week/${ts.id}" class="btn-icon-timesheet view" title="Voir le détail" aria-label="Voir le détail">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </a>
                    ${ts.status === 'soumis' ? `
                        <button class="btn-icon-timesheet approve" onclick="prepareApprove(${ts.id})" title="Valider">
                            Valider
                        </button>
                        <button class="btn-icon-timesheet reject" onclick="prepareReject(${ts.id})" title="Rejeter">
                            Rejeter
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

// ==================== ACTIONS DASHBOARD ====================

function prepareApprove(timesheetId) {
    state.selectedTimesheetId = timesheetId;
    state.selectedAction = 'approve';

    const ts = state.timesheets.find(t => t.id === timesheetId);
    const summary = document.getElementById('approveSummary');
    summary.innerHTML = `
        <div class="approve-summary-box">
            <p><strong>${ts.user.firstName} ${ts.user.lastName}</strong></p>
            <p>Semaine du ${formatDate(ts.startDate)}</p>
            <p><strong>${ts.totalHours.toFixed(2)}h</strong> de travail</p>
        </div>
    `;

    openModal('approveModal');
}

function prepareReject(timesheetId) {
    state.selectedTimesheetId = timesheetId;
    state.selectedAction = 'reject';
    document.getElementById('rejectReason').value = '';
    openModal('rejectModal');
}

async function confirmApprove() {
    try {
        closeModal('approveModal');
        showNotification('Validation en cours...', 'success');

        const response = await fetch(`/api/admin/timesheet/${state.selectedTimesheetId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            showNotification(errorData.message || 'Erreur lors de la validation', 'error');
            return;
        }

        const data = await response.json();
        showNotification('Feuille validée', 'success');
        loadTimesheets();

    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la validation', 'error');
    }
}

async function confirmReject() {
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
        showNotification('Rejet en cours...', 'danger');

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

        const data = await response.json();
        showNotification('Feuille rejetée', 'danger');
        loadTimesheets();

    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors du rejet', 'error');
    }
}

// ==================== FILTRES ====================

function applyFilters() {
    state.filters.dateFrom = document.getElementById('dateFrom').value;
    state.filters.dateTo = document.getElementById('dateTo').value;
    state.filters.userId = document.getElementById('filterUser').value;
    state.filters.status = document.getElementById('filterStatus').value;
    state.filters.clients = document.getElementById('filterClient').value;
    state.currentPage = 1;

    loadTimesheets();
}

function resetFilters() {
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    document.getElementById('filterUser').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterClient').value = '';

    state.filters = {
        dateFrom: null,
        dateTo: null,
        userId: null,
        status: null,
        clients: null
    };
    state.currentPage = 1;

    loadTimesheets();
}

// ==================== PAGINATION ====================

function changePage(direction) {
    const newPage = state.currentPage + direction;
    if (newPage >= 1) {
        state.currentPage = newPage;
        loadTimesheets();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function updatePagination(total) {
    const totalPages = Math.ceil(total / state.itemsPerPage);
    const startItem = (state.currentPage - 1) * state.itemsPerPage + 1;
    const endItem = Math.min(state.currentPage * state.itemsPerPage, total);

    document.getElementById('pageInfo').textContent =
        `${startItem} - ${endItem} de ${total}`;

    document.getElementById('prevPage').disabled = state.currentPage === 1;
    document.getElementById('nextPage').disabled = state.currentPage === totalPages;
}


// ==================== MODALES ====================

function openModal(modalId) {
    document.getElementById(modalId).classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

// ==================== UTILITAIRES ====================

function formatDate(dateString, endDateString) {
    const date = new Date(dateString);
    const formatted = new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: '2-digit'
    }).format(date);

    if (endDateString) {
        const endDate = new Date(endDateString);
        const endFormatted = new Intl.DateTimeFormat('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }).format(endDate);
        return `${formatted} - ${endFormatted}`;
    }

    return formatted;
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
}

function showNotification(message, type = 'info') {
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

// Animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);

// ==================== EXPOSITION GLOBALE ====================
window.prepareApprove = prepareApprove;
window.prepareReject = prepareReject;
window.openModal = openModal;
window.closeModal = closeModal;
window.applyFilters = applyFilters;
window.resetFilters = resetFilters;
window.changePage = changePage;