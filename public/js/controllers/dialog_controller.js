import { Controller } from "https://unpkg.com/@hotwired/stimulus/dist/stimulus.js"

/**
 * Generic controller wrapping a native <dialog>.
 * The trigger and the <dialog> share the same data-controller="dialog" ancestor;
 * the <dialog> itself carries data-dialog-target="dialog".
 */
export default class extends Controller {
    static targets = ['dialog']

    open() {
        this.dialogTarget.showModal()
    }

    close() {
        this.dialogTarget.close()
    }

    closeOnBackdrop(event) {
        if (event.target === this.dialogTarget) {
            this.close()
        }
    }
}
