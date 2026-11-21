import { Controller } from '@hotwired/stimulus';

const OPEN_CLASS = 'items-filter--open';

/**
 * Reveals the item-name filter field behind the magnifier button.
 *
 * The form is rendered collapsed unless a filter is applied, so the button is
 * the only visible affordance until it is clicked. A class (instead of the
 * `hidden` attribute) drives the state so the panel can animate open and shut.
 */
export default class extends Controller {
    static targets = ['button', 'field', 'input'];

    toggle() {
        this.setOpen(!this.fieldTarget.classList.contains(OPEN_CLASS));
    }

    close() {
        this.setOpen(false);
        this.buttonTarget.focus();
    }

    setOpen(isOpen) {
        this.fieldTarget.classList.toggle(OPEN_CLASS, isOpen);
        this.buttonTarget.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        if (isOpen) {
            this.inputTarget.focus();
        }
    }
}
