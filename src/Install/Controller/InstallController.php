<?php

declare(strict_types=1);

namespace App\Install\Controller;

use App\Install\DatabaseProbe;
use App\Install\EnvFileWriter;
use App\Install\RequirementsConfig;
use App\Service\WebControllerService;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Json\Json;
use Yiisoft\Requirements\RequirementsChecker;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Web-based install wizard: requirements check -> database configuration ->
 * table build -> handoff to the existing /signup and /login flows.
 *
 * Deliberately has no Cycle-ORM/DI-backed dependency in its constructor —
 * every database check is done via raw PDO ({@see DatabaseProbe}) so this
 * controller can render safely even when the database isn't configured,
 * reachable, or built yet.
 */
final class InstallController
{
    private const string ENV_PATH = __DIR__ . '/../../../.env';

    private WebViewRenderer $webViewRenderer;

    public function __construct(
        WebViewRenderer $webViewRenderer,
        private readonly WebControllerService $webService,
        private readonly DataResponseFactoryInterface $factory,
        private readonly DatabaseProbe $dbProbe,
        private readonly EnvFileWriter $envWriter,
    ) {
        $this->webViewRenderer = $webViewRenderer->withControllerName('install');
    }

    public function index(): ResponseInterface
    {
        $state = $this->determineState();

        if ($state['step'] === 'already-installed') {
            $this->disableBuildDatabaseIfStillOn();
            return $this->webService->getRedirectResponse('auth/login', ['_language' => 'en']);
        }

        if ($state['step'] === 'handoff') {
            // Tables can end up built as a side effect of any page render once
            // BUILD_DATABASE=true (Cycle resolves its schema for whichever
            // repository a globally-registered ViewInjection needs) — not only
            // via the dedicated "Build Tables" button. Catch that here too, or
            // BUILD_DATABASE could stay stuck on indefinitely.
            $this->disableBuildDatabaseIfStillOn();
            return $this->webViewRenderer->render('index', [
                'currentStep' => 'handoff',
                'requirements' => $state['requirements'],
                'summary' => $state['summary'],
            ]);
        }

        $this->ensureCookieSecretKey();
        $dbConfig = $this->readEnvDbConfig();

        return $this->webViewRenderer->render('index', [
            'currentStep' => $state['step'],
            'requirements' => $state['requirements'],
            'summary' => $state['summary'],
            'dbHost' => $dbConfig['host'],
            'dbName' => $dbConfig['name'],
            'dbUser' => $dbConfig['user'],
        ]);
    }

    public function testConnection(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->isAlreadyInstalled()) {
            return $this->jsonResponse(['success' => false, 'message' => 'Already installed.']);
        }

        $formConfig = $this->readFormDbConfig($request);
        $result = $this->dbProbe->testAndCreate(
            $formConfig['host'],
            $formConfig['name'],
            $formConfig['user'],
            $formConfig['password'],
        );

        return $this->jsonResponse($result);
    }

    public function saveDatabase(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->isAlreadyInstalled()) {
            return $this->webService->getRedirectResponse('auth/login', ['_language' => 'en']);
        }

        $formConfig = $this->readFormDbConfig($request);
        $result = $this->dbProbe->testAndCreate(
            $formConfig['host'],
            $formConfig['name'],
            $formConfig['user'],
            $formConfig['password'],
        );
        if (!$result['success']) {
            return $this->webService->getRedirectResponse('install/index', ['_language' => 'en']);
        }

        $this->envWriter->write(self::ENV_PATH, [
            'DB_HOST_IP_ADDRESS' => $formConfig['host'],
            'DB_NAME' => $formConfig['name'],
            'DB_USERNAME' => $formConfig['user'],
            'DB_PASSWORD' => $formConfig['password'] ?? '',
            'BUILD_DATABASE' => 'true',
        ]);

        return $this->webService->getRedirectResponse('install/index', ['_language' => 'en']);
    }

    public function buildTables(): ResponseInterface
    {
        if ($this->isAlreadyInstalled()) {
            return $this->jsonResponse(['success' => false, 'message' => 'Already installed.']);
        }

        $dbConfig = $this->readEnvDbConfig();

        try {
            $pdo = $this->dbProbe->connectToDatabase(
                $dbConfig['host'],
                $dbConfig['name'],
                $dbConfig['user'],
                $dbConfig['password'],
            );
        } catch (PDOException $e) {
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }

        if (!$this->dbProbe->tableExists($pdo, 'user')) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Tables were not created. Reload the app once, then try again.',
            ]);
        }

        $this->disableBuildDatabaseIfStillOn();

        return $this->jsonResponse(['success' => true, 'message' => 'Database tables are ready.']);
    }

    /**
     * @return array{step: string, requirements: list<array{name: string, mandatory: bool, condition: bool, by: string, memo: string, error: bool, warning: bool}>, summary: array{total: int, errors: int, warnings: int}}
     */
    private function determineState(): array
    {
        $checker = new RequirementsChecker();
        /** @var array{summary: array{total: int, errors: int, warnings: int}, requirements: list<array{name: string, mandatory: bool, condition: bool, by: string, memo: string, error: bool, warning: bool}>} $result */
        $result = $checker->check(RequirementsConfig::checks())->getResult();

        if ($result['summary']['errors'] > 0) {
            return ['step' => 'requirements', 'requirements' => $result['requirements'], 'summary' => $result['summary']];
        }

        $dbConfig = $this->readEnvDbConfig();

        try {
            $pdo = $this->dbProbe->connectToDatabase(
                $dbConfig['host'],
                $dbConfig['name'],
                $dbConfig['user'],
                $dbConfig['password'],
            );
        } catch (PDOException) {
            return ['step' => 'database', 'requirements' => $result['requirements'], 'summary' => $result['summary']];
        }

        if (!$this->dbProbe->tableExists($pdo, 'user')) {
            return ['step' => 'build', 'requirements' => $result['requirements'], 'summary' => $result['summary']];
        }

        if ($this->dbProbe->countRows($pdo, 'user') === 0) {
            return ['step' => 'handoff', 'requirements' => $result['requirements'], 'summary' => $result['summary']];
        }

        return ['step' => 'already-installed', 'requirements' => $result['requirements'], 'summary' => $result['summary']];
    }

    private function isAlreadyInstalled(): bool
    {
        return $this->determineState()['step'] === 'already-installed';
    }

    /**
     * @return array{host: string, name: string, user: string, password: ?string}
     */
    private function readEnvDbConfig(): array
    {
        $password = $_ENV['DB_PASSWORD'] ?? '';

        return [
            'host' => $_ENV['DB_HOST_IP_ADDRESS'] ?? 'localhost',
            'name' => $_ENV['DB_NAME'] ?? 'yii3_i',
            'user' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $password !== '' ? $password : null,
        ];
    }

    /**
     * @return array{host: string, name: string, user: string, password: ?string}
     */
    private function readFormDbConfig(ServerRequestInterface $request): array
    {
        $body = (array) $request->getParsedBody();
        /** @var array{dbHost?: string, dbName?: string, dbUser?: string, dbPassword?: string} $install */
        $install = (array) ($body['Install'] ?? []);
        $password = $install['dbPassword'] ?? '';

        return [
            'host' => $install['dbHost'] ?? 'localhost',
            'name' => $install['dbName'] ?? 'yii3_i',
            'user' => $install['dbUser'] ?? 'root',
            'password' => $password !== '' ? $password : null,
        ];
    }

    private function disableBuildDatabaseIfStillOn(): void
    {
        // autoload.php normalizes $_ENV['BUILD_DATABASE'] to an actual bool
        // via filter_var() — it is never the literal string 'true' here.
        if ((bool) ($_ENV['BUILD_DATABASE'] ?? false)) {
            $this->envWriter->write(self::ENV_PATH, ['BUILD_DATABASE' => 'false']);
            $_ENV['BUILD_DATABASE'] = false;
        }
    }

    private function ensureCookieSecretKey(): void
    {
        if (($_ENV['COOKIE_SECRET_KEY'] ?? '') !== '') {
            return;
        }
        $key = bin2hex(random_bytes(32));
        $this->envWriter->write(self::ENV_PATH, ['COOKIE_SECRET_KEY' => $key]);
        $_ENV['COOKIE_SECRET_KEY'] = $key;
    }

    /**
     * @param array{success: bool, message: string} $data
     */
    private function jsonResponse(array $data): ResponseInterface
    {
        return $this->factory->createResponse(Json::encode($data));
    }
}
