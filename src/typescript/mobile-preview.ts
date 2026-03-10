/**
 * Mobile Preview Toggle
 *
 * Adds a fixed 📱 button to admin index pages.
 * Click to open an Android phone-frame overlay (390 × 844 px) that loads
 * the current page in an iframe — giving a true responsive preview at the
 * Bootstrap sm breakpoint without switching to a test device.
 *
 * Press Esc or click 🖥️ Desktop View to dismiss.
 * Guard: button is suppressed when running inside the preview iframe itself.
 */

class MobilePreviewToggle {
    private isActive = false;
    private toggleBtn!: HTMLButtonElement;

    constructor() {
        // Do not activate when running inside the preview iframe itself
        if (window.self !== window.top) return;
        this.injectStyles();
        this.createButton();
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
                padding: 9px 18px; background: #212529; color: #fff;
                border: 2px solid #495057; border-radius: 22px; cursor: pointer;
                font-size: 13px; font-weight: 600;
                box-shadow: 0 4px 14px rgba(0,0,0,.35);
                transition: background .2s, transform .15s;
            }
            .mp-btn:hover { background: #495057; transform: translateY(-2px); }
            .mp-btn.mp-on { background: #0d6efd; border-color: #0d6efd; }
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
        this.toggleBtn.textContent = '📱 Mobile Preview';
        this.toggleBtn.title = 'Preview at Android 390 px width';
        this.toggleBtn.addEventListener('click', () => this.toggle());
        document.body.appendChild(this.toggleBtn);
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
        this.toggleBtn.textContent = '🖥️ Desktop View';
        this.toggleBtn.classList.add('mp-on');
    }

    private deactivate(): void {
        this.isActive = false;
        document.getElementById('mp-overlay')?.classList.remove('mp-show');
        this.toggleBtn.textContent = '📱 Mobile Preview';
        this.toggleBtn.classList.remove('mp-on');
    }

    private toggle(): void {
        this.isActive ? this.deactivate() : this.activate();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new MobilePreviewToggle();
});
