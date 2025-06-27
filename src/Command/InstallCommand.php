<?php

declare(strict_types=1);

namespace App\Command;

use PDO;
use PDOException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

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
        $this
            ->setDescription('Install and setup the invoice application')
            ->setHelp('This command sets up the application by creating necessary directories, copying configuration files, and initializing the database.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force installation even if already installed')
            ->addOption('db-host', null, InputOption::VALUE_REQUIRED, 'Database host', 'localhost')
            ->addOption('db-name', null, InputOption::VALUE_REQUIRED, 'Database name', 'invoice')
            ->addOption('db-user', null, InputOption::VALUE_REQUIRED, 'Database user', 'root')
            ->addOption('db-password', null, InputOption::VALUE_OPTIONAL, 'Database password', '');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $force = (bool) $input->getOption('force');
        $dbHost = $this->getStringOption($input, 'db-host');
        $dbName = $this->getStringOption($input, 'db-name');
        $dbUser = $this->getStringOption($input, 'db-user');
        $dbPassword = $this->getStringOption($input, 'db-password');

        $io->title('Invoice Application Installation');

        try {
            // Check if already installed
            if (!$force && $this->isAlreadyInstalled()) {
                $io->warning('Application appears to be already installed. Use --force to reinstall.');
                return ExitCode::OK;
            }

            // Create necessary directories
            $this->createDirectories($io);

            // Copy environment file
            $this->copyEnvironmentFile($io, $force);

            // Set permissions
            $this->setPermissions($io);

            // Test database connection
            $this->testDatabaseConnection($io, $dbHost, $dbName, $dbUser, $dbPassword);

            // Execute shell commands for setup
            $this->executeSetupCommands($io);

            $io->success('Installation completed successfully!');
            return ExitCode::OK;

        } catch (RuntimeException $e) {
            $io->error('Installation failed: ' . $e->getMessage());
            return ExitCode::SOFTWARE;
        } catch (PDOException $e) {
            $io->error('Database connection failed: ' . $e->getMessage());
            return ExitCode::CONFIG;
        }
    }

    private function getStringOption(InputInterface $input, string $name): string
    {
        $value = $input->getOption($name);
        if ($value === null) {
            return '';
        }
        return (string) $value;
    }

    private function isAlreadyInstalled(): bool
    {
        return file_exists('.env') && file_exists('runtime/install.lock');
    }

    /**
     * @throws RuntimeException
     */
    private function createDirectories(SymfonyStyle $io): void
    {
        $directories = [
            'runtime',
            'runtime/cache',
            'runtime/logs',
            'public/assets',
            'private/uploads'
        ];

        foreach ($directories as $dir) {
            $directoryPath = (string) $dir;
            if (!is_dir($directoryPath)) {
                $result = mkdir($directoryPath, 0755, true);
                if ($result === false) {
                    throw new RuntimeException(sprintf('Failed to create directory: %s', $directoryPath));
                }
                $io->writeln(sprintf('Created directory: %s', $directoryPath));
            }
        }
    }

    /**
     * @throws RuntimeException
     */
    private function copyEnvironmentFile(SymfonyStyle $io, bool $force): void
    {
        if (!file_exists('.env') || $force) {
            if (!file_exists('.env.example')) {
                throw new RuntimeException('.env.example file not found');
            }

            $content = file_get_contents('.env.example');
            if ($content === false) {
                throw new RuntimeException('Failed to read .env.example file');
            }

            $result = file_put_contents('.env', $content);
            if ($result === false) {
                throw new RuntimeException('Failed to create .env file');
            }

            $io->writeln('Copied .env.example to .env');
        }
    }

    /**
     * @throws RuntimeException
     */
    private function setPermissions(SymfonyStyle $io): void
    {
        /** @var array<string, int> $paths */
        $paths = [
            'runtime' => 0777,
            'public/assets' => 0777,
            'private/uploads' => 0755
        ];

        foreach ($paths as $path => $mode) {
            $pathString = (string) $path;
            $modeInt = (int) $mode;
            
            if (file_exists($pathString)) {
                $result = chmod($pathString, $modeInt);
                if ($result === false) {
                    throw new RuntimeException(sprintf('Failed to set permissions for: %s', $pathString));
                }
                $io->writeln(sprintf('Set permissions %o for: %s', $modeInt, $pathString));
            }
        }
    }

    /**
     * @throws PDOException
     */
    private function testDatabaseConnection(SymfonyStyle $io, string $host, string $dbName, string $user, string $password): void
    {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', (string) $host, (string) $dbName);
        
        $pdo = new PDO($dsn, (string) $user, (string) $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Test connection with a simple query
        $stmt = $pdo->prepare('SELECT 1 as test');
        if ($stmt === false) {
            throw new PDOException('Failed to prepare test query');
        }
        
        $executeResult = $stmt->execute();
        if ($executeResult === false) {
            throw new PDOException('Failed to execute test query');
        }
        
        $result = $stmt->fetch();
        
        if ($result === false || !is_array($result) || !isset($result['test'])) {
            throw new PDOException('Database connection test failed');
        }

        $io->writeln('Database connection test successful');
    }

    /**
     * @throws RuntimeException
     */
    private function executeSetupCommands(SymfonyStyle $io): void
    {
        $commands = [
            'php yii migrate/up --interactive=0' => 'Running database migrations',
            'php yii cache/flush' => 'Flushing cache',
        ];

        foreach ($commands as $command => $description) {
            $io->writeln((string) $description);
            
            $commandString = (string) $command;
            /** @var array<int, string> $output */
            $output = [];
            $returnVar = 0;
            
            exec($commandString, $output, $returnVar);
            
            if ($returnVar !== 0) {
                $errorMessage = sprintf('Command failed: %s (exit code: %d)', $commandString, $returnVar);
                if (count($output) > 0) {
                    $outputString = implode("\n", array_map(
                        static fn (string $line): string => (string) $line,
                        $output
                    ));
                    $errorMessage .= sprintf("\nOutput: %s", $outputString);
                }
                throw new RuntimeException($errorMessage);
            }
        }

        // Create install lock file
        $lockContent = date('Y-m-d H:i:s');
        if ($lockContent === false) {
            throw new RuntimeException('Failed to generate lock file content');
        }
        
        $result = file_put_contents('runtime/install.lock', $lockContent);
        if ($result === false) {
            throw new RuntimeException('Failed to create install lock file');
        }
    }
}