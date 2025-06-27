#!/usr/bin/env php
<?php

/**
 * Standalone installer that works without Symfony Console dependencies
 * This provides a fallback for when dependencies aren't installed yet
 */

declare(strict_types=1);

// Check if we're in the right directory
if (!file_exists('config/common/params.php')) {
    echo "Error: Please run this installer from the project root directory.\n";
    exit(1);
}

// Set up environment
$_ENV['APP_ENV'] = $_ENV['APP_ENV'] ?? 'local';

echo "\n";
echo "🚀 RossAddison Invoice - Standalone Installer\n";
echo "=============================================\n\n";

echo "This installer will guide you through the setup process.\n";
echo "If you have Symfony Console installed, use: ./yii install\n\n";

// Step 1: Preflight checks
echo "🔍 Performing preflight checks...\n";

$allPassed = true;

// PHP version check
$phpVersion = PHP_VERSION;
$requiredPhp = '8.3';
$phpOk = version_compare($phpVersion, $requiredPhp, '>=');
echo sprintf("   PHP version (%s): %s\n", $phpVersion, $phpOk ? '✅ OK' : '❌ FAIL');
if (!$phpOk) $allPassed = false;

// Required extensions
$requiredExtensions = ['curl', 'dom', 'fileinfo', 'filter', 'gd', 'intl', 'json', 'mbstring', 'openssl', 'pdo', 'pdo_mysql'];
foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    echo sprintf("   Extension %s: %s\n", $ext, $loaded ? '✅ OK' : '❌ MISSING');
    if (!$loaded) $allPassed = false;
}

// Composer check
$output = [];
$returnCode = 0;
exec('composer --version 2>/dev/null', $output, $returnCode);
$composerInstalled = $returnCode === 0;
echo sprintf("   Composer: %s\n", $composerInstalled ? '✅ OK' : '❌ NOT FOUND');
if (!$composerInstalled) $allPassed = false;

if (!$allPassed) {
    echo "\n❌ Some preflight checks failed. Please resolve the issues above.\n";
    exit(1);
}

echo "\n✅ All preflight checks passed!\n\n";

// Step 2: Dependencies
echo "📦 Checking dependencies...\n";

if (is_dir('vendor') && file_exists('vendor/autoload.php')) {
    echo "   ✅ Dependencies are already installed.\n";
    echo "   💡 You can update them with: composer update --no-dev\n\n";
} else {
    echo "   📦 Dependencies need to be installed.\n";
    echo "   ❓ Install dependencies now? [Y/n]: ";
    
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim(strtolower($line)) !== 'n') {
        echo "   Running: composer install --no-dev --optimize-autoloader\n";
        
        $command = 'composer install --no-dev --optimize-autoloader 2>&1';
        $output = [];
        $returnCode = 0;
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "   ✅ Dependencies installed successfully!\n\n";
        } else {
            echo "   ❌ Composer install failed:\n";
            foreach ($output as $line) {
                echo "      " . $line . "\n";
            }
            echo "\n   💡 You can run 'composer install' manually and then re-run this installer.\n\n";
        }
    } else {
        echo "   ⚠️  Dependencies installation skipped.\n";
        echo "   💡 Run 'composer install' manually to install them.\n\n";
    }
}

// Step 3: Database setup
echo "🗄️ Database setup...\n";

function parseDatabaseConfig(): array
{
    $paramsFile = 'config/common/params.php';
    
    if (!file_exists($paramsFile)) {
        throw new Exception('Configuration file not found: ' . $paramsFile);
    }

    $content = file_get_contents($paramsFile);
    $env = $_ENV['APP_ENV'] ?? 'local';
    
    // Default values
    $dbHost = 'localhost';
    $dbUser = 'root';
    $dbPassword = null;
    $dbName = 'yii3_i';
    
    // Parse switch statement
    if (preg_match('/case\s+[\'"]' . preg_quote($env) . '[\'"]:\s*(.*?)break;/s', $content, $matches)) {
        $caseContent = $matches[1];
        
        if (preg_match('/\$dbHost\s*=\s*[\'"]([^\'"]+)[\'"]/', $caseContent, $hostMatch)) {
            $dbHost = $hostMatch[1];
        }
        if (preg_match('/\$dbUser\s*=\s*[\'"]([^\'"]+)[\'"]/', $caseContent, $userMatch)) {
            $dbUser = $userMatch[1];
        }
        if (preg_match('/\$dbPassword\s*=\s*[\'"]([^\'"]+)[\'"]/', $caseContent, $passMatch)) {
            $dbPassword = $passMatch[1];
        }
    }
    
    // Extract database name from DSN
    if (preg_match('/[\'"]mysql:host=.*?;dbname=([^\'";,]+)/', $content, $dbMatch)) {
        $dbName = $dbMatch[1];
    }

    return [
        'host' => $dbHost,
        'database' => $dbName,
        'user' => $dbUser,
        'password' => $dbPassword
    ];
}

try {
    $dbConfig = parseDatabaseConfig();
    
    echo "   Database configuration found:\n";
    echo "   Host: " . $dbConfig['host'] . "\n";
    echo "   User: " . $dbConfig['user'] . "\n";
    echo "   Database: " . $dbConfig['database'] . "\n\n";
    
    echo "   ❓ Create database '{$dbConfig['database']}' if it doesn't exist? [Y/n]: ";
    
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim(strtolower($line)) !== 'n') {
        try {
            $dsn = sprintf('mysql:host=%s', $dbConfig['host']);
            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if database exists
            $stmt = $pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
            $stmt->execute([$dbConfig['database']]);
            
            if ($stmt->fetch()) {
                echo "   ✅ Database '{$dbConfig['database']}' already exists.\n\n";
            } else {
                // Create database
                $sql = sprintf('CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $dbConfig['database']);
                $pdo->exec($sql);
                echo "   ✅ Database '{$dbConfig['database']}' created successfully!\n\n";
            }
        } catch (PDOException $e) {
            echo "   ❌ Database operation failed: " . $e->getMessage() . "\n";
            echo "   💡 Please ensure MySQL is running and credentials are correct.\n\n";
        }
    } else {
        echo "   ⚠️  Database creation skipped.\n\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Database setup failed: " . $e->getMessage() . "\n\n";
}

// Step 4: Manual checklist
echo "📋 Manual Setup Checklist\n";
echo "=========================\n\n";

echo "Please complete the following steps manually:\n\n";

echo "1. 📝 Edit the .env file in the project root:\n";
echo "   Set: BUILD_DATABASE=true\n\n";

echo "2. 🚀 Start the application to trigger table creation:\n";
echo "   Run: ./yii serve\n";
echo "   Or visit your web server URL\n\n";

echo "3. 🔄 After tables are created, reset the BUILD_DATABASE setting:\n";
echo "   Edit .env and set: BUILD_DATABASE=false\n";
echo "   (This improves performance)\n\n";

echo "4. 👤 Create your first admin user:\n";
echo "   Visit the signup page in your browser\n";
echo "   The first user will automatically get admin privileges\n\n";

echo "⚠️  IMPORTANT: Remember to set BUILD_DATABASE=false after setup!\n";
echo "   Leaving it as true will impact application performance.\n\n";

echo "✅ Installation setup completed!\n";
echo "   Follow the manual steps above to complete the setup.\n\n";

echo "💡 For advanced features, use: ./yii install (requires dependencies)\n";