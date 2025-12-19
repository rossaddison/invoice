<?php

declare(strict_types=1);

/**
 * Flash Message Pause Test Page
 * This is a simple test page to demonstrate the new pause functionality for flash messages
 */

use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @var FlashInterface $flash
 * @var TranslatorInterface $translator
 * @var string $alert
 */

// Add some test flash messages
$flash->add('info', 'This is a short info message.', true);
$flash->add('success', 'This is a longer success message that demonstrates how the timer adapts to content length. It should take longer to auto-dismiss because it has more words to read.', true);
$flash->add('warning', 'Medium length warning message with some important information.', true);
$flash->add('danger', 'Critical error message!', true);

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flash Message Pause Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .instructions {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 class="mb-4">Flash Message Pause Functionality Test</h1>
        
        <div class="instructions">
            <h3>Instructions</h3>
            <ul>
                <li><strong>Individual Timer Control:</strong> Click on the countdown timer badge on each flash message to pause/resume that specific message</li>
                <li><strong>Global Controls:</strong> Use the Angular controls in the top-right corner to pause/resume/close all messages at once</li>
                <li><strong>Visual Indicators:</strong> 
                    <ul>
                        <li>⏸️ icon = message is running</li>
                        <li>▶️ icon = message is paused</li>
                        <li>Orange timer badge = paused state</li>
                        <li>Dark timer badge = running state</li>
                    </ul>
                </li>
                <li><strong>Adaptive Timing:</strong> Messages with more content will stay visible longer</li>
            </ul>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-3">
            <button type="button" class="btn btn-primary" onclick="addTestMessage()">Add Test Message</button>
            <button type="button" class="btn btn-secondary" onclick="addLongMessage()">Add Long Message</button>
        </div>

        <!-- Flash messages will appear here -->
        <?php echo $alert; ?>

        <!-- Flash Message Controls will be added automatically by the JavaScript -->
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Flash Message Controls (Standalone) -->
    <script src="/invoice/public/assets/flash-message-controls.js"></script>
    
    <!-- Angular App (if built) - Disabled for now due to build errors -->
    <!-- <script type="module" src="/invoice/angular/dist/main.js"></script> -->
    
    <script>
        // Test functions to add more flash messages
        function addTestMessage() {
            const messages = [
                'This is a dynamically added test message.',
                'Another test message to demonstrate the pause functionality.',
                'Short message.',
                'A much longer test message that contains multiple sentences and should demonstrate how the adaptive timing works with content of varying lengths, making sure users have enough time to read everything.'
            ];
            
            const types = ['info', 'success', 'warning', 'danger'];
            const randomMessage = messages[Math.floor(Math.random() * messages.length)];
            const randomType = types[Math.floor(Math.random() * types.length)];
            
            // Create a new flash message element
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${randomType} alert-dismissible fade show flash-message-fade`;
            alertDiv.setAttribute('role', 'alert');
            alertDiv.innerHTML = `
                ${randomMessage}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            // Add to the page
            const container = document.querySelector('.test-container');
            const instructions = container.querySelector('.instructions');
            container.insertBefore(alertDiv, instructions.nextSibling);
            
            // Reinitialize timer
            if (window.flashMessageTimer) {
                setTimeout(() => window.flashMessageTimer.init(), 100);
            }
        }
        
        function addLongMessage() {
            const longMessage = `This is an exceptionally long test message that is designed to demonstrate the adaptive timing feature of the flash message system. The timer automatically calculates how long this message should remain visible based on the number of words it contains, using an average reading speed to ensure users have adequate time to read and comprehend the entire message. This particular message contains many words and should therefore remain visible for a longer duration than shorter messages, proving that the content-aware timing system is working correctly. You should notice that this message's timer starts with a higher number than the shorter messages.`;
            
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-primary alert-dismissible fade show flash-message-fade';
            alertDiv.setAttribute('role', 'alert');
            alertDiv.innerHTML = `
                ${longMessage}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            const container = document.querySelector('.test-container');
            const instructions = container.querySelector('.instructions');
            container.insertBefore(alertDiv, instructions.nextSibling);
            
            if (window.flashMessageTimer) {
                setTimeout(() => window.flashMessageTimer.init(), 100);
            }
        }

        // Debug information
        console.log('Flash Message Pause Test Page Loaded');
        console.log('Available Angular components:', window.ng ? 'Angular loaded' : 'Angular not loaded');
        
        // Monitor flash timer initialization
        setTimeout(() => {
            console.log('Flash Timer Instance:', window.flashMessageTimer ? 'Available' : 'Not available');
            if (window.flashMessageTimer) {
                console.log('Active timers:', window.flashMessageTimer.timers.size);
            }
        }, 1000);
    </script>
</body>
</html>