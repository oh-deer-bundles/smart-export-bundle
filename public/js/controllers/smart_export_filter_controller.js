import { Controller } from "../vendor/stimulus.js"

// Must match Odb\SmartExportBundle\Services\SmartExportFilterOperators::needsSecondValue().
const RANGE_OPERATORS = ['between']

const COOKIE_MAX_AGE_DAYS = 30

/**
 * "Filtres" panel of the popup. Same second-value reveal + per-export cookie
 * persistence as the legacy filter_controller.js (identifier "filter", left
 * untouched for whatever still renders the old demo.html.twig), ported here
 * under the "smart-export-filter" identifier, plus two behaviors the old
 * popup didn't have: the Mosaïque/Liste layout toggle, and turning a
 * multi-select's real <select multiple> (visually hidden, .se-native-multiselect)
 * into a chip UI — the select stays the single source of truth throughout,
 * chips are only ever a view over its .selectedOptions.
 */
export default class extends Controller {
    static targets = ['row', 'operator', 'value', 'value2', 'grid', 'chipbox']
    static values = { uuid: String }

    connect() {
        this.loadFromCookie()
        this.rowTargets.forEach(row => this.updateValue2Visibility(row))
        this.chipboxTargets.forEach(chipbox => this.initChipbox(chipbox))
    }

    toggleValue2(event) {
        const row = event.currentTarget.closest('[data-smart-export-filter-target~="row"]')
        if (row) {
            this.updateValue2Visibility(row)
        }
    }

    setLayout(event) {
        const layout = event.currentTarget.dataset.layout
        this.element.querySelectorAll('.seg button').forEach(button => {
            button.setAttribute('aria-pressed', button === event.currentTarget ? 'true' : 'false')
        })
        this.gridTargets.forEach(grid => {
            grid.classList.toggle('mode-list', layout === 'list')
            grid.classList.toggle('mode-grid', layout === 'grid')
        })
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
        value2Field.style.display = RANGE_OPERATORS.includes(operatorField.value) ? '' : 'none'
    }

    fieldIn(row, targetName) {
        return row.querySelector(`[data-smart-export-filter-target~="${targetName}"]`)
    }

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
            if (value && radios.some(radio => radio.value === value)) {
                radios.forEach(radio => { radio.checked = radio.value === value })
            }
            return
        }

        if (field.multiple) {
            const values = Array.isArray(value) ? value : (value ? [value] : [])
            Array.from(field.options).forEach(option => { option.selected = values.includes(option.value) })
            this.renderChipsFor(field)
            return
        }

        if (field.options && !Array.from(field.options).some(option => option.value === value)) {
            return
        }

        field.value = value ?? ''
    }

    isRadioGroup(field) {
        return field.tagName === 'DIV' && null !== field.querySelector('input[type="radio"]')
    }

    // ---- multi-select chips: purely a view over the real <select multiple> ----

    initChipbox(chipbox) {
        const select = chipbox.closest('.tile-body')?.querySelector('.se-native-multiselect')
        if (!select) {
            return
        }
        this.renderChipsFor(select)
    }

    renderChipsFor(select) {
        const chipbox = select.closest('.tile-body')?.querySelector('[data-smart-export-filter-target~="chipbox"]')
        if (!chipbox) {
            return
        }

        chipbox.innerHTML = ''
        Array.from(select.selectedOptions).forEach(option => {
            const chip = document.createElement('span')
            chip.className = 'chip'
            chip.textContent = option.value
            const remove = document.createElement('button')
            remove.type = 'button'
            remove.textContent = '×'
            remove.setAttribute('aria-label', option.value)
            remove.addEventListener('click', () => {
                option.selected = false
                this.renderChipsFor(select)
            })
            chip.appendChild(remove)
            chipbox.appendChild(chip)
        })

        const remaining = Array.from(select.options).filter(option => !option.selected)
        if (remaining.length > 0) {
            const add = document.createElement('select')
            const placeholder = document.createElement('option')
            placeholder.value = ''
            placeholder.textContent = '+ ' + (chipbox.dataset.addLabel ?? 'ajouter…')
            placeholder.selected = true
            add.appendChild(placeholder)
            remaining.forEach(option => {
                const clone = document.createElement('option')
                clone.value = option.value
                clone.textContent = option.value
                add.appendChild(clone)
            })
            add.addEventListener('change', () => {
                if (!add.value) return
                const target = Array.from(select.options).find(option => option.value === add.value)
                if (target) target.selected = true
                this.renderChipsFor(select)
            })
            chipbox.appendChild(add)
        }
    }
}
