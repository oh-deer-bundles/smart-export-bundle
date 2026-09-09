import { Controller } from "../vendor/stimulus.js"

/**
 * Filtres / Colonnes / Format wizard: shows exactly one .panel at a time,
 * navigated either from the header stepper (each .step button) or the footer
 * Précédent/Suivant. `order` is computed from whichever .panel elements are
 * actually in the DOM, so the "no filterable columns" case (Filtres panel
 * absent) is handled automatically, without any special-casing here.
 *
 * Reaching the Format panel dispatches "smart-export-tabs:enteredFormat",
 * which count_controller.js listens for to run the implicit row-count check —
 * there is no explicit "Vérifier" button anymore.
 */
export default class extends Controller {
    static targets = ['back', 'next', 'generate', 'hint']

    connect() {
        this.order = Array.from(this.element.querySelectorAll('.panel')).map(panel => panel.id)
        // Re-derives the footer buttons/hint from whichever panel is actually first —
        // matters once the Colonnes and/or Filtres panels can both be absent
        // (smart_export_popup()'s `detailed: false` option with no visible filters),
        // leaving Format as the sole, first panel: the server already renders it
        // visible/selected, but only switchTo() also flips Suivant/Générer and fires
        // the implicit count check, exactly as if the user had navigated there.
        this.switchTo(this.order[0])
    }

    go(event) {
        this.switchTo(event.currentTarget.dataset.target)
    }

    next() {
        const index = this.order.indexOf(this.current)
        if (index < this.order.length - 1) {
            this.switchTo(this.order[index + 1])
        }
    }

    back() {
        const index = this.order.indexOf(this.current)
        if (index > 0) {
            this.switchTo(this.order[index - 1])
        }
    }

    close() {
        this.dispatch('close', { bubbles: true })
    }

    switchTo(targetId) {
        this.current = targetId

        this.element.querySelectorAll('.step').forEach(step => {
            step.setAttribute('aria-selected', step.dataset.target === targetId ? 'true' : 'false')
        })
        this.element.querySelectorAll('.panel').forEach(panel => {
            panel.hidden = panel.id !== targetId
        })

        const index = this.order.indexOf(targetId)
        const isFormat = targetId === 'sec-format'
        if (this.hasBackTarget) this.backTarget.hidden = index === 0
        if (this.hasNextTarget) this.nextTarget.hidden = isFormat
        if (this.hasGenerateTarget) this.generateTarget.hidden = !isFormat

        if (this.hasHintTarget) {
            const step = this.element.querySelector(`.step[data-target="${targetId}"]`)
            this.hintTarget.textContent = step?.dataset.hint ?? ''
        }

        if (isFormat) {
            this.dispatch('enteredFormat', { bubbles: true })
        }
    }
}
