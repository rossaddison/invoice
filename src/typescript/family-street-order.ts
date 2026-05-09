/**
 * Cleaning run — drag-and-drop street order.
 *
 * Reads the ordered <li data-id="N"> elements from #street-order-list,
 * posts the new sequence to the URL stored in data-reorder-url on the list,
 * and updates the position badges without a page reload.
 */

interface ReorderResponse {
    success: boolean;
    message?: string;
}

function collectIds(list: HTMLUListElement): number[] {
    return Array.from(list.querySelectorAll<HTMLLIElement>('li[data-id]'))
        .map(li => parseInt(li.dataset['id'] ?? '0', 10))
        .filter(id => id > 0);
}

function refreshPositionBadges(list: HTMLUListElement): void {
    list.querySelectorAll<HTMLLIElement>('li[data-id]').forEach((li, index) => {
        const badge = li.querySelector<HTMLElement>('.street-position');
        if (badge) badge.textContent = String(index + 1);
    });
}

function setStatus(el: HTMLElement, message: string, type: 'success' | 'danger' | 'info'): void {
    el.textContent = message;
    el.className = `alert alert-${type} py-1 px-2 mt-2`;
    el.style.display = 'block';
}

async function postOrder(url: string, csrf: string, ids: number[]): Promise<ReorderResponse> {
    const body = new URLSearchParams();
    body.append('_csrf', csrf);
    ids.forEach(id => body.append('order[]', String(id)));

    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
    });

    if (!response.ok) {
        return { success: false, message: `HTTP ${response.status}` };
    }
    return response.json() as Promise<ReorderResponse>;
}

/**
 * Must be called when the DOM is already ready (index.ts handles that timing).
 * Do NOT wrap this in a DOMContentLoaded listener — that event will have already
 * fired by the time index.ts calls this function.
 */
export function initStreetOrder(): void {
    const list = document.getElementById('street-order-list') as HTMLUListElement | null;
    if (!list) return;

    const reorderUrl = list.dataset['reorderUrl'] ?? '';
    const csrfInput  = document.getElementById('street-order-csrf') as HTMLInputElement | null;
    const statusEl   = document.getElementById('street-order-status') as HTMLElement | null;

    if (!reorderUrl || !csrfInput || !statusEl) return;

    let dragged: HTMLLIElement | null = null;
    let lastEnterTarget: HTMLLIElement | null = null;

    list.addEventListener('dragstart', (e: DragEvent) => {
        const target = (e.target as HTMLElement).closest<HTMLLIElement>('li[data-id]');
        if (!target) return;
        dragged = target;
        lastEnterTarget = null;
        target.classList.add('opacity-50');
        e.dataTransfer?.setData('text/plain', target.dataset['id'] ?? '');
    });

    list.addEventListener('dragend', () => {
        dragged?.classList.remove('opacity-50');
        dragged = null;
        lastEnterTarget = null;
    });

    // dragover must call preventDefault to allow drop — do not move items here.
    // Moving in dragover fires hundreds of times per second and causes the list
    // to reflow, which immediately flips the midpoint check and makes items bounce.
    list.addEventListener('dragover', (e: DragEvent) => {
        e.preventDefault();
    });

    // dragenter fires once when the cursor enters a new element — safe to reorder here.
    // lastEnterTarget prevents repeated moves when the cursor crosses child elements
    // (icon, badge) within the same <li>.
    list.addEventListener('dragenter', (e: DragEvent) => {
        e.preventDefault();
        if (!dragged) return;
        const target = (e.target as HTMLElement).closest<HTMLLIElement>('li[data-id]');
        if (!target || target === dragged || target === lastEnterTarget) return;

        lastEnterTarget = target;
        const rect = target.getBoundingClientRect();
        const after = e.clientY > rect.top + rect.height / 2;
        if (after) {
            target.after(dragged);
        } else {
            target.before(dragged);
        }
        refreshPositionBadges(list);
    });

    list.addEventListener('drop', async (e: DragEvent) => {
        e.preventDefault();
        refreshPositionBadges(list);

        const ids = collectIds(list);
        setStatus(statusEl, '…saving', 'info');

        try {
            const result = await postOrder(reorderUrl, csrfInput.value, ids);
            if (result.success) {
                setStatus(statusEl, '✓ Order saved', 'success');
            } else {
                setStatus(statusEl, `✗ ${result.message ?? 'Save failed'}`, 'danger');
            }
        } catch (err) {
            setStatus(statusEl, `✗ Network error: ${String(err)}`, 'danger');
        }
    });
}
