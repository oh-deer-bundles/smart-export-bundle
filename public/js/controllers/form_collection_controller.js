import { Controller } from "https://unpkg.com/@hotwired/stimulus/dist/stimulus.js"

export default class extends Controller {
    static targets = ['fields', 'field', 'addButton']
    static values = {
        prototype: String,
        maxItems: Number,
        itemsCount: Number,
        autocomplete: Boolean,
        autocompletePrototypeId: String,
        autocompletePrototypeName: String,
        addByTop: Boolean
    }

    index = 0

    connect() {
        if (this.itemsCountValue) {
            this.index = this.itemsCountValue
        } else {
            this.index = this.fieldTargets.length
        }

        this.index++
        if (this.autocompleteValue) {
            this.element.addEventListener("autocomplete.change", this.autocomplete.bind(this))
        }
    }

    autocomplete(event) {
        this.addItem(event)
    }

    addItem(event) {
        event.preventDefault()
        const prototype = JSON.parse(this.prototypeValue)
        const newField = prototype.replace(/__name__/g, this.index)
        if (this.addByTopValue) {
            this.fieldsTarget.insertAdjacentHTML('afterbegin', newField)
        } else {
            this.fieldsTarget.insertAdjacentHTML('beforeend', newField)
        }

        if (this.autocompleteValue) {
            const autocompletePrototypeId = this.autocompletePrototypeIdValue.replace(/__name__/g, this.index)
            const autocompletePrototypeName = this.autocompletePrototypeNameValue.replace(/__name__/g, this.index)
            document.getElementById(autocompletePrototypeId).value = event.detail.value
            document.getElementById(autocompletePrototypeName).value = event.detail.textValue
        }
        this.index++
        this.fieldsTarget.dispatchEvent(new CustomEvent("form-collection.add", {
            bubbles: true,
            detail: { itemCount: this.index, element: newField }
        }))
        this.reIndexPositions()
    }

    removeItem(event) {
        event.preventDefault()
        this.fieldTargets.forEach(element => {
            if (element.contains(event.target)) {
                element.remove()
                this.index--
                this.fieldsTarget.dispatchEvent(new CustomEvent("form-collection.remove", {
                    bubbles: true,
                    detail: { itemCount: this.index }
                }))
            }
        })
        this.reIndexPositions()
    }

    itemsCountValueChanged() {
        if (false === this.hasAddButtonTarget || 0 === this.maxItemsValue) {
            return
        }
        const maxItemsReached = this.index >= this.maxItemsValue
        this.addButtonTarget.classList.toggle('hidden', maxItemsReached)
    }

    rowUp(event) {
        const item = event.currentTarget.closest('tr.item')
        const previous = item.previousElementSibling
        if (previous) {
            this.fieldsTarget.insertBefore(item, previous)
            this.reIndexPositions()
        }
    }

    rowDown(event) {
        const item = event.currentTarget.closest('tr.item')
        const next = item.nextElementSibling
        if (next) {
            this.fieldsTarget.insertBefore(item, next.nextElementSibling)
            this.reIndexPositions()
        }
    }

    reIndexPositions() {
        for (const [key, item] of Object.entries(this.fieldsTarget.children)) {
            const inputPosition = item.querySelector('input.input_position')
            if (inputPosition) {
                inputPosition.value = key
            }
        }
    }
}
