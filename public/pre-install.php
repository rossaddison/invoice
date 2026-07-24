<?php

declare(strict_types=1);

/**
 * Standalone pre-install step — runs entirely outside Yii3's bootstrap.
 *
 * The app's DI container eagerly builds the full Cycle ORM schema while the
 * container itself is being constructed (yii-cycle's RepositoryContainer
 * resolves ORMInterface in its own constructor, registered as a container
 * delegate) — this happens on every request, before any route runs, and
 * requires the configured database to already exist on the server. On a
 * genuinely fresh install that database doesn't exist yet, which takes down
 * every route in the app, not just /install.
 *
 * This script's only job: make sure the configured database exists as a
 * schema (even empty) before handing off to the real app at /install, which
 * already handles everything from there (build tables -> signup -> 2FA).
 * Deliberately dependency-minimal: no Yii3 view rendering, no CSRF token (no
 * framework session/middleware is available here), no JavaScript — a plain
 * HTML form POST, same pattern well-known PHP installers (WordPress, phpBB)
 * use for this exact chicken-and-egg problem.
 */

chdir(dirname(__DIR__));
require dirname(__DIR__) . '/autoload.php';

use App\Install\DatabaseProbe;
use App\Install\EnvFileWriter;
use App\Install\RequirementsConfig;
use Yiisoft\Requirements\RequirementsChecker;

const ENV_PATH = __DIR__ . '/../.env';

/**
 * @return array{host: string, name: string, user: string, password: string}
 */
function envDbDefaults(): array
{
    return [
        'host' => $_ENV['DB_HOST_IP_ADDRESS'] ?? 'localhost',
        'name' => $_ENV['DB_NAME'] ?? 'yii3_i',
        'user' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
    ];
}

function renderPage(string $body): never
{
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Yii3-i &mdash; Pre-Install</title><style>'
        . 'body{font-family:system-ui,Arial,sans-serif;background:#f5f5f5;margin:0;padding:2rem;}'
        . '.card{max-width:520px;margin:0 auto;background:#fff;border:1px solid #ddd;border-radius:8px;padding:1.5rem 2rem;}'
        . 'h1{font-size:1.3rem;} label{display:block;margin-top:0.75rem;font-weight:600;}'
        . 'input{width:100%;padding:0.5rem;margin-top:0.25rem;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;}'
        . 'button{margin-top:1.25rem;padding:0.6rem 1.2rem;background:#0d6efd;color:#fff;border:0;border-radius:4px;cursor:pointer;}'
        . '.step-circle{display:inline-flex;align-items:center;justify-content:center;width:1.8rem;height:1.8rem;border-radius:50%;'
        . 'background:#0d6efd;color:#fff;font-weight:bold;margin-right:0.5rem;}'
        . 'table{width:100%;border-collapse:collapse;margin-top:0.5rem;} td,th{padding:0.3rem 0.4rem;border-bottom:1px solid #eee;text-align:left;font-size:0.9rem;}'
        . '.fail{color:#b00020;font-weight:bold;} .ok{color:#1a7f37;}'
        . '.error{background:#fdecea;border:1px solid #f5c2c0;color:#611a15;padding:0.6rem 0.8rem;border-radius:4px;margin-top:1rem;}'
        . '</style></head><body><div class="card">' . $body . '</div></body></html>';
    exit;
}

/**
 * @return array{requirements: list<array{name: string, mandatory: bool, condition: bool, by: string, memo: string, error: bool, warning: bool}>, errors: int}
 */
function checkRequirements(): array
{
    $checker = new RequirementsChecker();
    /** @var array{summary: array{errors: int}, requirements: list<array{name: string, mandatory: bool, condition: bool, by: string, memo: string, error: bool, warning: bool}>} $result */
    $result = $checker->check(RequirementsConfig::checks())->getResult();
    return ['requirements' => $result['requirements'], 'errors' => $result['summary']['errors']];
}

/**
 * @param list<array{name: string, mandatory: bool, condition: bool, by: string, memo: string, error: bool, warning: bool}> $requirements
 */
function requirementsTableHtml(array $requirements): string
{
    $rows = '';
    foreach ($requirements as $requirement) {
        $status = $requirement['condition'] ? '<span class="ok">&check; OK</span>' : '<span class="fail">&cross; FAIL</span>';
        $rows .= '<tr><td>' . htmlspecialchars($requirement['name']) . '</td><td>' . $status . '</td></tr>';
    }
    return '<table><thead><tr><th>Requirement</th><th>Status</th></tr></thead><tbody>' . $rows . '</tbody></table>';
}

function databaseFormHtml(string $host, string $name, string $user): string
{
    return '<form method="post">'
        . '<label for="db_host">Host</label><input type="text" id="db_host" name="db_host" value="' . htmlspecialchars($host) . '">'
        . '<label for="db_name">Database name</label><input type="text" id="db_name" name="db_name" value="' . htmlspecialchars($name) . '">'
        . '<label for="db_user">Username</label><input type="text" id="db_user" name="db_user" value="' . htmlspecialchars($user) . '">'
        . '<label for="db_password">Password</label><input type="password" id="db_password" name="db_password" autocomplete="new-password">'
        . '<button type="submit">Create Database &amp; Continue &rarr;</button>'
        . '</form>';
}

function postString(string $key, string $default): string
{
    $value = $_POST[$key] ?? null;
    return is_string($value) ? $value : $default;
}

// --- Execution starts here ---

$requirementsResult = checkRequirements();

if ($requirementsResult['errors'] > 0) {
    renderPage(
        '<h1><span class="step-circle">1</span>Requirements</h1>'
        . '<p>Resolve the following before continuing:</p>'
        . requirementsTableHtml($requirementsResult['requirements']),
    );
}

$dbProbe = new DatabaseProbe();
$defaults = envDbDefaults();

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($requestMethod === 'POST') {
    $host = postString('db_host', $defaults['host']);
    $name = postString('db_name', $defaults['name']);
    $user = postString('db_user', $defaults['user']);
    $password = postString('db_password', '');

    $result = $dbProbe->testAndCreate($host, $name, $user, $password !== '' ? $password : null);

    if ($result['success']) {
        new EnvFileWriter()->write(ENV_PATH, [
            'DB_HOST_IP_ADDRESS' => $host,
            'DB_NAME' => $name,
            'DB_USERNAME' => $user,
            'DB_PASSWORD' => $password,
        ]);
        header('Location: /install');
        exit;
    }

    renderPage(
        '<h1><span class="step-circle">2</span>Database</h1>'
        . '<div class="error">' . htmlspecialchars($result['message']) . '</div>'
        . databaseFormHtml($host, $name, $user),
    );
}

try {
    $serverPdo = $dbProbe->connectToServer(
        $defaults['host'],
        $defaults['user'],
        $defaults['password'] !== '' ? $defaults['password'] : null,
    );
    if ($dbProbe->databaseExists($serverPdo, $defaults['name'])) {
        header('Location: /install');
        exit;
    }
} catch (\Throwable) {
    // Server unreachable or credentials wrong — fall through to the form below.
}

renderPage(
    '<h1><span class="step-circle">2</span>Database</h1>'
    . '<p>Enter your MySQL connection details. The database will be created automatically if it doesn&rsquo;t exist yet.</p>'
    . databaseFormHtml($defaults['host'], $defaults['name'], $defaults['user']),
);
