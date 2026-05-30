import type { OriginalStyles } from './types.js';

export class AmountMagnifier {
    private readonly magnificationFactor = 1.4;
    private readonly animationDuration = 250;
    private observer!: MutationObserver;

    constructor(private readonly tableId: string) {
        this.attachMagnifiers();
        this.setupObserver();
    }

    private attachMagnifiers(): void {
        ['.badge.bg-success', '.badge.bg-warning', '.badge.bg-danger'].forEach(selector => {
            document.querySelectorAll<HTMLElement>(selector).forEach(el => {
                if (this.isAmount(el) && !el.dataset['magnifierInitialized']) {
                    this.addBehavior(el);
                    el.dataset['magnifierInitialized'] = 'true';
                }
            });
        });
    }

    private isAmount(el: HTMLElement): boolean {
        const text = el.textContent?.trim() ?? '';
        // Length cap bounds worst-case backtracking to O(20²) = 400 steps.
        // JS lacks atomic groups/possessive quantifiers, so capping is the
        // correct mitigation for the [\d,]+ / \d* digit-overlap ReDoS risk.
        if (text.length === 0 || text.length > 20) return false;
        return /^[\d,]+\.?\d*$/.test(text);
    }

    private addBehavior(el: HTMLElement): void {
        let borderColor = '#007bff';
        let bgColor = 'rgba(255, 255, 255, 0.95)';
        if (el.classList.contains('bg-success'))      { borderColor = '#28a745'; bgColor = '#d4edda'; }
        else if (el.classList.contains('bg-warning')) { borderColor = '#ffc107'; bgColor = '#fff3cd'; }
        else if (el.classList.contains('bg-danger'))  { borderColor = '#dc3545'; bgColor = '#f8d7da'; }

        const cs = globalThis.getComputedStyle(el);
        const orig: OriginalStyles = {
            fontSize: cs.fontSize, fontWeight: cs.fontWeight, backgroundColor: cs.backgroundColor,
            border: cs.border, borderRadius: cs.borderRadius, padding: cs.padding,
            zIndex: cs.zIndex, position: cs.position, transform: cs.transform, boxShadow: cs.boxShadow,
        };

        el.style.transition = `all ${this.animationDuration}ms ease-in-out`;
        el.style.cursor = 'pointer';
        el.classList.add('amount-magnifiable');

        let hovered = false;
        el.addEventListener('mouseenter', () => { if (!hovered) { hovered = true;  this.magnify(el, orig, borderColor, bgColor); } });
        el.addEventListener('mouseleave', () => { if (hovered)  { hovered = false; this.restore(el, orig); } });
        el.addEventListener('click', e => {
            e.preventDefault();
            if (hovered) { this.restore(el, orig); hovered = false; }
            else         { this.magnify(el, orig, borderColor, bgColor); hovered = true; }
        });
    }

    private magnify(el: HTMLElement, orig: OriginalStyles, borderColor: string, bgColor: string): void {
        const newSize = Number.parseFloat(orig.fontSize) * this.magnificationFactor;
        el.style.fontSize        = `${newSize}px`;
        el.style.fontWeight      = 'bold';
        el.style.backgroundColor = bgColor;
        el.style.border          = `2px solid ${borderColor}`;
        el.style.borderRadius    = '6px';
        el.style.padding         = '8px 12px';
        el.style.zIndex          = '1000';
        el.style.position        = 'relative';
        el.style.transform       = 'scale(1.1)';
        el.style.boxShadow       = '0 4px 12px rgba(0,0,0,0.15)';
    }

    private restore(el: HTMLElement, orig: OriginalStyles): void {
        const style = el.style as unknown as Record<string, string>;
        (Object.keys(orig) as Array<keyof OriginalStyles>).forEach(k => { style[k] = orig[k]; });
    }

    private setupObserver(): void {
        this.observer = new MutationObserver(mutations => {
            for (const m of mutations) {
                if (m.type === 'childList' && m.addedNodes.length > 0) {
                    setTimeout(() => this.attachMagnifiers(), 100);
                    break;
                }
            }
        });
        const container = document.getElementById(this.tableId) ?? document.querySelector('.table-responsive');
        if (container) this.observer.observe(container, { childList: true, subtree: true });
    }
}

export function initGroupBySelect(): void {
    const select = document.querySelector<HTMLSelectElement>('.group-by-select');
    if (!select) return;
    const allowed = new Set(['none', 'status', 'client', 'client_group', 'month', 'year', 'date', 'amount_range']);
    select.addEventListener('change', function () {
        if (allowed.has(this.value)) {
            const base = this.dataset['baseUrl'] ?? '';
            globalThis.location.href = `${base}?groupBy=${encodeURIComponent(this.value)}`;
        }
    });
}

export function initGroupCollapsible(): void {
    (globalThis as unknown as Record<string, unknown>)['toggleGroupRows'] = (groupHeader: HTMLElement): void => {
        const icon = groupHeader.querySelector<HTMLElement>('.group-toggle-icon');
        if (!icon) return;
        const collapsing = icon.classList.contains('bi-chevron-down');
        icon.classList.toggle('bi-chevron-down', !collapsing);
        icon.classList.toggle('bi-chevron-right', collapsing);
        let next = groupHeader.nextElementSibling as HTMLElement | null;
        while (next !== null && !next.classList.contains('group-header')) {
            next.style.display = collapsing ? 'none' : '';
            next = next.nextElementSibling as HTMLElement | null;
        }
    };

    (globalThis as unknown as Record<string, unknown>)['toggleAllGroups'] = (expand: boolean | null = null): void => {
        document.querySelectorAll<HTMLElement>('.group-header').forEach(header => {
            const icon = header.querySelector('.group-toggle-icon');
            const collapsed = icon?.classList.contains('bi-chevron-right') ?? false;
            const toggle = (globalThis as Record<string, unknown>)['toggleGroupRows'] as (h: HTMLElement) => void;
            if (expand === null || (expand && collapsed) || (!expand && !collapsed)) toggle(header);
        });
    };
}
