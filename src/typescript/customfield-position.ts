/**
 * Populates the #location <select> options from a per-table position map when
 * #table changes. Reads its data from a JSON data-island (a <script
 * type="application/json"> — inert as far as CSP script-src is concerned)
 * instead of interpolating server data directly into an executable inline
 * <script>, so script-src no longer needs 'unsafe-inline'.
 *
 * Extracted from resources/views/invoice/customfield/_form.php.
 */

interface CustomFieldPositionConfig {
    positions: Record<string, Record<string, string>>;
    valueSelected: string | null;
}

export function initCustomFieldPosition(): void {
    const configEl = document.getElementById('customfield-position-config');
    const tableEl = document.getElementById('table') as HTMLSelectElement | null;
    const locationEl = document.getElementById('location') as HTMLSelectElement | null;
    if (!configEl || !tableEl || !locationEl) return;

    const config = JSON.parse(configEl.textContent ?? '{}') as CustomFieldPositionConfig;
    const jsonPositions = config.positions;

    const updatePositions = (index: number, selKey?: string | null): void => {
        locationEl.innerHTML = '';

        const keys = Object.keys(jsonPositions);
        if (keys.length === 0) return;

        if (typeof index !== 'number' || index < 0 || index >= keys.length) {
            index = 0;
        }

        const key = keys[index];
        const map = key !== undefined ? (jsonPositions[key] ?? {}) : {};

        for (const pos of Object.keys(map)) {
            const opt = document.createElement('option');
            opt.value = pos;
            opt.textContent = map[pos] ?? '';
            if (selKey !== undefined && selKey !== null && String(selKey) === String(pos)) {
                opt.selected = true;
            }
            locationEl.appendChild(opt);
        }
    };

    let optionIndex = tableEl.selectedIndex ?? 0;

    tableEl.addEventListener('change', () => {
        optionIndex = tableEl.selectedIndex;
        updatePositions(optionIndex);
    });

    updatePositions(optionIndex, config.valueSelected);
}
