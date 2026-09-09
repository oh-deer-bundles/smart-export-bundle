import { Controller } from "../vendor/stimulus.js"

/**
 * "Colonnes" panel: a chip per exportable column (replaces the previous
 * dual-listbox for this popup — see dual_list_controller.js, kept unchanged
 * for anything still using the old markup). Keeps the hidden `fields` input
 * in sync as a JSON array of column ids, in the exact shape
 * SmartExportChoice::parseChoices() already expects — no PHP-side change
 * needed. Initial aria-pressed state comes straight from the server
 * (SmartExportColumn::selectedByDefault), connect() just reads it back.
 */
export default class extends Controller {
    static targets = ['chips', 'input', 'count', 'stepTag']
    static values = { selectedSuffix: String, noneSelected: String }

    connect() {
        this.updateState()
    }

    toggle(event) {
        const chip = event.currentTarget
        chip.setAttribute('aria-pressed', chip.getAttribute('aria-pressed') === 'true' ? 'false' : 'true')
        this.updateState()
    }

    selectAll() {
        this.chipTargets.forEach(chip => chip.setAttribute('aria-pressed', 'true'))
        this.updateState()
    }

    selectNone() {
        this.chipTargets.forEach(chip => chip.setAttribute('aria-pressed', 'false'))
        this.updateState()
    }

    get chipTargets() {
        return Array.from(this.chipsTarget.querySelectorAll('.col-chip'))
    }

    updateState() {
        const selected = this.chipTargets.filter(chip => chip.getAttribute('aria-pressed') === 'true')
        const total = this.chipTargets.length

        this.inputTarget.value = JSON.stringify(selected.map(chip => parseInt(chip.dataset.value, 10)))

        if (this.hasCountTarget) {
            this.countTarget.textContent = `${selected.length} / ${total} ${this.selectedSuffixValue}`
        }
        if (this.hasStepTagTarget) {
            this.stepTagTarget.textContent = selected.length === 0
                ? this.noneSelectedValue
                : `${selected.length} ${this.selectedSuffixValue}`
        }

        const step = this.element.querySelector('.step[data-target="sec-colonnes"]')
        if (step) {
            step.dataset.done = selected.length === 0 ? 'warn' : 'true'
        }
    }
}
