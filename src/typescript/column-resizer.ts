/**
 * Drag-to-resize column widths for a <colgroup>-rendered table (GridView's
 * columnGrouping(true) + the shared 'resizable-grid' CSS class — see
 * components.css). Widths persist per browser (localStorage) so a resize
 * survives reloads and HTMX's partial table refreshes on filter/sort/page
 * changes.
 *
 * On first attach() the table is still in the browser's default auto layout,
 * so every header's current rendered width is read and written onto its
 * matching <col> before the table is flipped to table-layout:fixed — all
 * synchronously, so there's no visible flash of equal-width columns.
 *
 * Shared by every index/guest grid that opts in: inv/index, inv/guest,
 * quote/index, quote/guest, salesorder/index, salesorder/guest.
 */
export class ColumnResizer {
    private readonly storageKeyPrefix: string;
    private dragging: { col: HTMLTableColElement; startX: number; startWidth: number; index: number } | null = null;

    constructor(private readonly tableId: string) {
        this.storageKeyPrefix = `col-width:${tableId}:`;
    }

    attach(): void {
        const parts = this.findParts();
        if (parts === null) return;
        const { table, cols, headerCells } = parts;

        headerCells.forEach((th, index) => {
            const col = cols[index];
            if (col === undefined) return;
            this.applyWidth(col, th, index);
            this.addHandle(th, col, index);
        });

        table.style.tableLayout = 'fixed';
    }

    /**
     * Recomputes every column's width from its data — the widest cell across
     * every *body* row — and persists the result, overwriting any previously
     * saved/dragged widths. "Auto-fit column widths" toolbar button.
     *
     * Deliberately ignores the header label's own width: a column like
     * "Street Address (cont'd)" has a long header but often short/blank
     * data, and fitting to the header would leave it no narrower than
     * whatever attach() already measured on load (headers don't shrink).
     * The header is left to wrap instead — see the white-space:normal rule
     * on .resizable-grid thead th — rather than dictating the column's width.
     */
    autoFit(): void {
        const parts = this.findParts();
        if (parts === null) return;
        const { table, cols, headerCells } = parts;

        // table-layout:auto alone isn't enough: Bootstrap's .table class sets
        // width:100%, so even in auto layout the browser distributes all of
        // that width across columns rather than shrinking to their true
        // minimum content width. Overriding the table's own width to
        // max-content too lets it (and therefore every column) shrink-wrap
        // to content, the way a real "auto-fit" needs to.
        const previousWidth = table.style.width;
        table.style.tableLayout = 'auto';
        table.style.width = 'max-content';
        cols.forEach((col) => { col.style.width = ''; });

        const bodyRows = Array.from(table.querySelectorAll('tbody tr'));

        headerCells.forEach((_, index) => {
            const col = cols[index];
            if (col === undefined) return;

            let width = 0;
            bodyRows.forEach((row) => {
                const cell = row.children[index] as HTMLElement | undefined;
                if (cell !== undefined) {
                    width = Math.max(width, Math.round(cell.getBoundingClientRect().width));
                }
            });

            if (width > 0) {
                col.style.width = `${width}px`;
                globalThis.localStorage.setItem(this.storageKeyPrefix + String(index), String(width));
            }
        });

        table.style.width = previousWidth;
        table.style.tableLayout = 'fixed';
    }

    /**
     * Clears every column's saved/dragged width and returns the table to the
     * browser's own free-flowing auto layout — the only way back once
     * anything (a drag, or autoFit()) has pinned widths in localStorage.
     * "Reset column widths" toolbar button.
     */
    reset(): void {
        const parts = this.findParts();
        if (parts === null) return;
        const { table, cols } = parts;

        cols.forEach((col, index) => {
            col.style.width = '';
            globalThis.localStorage.removeItem(this.storageKeyPrefix + String(index));
        });
        table.style.tableLayout = 'auto';
    }

    private findParts(): {
        table: HTMLTableElement;
        cols: HTMLTableColElement[];
        headerCells: HTMLTableCellElement[];
    } | null {
        const table = document.getElementById(this.tableId) as HTMLTableElement | null;
        const colgroup = table?.querySelector('colgroup') ?? null;
        const headerRow = table?.querySelector('thead tr:first-child') ?? null;
        if (table === null || colgroup === null || headerRow === null) return null;

        const cols = Array.from(colgroup.querySelectorAll('col'));
        const headerCells = Array.from(headerRow.querySelectorAll('th'));
        if (cols.length === 0 || cols.length !== headerCells.length) return null;

        return { table, cols, headerCells };
    }

    private applyWidth(col: HTMLTableColElement, th: HTMLTableCellElement, index: number): void {
        const saved = globalThis.localStorage.getItem(this.storageKeyPrefix + String(index));
        const width = saved !== null ? Number.parseInt(saved, 10) : Math.round(th.getBoundingClientRect().width);
        if (width > 0) {
            col.style.width = `${width}px`;
        }
    }

    private addHandle(th: HTMLTableCellElement, col: HTMLTableColElement, index: number): void {
        // Idempotent: attach() re-runs after every HTMX table refresh, and a
        // swap that doesn't touch this specific table would otherwise double up.
        if (th.querySelector('.col-resize-handle') !== null) return;

        const handle = document.createElement('span');
        handle.className = 'col-resize-handle';
        handle.addEventListener('mousedown', (e: MouseEvent) => { this.startDrag(e, col, index); });
        th.appendChild(handle);
    }

    private startDrag(e: MouseEvent, col: HTMLTableColElement, index: number): void {
        e.preventDefault();
        // <col> elements have no rendered box in any browser, so
        // getBoundingClientRect() on one is unreliable — read back the width
        // this class already wrote to its inline style instead.
        const startWidth = Number.parseInt(col.style.width, 10) || 0;
        this.dragging = { col, startX: e.clientX, startWidth, index };
        document.body.classList.add('col-resizing');
        document.addEventListener('mousemove', this.onDrag);
        document.addEventListener('mouseup', this.stopDrag);
    }

    private readonly onDrag = (e: MouseEvent): void => {
        if (this.dragging === null) return;
        const delta = e.clientX - this.dragging.startX;
        const newWidth = Math.max(40, Math.round(this.dragging.startWidth + delta));
        this.dragging.col.style.width = `${newWidth}px`;
    };

    private readonly stopDrag = (): void => {
        if (this.dragging !== null) {
            const finalWidth = Number.parseInt(this.dragging.col.style.width, 10);
            if (finalWidth > 0) {
                globalThis.localStorage.setItem(
                    this.storageKeyPrefix + String(this.dragging.index), String(finalWidth),
                );
            }
        }
        this.dragging = null;
        document.body.classList.remove('col-resizing');
        document.removeEventListener('mousemove', this.onDrag);
        document.removeEventListener('mouseup', this.stopDrag);
    };
}

export interface ColumnResizerButtonIds {
    autoFitButtonId?: string;
    resetButtonId?: string;
}

/**
 * Wires a ColumnResizer up to a table: attaches it immediately, re-attaches
 * after every HTMX partial swap (idempotent, so safe for unrelated swaps
 * too), and hooks up the shared 📐 auto-fit / 🔄 reset toolbar buttons if
 * present on the page. Returns the instance in case a caller needs it
 * (inv-index.ts currently doesn't, but keeps the door open).
 */
export function initColumnResizer(tableId: string, buttonIds: ColumnResizerButtonIds = {}): ColumnResizer {
    const { autoFitButtonId = 'btn-autofit-columns', resetButtonId = 'btn-reset-column-widths' } = buttonIds;

    const columnResizer = new ColumnResizer(tableId);
    columnResizer.attach();
    // HTMX replaces the table wholesale on filter/sort/page changes (hx-boost
    // wiring on the various *ListWidget classes) — re-attach afterward so the
    // fresh table's headers get their handles and saved widths back.
    document.body.addEventListener('htmx:afterSwap', () => { columnResizer.attach(); });

    document.getElementById(autoFitButtonId)?.addEventListener('click', () => {
        columnResizer.autoFit();
    });
    document.getElementById(resetButtonId)?.addEventListener('click', () => {
        columnResizer.reset();
    });

    return columnResizer;
}
