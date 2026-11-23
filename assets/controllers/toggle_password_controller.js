import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    toggle() {
        const input = this.element.querySelector('input');
        const isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';

        // Change l'icône (œil / œil barré) via la classe CSS
        this.element.classList.toggle('is-visible', isHidden);

        const button = this.element.querySelector('.toggle-password-btn');
        if (button) {
            button.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        }
    }
}
