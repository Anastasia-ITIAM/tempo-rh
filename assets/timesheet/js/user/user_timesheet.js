console.log(' Script timesheet loaded!');

function getConfig() {
    return {
        csrfToken: document.getElementById('csrfToken').value,
        timesheetWeekId: document.getElementById('timesheetWeekId').value,
        weekStart: document.getElementById('weekStart').value,
        clientsData: JSON.parse(document.getElementById('clientsData').textContent),
        weekDays: JSON.parse(document.getElementById('weekDays').value)
    };
}

export function initTimesheet(config) {
    const {
        csrfToken,
        timesheetWeekId,
        weekStart,
        clientsData,
        weekDays
    } = config;

    // ---------- Navigation semaine ----------

    function addDays(dateStr, days) {
        const [year, month, day] = dateStr.split('-').map(Number);

        const date = new Date(Date.UTC(year, month - 1, day));

        date.setUTCDate(date.getUTCDate() + days);

        return date.toISOString().split('T')[0];
    }

    const prevWeekBtn = document.getElementById('prevWeekBtn');
    const nextWeekBtn = document.getElementById('nextWeekBtn');
    const todayBtn = document.getElementById('todayBtn');

    if (prevWeekBtn) {
        prevWeekBtn.onclick = () => {
            const previousMonday = addDays(weekStart, -7);

            window.location.href =
                `/timesheet/user/timesheet/${previousMonday}`;
        };
    }

    if (nextWeekBtn) {
        nextWeekBtn.onclick = () => {
            const nextMonday = addDays(weekStart, 7);

            window.location.href =
                `/timesheet/user/timesheet/${nextMonday}`;
        };
    }

    if (todayBtn) {
        todayBtn.onclick = () => {
            window.location.href = `/timesheet/user/timesheet/`;
        };
    }

    // ---------- Ajouter une ligne ----------

    document.getElementById('addRowBtn')?.addEventListener('click', addNewRow);

    document.getElementById('addFirstRowBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        addNewRow();
    });

    function addNewRow() {
        document.getElementById('emptyRow')?.remove();
        const tbody = document.getElementById('timesheetBody');
        const tr = document.createElement('tr');
        tr.className = 'timesheet-row new-row';
        tr.dataset.persisted = 'false';

        let dayCells = '';
        weekDays.forEach(date => {
            dayCells += `
                <td class="day-cell text-center">
                    <div class="day-cell-inner">
                        <input type="number" class="form-control form-control-sm hours-input"
                               step="0.5" min="0" max="24" data-date="${date}" disabled>
                        <button type="button" class="btn btn-sm comment-btn" data-date="${date}" disabled title="Commentaire">
                            <i class="fa fa-comment"></i>
                        </button>
                    </div>
                </td>`;
        });

        tr.innerHTML = `
            <td class="row-header">
                <button class="btn btn-sm btn-outline-danger rounded-circle delete-row-btn" title="Annuler cette ligne">
                    <i class="fa fa-minus"></i>
                </button>
                <select class="form-control form-control-sm d-inline-block new-client-select">
                    <option value="">Client...</option>
                    ${clientsData.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}
                </select>
                <select class="form-control form-control-sm d-inline-block new-project-select mt-1" disabled>
                    <option value="">Projet...</option>
                </select>
                <select class="form-control form-control-sm d-inline-block new-task-select mt-1" disabled>
                    <option value="">Tâche...</option>
                </select>
            </td>
            ${dayCells}
            <td class="text-center fw-bold row-total">0,00</td>
        `;

        tbody.appendChild(tr);
    }

    // ---------- Cascade client → projet → tâche ----------

    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('new-client-select')) {
            const row = e.target.closest('tr');
            const projectSelect = row.querySelector('.new-project-select');
            const taskSelect = row.querySelector('.new-task-select');
            const clientId = e.target.value;

            projectSelect.innerHTML = '<option value="">Projet...</option>';
            taskSelect.innerHTML = '<option value="">Tâche...</option>';
            taskSelect.disabled = true;
            setRowInputsEnabled(row, false);

            if (!clientId) {
                projectSelect.disabled = true;
                return;
            }

            fetch(`/api/timesheet/ajax/projects?client=${clientId}`)
                .then(r => r.json())
                .then(projects => {
                    projects.forEach(p => {
                        projectSelect.innerHTML += `<option value="${p.id}">${p.name}</option>`;
                    });
                    projectSelect.disabled = false;
                })
                .catch(err => console.error('Erreur projets:', err));
        }

        if (e.target.classList.contains('new-project-select')) {
            const row = e.target.closest('tr');
            const taskSelect = row.querySelector('.new-task-select');
            const projectId = e.target.value;

            taskSelect.innerHTML = '<option value="">Tâche...</option>';
            setRowInputsEnabled(row, false);

            if (!projectId) {
                taskSelect.disabled = true;
                return;
            }

            fetch(`/api/timesheet/ajax/tasks?project=${projectId}`)
                .then(r => r.json())
                .then(tasks => {
                    tasks.forEach(t => {
                        taskSelect.innerHTML += `<option value="${t.id}">${t.name}</option>`;
                    });
                    taskSelect.disabled = false;
                })
                .catch(err => console.error('Erreur tâches:', err));
        }

        if (e.target.classList.contains('new-task-select')) {
            const row = e.target.closest('tr');
            setRowInputsEnabled(row, !!e.target.value);
        }
    });

    function setRowInputsEnabled(row, enabled) {
        row.querySelectorAll('.hours-input, .comment-btn').forEach(el => {
            el.disabled = !enabled;
        });
    }

    // ---------- Suppression de ligne ----------

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.delete-row-btn')) return;
        const row = e.target.closest('tr');

        if (row.dataset.persisted !== 'true') {
            row.remove();
            return;
        }

        if (!confirm('Supprimer cette ligne et toutes ses heures pour la semaine ?')) return;

        fetch('/api/timesheet/entry/row', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                timesheetWeekId,
                clientId: row.dataset.clientId,
                projectId: row.dataset.projectId,
                taskId: row.dataset.taskId,
            })
        })
            .then(r => r.json().then(data => ({ status: r.status, data })))
            .then(({ status, data }) => {
                if (status >= 400 || data.error) {
                    alert('Erreur suppression : ' + (data.error || 'Erreur inconnue'));
                    return;
                }
                row.remove();
                recomputeTotals();
            })
            .catch(err => console.error('Erreur réseau:', err));
    });

    // ---------- Saisie des heures ----------

    document.addEventListener('change', (e) => {
        if (!e.target.classList.contains('hours-input')) return;
        saveHours(e.target);
    });

    function saveHours(input) {
        const row = input.closest('tr');
        const date = input.dataset.date;
        const hours = parseFloat(input.value) || 0;

        let clientId, projectId, taskId;

        if (row.dataset.persisted === 'true') {
            clientId = row.dataset.clientId;
            projectId = row.dataset.projectId;
            taskId = row.dataset.taskId;
        } else {
            clientId = row.querySelector('.new-client-select')?.value;
            projectId = row.querySelector('.new-project-select')?.value;
            taskId = row.querySelector('.new-task-select')?.value;
        }

        if (!clientId || !projectId || !taskId) {
            alert('Choisissez client, projet et tâche avant de saisir des heures.');
            input.value = '';
            return;
        }

        fetch('/api/timesheet/entry/upsert', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ timesheetWeekId, clientId, projectId, taskId, date, hours, weekStart })})
            .then(r => r.json().then(data => ({ status: r.status, data })))
            .then(({ status, data }) => {
                if (status >= 400 || data.error) {
                    alert('Erreur enregistrement : ' + (data.error || 'Erreur inconnue'));
                    return;
                }
                if (row.dataset.persisted !== 'true') {
                    location.reload();
                    return;
                }
                recomputeTotals();
            })
            .catch(err => console.error('Erreur réseau:', err));
    }

    // ---------- Modale Rejet ----------

    const rejectionBtn = document.getElementById('rejectionReasonBtn');
    const rejectionModal = document.getElementById('rejectionModalOverlay');
    const rejectionModalCloseBtn = document.getElementById('rejectionModalCloseBtn');

    if (rejectionBtn && rejectionModal) {
        function closeRejectionModal() {
            rejectionModal.style.display = 'none';
        }

        rejectionBtn.addEventListener('click', function() {
            rejectionModal.style.display = 'flex';
        });

        rejectionModalCloseBtn.addEventListener('click', closeRejectionModal);

        rejectionModal.addEventListener('click', function(e) {
            if (e.target === rejectionModal) {
                closeRejectionModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && rejectionModal.style.display === 'flex') {
                closeRejectionModal();
            }
        });
    }

    //  ---------- Modale commentaire ----------

    const commentModalOverlay = document.getElementById('commentModalOverlay');
    const commentModalTextarea = document.getElementById('commentModalTextarea');
    let currentCommentBtn = null;

    function closeCommentModal() {
        commentModalOverlay.style.display = 'none';
        currentCommentBtn = null;
    }

    // EVENT LISTENER INITIAL POUR OUVRIR LE MODAL
    // À L'EXTÉRIEUR de openCommentModal, pour qu'il soit toujours actif
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.comment-btn');
        if (!btn) return;

        const isReadOnly = btn.classList.contains('comment-readonly');

        if (!isReadOnly) {
            const row = btn.closest('tr');
            const input = row.querySelector(`.hours-input[data-date="${btn.dataset.date}"]`);
            const hours = parseFloat(input?.value) || 0;

            if (hours <= 0) {
                alert('Saisissez d\'abord des heures avant d\'ajouter un commentaire.');
                return;
            }
        }

        openCommentModal(btn);
    });

    function openCommentModal(btn) {
        currentCommentBtn = btn;
        const comment = btn.dataset.comment || '';
        const isReadOnly = btn.classList.contains('comment-readonly');

        commentModalTextarea.value = comment;
        commentModalTextarea.disabled = isReadOnly;

        document.getElementById('commentModalCancel').style.display = isReadOnly ? 'none' : 'block';
        document.getElementById('commentModalSave').style.display = isReadOnly ? 'none' : 'block';
        document.getElementById('commentModalCloseBtn').style.display = isReadOnly ? 'block' : 'none';

        commentModalOverlay.style.display = 'flex';
        if (!isReadOnly) commentModalTextarea.focus();
    }

    // Fermer le modal
    document.getElementById('commentModalClose')?.addEventListener('click', closeCommentModal);
    document.getElementById('commentModalCancel')?.addEventListener('click', closeCommentModal);
    document.getElementById('commentModalCloseBtn')?.addEventListener('click', closeCommentModal);

    commentModalOverlay?.addEventListener('click', (e) => {
        if (e.target === commentModalOverlay) closeCommentModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && commentModalOverlay?.style.display !== 'none') {
            closeCommentModal();
        }
    });

    // Sauvegarder le commentaire
    document.getElementById('commentModalSave')?.addEventListener('click', () => {
        if (!currentCommentBtn) return;

        const btn = currentCommentBtn;
        const row = btn.closest('tr');
        const input = row.querySelector(`.hours-input[data-date="${btn.dataset.date}"]`);
        const hours = parseFloat(input?.value) || 0;
        const comment = commentModalTextarea.value;

        let clientId, projectId, taskId;
        if (row.dataset.persisted === 'true') {
            clientId = row.dataset.clientId;
            projectId = row.dataset.projectId;
            taskId = row.dataset.taskId;
        } else {
            clientId = row.querySelector('.new-client-select')?.value;
            projectId = row.querySelector('.new-project-select')?.value;
            taskId = row.querySelector('.new-task-select')?.value;
        }

        fetch('/api/timesheet/entry/upsert', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                timesheetWeekId,
                clientId,
                projectId,
                taskId,
                date: btn.dataset.date,
                hours,
                comment,
                weekStart
            })
        })
            .then(r => r.json().then(data => ({status: r.status, data})))
            .then(({status, data}) => {
                if (status >= 400 || data.error) {
                    alert('Erreur : ' + (data.error || 'Erreur inconnue'));
                    return;
                }
                btn.dataset.comment = comment;
                btn.classList.toggle('has-comment', comment.trim() !== '');
                closeCommentModal();
            })
            .catch(err => console.error('Erreur réseau:', err));
    });

    // ---------- Totaux ----------

    function recomputeTotals() {
        let grandTotal = 0;
        const dailyTotals = {};
        weekDays.forEach(d => dailyTotals[d] = 0);

        document.querySelectorAll('#timesheetBody tr.timesheet-row').forEach(row => {
            let rowTotal = 0;
            row.querySelectorAll('.hours-input').forEach(input => {
                const val = parseFloat(input.value) || 0;
                rowTotal += val;
                if (dailyTotals[input.dataset.date] !== undefined) {
                    dailyTotals[input.dataset.date] += val;
                }
            });
            const totalCell = row.querySelector('.row-total');
            if (totalCell) totalCell.textContent = rowTotal.toFixed(2).replace('.', ',');
            grandTotal += rowTotal;
        });

        document.querySelectorAll('.daily-total').forEach(cell => {
            const d = cell.dataset.date;
            cell.textContent = (dailyTotals[d] || 0).toFixed(2).replace('.', ',');
        });

        const grandTotalEl = document.getElementById('grandTotal');
        if (grandTotalEl) grandTotalEl.textContent = grandTotal.toFixed(2).replace('.', ',');
    }

    // ---------- Boutons ----------

    document.getElementById('submitBtn')?.addEventListener('click', () => {

        const grandTotal = parseFloat(document.getElementById('grandTotal').textContent.replace(',', '.')) || 0;

        if (grandTotal === 0) {
            alert('Impossible de soumettre une feuille vide. Ajouter au moins une ligne avec des heures.');
            return;
        }

        if (!confirm('Êtes-vous sûr ? Une fois soumise, non modifiable.')) return;

        fetch(`/api/timesheet/timesheet/${timesheetWeekId}/submit`, {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken }
        })
            .then(r => r.json().then(data => ({ status: r.status, data })))
            .then(({ status, data }) => {
                if (status >= 400 || data.error) {
                    alert('Erreur : ' + (data.error || 'Erreur inconnue'));
                    return;
                }
                location.reload();
            })
            .catch(err => console.error('Erreur réseau:', err));
    });
}

// ========== AUTO-INIT ==========
document.addEventListener('DOMContentLoaded', () => {
    initTimesheet(getConfig());
});