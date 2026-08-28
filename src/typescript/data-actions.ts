/**
 * Delegated click handling for small, page-agnostic behaviors that used to be
 * inline onclick="..." attributes (this.showPicker(), window.close(),
 * window.print(), toggleCommalistPicker(), confirm-before-submit,
 * window.history.back(), toggleAllGroups()), plus two generic reusable
 * primitives: toggle-panel (show/hide an element by selector) and
 * copy-to-clipboard (copy another element's value/text content). Delegating
 * from document means script-src no longer needs 'unsafe-inline' for these.
 */
function copyToClipboard(actionEl: HTMLElement): void {
    // data-copy-target-id (plain element id, resolved via getElementById)
    // takes priority over data-copy-target (a CSS selector, via
    // querySelector) — needed for gateway credential fields whose id
    // contains literal [ ] characters (e.g. "gateway_stripe_secretKey]"),
    // which would break querySelector('#' + id) without manual escaping.
    const targetId = actionEl.dataset['copyTargetId'];
    const selector = actionEl.dataset['copyTarget'];
    const source = targetId
        ? document.getElementById(targetId)
        : selector
            ? document.querySelector<HTMLElement>(selector)
            : null;
    if (!source || !navigator.clipboard?.writeText) {
        return;
    }
    const value =
        source instanceof HTMLInputElement || source instanceof HTMLTextAreaElement
            ? source.value
            : (source.textContent ?? '');
    void navigator.clipboard.writeText(value).then(() => {
        flashCopied(actionEl);
    });
}

/**
 * Post-copy feedback. An icon-only button (e.g. the clipboard icon next to
 * a gateway credential field) flashes its icon to a checkmark instead of
 * overwriting textContent, which would blank the icon out; a text button
 * (e.g. partial_settings_system_updates.php's "Copy") keeps the original
 * textContent-swap behaviour. data-copied-label carries a server-translated
 * "Copied!" (falls back to the untranslated English literal when absent,
 * for callers that predate this attribute).
 */
function flashCopied(actionEl: HTMLElement): void {
    const copiedLabel = actionEl.dataset['copiedLabel'];
    const icon = actionEl.querySelector<HTMLElement>('i.bi');
    if (icon) {
        const originalIconClass = icon.className;
        const originalLabel = actionEl.getAttribute('aria-label');
        const originalTitle = actionEl.getAttribute('title');
        icon.className = 'bi bi-clipboard-check';
        if (copiedLabel) {
            actionEl.setAttribute('aria-label', copiedLabel);
            actionEl.setAttribute('title', copiedLabel);
        }
        setTimeout(() => {
            icon.className = originalIconClass;
            if (copiedLabel) {
                setOrRemoveAttribute(actionEl, 'aria-label', originalLabel);
                setOrRemoveAttribute(actionEl, 'title', originalTitle);
            }
        }, 1500);
        return;
    }
    const original = actionEl.textContent;
    actionEl.textContent = copiedLabel ?? 'Copied!';
    setTimeout(() => {
        actionEl.textContent = original;
    }, 1500);
}

function setOrRemoveAttribute(el: HTMLElement, name: string, value: string | null): void {
    if (value === null) {
        el.removeAttribute(name);
    } else {
        el.setAttribute(name, value);
    }
}

function handleDataAction(actionEl: HTMLElement): void {
    switch (actionEl.dataset['action']) {
        case 'show-picker':
            (actionEl as HTMLInputElement).showPicker?.();
            break;
        case 'window-close':
            globalThis.close();
            break;
        case 'window-print':
            globalThis.print();
            break;
        case 'toggle-commalist-picker':
            globalThis.toggleCommalistPicker?.();
            break;
        case 'history-back':
            globalThis.history.back();
            break;
        case 'toggle-all-groups': {
            const toggleAll = (globalThis as unknown as Record<string, unknown>)['toggleAllGroups'] as
                | ((expand: boolean) => void)
                | undefined;
            toggleAll?.(actionEl.dataset['expand'] === 'true');
            break;
        }
        case 'toggle-panel': {
            const selector = actionEl.dataset['target'];
            const panel = selector ? document.querySelector<HTMLElement>(selector) : null;
            panel?.classList.toggle('d-none');
            break;
        }
        case 'copy-to-clipboard':
            copyToClipboard(actionEl);
            break;
        default:
            break;
    }
}

// Guards against binding the same document-level listeners twice — every
// call used to add another 'click'/'change' listener on top of whatever was
// already bound, so a second call (accidental re-init, or a test file that
// calls this once per test) meant every click ran handleDataAction() once
// per accumulated listener instead of once. Found via a genuinely flaky
// test: flashCopied()'s setTimeout-deferred revert kept losing a race
// against a second, stale invocation of itself.
let initialized = false;

export function initDataActions(): void {
    if (initialized) {
        return;
    }
    initialized = true;

    document.addEventListener('click', (e: MouseEvent) => {
        const target = e.target as HTMLElement | null;
        if (!target) return;

        const actionEl = target.closest<HTMLElement>('[data-action]');
        if (actionEl) {
            handleDataAction(actionEl);
        }

        const confirmEl = target.closest<HTMLElement>('[data-confirm]');
        if (confirmEl && !globalThis.confirm(confirmEl.dataset['confirm'] ?? '')) {
            e.preventDefault();
        }
    });

    // Dropdown filters (Yiisoft\Yii\DataView DropdownFilter, class "native-reset")
    // used to auto-submit via an inline onChange="this.form.submit()" attribute
    // that the vendor widget renders directly — CSP script-src 'self' blocks it
    // since it's outside this app's own inline-script sweep. Submit on their
    // behalf via a delegated change listener instead.
    document.addEventListener('change', (e: Event) => {
        const target = e.target as HTMLElement | null;
        if (target instanceof HTMLSelectElement && target.classList.contains('native-reset')) {
            target.form?.submit();
        }

        // Generic "select all" checkbox: data-target points at a container
        // whose child checkboxes all get set to this checkbox's own state.
        if (target instanceof HTMLInputElement
                && target.type === 'checkbox'
                && target.dataset['action'] === 'select-all') {
            const selector = target.dataset['target'];
            const container = selector ? document.querySelector<HTMLElement>(selector) : null;
            container?.querySelectorAll<HTMLInputElement>('input[type="checkbox"]').forEach((cb) => {
                cb.checked = target.checked;
            });
        }
    });
}
