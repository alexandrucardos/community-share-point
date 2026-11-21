import { Controller } from '@hotwired/stimulus';

/**
 * Shows a full-screen overlay while the browser submits the item form.
 *
 * Photo uploads can take a while, and without feedback the form looks frozen,
 * so submitting also disables the submit buttons to prevent double saves.
 */
export default class extends Controller {
    static targets = ['overlay'];

    connect() {
        window.addEventListener('pageshow', this.resetOnRestore);
    }

    disconnect() {
        window.removeEventListener('pageshow', this.resetOnRestore);
    }

    show() {
        this.overlayTarget.hidden = false;
        this.element.setAttribute('aria-busy', 'true');
        this.submitButtons().forEach((button) => {
            button.disabled = true;
        });
    }

    /* Going back from the saved page can restore this one from the bfcache. */
    resetOnRestore = (event) => {
        if (!event.persisted) {
            return;
        }

        this.overlayTarget.hidden = true;
        this.element.removeAttribute('aria-busy');
        this.submitButtons().forEach((button) => {
            button.disabled = false;
        });
    };

    submitButtons() {
        const formId = this.element.id;

        return document.querySelectorAll(
            `#${formId} button[type="submit"], button[form="${formId}"]`,
        );
    }
}
