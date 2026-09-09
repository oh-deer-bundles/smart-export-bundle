import { Controller } from "../vendor/stimulus.js"

/**
 * Row-count check for the popup's "Format" panel. Unlike the legacy
 * count_controller.js (bound to an explicit "Vérifier" button, still used by
 * whatever still renders the old demo.html.twig markup), this one is driven
 * entirely by the wizard: tabs_controller.js dispatches
 * "smart-export-tabs:enteredFormat" every time the Format panel becomes
 * active, and check() re-runs unconditionally — no caching, so a filter or
 * column changed on an earlier visit to those panels is always reflected.
 * Picking a different file format never re-triggers this: the row count
 * doesn't depend on it, and nothing here listens for that change.
 */
export default class extends Controller {
    static targets = ['card', 'generate']
    static values = { url: String }

    async check() {
        this.generateTarget.disabled = true
        this.setStepDone(false)
        this.cardTarget.className = 'verify-card'
        this.cardTarget.innerHTML = '<span class="spinner"></span><span>' + this.loadingText() + '</span>'

        const form = this.element.querySelector('form')
        let data
        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
            data = await response.json()
        } catch {
            this.cardTarget.className = 'verify-card blocked'
            this.cardTarget.textContent = this.errorText()
            return
        }

        this.generateTarget.disabled = !data.allowed
        this.setStepDone(data.allowed)

        if (data.allowed) {
            this.cardTarget.className = 'verify-card ok'
            this.cardTarget.innerHTML = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8.5l3.2 3.2L13 5"/></svg>'
                + '<span><span class="n">' + data.count + '</span> ' + this.okText() + '</span>'
        } else {
            this.cardTarget.className = 'verify-card blocked'
            this.cardTarget.textContent = data.count + ' ' + this.blockedText(data.maxRows)
        }
    }

    setStepDone(done) {
        const step = this.element.querySelector('.step[data-target="sec-format"]')
        if (step) {
            step.dataset.done = done ? 'true' : 'false'
        }
    }

    loadingText() {
        return this.cardTarget.dataset.loadingText ?? 'Calcul du nombre de lignes…'
    }

    okText() {
        return this.cardTarget.dataset.okText ?? 'lignes — export possible'
    }

    blockedText(maxRows) {
        return (this.cardTarget.dataset.blockedText ?? 'lignes, la limite est de %max%. Réduisez la sélection.').replace('%max%', maxRows)
    }

    errorText() {
        return this.cardTarget.dataset.errorText ?? 'Impossible de vérifier le nombre de lignes.'
    }
}
