/**
 * Serialises the taps on one invoice's allocation cell (id "alloc-<inv id>").
 *
 * The lead taps in split seconds. Two quick taps on the same cell would otherwise be two
 * parallel requests the server can handle in either order, so the last write to win could be
 * the *earlier* tap (tap Dave then Sam on a side and end on Dave), and the cell could finish
 * showing a stale state. Here a tap on a cell is held back while an earlier one for the same
 * cell is still in flight, and replayed, in the order tapped, once that one has completed and
 * its reply has been swapped in. Different houses (cells) never wait for each other.
 *
 * htmx's own `hx-sync="...:queue all"` cannot do this: every tap swaps the cell it came from
 * out of the page (outerHTML), and htmx refuses to issue a queued request whose element is no
 * longer in the document, so a queued second tap would simply be lost.
 */

interface QueuedTap {
    path: string;
    values: Record<string, string>;
}

interface RequestEventDetail {
    target?: unknown;
    requestConfig?: { path?: string; formData?: FormData };
}

/** A lock older than this is ignored, so a request that never reports back cannot freeze a cell. */
const STALE_LOCK_MS = 20_000;

const inFlight = new Map<string, number>();
const waiting = new Map<string, QueuedTap[]>();
let replaying = false;

function cellIdOf(target: unknown): string | null {
    return target instanceof HTMLElement && target.id.startsWith('alloc-') ? target.id : null;
}

function valuesOf(formData: FormData): Record<string, string> {
    const values: Record<string, string> = {};
    formData.forEach((value, key) => {
        // a form field is text; a file input contributes its file name
        values[key] = typeof value === 'string' ? value : value.name;
    });
    return values;
}

function replay(cellId: string, tap: QueuedTap): void {
    replaying = true;
    try {
        void globalThis.htmx?.ajax('post', tap.path, { target: '#' + cellId, swap: 'outerHTML', values: tap.values });
    } finally {
        // the request's own beforeRequest has fired by now; never let the flag leak to another request
        replaying = false;
    }
}

export function initAllocationRequestQueue(): void {
    document.body.addEventListener('htmx:beforeRequest', ((e: CustomEvent<RequestEventDetail>) => {
        const cellId = cellIdOf(e.detail.target);
        if (cellId === null) return;

        if (replaying) {
            // our own replay of a held tap: let it through, and it now holds the cell
            inFlight.set(cellId, Date.now());
            return;
        }

        const since = inFlight.get(cellId);
        const config = e.detail.requestConfig;
        if (since !== undefined && Date.now() - since < STALE_LOCK_MS && config?.path && config.formData) {
            // an earlier tap on this cell is still in flight: hold this one, in order
            e.preventDefault();
            const queue = waiting.get(cellId) ?? [];
            queue.push({ path: config.path, values: valuesOf(config.formData) });
            waiting.set(cellId, queue);
            return;
        }
        inFlight.set(cellId, Date.now());
    }) as EventListener);

    // fires once the reply has been processed and swapped in, for success and error alike
    document.body.addEventListener('htmx:afterRequest', ((e: CustomEvent<RequestEventDetail>) => {
        const cellId = cellIdOf(e.detail.target);
        if (cellId === null) return;

        inFlight.delete(cellId);
        const queue = waiting.get(cellId);
        const next = queue?.shift();
        if (queue?.length === 0) waiting.delete(cellId);
        if (next) replay(cellId, next);
    }) as EventListener);
}

/** For tests: forget every lock and held tap. */
export function resetAllocationRequestQueue(): void {
    inFlight.clear();
    waiting.clear();
    replaying = false;
}
