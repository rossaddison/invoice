/**
 * A floating "📱 Mobile Preview" button that opens the current page in a
 * popup window sized like an Android phone (390×844), so a real mobile
 * layout can be eyeballed without leaving the desktop browser or opening
 * devtools' own device toolbar.
 *
 * Originally lived inline in inv-index.ts (used by inv/index and, via
 * initInvIndex()'s tableId/configElId params, inv/guest too — see
 * reference_mobile_preview_inline memory for that 2026-07-15 dedup).
 * Extracted here so a page that doesn't have an invoice grid at all (e.g.
 * inv/calendar) can reuse it without pulling in initInvIndex()'s
 * grid-specific setup (AmountMagnifier, group-by select, column resizer).
 */
export class MobilePreviewToggle {
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
        const features = 'width=390,height=844,resizable=yes,scrollbars=yes,location=no,menubar=no,toolbar=no,status=no';
        const previewWin = globalThis.open(globalThis.location.href, 'mp-preview', features);
        // CodeRabbit (PR #1248): globalThis.open() returns null when the
        // popup is blocked (or otherwise refused) -- previously isActive/
        // the button label/mp-on were all set unconditionally first, so a
        // blocked popup left the UI claiming "Close Preview" with nothing
        // actually open. Bail out before touching any of that state.
        if (previewWin === null) return;

        this.previewWin = previewWin;
        this.isActive = true;
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
