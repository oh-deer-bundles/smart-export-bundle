import { Controller } from "https://unpkg.com/@hotwired/stimulus/dist/stimulus.js"

// Must match Odb\SmartExportBundle\Services\SmartExportFilterOperators::needsSecondValue().
const RANGE_OPERATORS = ['between']

const COOKIE_MAX_AGE_DAYS = 30

/**
 * Manages the "Filtres" section of the demo export popup: shows/hides each
 * row's second value field depending on the chosen operator, and persists the
 * submitted filter values in a per-export cookie so reopening the popup later
 * prefills the same filters (falling back to each column's admin-configured
 * default value when the cookie has nothing for it).
 */
export default class extends Controller {
    static targets = ['row', 'operator', 'value', 'value2']
    static values = { uuid: String }

    connect() {
        this.loadFromCookie()
        this.rowTargets.forEach(row => this.updateValue2Visibility(row))
    }

    toggleValue2(event) {
        const row = event.currentTarget.closest('[data-filter-target~="row"]')
        if (row) {
            this.updateValue2Visibility(row)
        }
    }

    persist() {
        const state = {}
        this.rowTargets.forEach(row => {
            const operator = this.fieldIn(row, 'operator')?.value
            if (!operator) {
                return
            }
            state[row.dataset.filterColumn] = {
                operator,
                value: this.readFieldValue(this.fieldIn(row, 'value')),
                value2: this.readFieldValue(this.fieldIn(row, 'value2')),
            }
        })

        const expires = new Date(Date.now() + COOKIE_MAX_AGE_DAYS * 24 * 60 * 60 * 1000).toUTCString()
        document.cookie = `smart_export_filter_${this.uuidValue}=${encodeURIComponent(JSON.stringify(state))}; expires=${expires}; path=/; SameSite=Lax`
    }

    loadFromCookie() {
        const cookieName = `smart_export_filter_${this.uuidValue}=`
        const cookie = document.cookie.split('; ').find(row => row.startsWith(cookieName))
        if (!cookie) {
            return
        }

        let state
        try {
            state = JSON.parse(decodeURIComponent(cookie.substring(cookieName.length)))
        } catch {
            return
        }

        this.rowTargets.forEach(row => {
            const saved = state[row.dataset.filterColumn]
            if (!saved) {
                return
            }
            this.writeFieldValue(this.fieldIn(row, 'operator'), saved.operator)
            this.writeFieldValue(this.fieldIn(row, 'value'), saved.value)
            this.writeFieldValue(this.fieldIn(row, 'value2'), saved.value2)
        })
    }

    updateValue2Visibility(row) {
        const operatorField = this.fieldIn(row, 'operator')
        const value2Field = this.fieldIn(row, 'value2')
        if (!operatorField || !value2Field) {
            return
        }
        // Inline style, not the `hidden` attribute/class: the form theme's "block" utility
        // class on this field has equal CSS specificity and can win the cascade, silently
        // keeping the field visible — an inline style always takes precedence over either.
        value2Field.style.display = RANGE_OPERATORS.includes(operatorField.value) ? '' : 'none'
    }

    fieldIn(row, targetName) {
        return row.querySelector(`[data-filter-target~="${targetName}"]`)
    }

    // A <select multiple> (the "value" field of a select-widget filter)'s own .value
    // getter/setter only ever sees the FIRST selected option — reading/writing it
    // directly would silently drop every other selected value. A boolean "value" field
    // is a two-way radio choice rendered by Symfony as a <div> wrapping the two <input
    // type=radio> (see SmartExportFilterType::booleanValueOptions()), not a single
    // <select>/<input> — it needs its own read/write path.
    readFieldValue(field) {
        if (!field) {
            return ''
        }
        if (this.isRadioGroup(field)) {
            return field.querySelector('input[type="radio"]:checked')?.value ?? ''
        }
        return field.multiple ? Array.from(field.selectedOptions).map(option => option.value) : field.value
    }

    writeFieldValue(field, value) {
        if (!field) {
            return
        }

        if (this.isRadioGroup(field)) {
            const radios = Array.from(field.querySelectorAll('input[type="radio"]'))
            // Same staleness guard as the <select> case below: only apply a saved value
            // that still matches one of the two radios, otherwise leave neither checked
            // (which is exactly the "no filter" state).
            if (value && radios.some(radio => radio.value === value)) {
                radios.forEach(radio => { radio.checked = radio.value === value })
            }
            return
        }

        if (field.multiple) {
            const values = Array.isArray(value) ? value : (value ? [value] : [])
            Array.from(field.options).forEach(option => { option.selected = values.includes(option.value) })
            return
        }

        // A <select> field's admin-configured operators/choices can change after this
        // cookie was written (e.g. the column's filter widget or interpreter was edited).
        // Assigning a value that no longer matches any <option> leaves the field with
        // nothing selected at all — an empty-looking dropdown, not the intended "no
        // filter" placeholder — so only restore it when the value is still valid.
        if (field.options && !Array.from(field.options).some(option => option.value === value)) {
            return
        }

        field.value = value ?? ''
    }

    isRadioGroup(field) {
        return field.tagName === 'DIV' && null !== field.querySelector('input[type="radio"]')
    }
}
