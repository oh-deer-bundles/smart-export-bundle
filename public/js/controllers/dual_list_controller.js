import { Controller } from "https://unpkg.com/@hotwired/stimulus/dist/stimulus.js"

const SELECTED_CLASSES = ['bg-indigo-100', 'dark:bg-indigo-900/40', 'text-indigo-900', 'dark:text-indigo-200']

/**
 * Two-list picker (available / chosen) with drag & drop and button-based
 * moves. Writes the ordered list of chosen item ids as a JSON array into
 * the hidden input target, matching Odb\SmartExportBundle\Form\SmartExportType's
 * "fields" HiddenType field.
 */
export default class extends Controller {
    static targets = ['available', 'chosen', 'item', 'input', 'chooseAll', 'chooseSelected', 'resetAll', 'resetSelected']

    connect() {
        this.dragged = null
        this.updateState()
    }

    toggleSelect(event) {
        const isSelected = event.currentTarget.classList.toggle('selected')
        event.currentTarget.classList[isSelected ? 'add' : 'remove'](...SELECTED_CLASSES)
        this.updateState()
    }

    dragStart(event) {
        this.dragged = event.currentTarget
        event.dataTransfer.setData('text/plain', '')
    }

    allowDrop(event) {
        if (this.dragged) {
            event.preventDefault()
        }
    }

    dropOnAvailable(event) {
        event.preventDefault()
        this.moveItem(this.dragged, this.availableTarget)
        this.dragged = null
        this.updateState()
    }

    dropOnChosen(event) {
        event.preventDefault()
        this.moveItem(this.dragged, this.chosenTarget)
        this.dragged = null
        this.updateState()
    }

    chooseAll() {
        this.itemsIn(this.availableTarget).forEach(item => this.moveItem(item, this.chosenTarget))
        this.updateState()
    }

    chooseSelected() {
        this.selectedItemsIn(this.availableTarget).forEach(item => this.moveItem(item, this.chosenTarget))
        this.updateState()
    }

    resetAll() {
        this.itemsIn(this.chosenTarget).forEach(item => this.moveItem(item, this.availableTarget))
        this.updateState()
    }

    resetSelected() {
        this.selectedItemsIn(this.chosenTarget).forEach(item => this.moveItem(item, this.availableTarget))
        this.updateState()
    }

    itemsIn(list) {
        return Array.from(list.querySelectorAll('[data-dual-list-target~="item"]'))
    }

    selectedItemsIn(list) {
        return this.itemsIn(list).filter(item => item.classList.contains('selected'))
    }

    moveItem(item, target) {
        if (!item) {
            return
        }
        item.classList.remove('selected', ...SELECTED_CLASSES)
        target.appendChild(item)
    }

    updateState() {
        const availableCount = this.itemsIn(this.availableTarget).length
        const availableSelected = this.selectedItemsIn(this.availableTarget).length
        const chosenItems = this.itemsIn(this.chosenTarget)
        const chosenSelected = this.selectedItemsIn(this.chosenTarget).length

        if (this.hasChooseAllTarget) this.chooseAllTarget.disabled = availableCount === 0
        if (this.hasChooseSelectedTarget) this.chooseSelectedTarget.disabled = availableSelected === 0
        if (this.hasResetAllTarget) this.resetAllTarget.disabled = chosenItems.length === 0
        if (this.hasResetSelectedTarget) this.resetSelectedTarget.disabled = chosenSelected === 0

        const values = chosenItems.map(item => parseInt(item.dataset.value, 10))
        this.inputTarget.value = values.length > 0 ? JSON.stringify(values) : ''
    }
}
