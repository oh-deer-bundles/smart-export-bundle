import { Controller } from "../vendor/stimulus.js"

/**
 * Lives on the trigger link printed by smart_export_popup() (see
 * templates/popup/trigger.html.twig). The modal shell (overlay + chrome) is
 * already in the page — cheap, no DB access — but its content is only
 * fetched on click, with a plain fetch() (not Turbo Drive, never guaranteed
 * present in an arbitrary host page) straight to the demoExport route. The
 * fetched HTML is injected as-is; its own data-controller attributes are
 * picked up by Stimulus automatically once they land in the DOM.
 */
export default class extends Controller {
    static targets = ['shell', 'loading', 'content']
    static values = { exportUrl: String }

    async open(event) {
        // The trigger is a real <a href="#"> (see trigger.html.twig) so it can be
        // styled/positioned like any other link — this is the only thing keeping
        // it from actually navigating.
        event.preventDefault()

        this.shellTarget.hidden = false
        this.loadingTarget.hidden = false

        const response = await fetch(this.exportUrlValue, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        this.contentTarget.innerHTML = await response.text()
        this.loadingTarget.hidden = true
    }

    close() {
        this.shellTarget.hidden = true
        // Clears out the fetched form (and disconnects its controllers) so
        // reopening always starts from a fresh fetch, never stale state.
        this.contentTarget.innerHTML = ''
        this.loadingTarget.hidden = false
    }
}
