/**
 * Formulaire admin « Créer une demande de congé » (LeaveRequestCrudController).
 * Affiche le solde CP / RTT de l'employé choisi dans le champ « Demandeur ».
 */

document.addEventListener('DOMContentLoaded', () => {
    const userField = document.querySelector('#LeaveRequest_requestedBy');
    const balance = document.querySelector('#leave-balance');

    if (!userField || !balance) {
        return;
    }

    async function loadBalance(userId) {
        if (!userId) {
            balance.textContent = 'CP: -- | RTT: --';
            return;
        }

        try {
            const response = await fetch(`/admin/leave/balance/${encodeURIComponent(userId)}`);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            balance.textContent = `CP: ${data.cp} | RTT: ${data.rtt}`;
        } catch (e) {
            balance.textContent = 'Erreur chargement solde';
            console.error('Erreur chargement du solde de congés', e);
        }
    }

    userField.addEventListener('change', (e) => loadBalance(e.target.value));

    if (userField.value) {
        loadBalance(userField.value);
    }
});
