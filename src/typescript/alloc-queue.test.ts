import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { initAllocationRequestQueue, resetAllocationRequestQueue } from './alloc-queue.js';

type Ext = Record<string, unknown>;

/** htmx:beforeRequest as htmx fires it: cancelable, with the request's path and form data. */
function beforeRequest(cell: Element, path: string, fields: Record<string, string>): CustomEvent {
    const formData = new FormData();
    Object.entries(fields).forEach(([k, v]) => formData.append(k, v));
    const event = new CustomEvent('htmx:beforeRequest', {
        bubbles: true,
        cancelable: true,
        detail: { target: cell, requestConfig: { path, formData } },
    });
    cell.dispatchEvent(event);
    return event;
}

function afterRequest(cell: Element): void {
    cell.dispatchEvent(new CustomEvent('htmx:afterRequest', { bubbles: true, detail: { target: cell } }));
}

describe('allocation request queue', () => {
    const ajax = vi.fn();

    beforeAll(() => {
        initAllocationRequestQueue();
    });

    beforeEach(() => {
        resetAllocationRequestQueue();
        ajax.mockReset();
        // like htmx.ajax: the replayed request announces itself with its own beforeRequest
        ajax.mockImplementation((_verb: string, path: string, ctx: { target: string; values: Record<string, string> }) => {
            beforeRequest(document.querySelector(ctx.target)!, path, ctx.values);
        });
        (globalThis as Ext)['htmx'] = { ajax };
        document.body.innerHTML = '<div id="alloc-1"></div><div id="alloc-2"></div><div id="other"></div>';
    });

    afterEach(() => {
        document.body.innerHTML = '';
        vi.useRealTimers();
    });

    it('lets the first tap on a cell through', () => {
        const first = beforeRequest(document.getElementById('alloc-1')!, '/inv/allocateside/1', { side: 'front' });

        expect(first.defaultPrevented).toBe(false);
    });

    it('holds a second tap on the same cell while the first is in flight', () => {
        const cell = document.getElementById('alloc-1')!;
        beforeRequest(cell, '/inv/allocateside/1', { side: 'front', worker_id: '7' });

        const second = beforeRequest(cell, '/inv/allocateside/1', { side: 'front', worker_id: '8' });

        expect(second.defaultPrevented).toBe(true);
        expect(ajax).not.toHaveBeenCalled();
    });

    it('replays the held tap, with its own fields, once the first has completed', () => {
        const cell = document.getElementById('alloc-1')!;
        beforeRequest(cell, '/inv/allocateside/1', { side: 'front', worker_id: '7', _csrf: 't' });
        beforeRequest(cell, '/inv/allocateside/1', { side: 'front', worker_id: '8', _csrf: 't' });

        afterRequest(cell);

        expect(ajax).toHaveBeenCalledTimes(1);
        expect(ajax).toHaveBeenCalledWith('post', '/inv/allocateside/1', {
            target: '#alloc-1',
            swap: 'outerHTML',
            values: { side: 'front', worker_id: '8', _csrf: 't' },
        });
    });

    it('replays several held taps one at a time, in the order they were tapped', () => {
        const cell = document.getElementById('alloc-1')!;
        beforeRequest(cell, '/a', { n: '1' });
        beforeRequest(cell, '/a', { n: '2' });
        beforeRequest(cell, '/a', { n: '3' });

        afterRequest(cell); // tap 1 done: tap 2 goes
        expect(ajax).toHaveBeenCalledTimes(1);
        expect(ajax.mock.calls[0]![2].values).toEqual({ n: '2' });

        afterRequest(cell); // tap 2 done: tap 3 goes
        expect(ajax).toHaveBeenCalledTimes(2);
        expect(ajax.mock.calls[1]![2].values).toEqual({ n: '3' });

        afterRequest(cell); // tap 3 done: nothing left
        expect(ajax).toHaveBeenCalledTimes(2);
    });

    it('does not hold a replayed tap behind itself', () => {
        const cell = document.getElementById('alloc-1')!;
        beforeRequest(cell, '/a', { n: '1' });
        beforeRequest(cell, '/a', { n: '2' });
        afterRequest(cell);

        // the replay took the lock, so a third tap now waits behind IT
        const third = beforeRequest(cell, '/a', { n: '3' });

        expect(third.defaultPrevented).toBe(true);
    });

    it('frees the cell when nothing is held, so the next tap goes straight through', () => {
        const cell = document.getElementById('alloc-1')!;
        beforeRequest(cell, '/a', { n: '1' });
        afterRequest(cell);

        const next = beforeRequest(cell, '/a', { n: '2' });

        expect(next.defaultPrevented).toBe(false);
        expect(ajax).not.toHaveBeenCalled();
    });

    it('never makes one house wait for another', () => {
        beforeRequest(document.getElementById('alloc-1')!, '/a', { n: '1' });

        const otherHouse = beforeRequest(document.getElementById('alloc-2')!, '/b', { n: '1' });

        expect(otherHouse.defaultPrevented).toBe(false);
    });

    it('leaves requests that are not for an allocation cell alone', () => {
        const other = document.getElementById('other')!;
        beforeRequest(other, '/x', { n: '1' });

        const second = beforeRequest(other, '/x', { n: '2' });

        expect(second.defaultPrevented).toBe(false);
    });

    it('ignores a lock that never reported back, so a cell cannot freeze', () => {
        vi.useFakeTimers();
        const cell = document.getElementById('alloc-1')!;
        beforeRequest(cell, '/a', { n: '1' });

        vi.advanceTimersByTime(21_000);
        const later = beforeRequest(cell, '/a', { n: '2' });

        expect(later.defaultPrevented).toBe(false);
    });

    it('lets a tap through rather than losing it when it cannot be replayed', () => {
        const cell = document.getElementById('alloc-1')!;
        beforeRequest(cell, '/a', { n: '1' });
        const noConfig = new CustomEvent('htmx:beforeRequest', { bubbles: true, cancelable: true, detail: { target: cell } });

        cell.dispatchEvent(noConfig);

        expect(noConfig.defaultPrevented).toBe(false);
    });
});
