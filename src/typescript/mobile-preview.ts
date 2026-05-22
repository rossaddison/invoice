/**
 * Mobile Preview Toggle
 *
 * Adds a fixed 📱 button to admin index pages.
 * Click to open an Android phone-frame overlay (390 × 844 px) that loads
 * the current page in an iframe — giving a true responsive preview at the
 * Bootstrap sm breakpoint without switching to a test device.
 *
 * Press Esc or click 🖥️ Desktop View to dismiss.
 * Click ‹ on the button to collapse it to a left-margin tab; click the tab to restore.
 * Guard: button is suppressed when running inside the preview iframe itself.
 */

class MobilePreviewToggle {
    private isActive = false;
    private isDismissed = false;
    private toggleBtn!: HTMLButtonElement;
    private dismissBtn!: HTMLButtonElement;
    private sideTab!: HTMLButtonElement;

    constructor() {
        if (window.self !== window.top) return;
        this.injectStyles();
        this.createButton();
        this.createSideTab();
        document.addEventListener('keydown', (e: KeyboardEvent) => {
            if (e.key === 'Escape' && this.isActive) this.deactivate();
        });
    }

    private injectStyles(): void {
        if (document.getElementById('mp-styles')) return;
        const s = document.createElement('style');
        s.id = 'mp-styles';
        s.textContent = `
            .mp-btn {
                position: fixed; bottom: 72px; right: 20px; z-index: 10001;
                display: flex; align-items: center; gap: 6px;
                padding: 9px 14px 9px 18px; background: #212529; color: #fff;
                border: 2px solid #495057; border-radius: 22px; cursor: pointer;
                font-size: 13px; font-weight: 600;
                box-shadow: 0 4px 14px rgba(0,0,0,.35);
                transition: background .2s, transform .15s, opacity .2s;
            }
            .mp-btn:hover { background: #495057; transform: translateY(-2px); }
            .mp-btn.mp-on { background: #0d6efd; border-color: #0d6efd; }
            .mp-dismiss {
                display: inline-flex; align-items: center; justify-content: center;
                width: 20px; height: 20px; margin-left: 2px;
                background: rgba(255,255,255,.15); border: none; border-radius: 50%;
                color: #fff; font-size: 14px; line-height: 1; cursor: pointer;
                flex-shrink: 0; padding: 0;
                transition: background .15s;
            }
            .mp-dismiss:hover { background: rgba(255,255,255,.35); }
            .mp-side-tab {
                position: fixed; top: 50%; left: 0; z-index: 10001;
                transform: translateY(-50%);
                writing-mode: vertical-lr; text-orientation: mixed;
                padding: 12px 6px; background: #212529; color: #fff;
                border: 2px solid #495057; border-left: none;
                border-radius: 0 10px 10px 0; cursor: pointer;
                font-size: 12px; font-weight: 600;
                box-shadow: 3px 0 10px rgba(0,0,0,.3);
                transition: background .2s;
                display: none;
            }
            .mp-side-tab:hover { background: #495057; }
            .mp-side-tab.mp-visible { display: block; }
            #mp-overlay {
                display: none; position: fixed; inset: 0; z-index: 10000;
                background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
                overflow-y: auto; justify-content: center;
                align-items: flex-start; padding: 30px 0 60px;
            }
            #mp-overlay.mp-show { display: flex; }
            #mp-phone {
                width: 390px; background: #fff; border-radius: 48px;
                box-shadow: inset 0 0 0 2px #555, 0 0 0 10px #1c1c1e,
                            0 0 0 12px #3a3a3c, 0 40px 100px rgba(0,0,0,.6);
                overflow: hidden; position: relative; flex-shrink: 0;
            }
            #mp-badge {
                position: absolute; top: -28px; left: 50%;
                transform: translateX(-50%); background: rgba(0,0,0,.6);
                color: #a0cfff; font-size: 11px; padding: 2px 10px;
                border-radius: 10px; white-space: nowrap;
            }
            #mp-notch-bar {
                background: #1c1c1e; height: 34px;
                display: flex; align-items: center; justify-content: center;
            }
            #mp-notch { width: 110px; height: 24px; background: #1c1c1e; border-radius: 0 0 16px 16px; }
            #mp-iframe { width: 390px; height: 800px; border: none; display: block; }
            #mp-home-bar {
                background: #1c1c1e; height: 28px;
                display: flex; align-items: center; justify-content: center;
            }
            #mp-home-ind { width: 120px; height: 5px; background: #555; border-radius: 3px; }
            #mp-hint {
                position: fixed; bottom: 18px; left: 50%;
                transform: translateX(-50%); z-index: 10002;
                color: rgba(255,255,255,.45); font-size: 12px;
                pointer-events: none; white-space: nowrap;
            }
        `;
        document.head.appendChild(s);
    }

    private createButton(): void {
        this.toggleBtn = document.createElement('button');
        this.toggleBtn.className = 'mp-btn';
        this.toggleBtn.title = 'Preview at Android 390 px width';

        const label = document.createElement('span');
        label.textContent = '📱 Mobile Preview';
        this.toggleBtn.appendChild(label);

        this.dismissBtn = document.createElement('button');
        this.dismissBtn.className = 'mp-dismiss';
        this.dismissBtn.title = 'Collapse to left margin';
        this.dismissBtn.textContent = '‹';
        this.dismissBtn.addEventListener('click', (e: MouseEvent) => {
            e.stopPropagation();
            this.collapse();
        });
        this.toggleBtn.appendChild(this.dismissBtn);

        this.toggleBtn.addEventListener('click', () => this.toggle());
        document.body.appendChild(this.toggleBtn);
    }

    private createSideTab(): void {
        this.sideTab = document.createElement('button');
        this.sideTab.className = 'mp-side-tab';
        this.sideTab.title = 'Restore Mobile Preview button';
        this.sideTab.textContent = '📱 Preview';
        this.sideTab.addEventListener('click', () => this.restore());
        document.body.appendChild(this.sideTab);
    }

    private collapse(): void {
        this.isDismissed = true;
        if (this.isActive) this.deactivate();
        this.toggleBtn.style.display = 'none';
        this.sideTab.classList.add('mp-visible');
    }

    private restore(): void {
        this.isDismissed = false;
        this.sideTab.classList.remove('mp-visible');
        this.toggleBtn.style.display = '';
    }

    private buildOverlay(): void {
        if (document.getElementById('mp-overlay')) return;

        const overlay = document.createElement('div');
        overlay.id = 'mp-overlay';

        const phone = document.createElement('div');
        phone.id = 'mp-phone';

        const badge = document.createElement('div');
        badge.id = 'mp-badge';
        badge.textContent = '📱 Android — 390 × 844 px';
        phone.appendChild(badge);

        const notchBar = document.createElement('div');
        notchBar.id = 'mp-notch-bar';
        const notch = document.createElement('div');
        notch.id = 'mp-notch';
        notchBar.appendChild(notch);
        phone.appendChild(notchBar);

        const iframe = document.createElement('iframe') as HTMLIFrameElement;
        iframe.id = 'mp-iframe';
        iframe.src = window.location.href;
        phone.appendChild(iframe);

        const homeBar = document.createElement('div');
        homeBar.id = 'mp-home-bar';
        const homeInd = document.createElement('div');
        homeInd.id = 'mp-home-ind';
        homeBar.appendChild(homeInd);
        phone.appendChild(homeBar);

        overlay.appendChild(phone);

        const hint = document.createElement('div');
        hint.id = 'mp-hint';
        hint.textContent = 'Press Esc or click 🖥️ Desktop View to exit';
        overlay.appendChild(hint);

        document.body.appendChild(overlay);
    }

    private activate(): void {
        this.isActive = true;
        this.buildOverlay();
        document.getElementById('mp-overlay')?.classList.add('mp-show');
        const label = this.toggleBtn.querySelector('span');
        if (label) label.textContent = '🖥️ Desktop View';
        this.toggleBtn.classList.add('mp-on');
    }

    private deactivate(): void {
        this.isActive = false;
        document.getElementById('mp-overlay')?.classList.remove('mp-show');
        const label = this.toggleBtn.querySelector('span');
        if (label) label.textContent = '📱 Mobile Preview';
        this.toggleBtn.classList.remove('mp-on');
    }

    private toggle(): void {
        this.isActive ? this.deactivate() : this.activate();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new MobilePreviewToggle();
});
