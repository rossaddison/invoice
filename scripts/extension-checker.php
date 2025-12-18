<?php

declare(strict_types=1);

/**
 * Extension Checker for Invoice System
 * 
 * Validates required PHP extensions based on invoice_build.yml workflow
 * Checks both CLI and Apache PHP configurations
 */

class ExtensionChecker
{
    // Required extensions from invoice_build.yml workflow
    private array $requiredExtensions = [
        'apcu' => 'Memory caching for performance optimization',
        'fileinfo' => 'File type detection and validation',
        'pdo' => 'Database abstraction layer',
        'pdo_sqlite' => 'SQLite database support',
        'intl' => 'Internationalization support',
        'gd' => 'Image processing and manipulation', 
        'openssl' => 'SSL/TLS cryptographic functions',
        'dom' => 'XML DOM manipulation',
        'json' => 'JSON data processing',
        'mbstring' => 'Multi-byte string handling',
        'curl' => 'HTTP client functionality',
        'uopz' => 'Testing and debugging utilities',
        'sodium' => 'Modern cryptography library'
    ];

    private array $results = [];
    private string $phpVersion = '';
    private string $wampPath = '';
    private bool $wampRunning = false;
    private array $webExtensions = [];
    private string $webEndpointUrl = '';

    public function __construct()
    {
        $this->phpVersion = PHP_VERSION;
        $this->detectWampPath();
        $this->webEndpointUrl = 'http://localhost/invoice/public/check-web-extensions.php';
    }

    public function checkAllExtensions(): array
    {
        echo "=== PHP Extension Checker for Invoice System ===\n";
        echo "PHP Version: {$this->phpVersion}\n";
        echo "WAMP Path: {$this->wampPath}\n";
        
        $this->checkWampServerStatus();
        echo "WAMP Server Status: " . ($this->wampRunning ? "✓ Running" : "✗ Not Running") . "\n\n";

        $this->checkCliExtensions();
        
        if ($this->wampRunning) {
            $this->checkWebExtensions();
            $this->compareExtensions();
        } else {
            $this->checkApacheExtensionsFromIni();
            echo "[WARNING] WAMP Server not running - using php.ini file analysis instead of live check\n";
        }
        
        $this->generateReport();

        return $this->results;
    }

    private function checkWampServerStatus(): void
    {
        // Check if WAMP services are running
        $apacheRunning = $this->isServiceRunning('Apache');
        $mysqlRunning = $this->isServiceRunning('MySQL'); 
        
        // Try HTTP request to localhost
        $httpResponds = $this->testHttpConnection();
        
        $this->wampRunning = $httpResponds && ($apacheRunning || $this->isPortOpen(80));
        
        if (!$this->wampRunning) {
            echo "[INFO] WAMP Server appears to be stopped. Please start WAMP Server for complete extension checking.\n";
            echo "[INFO] Checking services: Apache=" . ($apacheRunning ? 'Running' : 'Stopped') . 
                 ", HTTP Port 80=" . ($this->isPortOpen(80) ? 'Open' : 'Closed') . "\n";
        }
    }

    private function isServiceRunning(string $serviceName): bool
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return false;
        }

        $command = "sc query | findstr /I \"$serviceName\"";
        $output = shell_exec($command);
        
        return $output !== null && (
            stripos($output, 'RUNNING') !== false ||
            stripos($output, 'wamp') !== false
        );
    }

    private function isPortOpen(int $port): bool
    {
        $connection = @fsockopen('localhost', $port, $errno, $errstr, 2);
        if ($connection) {
            fclose($connection);
            return true;
        }
        return false;
    }

    private function testHttpConnection(): bool
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents('http://localhost/', false, $context);
        return $response !== false;
    }

    private function checkWebExtensions(): void
    {
        echo "Checking Web/Apache Extensions (via HTTP request):\n";
        echo str_repeat("-", 50) . "\n";

        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET'
            ]
        ]);

        $response = @file_get_contents($this->webEndpointUrl, false, $context);
        
        if ($response === false) {
            echo "[ERROR] Failed to connect to web endpoint: {$this->webEndpointUrl}\n";
            echo "[INFO] Make sure WAMP Server is running and the endpoint file exists.\n\n";
            return;
        }

        $webData = json_decode($response, true);
        
        if (!$webData || !$webData['success']) {
            echo "[ERROR] Invalid response from web endpoint\n\n";
            return;
        }

        $this->webExtensions = $webData['loaded_extensions'];
        
        echo "Web PHP Version: {$webData['php_version']}\n";
        echo "Web SAPI: {$webData['sapi']}\n";
        echo "Web Config File: {$webData['config_file']}\n";
        echo "Total Web Extensions: {$webData['extension_count']}\n\n";

        foreach ($this->requiredExtensions as $extension => $description) {
            $loaded = in_array($extension, $this->webExtensions);
            $this->results['web'][$extension] = [
                'loaded' => $loaded,
                'description' => $description,
                'sapi' => $webData['sapi'],
                'config_file' => $webData['config_file']
            ];

            $status = $loaded ? '[✓]' : '[✗]';
            echo sprintf("%-15s %s %s\n", $extension, $status, $description);
        }
        echo "\n";
    }

    private function compareExtensions(): void
    {
        if (empty($this->webExtensions)) {
            return;
        }

        echo "Extension Comparison (CLI vs Web):\n";
        echo str_repeat("-", 50) . "\n";

        $cliExtensions = get_loaded_extensions();
        $differences = [];

        // Check for extensions in CLI but not Web
        $cliOnly = array_diff($cliExtensions, $this->webExtensions);
        if (!empty($cliOnly)) {
            $differences['cli_only'] = $cliOnly;
            echo "Extensions loaded in CLI only: " . implode(', ', $cliOnly) . "\n";
        }

        // Check for extensions in Web but not CLI
        $webOnly = array_diff($this->webExtensions, $cliExtensions);
        if (!empty($webOnly)) {
            $differences['web_only'] = $webOnly;
            echo "Extensions loaded in Web only: " . implode(', ', $webOnly) . "\n";
        }

        if (empty($differences)) {
            echo "[✓] CLI and Web extensions match perfectly!\n";
        } else {
            echo "[WARNING] Extensions differ between CLI and Web contexts\n";
        }
        
        $this->results['comparison'] = $differences;
        echo "\n";
    }

    private function detectWampPath(): void
    {
        $possiblePaths = [
            'C:\\wamp64\\',
            'C:\\wamp\\',
            'D:\\wamp64\\',
            'D:\\wamp\\'
        ];

        foreach ($possiblePaths as $path) {
            if (is_dir($path)) {
                $this->wampPath = $path;
                return;
            }
        }

        $this->wampPath = 'NOT_DETECTED';
    }

    private function checkCliExtensions(): void
    {
        echo "Checking CLI Extensions (php.ini):\n";
        echo str_repeat("-", 50) . "\n";

        foreach ($this->requiredExtensions as $extension => $description) {
            $loaded = extension_loaded($extension);
            $this->results['cli'][$extension] = [
                'loaded' => $loaded,
                'description' => $description,
                'ini_file' => php_ini_loaded_file()
            ];

            $status = $loaded ? '[✓]' : '[✗]';
            $color = $loaded ? '' : '';
            echo sprintf("%-15s %s %s\n", $extension, $status, $description);
        }
        echo "\n";
    }

    private function checkApacheExtensionsFromIni(): void
    {
        echo "Checking Apache Extensions (phpForApache.ini - File Analysis):\n";
        echo str_repeat("-", 50) . "\n";

        $apacheIniPath = $this->getApacheIniPath();
        
        if (!$apacheIniPath || !file_exists($apacheIniPath)) {
            echo "[WARNING] Apache php.ini not found at: {$apacheIniPath}\n\n";
            return;
        }

        $iniContent = file_get_contents($apacheIniPath);
        
        foreach ($this->requiredExtensions as $extension => $description) {
            $enabled = $this->checkExtensionInIni($iniContent, $extension);
            $this->results['apache'][$extension] = [
                'enabled' => $enabled,
                'description' => $description,
                'ini_file' => $apacheIniPath,
                'method' => 'ini_file_analysis'
            ];

            $status = $enabled ? '[✓]' : '[✗]';
            echo sprintf("%-15s %s %s\n", $extension, $status, $description);
        }
        echo "\n";
    }

    private function getApacheIniPath(): string
    {
        if ($this->wampPath === 'NOT_DETECTED') {
            return '';
        }

        // Extract major.minor version (e.g., "8.3" from "8.3.0")
        $versionParts = explode('.', $this->phpVersion);
        $shortVersion = $versionParts[0] . '.' . $versionParts[1];

        $possiblePaths = [
            $this->wampPath . "bin\\apache\\apache2.4.54\\bin\\php.ini",
            $this->wampPath . "bin\\apache\\apache2.4.55\\bin\\php.ini", 
            $this->wampPath . "bin\\apache\\apache2.4.56\\bin\\php.ini",
            $this->wampPath . "bin\\apache\\apache2.4.57\\bin\\php.ini",
            $this->wampPath . "bin\\apache\\apache2.4.58\\bin\\php.ini",
            // Fallback to PHP directory
            $this->wampPath . "bin\\php\\php{$this->phpVersion}\\phpForApache.ini"
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return '';
    }

    private function checkExtensionInIni(string $iniContent, string $extension): bool
    {
        // Check for uncommented extension line
        $patterns = [
            "/^extension={$extension}$/m",
            "/^extension=\"{$extension}\"$/m", 
            "/^extension=php_{$extension}\.dll$/m",
            "/^extension=\"php_{$extension}\.dll\"$/m"
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $iniContent)) {
                return true;
            }
        }

        return false;
    }

    private function generateReport(): void
    {
        echo "=== EXTENSION STATUS REPORT ===\n";
        
        $cliMissing = [];
        $webMissing = [];
        $apacheMissing = [];
        
        foreach ($this->requiredExtensions as $extension => $description) {
            if (!($this->results['cli'][$extension]['loaded'] ?? false)) {
                $cliMissing[] = $extension;
            }
            
            // Check web extensions if WAMP was running
            if ($this->wampRunning && isset($this->results['web'])) {
                if (!($this->results['web'][$extension]['loaded'] ?? false)) {
                    $webMissing[] = $extension;
                }
            } else {
                // Fallback to Apache INI check
                if (!($this->results['apache'][$extension]['enabled'] ?? false)) {
                    $apacheMissing[] = $extension;
                }
            }
        }

        // Check for perfect configuration
        if ($this->wampRunning) {
            if (empty($cliMissing) && empty($webMissing)) {
                echo "[✓] All required extensions are properly configured in both CLI and Web contexts!\n";
                if (!empty($this->results['comparison'])) {
                    echo "[INFO] Note: Some non-required extensions differ between contexts (see comparison above)\n";
                }
                echo "\n";
                return;
            }
        } else {
            if (empty($cliMissing) && empty($apacheMissing)) {
                echo "[✓] All required extensions appear to be configured!\n";
                echo "[INFO] Start WAMP Server for live web extension verification\n\n";
                return;
            }
        }

        // Show missing extensions
        if (!empty($cliMissing)) {
            echo "\n[✗] Missing CLI Extensions:\n";
            foreach ($cliMissing as $extension) {
                echo "  - {$extension}: {$this->requiredExtensions[$extension]}\n";
            }
            $this->showCliInstructions($cliMissing);
        }

        if (!empty($webMissing)) {
            echo "\n[✗] Missing Web/Apache Extensions (Live Check):\n";
            foreach ($webMissing as $extension) {
                echo "  - {$extension}: {$this->requiredExtensions[$extension]}\n";
            }
            $this->showWebExtensionInstructions($webMissing);
        } elseif (!empty($apacheMissing)) {
            echo "\n[✗] Missing Apache Extensions (INI File Analysis):\n";
            foreach ($apacheMissing as $extension) {
                echo "  - {$extension}: {$this->requiredExtensions[$extension]}\n";
            }
            $this->showApacheInstructions($apacheMissing);
        }

        // WAMP Server status reminder
        if (!$this->wampRunning) {
            echo "\n[REMINDER] Start WAMP Server and re-run this check for complete verification\n";
        }
    }

    private function showWebExtensionInstructions(array $missing): void
    {
        echo "\nWEB/APACHE EXTENSION INSTRUCTIONS:\n";
        echo "Extensions missing from live Apache PHP context\n";
        
        $apacheIniPath = $this->getApacheIniPath();
        if ($apacheIniPath) {
            echo "Edit Apache PHP configuration file: {$apacheIniPath}\n";
        } else {
            echo "Edit your Apache PHP configuration file (phpForApache.ini)\n";
        }
        
        echo "Add these lines to the [Extension] section:\n\n";
        
        foreach ($missing as $extension) {
            if ($extension === 'pdo_sqlite') {
                echo "extension=pdo_sqlite\n";
            } elseif ($extension === 'openssl') {
                echo "extension=openssl\n";
            } else {
                echo "extension={$extension}\n";
            }
        }
        echo "\nThen restart Apache through WAMP Manager.\n";
    }

    private function showCliInstructions(array $missing): void
    {
        $cliIniPath = php_ini_loaded_file();
        echo "\nCLI PHP.INI INSTRUCTIONS:\n";
        echo "File: {$cliIniPath}\n";
        echo "Add these lines to the [Extension] section:\n\n";
        
        foreach ($missing as $extension) {
            echo "extension={$extension}\n";
        }
        echo "\nThen restart your terminal/command prompt.\n";
    }

    private function showApacheInstructions(array $missing): void
    {
        $apacheIniPath = $this->getApacheIniPath();
        echo "\nAPACHE PHP.INI INSTRUCTIONS:\n";
        echo "File: {$apacheIniPath}\n";
        echo "Add these lines to the [Extension] section:\n\n";
        
        foreach ($missing as $extension) {
            // For WAMP, extensions are typically loaded as DLLs
            if ($extension === 'pdo_sqlite') {
                echo "extension=pdo_sqlite\n";
            } elseif ($extension === 'openssl') {
                echo "extension=openssl\n";
            } else {
                echo "extension={$extension}\n";
            }
        }
        echo "\nThen restart Apache through WAMP Manager.\n";
    }

    public function jsonOutput(): string
    {
        return json_encode($this->results, JSON_PRETTY_PRINT);
    }

    public function returnCode(): int
    {
        $allOk = true;
        
        foreach ($this->requiredExtensions as $extension => $description) {
            if (!($this->results['cli'][$extension]['loaded'] ?? false) ||
                !($this->results['apache'][$extension]['enabled'] ?? false)) {
                $allOk = false;
                break;
            }
        }

        return $allOk ? 0 : 1;
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $checker = new ExtensionChecker();
    $results = $checker->checkAllExtensions();
    
    // Handle command line arguments
    if (isset($argv[1])) {
        switch ($argv[1]) {
            case '--json':
                echo $checker->jsonOutput();
                break;
            case '--silent':
                // Silent mode, just exit with code
                break;
            default:
                echo "Usage: php extension-checker.php [--json|--silent]\n";
        }
    }
    
    exit($checker->returnCode());
}