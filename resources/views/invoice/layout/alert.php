<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/**
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var App\Invoice\Setting\SettingRepository $s
 */

?>

<?php

$alertMessageFont = $s->getSetting('bootstrap5_alert_message_font') ?: 'Arial';
$alertMessageFontSize = $s->getSetting('bootstrap5_alert_message_font_size') ?: '16';
$alertCloseButtonFontSize = $s->getSetting('bootstrap5_alert_close_button_font_size') ?: '10';

$danger =  AlertVariant::DANGER;
$info = AlertVariant::INFO;
$primary =  AlertVariant::PRIMARY;
$secondary = AlertVariant::SECONDARY;
$success = AlertVariant::SUCCESS;
$warning = AlertVariant::WARNING;
$light = AlertVariant::LIGHT;
$dark = AlertVariant::DARK;

$flashMessages = $flash->getAll();

// Debug: Check if there are any flash messages
if (empty($flashMessages)) {
    // Uncomment the line below to test if alerts are working
    // echo '<div class="alert alert-info alert-dismissible fade show" role="alert">Test message - no flash messages found <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}
?>

<!-- Flash Messages CSS and JavaScript -->
<style>
.flash-message-container {
    position: relative;
    margin-bottom: 1rem;
}

.countdown-timer {
    position: absolute;
    top: -3px;
    right: -3px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
    z-index: 1051;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.countdown-timer:hover {
    background: rgba(0, 0, 0, 0.9);
}

.countdown-timer.paused {
    background: rgba(255, 165, 0, 0.8);
}

.countdown-timer.paused:hover {
    background: rgba(255, 165, 0, 1);
}

.pause-button {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 8px;
    z-index: 1;
}

.countdown-progress {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: conic-gradient(from 0deg, #007bff 0%, #007bff var(--progress), transparent var(--progress), transparent 100%);
    opacity: 0.8;
}

.flash-message-fade {
    transition: opacity 0.5s ease-in-out, transform 0.3s ease-in-out;
    display: block !important;
    opacity: 1 !important;
}

.flash-message-fade.hiding {
    opacity: 0;
    transform: translateX(100%);
}
</style>

<script>
class FlashMessageTimer {
    constructor() {
        this.baseDuration = 3000; // 3 seconds base time
        this.wordsPerSecond = 3; // Average reading speed: 3 words per second
        this.minDuration = 2000; // Minimum 2 seconds
        this.maxDuration = 15000; // Maximum 15 seconds
        this.interval = 100; // Update every 100ms
        this.timers = new Map(); // Map of alert -> timer data
        this.paused = new Map(); // Map of alert -> pause state
    }

    calculateDuration(text) {
        // Remove HTML tags and get clean text
        const cleanText = text.replace(/<[^>]*>/g, '');
        
        // Count words (split by whitespace and filter empty strings)
        const wordCount = cleanText.trim().split(/\s+/).filter(word => word.length > 0).length;
        
        // Calculate reading time: base time + (words / reading speed) * 1000ms
        const readingTime = this.baseDuration + (wordCount / this.wordsPerSecond) * 1000;
        
        // Ensure duration is within min/max bounds
        return Math.max(this.minDuration, Math.min(this.maxDuration, readingTime));
    }

    init() {
        // Initialize timers for all flash messages
        const alerts = document.querySelectorAll('.alert.flash-message-fade');
        console.log('Found alerts:', alerts.length); // Debug log
        
        alerts.forEach((alert, index) => {
            // Skip if timer already exists
            if (this.timers.has(alert)) return;
            
            this.createTimer(alert, index);
        });
    }

    createTimer(alert, index) {
        // Calculate content-based duration
        const messageText = alert.textContent || alert.innerText || '';
        const duration = this.calculateDuration(messageText);
        
        console.log(`Message: "${messageText.substring(0, 50)}..." - Duration: ${duration}ms`); // Debug log
        
        // Create countdown container
        const container = document.createElement('div');
        container.className = 'flash-message-container';
        alert.parentNode.insertBefore(container, alert);
        container.appendChild(alert);

        // Create countdown timer display
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

        // Initialize pause state
        this.paused.set(alert, false);

        let remaining = duration;
        let startTime = Date.now();
        let pausedTime = 0;

        const updateTimer = () => {
            if (this.paused.get(alert)) {
                return; // Skip update when paused
            }
            
            const elapsed = Date.now() - startTime - pausedTime;
            remaining = Math.max(0, duration - elapsed);
            
            const seconds = Math.ceil(remaining / 1000);
            const progress = ((duration - remaining) / duration) * 100;
            
            const progressElement = timerElement.querySelector('.countdown-progress');
            const textElement = timerElement.querySelector('.countdown-text');
            const pauseButtonElement = timerElement.querySelector('.pause-button');
            
            if (progressElement && textElement && pauseButtonElement) {
                progressElement.style.setProperty('--progress', progress + '%');
                textElement.textContent = seconds;
                pauseButtonElement.textContent = this.paused.get(alert) ? '▶️' : '⏸️';
            }

            if (remaining <= 0) {
                this.hideAlert(alert, container);
            }
        };

        // Add pause/resume click handler
        timerElement.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.togglePause(alert, timerElement);
        });

        // Update immediately and then set interval
        updateTimer();
        const intervalId = setInterval(updateTimer, this.interval);
        
        // Store timer reference with additional data
        this.timers.set(alert, {
            intervalId: intervalId,
            startTime: startTime,
            duration: duration,
            remaining: remaining,
            pausedTime: 0,
            container: container
        });

        // Handle manual close button
        const closeButton = alert.querySelector('.btn-close');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                const timerData = this.timers.get(alert);
                if (timerData) {
                    clearInterval(timerData.intervalId);
                }
                this.cleanupTimer(alert);
            });
        }
    }

    togglePause(alert, timerElement) {
        const isPaused = this.paused.get(alert);
        const timerData = this.timers.get(alert);
        
        if (!timerData) return;

        if (isPaused) {
            // Resume: add paused duration to total pausedTime
            timerData.pausedTime += Date.now() - timerData.pauseStartTime;
            this.paused.set(alert, false);
            timerElement.classList.remove('paused');
            timerElement.title = 'Click to pause timer';
        } else {
            // Pause: record when we paused
            timerData.pauseStartTime = Date.now();
            this.paused.set(alert, true);
            timerElement.classList.add('paused');
            timerElement.title = 'Click to resume timer';
        }

        // Update pause button icon immediately
        const pauseButtonElement = timerElement.querySelector('.pause-button');
        if (pauseButtonElement) {
            pauseButtonElement.textContent = this.paused.get(alert) ? '▶️' : '⏸️';
        }
    }

    cleanupTimer(alert) {
        this.timers.delete(alert);
        this.paused.delete(alert);
    }

    hideAlert(alert, container) {
        alert.classList.add('hiding');
        
        setTimeout(() => {
            if (container && container.parentNode) {
                container.parentNode.removeChild(container);
            }
            this.cleanupTimer(alert);
        }, 500); // Match CSS transition duration
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing flash timer'); // Debug log
    
    const flashTimer = new FlashMessageTimer();
    
    // Expose globally for Angular integration
    window.flashMessageTimer = flashTimer;
    window.flashMessageTimerInstance = flashTimer;
    
    // Small delay to ensure all elements are rendered
    setTimeout(() => {
        flashTimer.init();
    }, 100);
    
    // Re-initialize for dynamically added messages
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.classList && node.classList.contains('alert')) {
                        setTimeout(() => flashTimer.init(), 100);
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, { childList: true, subtree: true });
});
</script>

<?php
/**
 * @var array $flashMessages
 * @var array|string $value
 * @var string $key
 */
foreach ($flashMessages as $key => $value) {
    if (is_array($value)) {
        /**
         * @var Stringable|string $body
         */
        foreach ($value as $key2 => $body) {
            $matchedKey = match ($key) {
                'danger' => $danger,
                'info' => $info,
                'primary' => $primary,
                'secondary' => $secondary,
                'success' => $success,
                'warning' => $warning,
                'light' => $light,
                'dark' => $dark,
                'default' => $info,
            };
            
            $alert = Alert::widget()
                     ->addCssStyle([
                         'font-size' => $alertMessageFontSize . 'px',
                         'font-family' =>  $alertMessageFont,
                     ])
                     ->addClass('btn-flash-message-close flash-message-fade')
                     ->closeButtonTag('button')
                     ->closeButtonAttributes(['style' => 'font-size:' . $alertCloseButtonFontSize . 'px'])
                     ->variant($matchedKey)
                     // do not html encode since not user-generated code.
                     ->body($body, false)
                     ->dismissable(true)
                     ->render();
            echo $alert;
        }
    }
}
