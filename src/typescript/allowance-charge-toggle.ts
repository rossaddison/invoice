interface AcTemplate {
    readonly mfn: number;
    readonly base: number;
}

type AcTemplateMap = Record<string, AcTemplate>;

export class AllowanceChargeToggleHandler {
    constructor() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.#init());
        } else {
            this.#init();
        }
    }

    #init(): void {
        const select = document.getElementById('allowance_charge_id') as HTMLSelectElement | null;
        if (!select) return;

        let templates: AcTemplateMap;
        try {
            templates = JSON.parse(select.dataset['acTemplates'] ?? '{}') as AcTemplateMap;
        } catch {
            templates = {};
        }

        const baseRow    = document.getElementById('row-base-amount');
        const baseInput  = document.getElementById('base_amount_calc') as HTMLInputElement | null;
        const amountInput = document.getElementById('amount') as HTMLInputElement | null;
        const formulaHint = document.getElementById('amount-formula');

        if (!baseRow || !baseInput || !amountInput || !formulaHint) return;

        const getTemplate = (): AcTemplate =>
            templates[select.value] ?? { mfn: 0, base: 0 };

        const recalculate = (): void => {
            const t = getTemplate();
            if (t.mfn <= 0) return;
            const base = parseFloat(baseInput.value) || 0;
            const result = Math.round(t.mfn * base) / 100;
            amountInput.value = result.toFixed(2);
            formulaHint.textContent = `${t.mfn} × ${base} ÷ 100 = ${result.toFixed(2)}`;
        };

        const applyMode = (): void => {
            const t = getTemplate();
            const variable = t.mfn > 0;
            baseRow.style.display = variable ? '' : 'none';
            if (variable) {
                if (!baseInput.dataset['userModified']) {
                    baseInput.value = String(t.base);
                }
                recalculate();
            } else {
                formulaHint.textContent = '';
            }
        };

        select.addEventListener('change', () => {
            baseInput.dataset['userModified'] = '';
            applyMode();
        });

        baseInput.addEventListener('input', () => {
            baseInput.dataset['userModified'] = '1';
            recalculate();
        });

        applyMode();
    }
}
