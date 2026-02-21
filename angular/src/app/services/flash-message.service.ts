import { Injectable } from '@angular/core';

declare global {
  interface Window {
    flashMessageTimer?: any;
  }
}

@Injectable({
  providedIn: 'root'
})
export class FlashMessageService {
  
  constructor() {}

  /**
   * Pause all flash message timers
   */
  pauseAll(): void {
    const flashTimer = this.getFlashTimer();
    if (flashTimer && flashTimer.timers) {
      flashTimer.timers.forEach((timerData: any, alert: any) => {
        if (!flashTimer.paused.get(alert)) {
          const timerElement = alert.parentNode?.querySelector('.countdown-timer');
          if (timerElement) {
            flashTimer.togglePause(alert, timerElement);
          }
        }
      });
    }
  }

  /**
   * Resume all flash message timers
   */
  resumeAll(): void {
    const flashTimer = this.getFlashTimer();
    if (flashTimer && flashTimer.timers) {
      flashTimer.timers.forEach((timerData: any, alert: any) => {
        if (flashTimer.paused.get(alert)) {
          const timerElement = alert.parentNode?.querySelector('.countdown-timer');
          if (timerElement) {
            flashTimer.togglePause(alert, timerElement);
          }
        }
      });
    }
  }

  /**
   * Toggle pause/resume for all flash message timers
   */
  togglePauseAll(): void {
    const flashTimer = this.getFlashTimer();
    if (flashTimer && flashTimer.timers) {
      // Check if any timer is currently running (not paused)
      let hasRunningTimer = false;
      flashTimer.timers.forEach((timerData: any, alert: any) => {
        if (!flashTimer.paused.get(alert)) {
          hasRunningTimer = true;
        }
      });

      if (hasRunningTimer) {
        this.pauseAll();
      } else {
        this.resumeAll();
      }
    }
  }

  /**
   * Get the number of active flash messages
   */
  getActiveCount(): number {
    const flashTimer = this.getFlashTimer();
    return flashTimer && flashTimer.timers ? flashTimer.timers.size : 0;
  }

  /**
   * Get the number of paused flash messages
   */
  getPausedCount(): number {
    const flashTimer = this.getFlashTimer();
    if (!flashTimer || !flashTimer.paused) return 0;
    
    let pausedCount = 0;
    flashTimer.paused.forEach((isPaused: boolean) => {
      if (isPaused) pausedCount++;
    });
    return pausedCount;
  }

  /**
   * Check if all flash messages are paused
   */
  areAllPaused(): boolean {
    const activeCount = this.getActiveCount();
    const pausedCount = this.getPausedCount();
    return activeCount > 0 && activeCount === pausedCount;
  }

  /**
   * Check if any flash messages are paused
   */
  areAnyPaused(): boolean {
    return this.getPausedCount() > 0;
  }

  /**
   * Close all flash messages immediately
   */
  closeAll(): void {
    const flashTimer = this.getFlashTimer();
    if (flashTimer && flashTimer.timers) {
      const alerts = Array.from(flashTimer.timers.keys());
      alerts.forEach((alert: any) => {
        const timerData = flashTimer.timers.get(alert);
        if (timerData && timerData.container) {
          flashTimer.hideAlert(alert, timerData.container);
        }
      });
    }
  }

  /**
   * Add event listener for flash message events
   */
  onFlashMessageUpdate(callback: (event: { activeCount: number,
   pausedCount: number }) => void): void {
    // Set up a polling mechanism to check for changes
    const checkInterval = setInterval(() => {
      const activeCount = this.getActiveCount();
      const pausedCount = this.getPausedCount();
      callback({ activeCount, pausedCount });
      
      // Stop polling if no active messages
      if (activeCount === 0) {
        clearInterval(checkInterval);
      }
    }, 500);
  }

  /**
   * Get the global flash timer instance
   */
  private getFlashTimer(): any {
    // Try to get from window first
    if (globalThis.flashMessageTimer) {
      return globalThis.flashMessageTimer;
    }

    // Try to find in DOM through window object
    if ((globalThis as any).flashMessageTimerInstance) {
      return (globalThis as any).flashMessageTimerInstance;
    }

    return null;
  }

  /**
   * Initialize the service and expose the flash timer globally
   */
  init(): void {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.setupGlobalAccess());
    } else {
      this.setupGlobalAccess();
    }
  }

  private setupGlobalAccess(): void {
    // Wait a bit for the flash timer to be initialized
    setTimeout(() => {
      const flashTimer = this.findFlashTimerInstance();
      if (flashTimer) {
        globalThis.window.flashMessageTimer = flashTimer;
      }
    }, 200);
  }

  private findFlashTimerInstance(): any {
    // Try to access the flash timer through various methods
    const alerts = document.querySelectorAll('.alert.flash-message-fade');
    if (alerts.length > 0) {
      // The flash timer should be accessible through the global scope
      // when the FlashMessageTimer class is instantiated
      return (globalThis.window as any).flashMessageTimerInstance;
    }
    return null;
  }
}