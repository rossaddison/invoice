import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { AllowanceChargeToggleHandler } from './allowance-charge-toggle.js';

// Template fixtures
const FIXED    = { mfn: 0,  base: 0   };
const VARIABLE = { mfn: 10, base: 500 };
const TEMPLATES = { '1': FIXED, '2': VARIABLE };

/**
 * Builds the full DOM required by AllowanceChargeToggleHandler and returns
 * typed references to each element.  templates is serialised onto the select's
 * dataset so no HTML-escaping of JSON is needed.
 */
function makeDOM(
    selectValue = '',
    templates: object = TEMPLATES,
): {
    select: HTMLSelectElement;
    baseRow: HTMLElement;
    baseInput: HTMLInputElement;
    amountInput: HTMLInputElement;
    formulaHint: HTMLElement;
} {
    document.body.innerHTML = `
        <select id="allowance_charge_id">
            <option value="">--</option>
            <option value="1">Fixed</option>
            <option value="2">Variable</option>
            <option value="3">Other</option>
        </select>
        <div id="row-base-amount"></div>
        <input id="base_amount_calc" type="number" value="0">
        <input id="amount" type="number" value="0">
        <div id="amount-formula"></div>`;

    const select = document.getElementById('allowance_charge_id') as HTMLSelectElement;
    select.dataset['acTemplates'] = JSON.stringify(templates);
    if (selectValue) select.value = selectValue;

    return {
        select,
        baseRow:    document.getElementById('row-base-amount')!,
        baseInput:  document.getElementById('base_amount_calc') as HTMLInputElement,
        amountInput: document.getElementById('amount') as HTMLInputElement,
        formulaHint: document.getElementById('amount-formula')!,
    };
}

// ─── Missing DOM ──────────────────────────────────────────────────────────────

describe('AllowanceChargeToggleHandler — missing DOM', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        Object.defineProperty(document, 'readyState', { value: 'complete', configurable: true });
    });
    afterEach(() => { document.body.innerHTML = ''; });

    it('does not throw when #allowance_charge_id is absent', () => {
        expect(() => new AllowanceChargeToggleHandler()).not.toThrow();
    });

    it('does not throw when select is present but sibling elements are absent', () => {
        document.body.innerHTML = '<select id="allowance_charge_id"></select>';
        expect(() => new AllowanceChargeToggleHandler()).not.toThrow();
    });

    it('survives malformed JSON in data-ac-templates', () => {
        document.body.innerHTML = `
            <select id="allowance_charge_id" data-ac-templates="NOT JSON"></select>
            <div id="row-base-amount"></div>
            <input id="base_amount_calc">
            <input id="amount">
            <div id="amount-formula"></div>`;
        expect(() => new AllowanceChargeToggleHandler()).not.toThrow();
    });
});

// ─── Initial applyMode (readyState complete) ──────────────────────────────────

describe('AllowanceChargeToggleHandler — initial applyMode', () => {
    beforeEach(() => {
        Object.defineProperty(document, 'readyState', { value: 'complete', configurable: true });
    });
    afterEach(() => { document.body.innerHTML = ''; });

    it('hides baseRow on init when the selected template has mfn = 0', () => {
        const { baseRow } = makeDOM('1');
        new AllowanceChargeToggleHandler();
        expect(baseRow.style.display).toBe('none');
    });

    it('clears formulaHint on init when mfn = 0', () => {
        const { formulaHint } = makeDOM('1');
        formulaHint.textContent = 'leftover text';
        new AllowanceChargeToggleHandler();
        expect(formulaHint.textContent).toBe('');
    });

    it('shows baseRow on init when the selected template has mfn > 0', () => {
        const { baseRow } = makeDOM('2');
        new AllowanceChargeToggleHandler();
        expect(baseRow.style.display).toBe('');
    });

    it('pre-fills baseInput with template.base on init', () => {
        // VARIABLE: base = 500
        const { baseInput } = makeDOM('2');
        new AllowanceChargeToggleHandler();
        expect(baseInput.value).toBe('500');
    });

    it('calculates amountInput on init — mfn=10, base=500 → 50.00', () => {
        // Math.round(10 * 500) / 100 = 50.00
        const { amountInput } = makeDOM('2');
        new AllowanceChargeToggleHandler();
        expect(amountInput.value).toBe('50.00');
    });

    it('writes the formula hint on init', () => {
        const { formulaHint } = makeDOM('2');
        new AllowanceChargeToggleHandler();
        expect(formulaHint.textContent).toBe('10 × 500 ÷ 100 = 50.00');
    });

    it('hides baseRow for an unknown select value (falls back to mfn=0)', () => {
        // value='99' not in TEMPLATES → getTemplate returns { mfn:0, base:0 }
        const { baseRow } = makeDOM('99');
        new AllowanceChargeToggleHandler();
        expect(baseRow.style.display).toBe('none');
    });
});

// ─── Select change ────────────────────────────────────────────────────────────

describe('AllowanceChargeToggleHandler — select change', () => {
    beforeEach(() => {
        Object.defineProperty(document, 'readyState', { value: 'complete', configurable: true });
    });
    afterEach(() => { document.body.innerHTML = ''; });

    it('shows baseRow when changed from fixed to variable template', () => {
        const { select, baseRow } = makeDOM('1');
        new AllowanceChargeToggleHandler();
        expect(baseRow.style.display).toBe('none');

        select.value = '2';
        select.dispatchEvent(new Event('change'));
        expect(baseRow.style.display).toBe('');
    });

    it('hides baseRow when changed from variable to fixed template', () => {
        const { select, baseRow } = makeDOM('2');
        new AllowanceChargeToggleHandler();
        expect(baseRow.style.display).toBe('');

        select.value = '1';
        select.dispatchEvent(new Event('change'));
        expect(baseRow.style.display).toBe('none');
    });

    it('resets baseInput to the new template.base on select change', () => {
        const templates = { '2': { mfn: 10, base: 500 }, '3': { mfn: 20, base: 300 } };
        const { select, baseInput } = makeDOM('2', templates);
        new AllowanceChargeToggleHandler();
        expect(baseInput.value).toBe('500');

        select.value = '3';
        select.dispatchEvent(new Event('change'));
        expect(baseInput.value).toBe('300');
    });

    it('updates amountInput and formulaHint on select change', () => {
        // Switch to variable: mfn=10, base=500 → 50.00
        const { select, amountInput, formulaHint } = makeDOM('1');
        new AllowanceChargeToggleHandler();

        select.value = '2';
        select.dispatchEvent(new Event('change'));
        expect(amountInput.value).toBe('50.00');
        expect(formulaHint.textContent).toBe('10 × 500 ÷ 100 = 50.00');
    });

    it('clears formulaHint but does not zero amountInput when switched to fixed', () => {
        // Switch to variable first to set a real amount, then back to fixed
        const { select, amountInput, formulaHint } = makeDOM('2');
        new AllowanceChargeToggleHandler();
        expect(amountInput.value).toBe('50.00');

        select.value = '1';
        select.dispatchEvent(new Event('change'));
        expect(formulaHint.textContent).toBe('');
        expect(amountInput.value).toBe('50.00'); // not cleared by applyMode
    });
});

// ─── Base input event ─────────────────────────────────────────────────────────

describe('AllowanceChargeToggleHandler — base input event', () => {
    beforeEach(() => {
        Object.defineProperty(document, 'readyState', { value: 'complete', configurable: true });
    });
    afterEach(() => { document.body.innerHTML = ''; });

    it('recalculates amountInput when user types a new base value', () => {
        // mfn=10, user types 200 → Math.round(10 * 200) / 100 = 20.00
        const { baseInput, amountInput } = makeDOM('2');
        new AllowanceChargeToggleHandler();

        baseInput.value = '200';
        baseInput.dispatchEvent(new Event('input'));
        expect(amountInput.value).toBe('20.00');
    });

    it('updates formulaHint when user types a new base value', () => {
        const { baseInput, formulaHint } = makeDOM('2');
        new AllowanceChargeToggleHandler();

        baseInput.value = '200';
        baseInput.dispatchEvent(new Event('input'));
        expect(formulaHint.textContent).toBe('10 × 200 ÷ 100 = 20.00');
    });

    it('sets the userModified flag on baseInput after typing', () => {
        const { baseInput } = makeDOM('2');
        new AllowanceChargeToggleHandler();

        baseInput.value = '999';
        baseInput.dispatchEvent(new Event('input'));
        expect(baseInput.dataset['userModified']).toBe('1');
    });

    it('treats an empty base as 0 — result is 0.00', () => {
        const { baseInput, amountInput } = makeDOM('2');
        new AllowanceChargeToggleHandler();

        baseInput.value = '';
        baseInput.dispatchEvent(new Event('input'));
        expect(amountInput.value).toBe('0.00');
    });

    it('does not update amountInput when the selected template has mfn = 0', () => {
        // Fixed template: recalculate returns early for mfn <= 0
        const { baseInput, amountInput } = makeDOM('1');
        new AllowanceChargeToggleHandler();
        const before = amountInput.value;

        baseInput.value = '999';
        baseInput.dispatchEvent(new Event('input'));
        expect(amountInput.value).toBe(before);
    });
});

// ─── DOMContentLoaded path ────────────────────────────────────────────────────

describe('AllowanceChargeToggleHandler — DOMContentLoaded path', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        Object.defineProperty(document, 'readyState', { value: 'complete', configurable: true });
    });

    it('defers init until DOMContentLoaded fires when readyState is loading', () => {
        const { baseRow } = makeDOM('1'); // fixed → should become 'none' after init
        Object.defineProperty(document, 'readyState', { value: 'loading', configurable: true });

        new AllowanceChargeToggleHandler();
        // #init has not run yet — baseRow still has no inline style
        expect(baseRow.style.display).toBe('');

        document.dispatchEvent(new Event('DOMContentLoaded'));
        // Now #init has run — fixed template hides baseRow
        expect(baseRow.style.display).toBe('none');
    });
});
