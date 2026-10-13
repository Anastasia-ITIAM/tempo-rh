import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    toggle() {
        const input = this.element.querySelector('input');

        input.type = input.type === 'password' ? 'text' : 'password';
    }
}