import { Controller } from '@hotwired/stimulus';

/**
 * Reveals the item-name filter field behind the magnifier button.
 *
 * The form is rendered collapsed unless a filter is applied, so the button is
 * the only visible affordance until it is clicked.
 */
export default class extends Controller {
    static targets = ['button', 'field', 'input'];

    toggle() {
        this.setOpen(this.fieldTarget.hidden);
    }

    close() {
        this.setOpen(false);
        this.buttonTarget.focus();
    }

    setOpen(isOpen) {
        this.fieldTarget.hidden = !isOpen;
        this.buttonTarget.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        if (isOpen) {
            this.inputTarget.focus();
        }
    }
}
