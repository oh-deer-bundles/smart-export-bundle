import { Controller } from "https://unpkg.com/@hotwired/stimulus/dist/stimulus.js"

const OK_CLASSES = ['text-emerald-600', 'dark:text-emerald-400']
const BLOCKED_CLASSES = ['text-red-600', 'dark:text-red-400']

/**
 * Row-count guard for the demo export form: "Vérifier" fires an AJAX count
 * request (same form data, no page reload) and only then enables "Generate"
 * if the result is within the bundle's max_rows limit. Any later change to
 * the form (columns picked, filters, ...) invalidates the check again.
 */
export default class extends Controller {
    static targets = ['result', 'generate']
    static values = { url: String }

    connect() {
        this.invalidate()
    }

    invalidate() {
        this.generateTarget.disabled = true
        this.resultTarget.textContent = ''
        this.resultTarget.classList.remove(...OK_CLASSES, ...BLOCKED_CLASSES)
    }

    async check() {
        const form = this.element.querySelector('form')
        const response = await fetch(this.urlValue, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        const data = await response.json()

        this.generateTarget.disabled = !data.allowed
        this.resultTarget.classList.remove(...OK_CLASSES, ...BLOCKED_CLASSES)
        this.resultTarget.classList.add(...(data.allowed ? OK_CLASSES : BLOCKED_CLASSES))
        this.resultTarget.textContent = data.allowed
            ? `${data.count} ligne(s) — export possible.`
            : `${data.count} ligne(s), la limite est de ${data.maxRows}. Réduisez la sélection.`
    }
}
