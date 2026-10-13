import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your abstracts.html.twig.
 */
// import 'bootstrap/dist/css/bootstrap.min.css';
import './styles/app.css';

console.log('🚀 app.js loaded (AssetMapper OK)');

document.addEventListener('DOMContentLoaded', () => {

    // =========================================================
    //  LEAVE REQUEST
    // =========================================================
    const userField = document.querySelector('#LeaveRequest_requestedBy');
    const balance = document.querySelector('#leave-balance');

    if (userField && balance) {

        async function loadBalance(userId) {
            if (!userId) {
                balance.textContent = 'CP: -- | RTT: --';
                return;
            }

            try {
                const response = await fetch(`/admin/leave/balance/${userId}`);
                const data = await response.json();

                balance.textContent = `CP: ${data.cp} | RTT: ${data.rtt}`;
            } catch (e) {
                balance.textContent = 'Erreur chargement solde';
                console.error('balance error', e);
            }
        }

        userField.addEventListener('change', (e) => {
            loadBalance(e.target.value);
        });

        if (userField.value) {
            loadBalance(userField.value);
        }
    }
});