/**
 * HomeCare Offline PWA: the "which side is whose" block of an invoice card
 * (see homecare-offline-shell.ts). Its own small module so it can be tested
 * without the shell's top-level await (which renders on import), and so the
 * shell bundle the service worker precaches stays small.
 *
 * The offline page is a standalone document with no icon font, so the pictures
 * are plain Unicode symbols that render on any device, shown beside (never
 * instead of) the words. Builds DOM nodes directly rather than HTML strings:
 * the worker names are free text.
 */

import type { OfflineInvoiceSide } from './homecare-offline-db.js';

const SIDE_SYMBOL: Record<string, string> = {
    both: '🏠',
    front: '🚪',
    back: '🌳',
};

const STATUS_SYMBOL: Record<string, string> = {
    allocated: '🕐',
    completed: '✓',
    postponed: '⏸',
    cancelled: '✖',
};

/** Marks a side whose card has been left at the house (evidence it was cleaned). */
const CARDED_SYMBOL = '✉';

function part(className: string, text: string): HTMLElement {
    const el = document.createElement('span');
    el.className = className;
    el.textContent = text;
    return el;
}

function renderSide(side: OfflineInvoiceSide): HTMLElement {
    const line = document.createElement('div');
    line.className = side.mine ? 'offline-side mine' : 'offline-side';
    line.appendChild(part('symbol', SIDE_SYMBOL[side.side] ?? ''));
    line.appendChild(part('label', side.sideLabel));
    line.appendChild(part('worker', side.workerName));
    line.appendChild(part('state', `${STATUS_SYMBOL[side.status] ?? ''} ${side.statusLabel}`.trim()));
    if (side.carded) {
        line.appendChild(part('carded', CARDED_SYMBOL));
    }
    return line;
}

/**
 * One line per side. Returns null when there is nothing to show: no sides, or a snapshot
 * downloaded before sides were added (the field is simply absent).
 */
export function renderSides(sides: OfflineInvoiceSide[] | undefined): HTMLElement | null {
    if (!sides || sides.length === 0) return null;
    const block = document.createElement('div');
    block.className = 'offline-sides';
    for (const side of sides) {
        block.appendChild(renderSide(side));
    }
    return block;
}
