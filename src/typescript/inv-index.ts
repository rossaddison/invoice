import { AmountMagnifier, initGroupBySelect, initGroupCollapsible } from './list-utils.js';

// Module-level refs keep the MutationObserver and setInterval alive after setup()
let magnifier: AmountMagnifier;
let mobilePreview: MobilePreviewToggle;

class MobilePreviewToggle {
    private isActive = false;
    private previewWin: WindowProxy | null = null;
    private readonly toggleBtn: HTMLButtonElement;
    private readonly sideTab: HTMLButtonElement;

    constructor() {
        this.injectStyles();
        this.toggleBtn = this.createButton();
        this.sideTab   = this.createSideTab();
        this.watchPopup();
    }

    private injectStyles(): void {
        if (document.getElementById('mp-styles')) return;
        const s = document.createElement('style');
        s.id = 'mp-styles';
        s.textContent =
            '.mp-btn{position:fixed;bottom:72px;right:20px;z-index:10001;' +
            'display:flex;align-items:center;gap:6px;' +
            'padding:9px 14px 9px 18px;background:#212529;color:#fff;' +
            'border:2px solid #495057;border-radius:22px;cursor:pointer;' +
            'font-size:13px;font-weight:600;' +
            'box-shadow:0 4px 14px rgba(0,0,0,.35);' +
            'transition:background .2s,transform .15s;}' +
            '.mp-btn:hover{background:#495057;transform:translateY(-2px);}' +
            '.mp-btn.mp-on{background:#0d6efd;border-color:#0d6efd;}' +
            '.mp-dismiss{display:inline-flex;align-items:center;justify-content:center;' +
            'width:20px;height:20px;margin-left:2px;' +
            'background:rgba(255,255,255,.15);border:none;border-radius:50%;' +
            'color:#fff;font-size:14px;line-height:1;cursor:pointer;' +
            'flex-shrink:0;padding:0;transition:background .15s;}' +
            '.mp-dismiss:hover{background:rgba(255,255,255,.35);}' +
            '.mp-side-tab{position:fixed;top:50%;left:0;z-index:10001;' +
            'transform:translateY(-50%);width:28px;height:28px;padding:0;' +
            'background:#212529;color:#fff;' +
            'border:2px solid #495057;border-left:none;' +
            'border-radius:0 8px 8px 0;cursor:pointer;' +
            'font-size:15px;line-height:28px;text-align:center;' +
            'box-shadow:3px 0 10px rgba(0,0,0,.3);' +
            'transition:background .2s;display:none;}' +
            '.mp-side-tab:hover{background:#495057;}' +
            '.mp-side-tab.mp-visible{display:block;}';
        document.head.appendChild(s);
    }

    private createButton(): HTMLButtonElement {
        const btn = document.createElement('button');
        btn.className = 'mp-btn';
        btn.title = 'Preview at Android 390 px width';

        const label = document.createElement('span');
        label.textContent = '📱 Mobile Preview';
        btn.appendChild(label);

        const dismiss = document.createElement('button');
        dismiss.className = 'mp-dismiss';
        dismiss.title = 'Collapse to left margin';
        dismiss.textContent = '‹';
        dismiss.addEventListener('click', (e: MouseEvent) => { e.stopPropagation(); this.collapse(); });
        btn.appendChild(dismiss);

        btn.addEventListener('click', () => this.toggle());
        document.body.appendChild(btn);
        return btn;
    }

    private createSideTab(): HTMLButtonElement {
        const tab = document.createElement('button');
        tab.className = 'mp-side-tab';
        tab.title = 'Restore Mobile Preview button';
        tab.textContent = '📱';
        tab.addEventListener('click', () => this.restore());
        document.body.appendChild(tab);
        return tab;
    }

    private collapse(): void {
        if (this.isActive) this.deactivate();
        this.toggleBtn.style.display = 'none';
        this.sideTab.classList.add('mp-visible');
    }

    private restore(): void {
        this.sideTab.classList.remove('mp-visible');
        this.toggleBtn.style.display = '';
    }

    private activate(): void {
        this.isActive = true;
        const features = 'width=390,height=844,resizable=yes,scrollbars=yes,location=no,menubar=no,toolbar=no,status=no';
        this.previewWin = globalThis.open(globalThis.location.href, 'mp-preview', features) ?? null;
        const span = this.toggleBtn.querySelector('span');
        if (span) span.textContent = '🖥️ Close Preview';
        this.toggleBtn.classList.add('mp-on');
    }

    private deactivate(): void {
        this.isActive = false;
        if (this.previewWin !== null && !this.previewWin.closed) this.previewWin.close();
        this.previewWin = null;
        const span = this.toggleBtn.querySelector('span');
        if (span) span.textContent = '📱 Mobile Preview';
        this.toggleBtn.classList.remove('mp-on');
    }

    private toggle(): void {
        if (this.isActive) this.deactivate(); else this.activate();
    }

    private watchPopup(): void {
        setInterval(() => {
            if (this.isActive && this.previewWin?.closed === true) {
                this.isActive = false;
                this.previewWin = null;
                const span = this.toggleBtn.querySelector('span');
                if (span) span.textContent = '📱 Mobile Preview';
                this.toggleBtn.classList.remove('mp-on');
            }
        }, 800);
    }
}

/**
 * Drag-to-resize column widths for a <colgroup>-rendered table (GridView's
 * columnGrouping(true), already enabled on #table-invoice). Widths persist
 * per browser (localStorage) so a resize survives reloads and HTMX's partial
 * table refreshes on filter/sort/page changes.
 *
 * On first attach() the table is still in the browser's default auto layout,
 * so every header's current rendered width is read and written onto its
 * matching <col> before the table is flipped to table-layout:fixed — all
 * synchronously, so there's no visible flash of equal-width columns.
 */
class ColumnResizer {
    private readonly storageKeyPrefix: string;
    private dragging: { col: HTMLTableColElement; startX: number; startWidth: number; index: number } | null = null;

    constructor(private readonly tableId: string) {
        this.storageKeyPrefix = `col-width:${tableId}:`;
    }

    attach(): void {
        const table = document.getElementById(this.tableId) as HTMLTableElement | null;
        const colgroup = table?.querySelector('colgroup') ?? null;
        const headerRow = table?.querySelector('thead tr:first-child') ?? null;
        if (table === null || colgroup === null || headerRow === null) return;

        const cols = Array.from(colgroup.querySelectorAll('col'));
        const headerCells = Array.from(headerRow.querySelectorAll('th'));
        if (cols.length === 0 || cols.length !== headerCells.length) return;

        headerCells.forEach((th, index) => {
            const col = cols[index];
            if (col === undefined) return;
            this.applyWidth(col, th, index);
            this.addHandle(th, col, index);
        });

        table.style.tableLayout = 'fixed';
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

export function initInvIndex(tableId = 'table-invoice', configElId = 'inv-filter-config'): void {
    const setup = (): void => {
        const configEl = document.getElementById(configElId);
        const labels = configEl
            ? (JSON.parse(configEl.textContent ?? '{}') as Record<string, string>)
            : {};

        magnifier = new AmountMagnifier(tableId);
        initGroupBySelect();

        Object.entries(labels).forEach(([id, label]) => {
            const sel = document.getElementById(id) as HTMLSelectElement | null;
            if (sel !== null && sel.options.length > 0) sel.options[0].text = label;
        });

        if (document.querySelector('.group-header') !== null) {
            initGroupCollapsible();
        }

        mobilePreview = new MobilePreviewToggle();

        const columnResizer = new ColumnResizer(tableId);
        columnResizer.attach();
        // HTMX replaces #table-invoice wholesale on filter/sort/page changes
        // (see InvsListWidget's hx-boost wiring) — re-attach afterward so the
        // fresh table's headers get their handles and saved widths back.
        // attach() is idempotent, so this is safe even for unrelated swaps.
        document.body.addEventListener('htmx:afterSwap', () => { columnResizer.attach(); });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setup);
    } else {
        setup();
    }
}
