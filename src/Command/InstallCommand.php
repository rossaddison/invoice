<?php

declare(strict_types=1);

namespace App\Command;

use Exception;
use PDO;
use PDOException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
//use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process as SymfonyProcess;

/**
 * Interactive installer command for setting up the invoice application
 */
final class InstallCommand extends Command
{
    protected static string $defaultName = 'install';

    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Interactive installer for the invoice application')
             ->setHelp('This command guides you through the complete setup process for the invoice application.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Welcome message with styling
        $io->title('🚀 Invoice Application Installer');
        $io->text([
            'Welcome to the interactive installer!',
            'This installer will guide you through the setup process.',
            '',
        ]);

        // Preflight checks
        if (!$this->performPreflightChecks($io)) {
            return Command::FAILURE;
        }

        // Step 1: Database setup
        if (!$this->handleDatabaseSetup($io)) {
            return Command::FAILURE;
        }

        // Step 2: Manual checklist
        $this->displayManualChecklist($io);

        $io->success([
            'Installation setup completed successfully!',
            'Please follow the manual steps above to complete the setup.',
        ]);

        return Command::SUCCESS;
    }

    private function performPreflightChecks(SymfonyStyle $io): bool
    {
        $io->section('🔍 Preflight Checks');

        $checks = [];
        $allPassed = true;

        // PHP version check
        $phpVersion = PHP_VERSION;
        $requiredPhp = '8.3';
        $phpOk = version_compare($phpVersion, $requiredPhp, '>=');
        $checks[] = [
            'PHP version (' . $phpVersion . ')',
            $phpOk ? '✅ OK' : '❌ FAIL (requires >= ' . $requiredPhp . ')',
        ];
        if (!$phpOk) {
            $allPassed = false;
        }

        // Required extensions
        $requiredExtensions = ['curl', 'dom', 'fileinfo', 'filter', 'gd', 'intl', 'json', 'mbstring', 'openssl', 'pdo', 'pdo_mysql'];
        foreach ($requiredExtensions as $ext) {
            $loaded = extension_loaded($ext);
            $checks[] = [
                'PHP extension: ' . $ext,
                $loaded ? '✅ OK' : '❌ MISSING',
            ];
            if (!$loaded) {
                $allPassed = false;
            }
        }

        $autoloadPath = dirname(dirname(__DIR__)) . '/vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            $checks[] = [
                'Composer',
                '❌ NOT FOUND',
            ];
            $allPassed = false;
        } else {
            /**
             * @see {root}/autoload.php
             */
            $checks[] = [
                'Composer',
                '✅ OK',
            ];
        }
        
        // Display results in a table
        $io->table(['Check', 'Status'], $checks);

        if (!$allPassed) {
            $io->error('Some preflight checks failed. Please resolve the issues above before continuing.');
            return false;
        }

        $io->success('All preflight checks passed!');
        return true;
    }
    
    private function handleDatabaseSetup(SymfonyStyle $io): bool
    {
        $io->section('🗄️ Database Setup');

        try {
            $dbConfig = $this->parseDatabaseConfig();

            $io->text([
                'Database configuration found:',
                'Host: ' . (string)$dbConfig['host'],
                'User: ' . (string)$dbConfig['user'],
                'Database: ' . (string)$dbConfig['database'],
                '',
            ]);

            if (!$io->confirm('Create database "' . (string)$dbConfig['database'] . '" if it doesn\'t exist?', true)) {
                $io->note('Database creation skipped.');
                return true;
            }

            return $this->createDatabase($dbConfig, $io);
        } catch (Exception $e) {
            $io->error('Failed to setup database: ' . $e->getMessage());
            return false;
        }
    }

    private function parseDatabaseConfig(): array
    {
        $paramsFile = __DIR__ . '/../../config/common/params.php';

        if (!file_exists($paramsFile)) {
            throw new Exception('Configuration file not found: ' . $paramsFile);
        }

        // Set environment variables if not set to get proper config
        if (!isset($_ENV['APP_ENV'])) {
            $_ENV['APP_ENV'] = 'local';
        }

        // Parse the file to extract the database variables
        $content = file_get_contents($paramsFile);
        if ($content === false) {
            throw new Exception('Failed to read configuration file: ' . $paramsFile);
        }

        // Extract the switch statement values for the current environment
        $env = $_ENV['APP_ENV'] ?? 'local';

        // Default values that match the params.php structure
        $dbHost = 'localhost';
        $dbUser = 'root';
        $dbPassword = null;
        $dbName = 'yii3_i'; // This is hardcoded in the DSN

        // Try to extract values by parsing the switch statement
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

        // Extract database name from DSN pattern
        if (preg_match('/[\'"]mysql:host=.*?;dbname=([^\'";,]+)/', $content, $dbMatch)) {
            $dbName = $dbMatch[1];
        }

        return [
            'host' => $dbHost,
            'database' => $dbName,
            'user' => $dbUser,
            'password' => $dbPassword,
        ];
    }

    private function createDatabase(array $config, SymfonyStyle $io): bool
    {
        $host = (string)$config['host'];
        $password = (string)$config['password'];
        $database = (string)$config['database'];
        $user = (string)$config['user'];
        try {
            // Connect without specifying database to create it
            $dsn = sprintf('mysql:host=%s', $host);
            $pdo = new PDO($dsn, $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if database exists
            $stmt = $pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
            $stmt->execute([$database]);

            if ($stmt->fetch()) {
                $io->note('Database "' . $database . '" already exists.');
                return true;
            }

            // Create database
            $sql = sprintf('CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $database);
            $pdo->exec($sql);

            $io->success('Database "' . $database . '" created successfully!');
            return true;
        } catch (PDOException $e) {
            $io->error([
                'Failed to create database:',
                $e->getMessage(),
                '',
                'Please ensure:',
                '- MySQL server is running',
                '- Database credentials are correct',
                '- User has permission to create databases',
            ]);
            return false;
        }
    }

    private function displayManualChecklist(SymfonyStyle $io): void
    {
        $io->section('📋 Manual Setup Checklist');

        $io->text([
            'Please complete the following steps manually:',
            '',
        ]);

        $steps = [
            '1. Edit the .env file in the project root',
            '   Set: BUILD_DATABASE=true',
            '',
            '2. Start the application to trigger table creation:',
            '   Run: ./yii serve',
            '   Or visit your web server URL',
            '',
            '3. After tables are created, reset the BUILD_DATABASE setting:',
            '   Edit .env and set: BUILD_DATABASE=false',
            '   (This improves performance by disabling schema rebuilding)',
            '',
            '4. Create your first admin user by visiting the signup page',
            '   The first user will automatically get admin privileges',
            '',
        ];

        foreach ($steps as $step) {
            if (empty($step)) {
                $io->text('');
            } elseif (str_starts_with($step, '   ')) {
                $io->text('<comment>' . $step . '</comment>');
            } else {
                $io->text('<info>' . $step . '</info>');
            }
        }

        $io->warning([
            'IMPORTANT: Remember to set BUILD_DATABASE=false after initial setup!',
            'Leaving it as true will impact application performance.',
        ]);
    }
}
