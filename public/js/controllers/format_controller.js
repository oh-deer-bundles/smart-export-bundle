import { Controller } from "../vendor/stimulus.js"

/**
 * "Format" panel: reveals the séparateur/encodage sub-form only for CSV/Texte
 * (Excel needs neither). The 3 branded buttons themselves need no JS at all —
 * their checked/unchecked look is pure CSS (.fmt-btn:has(input:checked)).
 */
export default class extends Controller {
    static targets = ['options']

    connect() {
        this.sync()
    }

    sync() {
        const checked = this.element.querySelector('.format-row input[type="radio"]:checked')
        this.optionsTarget.classList.toggle('open', checked ? checked.value !== 'xlsx' : false)
    }
}
