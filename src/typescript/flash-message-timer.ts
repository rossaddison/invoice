/**
 * Flash message auto-dismiss countdown, with a per-message pause/resume control.
 * Duration scales with message word count so longer messages stay visible longer.
 *
 * Extracted from an inline <script> in resources/views/invoice/layout/alert.php
 * so script-src no longer needs 'unsafe-inline'.
 */

interface TimerData {
    intervalId: ReturnType<typeof setInterval>;
    startTime: number;
    duration: number;
    remaining: number;
    pausedTime: number;
    pauseStartTime?: number;
    container: HTMLElement;
}

declare global {
    // var (not `interface Window`) — see htmx.ts for why.
    var flashMessageTimer: FlashMessageTimer | undefined;
}

export class FlashMessageTimer {
    private readonly baseDuration = 3000;
    private readonly wordsPerSecond = 3;
    private readonly minDuration = 2000;
    private readonly maxDuration = 15000;
    private readonly interval = 100;
    private readonly timers = new Map<Element, TimerData>();
    private readonly paused = new Map<Element, boolean>();

    private calculateDuration(text: string): number {
        // text comes from Element.textContent, which never contains markup.
        const wordCount = text.trim().split(/\s+/).filter(word => word.length > 0).length;
        const readingTime = this.baseDuration + (wordCount / this.wordsPerSecond) * 1000;
        return Math.max(this.minDuration, Math.min(this.maxDuration, readingTime));
    }

    init(): void {
        const alerts = document.querySelectorAll('.alert.flash-message-fade');
        alerts.forEach(alert => {
            if (this.timers.has(alert)) return;
            this.createTimer(alert);
        });
    }

    private createTimer(alert: Element): void {
        const messageText = alert.textContent ?? '';
        const duration = this.calculateDuration(messageText);

        const container = document.createElement('div');
        container.className = 'flash-message-container';
        alert.parentNode?.insertBefore(container, alert);
        container.appendChild(alert);

        const initialSeconds = Math.ceil(duration / 1000);
        const timerElement = document.createElement('div');
        timerElement.className = 'countdown-timer';
        timerElement.title = 'Click to pause/resume timer';
        timerElement.innerHTML = `
            <div class="countdown-progress"></div>
            <span class="countdown-text">${initialSeconds}</span>
            <span class="pause-button">⏸️</span>
        `;
        container.appendChild(timerElement);

        this.paused.set(alert, false);

        let remaining = duration;
        const startTime = Date.now();

        const updateTimer = (): void => {
            if (this.paused.get(alert) === true) return;

            const timerData = this.timers.get(alert);
            const pausedTime = timerData?.pausedTime ?? 0;
            const elapsed = Date.now() - startTime - pausedTime;
            remaining = Math.max(0, duration - elapsed);

            const seconds = Math.ceil(remaining / 1000);
            const progress = ((duration - remaining) / duration) * 100;

            const progressElement = timerElement.querySelector<HTMLElement>('.countdown-progress');
            const textElement = timerElement.querySelector<HTMLElement>('.countdown-text');
            const pauseButtonElement = timerElement.querySelector<HTMLElement>('.pause-button');

            if (progressElement && textElement && pauseButtonElement) {
                progressElement.style.setProperty('--progress', `${progress}%`);
                textElement.textContent = String(seconds);
                pauseButtonElement.textContent = this.paused.get(alert) === true ? '▶️' : '⏸️';
            }

            if (remaining <= 0) {
                this.hideAlert(alert, container);
            }
        };

        timerElement.addEventListener('click', (e: MouseEvent) => {
            e.preventDefault();
            e.stopPropagation();
            this.togglePause(alert, timerElement);
        });

        updateTimer();
        const intervalId = setInterval(updateTimer, this.interval);

        this.timers.set(alert, {
            intervalId,
            startTime,
            duration,
            remaining,
            pausedTime: 0,
            container,
        });

        const closeButton = alert.querySelector('.btn-close');
        closeButton?.addEventListener('click', () => {
            const timerData = this.timers.get(alert);
            if (timerData) clearInterval(timerData.intervalId);
            this.cleanupTimer(alert);
        });
    }

    private togglePause(alert: Element, timerElement: HTMLElement): void {
        const isPaused = this.paused.get(alert) === true;
        const timerData = this.timers.get(alert);
        if (!timerData) return;

        if (isPaused) {
            timerData.pausedTime += Date.now() - (timerData.pauseStartTime ?? Date.now());
            this.paused.set(alert, false);
            timerElement.classList.remove('paused');
            timerElement.title = 'Click to pause timer';
        } else {
            timerData.pauseStartTime = Date.now();
            this.paused.set(alert, true);
            timerElement.classList.add('paused');
            timerElement.title = 'Click to resume timer';
        }

        const pauseButtonElement = timerElement.querySelector<HTMLElement>('.pause-button');
        if (pauseButtonElement) {
            pauseButtonElement.textContent = this.paused.get(alert) === true ? '▶️' : '⏸️';
        }
    }

    private cleanupTimer(alert: Element): void {
        this.timers.delete(alert);
        this.paused.delete(alert);
    }

    private hideAlert(alert: Element, container: HTMLElement): void {
        alert.classList.add('hiding');
        setTimeout(() => {
            container.remove();
            this.cleanupTimer(alert);
        }, 500);
    }
}

function handleAddedNode(node: Node, flashTimer: FlashMessageTimer): void {
    if (node.nodeType === 1 && (node as Element).classList?.contains('alert')) {
        setTimeout(() => flashTimer.init(), 100);
    }
}

function handleMutation(mutation: MutationRecord, flashTimer: FlashMessageTimer): void {
    if (mutation.type !== 'childList') return;
    mutation.addedNodes.forEach(node => handleAddedNode(node, flashTimer));
}

export function initFlashMessageTimer(): void {
    const flashTimer = new FlashMessageTimer();
    globalThis.flashMessageTimer = flashTimer;

    setTimeout(() => flashTimer.init(), 100);

    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => handleMutation(mutation, flashTimer));
    });
    observer.observe(document.body, { childList: true, subtree: true });
}
