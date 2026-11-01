/**
 * Dependency-free multi-day calendar used by inv/index's 'Copy Invoice'
 * modal (resources/views/invoice/inv/modal_copy_inv_multiple.php): click
 * days to toggle them; each selected day becomes one copy of every ticked
 * invoice. Selected days live in the container's `data-selected`
 * (comma-separated Y-m-d) so InvoiceHandler can read them without a shared
 * module-level state.
 */
const MONTH_NAMES = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];
const WEEKDAYS = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

function pad(n: number): string {
    return String(n).padStart(2, '0');
}

function toIso(year: number, month: number, day: number): string {
    return `${year}-${pad(month + 1)}-${pad(day)}`;
}

export function getSelectedDates(container: HTMLElement): string[] {
    return (container.dataset.selected ?? '').split(',').filter(d => d !== '');
}

function renderMonth(container: HTMLElement, year: number, month: number, selected: Set<string>): void {
    const firstWeekday = (new Date(year, month, 1).getDay() + 6) % 7; // Monday first
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const todayIso = toIso(new Date().getFullYear(), new Date().getMonth(), new Date().getDate());

    let html = '<div class="d-flex align-items-center justify-content-between mb-2">'
        + '<button type="button" class="btn btn-outline-secondary btn-sm" data-mdp-nav="-1" aria-label="Previous month">'
        + '<i class="bi bi-chevron-left"></i></button>'
        + `<strong>${MONTH_NAMES[month]} ${year}</strong>`
        + '<button type="button" class="btn btn-outline-secondary btn-sm" data-mdp-nav="1" aria-label="Next month">'
        + '<i class="bi bi-chevron-right"></i></button></div>'
        + '<div class="d-grid text-center" style="grid-template-columns:repeat(7,1fr);gap:2px;">';
    for (const w of WEEKDAYS) {
        html += `<small class="text-muted fw-semibold">${w}</small>`;
    }
    for (let i = 0; i < firstWeekday; i++) {
        html += '<span></span>';
    }
    for (let d = 1; d <= daysInMonth; d++) {
        const iso = toIso(year, month, d);
        const isSelected = selected.has(iso);
        let cls = 'btn btn-light btn-sm';
        if (isSelected) {
            cls = 'btn btn-primary btn-sm';
        } else if (iso === todayIso) {
            cls = 'btn btn-outline-primary btn-sm';
        }
        html += `<button type="button" class="${cls}" data-mdp-date="${iso}" aria-pressed="${isSelected}">${d}</button>`;
    }
    html += '</div>';
    container.innerHTML = html;
}

function updateSummary(container: HTMLElement): void {
    const summary = document.getElementById(container.dataset.summaryId ?? '');
    if (summary) {
        const dates = getSelectedDates(container);
        summary.textContent = dates.length === 0 ? '' : `${dates.length}: ${dates.join(', ')}`;
    }
}

export function initMultiDatePicker(containerId = 'modal_created_dates_calendar'): void {
    const container = document.getElementById(containerId);
    if (!container || container.dataset.mdpReady === '1') {
        return;
    }
    container.dataset.mdpReady = '1';
    const selected = new Set<string>();
    const now = new Date();
    let year = now.getFullYear();
    let month = now.getMonth();

    const refresh = (): void => {
        container.dataset.selected = Array.from(selected).sort((a, b) => a.localeCompare(b)).join(',');
        renderMonth(container, year, month, selected);
        updateSummary(container);
    };

    container.addEventListener('click', (event: Event) => {
        const target = (event.target as HTMLElement).closest<HTMLElement>('[data-mdp-date],[data-mdp-nav]');
        if (!target) {
            return;
        }
        if (target.dataset.mdpDate) {
            const iso = target.dataset.mdpDate;
            if (selected.has(iso)) {
                selected.delete(iso);
            } else {
                selected.add(iso);
            }
        } else {
            month += Number(target.dataset.mdpNav);
            if (month < 0) {
                month = 11;
                year--;
            } else if (month > 11) {
                month = 0;
                year++;
            }
        }
        refresh();
    });

    refresh();
}
