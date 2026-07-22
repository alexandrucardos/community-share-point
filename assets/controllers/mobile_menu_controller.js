import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button'];

    toggle() {
        const isOpen = this.element.classList.toggle('app-nav--open');
        this.buttonTarget.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
}
