<?php
declare(strict_types=1);

// Let the PHP built-in server serve real static files (CSS, SVG, JS, etc.) directly.
// Returning false tells the server to fall back to its own static-file handler.
if (PHP_SAPI === 'cli-server') {
    $reqPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
    if ($reqPath !== '/' && is_file(__DIR__ . $reqPath)) {
        return false;
    }
}

session_start();
set_time_limit(0);
chdir(__DIR__);

// ── Token-only endpoints (called before command registry) ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['set_token'])) {
        $t = trim($_POST['sonar_token'] ?? '');
        if ($t !== '') {
            $_SESSION['sonar_token'] = $t;
        }
        header('Content-Type: text/plain');
        echo 'ok';
        exit;
    }
    if (isset($_GET['clear_token'])) {
        unset($_SESSION['sonar_token']);
        header('Content-Type: text/plain');
        echo 'ok';
        exit;
    }
    if (isset($_GET['set_gh_token'])) {
        $t = trim($_POST['github_token'] ?? '');
        $_SESSION['github_token'] = $t;
        header('Content-Type: text/plain');
        echo 'ok';
        exit;
    }
}

// ── Vulnerability Log (SQLite) ────────────────────────────────────────────────
function sqliteAvailable(): bool
{
    return in_array('sqlite', PDO::getAvailableDrivers(), true);
}

function getVulnDb(): PDO
{
    static $db = null;
    if ($db !== null) {
        return $db;
    }
    $db = new PDO('sqlite:' . __DIR__ . '/snyk-resolved.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS vuln_resolved (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            snyk_id       TEXT    NOT NULL DEFAULT '',
            title         TEXT    NOT NULL DEFAULT '',
            severity      TEXT    NOT NULL DEFAULT 'MEDIUM',
            category      TEXT    NOT NULL DEFAULT '',
            file_path     TEXT    NOT NULL DEFAULT '',
            reason        TEXT    NOT NULL DEFAULT '',
            advisory_url  TEXT    NOT NULL DEFAULT '',
            false_pos     INTEGER NOT NULL DEFAULT 0,
            ai_related    INTEGER NOT NULL DEFAULT 0,
            threat_vec    TEXT    NOT NULL DEFAULT '',
            resolved_date TEXT    NOT NULL DEFAULT (date('now')),
            created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
        )
    SQL);
    // Seed from .snyk policy entries on first run
    if ((int) $db->query('SELECT COUNT(*) FROM vuln_resolved')->fetchColumn() === 0) {
        seedVulnDb($db);
    }
    return $db;
}

function seedVulnDb(PDO $db): void
{
    // Pre-populated from .snyk ignore list — advisory_url uses CWE references
    $seed = [
        ['SNYK-CODE-09045b5d-96a4-401a-87d4-95b6ac56af1a',
         'Country code data flagged as hardcoded password',
         'MEDIUM','False Positive — Hardcoded Secret',
         'src/Invoice/Helpers/Country-list/ar/country.php',
         'ISO 3166-1 alpha-2 country codes in legitimate reference data, not actual passwords',
         'https://cwe.mitre.org/data/definitions/798.html', 1, 0, 'Hardcoded Secrets'],
        ['SNYK-CODE-ce50f6ff-6712-414c-9c98-0364f1e6fe44',
         'MD5 use flagged — Peppol SML DNS lookup spec',
         'MEDIUM','False Positive — Weak Hash',
         'src/Invoice/Peppol/SmpResolver.php',
         'MD5 is required by the Peppol SML DNS spec for participant ID hashing; RFC-compliant, not a security hash',
         'https://cwe.mitre.org/data/definitions/327.html', 1, 0, 'Cryptography — Weak Algorithm'],
        ['SNYK-CODE-df6503fc-3e50-4b9f-b496-f7fb03985431',
         'XXE risk in loadXML — Peppol trait (1 of 2)',
         'HIGH','False Positive — XXE',
         'src/Invoice/Inv/Trait/Peppol.php',
         'PHP 8.0+ disables external entity loading by default; project minimum is PHP 8.4',
         'https://cwe.mitre.org/data/definitions/611.html', 1, 0, 'XXE — XML External Entity'],
        ['SNYK-CODE-88498c5a-ad8f-4904-a307-fb408293928d',
         'XXE risk in loadXML — Peppol trait (2 of 2)',
         'HIGH','False Positive — XXE',
         'src/Invoice/Inv/Trait/Peppol.php',
         'PHP 8.0+ disables external entity loading by default; project minimum is PHP 8.4',
         'https://cwe.mitre.org/data/definitions/611.html', 1, 0, 'XXE — XML External Entity'],
        ['SNYK-CODE-ac3f0ded-f59b-4a8e-8fff-4fe5e073f542',
         'Hardcoded credential — unit test fixture',
         'LOW','False Positive — Test Fixture',
         'Tests/Unit/Invoice/Entity/UserEntityTest.php',
         "'newlogin' is an assertSame fixture value, not a production credential",
         'https://cwe.mitre.org/data/definitions/798.html', 1, 0, 'Hardcoded Secrets'],
        ['SNYK-CODE-5f593a3f-ee1f-486b-ab02-e1b5ea879747',
         'XSS — exception echo in CLI benchmark script',
         'MEDIUM','False Positive — CLI Tool',
         'benchmarks/run.php',
         'CLI script; exception message echoed to terminal stdout, never to a browser',
         'https://cwe.mitre.org/data/definitions/79.html', 1, 0, 'XSS — Cross-Site Scripting'],
        ['SNYK-CODE-816cf0ce-bb64-4b3c-8a4d-2dac8f88e723',
         'XSS — error echo in CLI benchmark script',
         'MEDIUM','False Positive — CLI Tool',
         'benchmarks/run.php',
         'CLI script; error string echoed to terminal stdout, never to a browser',
         'https://cwe.mitre.org/data/definitions/79.html', 1, 0, 'XSS — Cross-Site Scripting'],
        ['SNYK-CODE-b8c80a7c-dfc0-4e89-9b8b-4b09dfe4341d',
         'XSS in sonar-issues.php CLI tool (1 of 3)',
         'MEDIUM','False Positive — CLI Tool',
         'sonar-issues.php',
         'CLI developer tool; output to terminal stdout, not rendered as HTML',
         'https://cwe.mitre.org/data/definitions/79.html', 1, 0, 'XSS — Cross-Site Scripting'],
        ['SNYK-CODE-3b503f93-bacc-45e5-94fb-f57a7a74ee91',
         'XSS in sonar-issues.php CLI tool (2 of 3)',
         'MEDIUM','False Positive — CLI Tool',
         'sonar-issues.php',
         'CLI developer tool; output to terminal stdout, not rendered as HTML',
         'https://cwe.mitre.org/data/definitions/79.html', 1, 0, 'XSS — Cross-Site Scripting'],
        ['SNYK-CODE-63aacd20-88fc-4892-a2ac-86b37eea41b6',
         'XSS in sonar-issues.php CLI tool (3 of 3)',
         'MEDIUM','False Positive — CLI Tool',
         'sonar-issues.php',
         'CLI developer tool; output to terminal stdout, not rendered as HTML',
         'https://cwe.mitre.org/data/definitions/79.html', 1, 0, 'XSS — Cross-Site Scripting'],
        ['SNYK-CODE-11be5bd0-027f-4aea-8377-27b0d6192d5c',
         'XSS — binary PDF echoed via file_get_contents',
         'MEDIUM','False Positive — Content-Disposition',
         'src/Invoice/Inv/Trait/PdfTrait.php',
         'PDF binary served with Content-Disposition: attachment and Content-Type: application/pdf; cannot execute as HTML',
         'https://cwe.mitre.org/data/definitions/79.html', 1, 0, 'XSS — Cross-Site Scripting'],
        ['SNYK-CODE-0311cb06-61c1-4ec4-a45c-7e06061d896f',
         'Hardcoded secret — Peppol postal address config key',
         'LOW','False Positive — Config Key Name',
         'src/Invoice/Helpers/Peppol/PeppolHelper.php',
         "'SupplierPartyIdentificationPostalAddress' is an array key name for a postal address structure, not a cryptographic secret",
         'https://cwe.mitre.org/data/definitions/798.html', 1, 0, 'Hardcoded Secrets'],
        ['GH-114-web-token-jwt-framework',
         'JWSVerifier algorithm confusion via unprotected header (web-token/jwt-framework <=4.2.99)',
         'HIGH','False Positive — Transitive Dependency, Unreachable Code',
         'composer.lock',
         'Transitive dep via rossaddison/yii-auth-client; project uses phpseclib3 directly in GovUk.php — JWSVerifier and JWEDecrypter are never instantiated; no attack vector reachable. No patched version as at June 2026. Update snyk_id once Snyk assigns a SNYK-PHP-xxx ID.',
         'https://cwe.mitre.org/data/definitions/290.html', 1, 0, 'Algorithm Confusion — JWT JWS/JWE'],
        ['GHSA-3prj-6hqw-cm82',
         'PBES2 p2c unbounded iteration count — CPU-amplification DoS (web-token/jwt-framework)',
         'HIGH','Resolved — Fixed in installed version 4.1.7',
         'composer.lock',
         'Affected versions <= 4.1.6. Installed version is 4.1.7, which already contains the fix: DEFAULT_MAX_COUNT = 1_000_000 constant and p2c > max_count guard in PBES2AESKW::checkHeaderAdditionalParameters(). Additionally, the project never registers PBES2 algorithms — GovUk.php uses phpseclib3 directly. Both the installed vendor and the 4.2.x upstream branch contain the fix; no PR required.',
         'https://cwe.mitre.org/data/definitions/400.html', 0, 0, 'Uncontrolled Resource Consumption — PBKDF2 DoS'],
    ];
    $stmt = $db->prepare(
        'INSERT OR IGNORE INTO vuln_resolved
         (snyk_id,title,severity,category,file_path,reason,advisory_url,false_pos,ai_related,threat_vec)
         VALUES (?,?,?,?,?,?,?,?,?,?)'
    );
    foreach ($seed as $row) {
        $stmt->execute($row);
    }
}

// CRUD handlers for vulnerability log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['add_vuln'])) {
    if (!sqliteAvailable()) { header('Location: ?menu=snyk_resolved'); exit; }
    $db = getVulnDb();
    $db->prepare(
        'INSERT INTO vuln_resolved
         (snyk_id,title,severity,category,file_path,reason,advisory_url,false_pos,ai_related,threat_vec)
         VALUES (:snyk_id,:title,:severity,:category,:file_path,:reason,:advisory_url,:false_pos,:ai_related,:threat_vec)'
    )->execute([
        'snyk_id'      => trim($_POST['snyk_id']      ?? ''),
        'title'        => trim($_POST['title']        ?? ''),
        'severity'     => trim($_POST['severity']     ?? 'MEDIUM'),
        'category'     => trim($_POST['category']     ?? ''),
        'file_path'    => trim($_POST['file_path']    ?? ''),
        'reason'       => trim($_POST['reason']       ?? ''),
        'advisory_url' => trim($_POST['advisory_url'] ?? ''),
        'false_pos'    => isset($_POST['false_pos'])  ? 1 : 0,
        'ai_related'   => isset($_POST['ai_related']) ? 1 : 0,
        'threat_vec'   => trim($_POST['threat_vec']   ?? ''),
    ]);
    header('Location: ?menu=snyk_resolved');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['del_vuln'])) {
    if (!sqliteAvailable()) { header('Location: ?menu=snyk_resolved'); exit; }
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        getVulnDb()->prepare('DELETE FROM vuln_resolved WHERE id = ?')->execute([$id]);
    }
    header('Location: ?menu=snyk_resolved');
    exit;
}

// ── Command registry ──────────────────────────────────────────────────────────
// cmd     = shell template; {key} placeholders replaced with form inputs
// params  = ['key' => 'label shown in form']
// confirm = non-null triggers JS confirm() before running
// bg      = true → spawn detached window, don't capture output
// open    = URL to mention in bg output
// env     = env var names to inject from session
// filter  = special post-processing key

$CMDS = [
    // Psalm
    'psalm_full'        => ['cmd' => 'php vendor/bin/psalm --force-jit'],
    'psalm_file'        => ['cmd' => 'php vendor/bin/psalm {file}',
                            'params' => ['file' => 'File path (e.g. src/Invoice/Inv/InvController.php)']],
    'psalm_dir'         => ['cmd' => 'php vendor/bin/psalm {dir}',
                            'params' => ['dir' => 'Directory path (e.g. src/Invoice/Inv/)']],
    'psalm_cache'       => ['cmd' => 'php vendor/bin/psalm --clear-cache'],
    'psalm_info'        => ['cmd' => 'php vendor/bin/psalm --show-info'],

    // Composer
    'comp_outdated'     => ['cmd' => 'composer outdated --ansi'],
    'comp_whynot'       => ['cmd' => 'composer why-not {package} {version}',
                            'params' => ['package' => 'Package (e.g. vendor/package)', 'version' => 'Version (e.g. ^1.0)']],
    'comp_cache_lock'   => ['cmd' => 'composer clear-cache --ansi && composer update --lock --ansi'],
    'comp_validate'     => ['cmd' => 'composer validate --ansi --strict'],
    'comp_dump'         => ['cmd' => 'composer dump-autoload -o --ansi'],
    'comp_audit'        => ['cmd' => 'composer audit --ansi'],
    'comp_update'       => ['cmd' => 'composer update --ansi'],
    'comp_req_check'    => ['cmd' => 'php -d memory_limit=512M vendor/bin/composer-require-checker'],

    // Node
    'node_ncu'          => ['cmd' => 'npx npm-check-updates -u && npm install'],
    'node_nvm'          => ['cmd' => 'echo Download nvm-windows from: https://github.com/coreybutler/nvm-windows/releases'],
    'node_audit'        => ['cmd' => 'npm audit && npm cache clean --force && npm list --depth=0'],
    'node_outdated'     => ['cmd' => 'npm run upgrade:check'],
    'node_safe'         => ['cmd' => 'npm run upgrade:safe'],
    'node_minor'        => ['cmd' => 'npm run upgrade:minor'],
    'node_major'        => ['cmd' => 'npm run upgrade:major'],
    'node_es2024'       => ['cmd' => 'npm run es2024:verify'],
    'node_build'        => ['cmd' => 'npm run build'],

    // TypeScript
    'ts_prod'           => ['cmd' => 'npm run build:prod'],
    'ts_dev'            => ['cmd' => 'npm run build:dev'],
    'ts_watch'          => ['cmd' => 'start cmd /k npm run build:watch', 'bg' => true],
    'ts_check'          => ['cmd' => 'npm run type-check'],
    'ts_lint'           => ['cmd' => 'npm run lint'],
    'ts_format'         => ['cmd' => 'npm run format:check && npm run format'],

    // Angular
    'ang_install'       => ['cmd' => 'npm install',
                            'confirm' => 'This may modify TypeScript/ESLint config. Continue?'],
    'ang_serve'         => ['cmd' => 'start cmd /k npm run ng:serve', 'bg' => true,
                            'open' => 'http://localhost:4200'],
    'ang_build'         => ['cmd' => 'npm run ng:build'],
    'ang_gen'           => ['cmd' => 'npm run angular:generate-component {name}',
                            'params' => ['name' => 'Component name (e.g. dashboard)']],
    'ang_lint'          => ['cmd' => 'npm run lint:angular'],

    // Testing
    'test_entity'       => ['cmd' => 'php vendor/bin/phpunit Tests/Unit/Invoice/Entity/ --no-coverage --testdox --colors=always'],
    'test_unit'         => ['cmd' => 'php vendor/bin/phpunit Tests/Unit/ --no-coverage --testdox --colors=always'],
    'test_func'         => ['cmd' => 'php vendor/bin/phpunit Tests/Functional/ Tests/Integration/ Tests/PHPUnit/ --no-coverage --testdox --colors=always'],
    'test_cc_func'      => ['cmd' => 'php vendor/bin/codecept run Functional'],
    'test_cc_acc'       => ['cmd' => 'php vendor/bin/codecept run Acceptance'],
    'test_cc_all'       => ['cmd' => 'php vendor/bin/codecept run'],

    // PHP-CS-Fixer
    'fixer_dry'         => ['cmd' => 'php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --dry-run --show-progress=bar --verbose --ansi'],
    'fixer_fix'         => ['cmd' => 'php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --ansi'],

    // PHPCS
    'phpcs_full'        => ['cmd' => 'php vendor/bin/phpcs -d memory_limit=1024M --standard=phpcs.xml.dist --colors'],
    'phpcs_file'        => ['cmd' => 'php vendor/bin/phpcs -d memory_limit=1024M --standard=Generic --sniffs=Generic.Files.LineLength --runtime-set lineLimit 85 --runtime-set absoluteLineLimit 85 --colors {file}',
                            'params' => ['file' => 'File path (e.g. src/Invoice/Invoice.php)']],
    'phpcs_dir'         => ['cmd' => 'php vendor/bin/phpcs -d memory_limit=1024M --standard=Generic --sniffs=Generic.Files.LineLength --runtime-set lineLimit 85 --runtime-set absoluteLineLimit 85 --colors {dir}',
                            'params' => ['dir' => 'Directory path (e.g. src/Invoice/)']],
    'phpcs_report'      => ['cmd' => 'php vendor/bin/phpcs -d memory_limit=1024M --standard=phpcs.xml.dist --report=full --report-width=120 --colors'],

    // Rector
    'rector_dry'        => ['cmd' => 'php vendor/bin/rector process --dry-run --output-format=console --ansi'],
    'rector_apply'      => ['cmd' => 'php vendor/bin/rector --ansi'],

    // SonarCloud
    'sonar_all'         => ['cmd' => 'php sonar-issues.php',                              'env' => ['SONAR_TOKEN']],
    'sonar_pr'          => ['cmd' => 'php sonar-issues.php --pr={pr}',                    'env' => ['SONAR_TOKEN'], 'params' => ['pr' => 'PR number']],
    'sonar_type'        => ['cmd' => 'php sonar-issues.php --type={type}',                'env' => ['SONAR_TOKEN'], 'params' => ['type' => 'BUG / VULNERABILITY / CODE_SMELL']],
    'sonar_sev'         => ['cmd' => 'php sonar-issues.php --severity={sev}',             'env' => ['SONAR_TOKEN'], 'params' => ['sev' => 'BLOCKER / CRITICAL / MAJOR / MINOR / INFO']],
    'sonar_hotspots'    => ['cmd' => 'php sonar-issues.php --hotspots',                   'env' => ['SONAR_TOKEN']],
    'sonar_combined'    => ['cmd' => 'php sonar-issues.php --type={type} --severity={sev}','env' => ['SONAR_TOKEN'], 'params' => ['type' => 'Type', 'sev' => 'Severity']],
    'sonar_rule'        => ['cmd' => 'php sonar-issues.php --rule={rule}',                'env' => ['SONAR_TOKEN'], 'params' => ['rule' => 'Rule number'], 'paramSelect' => ['rule' => ['php','typescript','javascript','css','xml']], 'paramSelectSuffix' => ['rule' => ':S']],
    'sonar_file'        => ['cmd' => 'php sonar-issues.php --file={file}',                'env' => ['SONAR_TOKEN'], 'params' => ['file' => 'File path (e.g. src/Invoice/Inv/InvController.php)']],
    'sonar_reliability' => ['cmd' => 'php sonar-issues.php --type=BUG',                  'env' => ['SONAR_TOKEN']],
    'sonar_rely_grp'    => ['cmd' => 'php sonar-issues.php --type=BUG --grouped',        'env' => ['SONAR_TOKEN']],
    'sonar_all_grp'     => ['cmd' => 'php sonar-issues.php --grouped',                   'env' => ['SONAR_TOKEN']],
    'sonar_lang'        => ['cmd' => 'php sonar-issues.php --language={lang}',            'env' => ['SONAR_TOKEN'], 'params' => ['lang' => 'typescript / php / javascript / css / xml']],

    // Yii
    'yii_serve'         => ['cmd' => 'start cmd /k php yii serve', 'bg' => true, 'open' => 'http://localhost:8080'],
    'yii_user'          => ['cmd' => 'php yii user/create {user} {pass}',
                            'params' => ['user' => 'Username', 'pass' => 'Password']],
    'yii_role'          => ['cmd' => 'php yii user/assignRole {role} {uid}',
                            'params' => ['role' => 'Role (e.g. admin)', 'uid' => 'User ID']],
    'yii_routes'        => ['cmd' => 'php yii router/list'],
    'yii_translate'     => ['cmd' => 'php yii translator/translate {text} {lang}',
                            'params' => ['text' => 'Source text', 'lang' => 'Target language code (e.g. fr)']],
    'yii_items'         => ['cmd' => 'php yii invoice/items'],
    'yii_trunc_setting' => ['cmd' => 'php yii invoice/setting/truncate',                         'confirm' => 'DELETE all settings permanently?'],
    'yii_trunc_gen'     => ['cmd' => 'php yii invoice/generator/truncate',                       'confirm' => 'DELETE generator data permanently?'],
    'yii_trunc1'        => ['cmd' => 'php yii invoice/inv/truncate1',                            'confirm' => 'DELETE all invoices permanently?'],
    'yii_trunc2'        => ['cmd' => 'php yii invoice/quote/truncate2',                          'confirm' => 'DELETE all quotes permanently?'],
    'yii_trunc3'        => ['cmd' => 'php yii invoice/salesorder/truncate3',                     'confirm' => 'DELETE all sales orders permanently?'],
    'yii_trunc4'        => ['cmd' => 'php yii invoice/nonuserrelated/truncate4',                 'confirm' => 'DELETE all non-user-related data permanently?'],
    'yii_trunc5'        => ['cmd' => 'php yii invoice/userrelated/truncate5',                    'confirm' => 'DELETE all user-related data permanently?'],
    'yii_trunc6'        => ['cmd' => 'php yii invoice/autoincrementsettooneafter/truncate6',     'confirm' => 'RESET auto-increment counters permanently?'],

    // GitHub
    'gh_install'        => ['cmd' => 'winget install --id GitHub.cli'],
    'gh_status'         => ['cmd' => 'gh auth status'],
    'gh_copilot'        => ['cmd' => 'gh --version && gh api user/copilot_seat_details'],

    // Peppol
    'peppol_check'      => ['cmd' => 'php bin/check-peppol-codelists.php', 'env' => ['GITHUB_TOKEN']],

    // Benchmarks
    'bench_all'         => ['cmd' => 'php benchmarks/run.php'],
    'bench_di'          => ['cmd' => 'php benchmarks/run.php --suite=di'],
    'bench_injector'    => ['cmd' => 'php benchmarks/run.php --suite=injector'],
    'bench_router'      => ['cmd' => 'php benchmarks/run.php --suite=router'],
    'bench_strings'     => ['cmd' => 'php benchmarks/run.php --suite=strings'],
    'bench_dry'         => ['cmd' => 'php benchmarks/run.php --dry-run'],
    'bench_dashboard'   => ['cmd' => 'start cmd /k php -S localhost:8080 -t benchmarks', 'bg' => true,
                            'open' => 'http://localhost:8080/dashboard/'],

    // Snyk
    'snyk_install'      => ['cmd' => 'npm install -g snyk'],
    'snyk_auth'         => ['cmd' => 'start cmd /k snyk auth', 'bg' => true],
    'snyk_whoami'       => ['cmd' => 'snyk whoami'],
    'snyk_quick'        => ['cmd' => 'npm run security:quick'],
    'snyk_full'         => ['cmd' => 'npm run security:full'],
    'snyk_deps'         => ['cmd' => 'npm run security:deps'],
    'snyk_file'         => ['cmd' => 'snyk code test --file={file}',
                            'params' => ['file' => 'File path (e.g. src/Invoice/Inv/InvController.php)']],
    'snyk_summary'      => ['cmd' => 'snyk code test --no-color', 'filter' => 'snyk_summary'],
    'snyk_json'         => ['cmd' => 'snyk code test --json'],
    'snyk_report'       => ['cmd' => 'snyk code test'],

    // System
    'sys_versions'      => ['cmd' => 'php -v && composer --version && node -v && npm -v && npx tsc --version && composer check-platform-reqs && npm list --depth=0'],
    'sys_assets'        => ['cmd' => 'powershell -Command "Get-ChildItem -Path public/assets -Exclude .gitignore | Remove-Item -Recurse -Force; Write-Host \'Assets cache cleared.\'"'],
    'sys_extensions'    => ['cmd' => 'php scripts\extension-checker.php'],
    'sys_dl_icons'      => ['cmd' => 'php bin/download-cli-icons.php'],
];

// ── Menus (each item: [label, cmdKey]) ────────────────────────────────────────
$MENUS = [
    'psalm' => [
        'title' => 'Psalm — Static Analysis',
        'items' => [
            ['Run Psalm (Full)',        'psalm_full'],
            ['Psalm on File',          'psalm_file'],
            ['Psalm on Directory',     'psalm_dir'],
            ['Clear Psalm Cache',      'psalm_cache'],
            ['Show Config / Plugins',  'psalm_info'],
        ],
    ],
    'composer' => [
        'title' => 'Composer — PHP Dependencies',
        'items' => [
            ['Outdated Packages',            'comp_outdated'],
            ['why-not (version conflict)',   'comp_whynot'],
            ['Cache Clear + Lock Resolve',   'comp_cache_lock'],
            ['Validate composer.json',       'comp_validate'],
            ['Dump Autoload',                'comp_dump'],
            ['Audit (security)',             'comp_audit'],
            ['Update',                       'comp_update'],
            ['Require Checker',              'comp_req_check'],
        ],
    ],
    'node' => [
        'title' => 'Node — npm Packages',
        'items' => [
            ['Update Modules (npm-check-updates)', 'node_ncu'],
            ['nvm-windows Download Link',          'node_nvm'],
            ['Audit + Clean + List',               'node_audit'],
            ['Check Outdated',                     'node_outdated'],
            ['Safe Update (patch only)',            'node_safe'],
            ['Minor Update',                       'node_minor'],
            ['Major Update (interactive)',          'node_major'],
            ['ES2024 Feature Verify',              'node_es2024'],
            ['Build (npm run build)',               'node_build'],
        ],
    ],
    'typescript' => [
        'title' => 'TypeScript',
        'items' => [
            ['Build Production (minified)',         'ts_prod'],
            ['Build Development (source maps)',     'ts_dev'],
            ['Watch Mode (opens new window)',       'ts_watch'],
            ['Type Check',                         'ts_check'],
            ['Lint',                               'ts_lint'],
            ['Format Check + Fix',                 'ts_format'],
        ],
    ],
    'angular' => [
        'title' => 'Angular',
        'items' => [
            ['Install Dependencies',               'ang_install'],
            ['Serve Development (opens new window)','ang_serve'],
            ['Build Production',                   'ang_build'],
            ['Generate Component',                 'ang_gen'],
            ['Lint Check',                         'ang_lint'],
        ],
    ],
    'testing' => [
        'title' => 'Testing — PHPUnit + Codeception',
        'items' => [
            ['Entity Tests (Tests/Unit/Invoice/Entity/)', 'test_entity'],
            ['All Unit Tests (Tests/Unit/)',              'test_unit'],
            ['Functional / Integration',                  'test_func'],
            ['Codeception: Functional Suite',             'test_cc_func'],
            ['Codeception: Acceptance Suite',             'test_cc_acc'],
            ['Codeception: All Suites',                   'test_cc_all'],
        ],
    ],
    'fixer' => [
        'title' => 'PHP-CS-Fixer',
        'items' => [
            ['Dry Run (see proposed changes)', 'fixer_dry'],
            ['Apply Fix',                      'fixer_fix'],
        ],
    ],
    'phpcs' => [
        'title' => 'PHPCS — Code Style Checker',
        'items' => [
            ['Check Full Project (85-char limit)', 'phpcs_full'],
            ['Check Specific File',                'phpcs_file'],
            ['Check Specific Directory',           'phpcs_dir'],
            ['Detailed Report',                    'phpcs_report'],
        ],
    ],
    'rector' => [
        'title' => 'Rector — Automated Refactoring',
        'items' => [
            ['Dry Run (see proposed changes)', 'rector_dry'],
            ['Apply Changes',                  'rector_apply'],
        ],
    ],
    'sonar' => [
        'title' => 'SonarCloud — rossaddison_invoice',
        'items' => [
            ['All Open Issues',              'sonar_all'],
            ['Issues on a Specific PR',      'sonar_pr'],
            ['Filter by Type',               'sonar_type'],
            ['Filter by Severity',           'sonar_sev'],
            ['Security Hotspots',            'sonar_hotspots'],
            ['Combine Type + Severity',      'sonar_combined'],
            ['Filter by Rule Key',           'sonar_rule'],
            ['Filter by File Path',          'sonar_file'],
            ['Reliability Issues (BUG)',      'sonar_reliability'],
            ['Reliability Grouped by Rule',  'sonar_rely_grp'],
            ['All Issues Grouped by Rule',   'sonar_all_grp'],
            ['Filter by Language',           'sonar_lang'],
        ],
    ],
    'yii' => [
        'title' => 'Yii — Console Commands',
        'items' => [
            ['PHP Built-in Serve (opens new window)', 'yii_serve'],
            ['user/create',                           'yii_user'],
            ['user/assignRole',                       'yii_role'],
            ['router/list',                           'yii_routes'],
            ['translator/translate',                  'yii_translate'],
            ['invoice/items',                         'yii_items'],
            ['TRUNCATE: invoice/setting',             'yii_trunc_setting'],
            ['TRUNCATE: invoice/generator',           'yii_trunc_gen'],
            ['TRUNCATE: invoice/inv (invoices)',      'yii_trunc1'],
            ['TRUNCATE: invoice/quote',               'yii_trunc2'],
            ['TRUNCATE: invoice/salesorder',          'yii_trunc3'],
            ['TRUNCATE: invoice/nonuserrelated',      'yii_trunc4'],
            ['TRUNCATE: invoice/userrelated',         'yii_trunc5'],
            ['TRUNCATE: autoincrementsettooneafter',  'yii_trunc6'],
        ],
    ],
    'github' => [
        'title' => 'GitHub CLI',
        'items' => [
            ['Install GitHub CLI (winget)', 'gh_install'],
            ['Auth Status',                 'gh_status'],
            ['Copilot / Version',           'gh_copilot'],
        ],
    ],
    'peppol' => [
        'title' => 'Peppol — Code-List Currency Check',
        'items' => [
            ['Check Peppol Code Lists', 'peppol_check'],
        ],
    ],
    'bench' => [
        'title' => 'Performance Benchmarks',
        'items' => [
            ['Run All Suites (saves to history.json)', 'bench_all'],
            ['DI Container Suite',                     'bench_di'],
            ['Injector Suite',                         'bench_injector'],
            ['Router Suite',                           'bench_router'],
            ['String Helpers Suite',                   'bench_strings'],
            ['Dry Run (no save)',                       'bench_dry'],
            ['Serve Dashboard (localhost:8080)',        'bench_dashboard'],
        ],
    ],
    'snyk' => [
        'title' => 'Snyk Security Scanner',
        'items' => [
            ['Resolved Vulnerabilities Index',         '__nav__:snyk_resolved'],
            ['[SETUP 1] Install Snyk CLI',             'snyk_install'],
            ['[SETUP 2] Authenticate (browser login)', 'snyk_auth'],
            ['[SETUP 3] Verify auth (whoami)',         'snyk_whoami'],
            ['Quick Scan (high-severity only)',         'snyk_quick'],
            ['Full Scan (code + dependencies)',         'snyk_full'],
            ['Dependencies Only',                      'snyk_deps'],
            ['Code Scan on File',                      'snyk_file'],
            ['Issue Count Summary',                    'snyk_summary'],
            ['JSON Output',                            'snyk_json'],
            ['Full Scan + Save to snyk-report.txt',   'snyk_report'],
        ],
    ],
    'system' => [
        'title' => 'System — Versions + Utilities',
        'items' => [
            ['Version Info (PHP, Composer, Node, TS)', 'sys_versions'],
            ['Clear Public Assets Cache',              'sys_assets'],
            ['PHP Extension Checker',                  'sys_extensions'],
            ['Download Menu Icons',                    'sys_dl_icons'],
        ],
    ],
];

// ── SonarCloud quick-rule reference (shown as clickable badges) ───────────────
$SONAR_RULES = [
    'php:S1192'        => 'String literals duplicated 3+',
    'php:S3776'        => 'Cognitive complexity',
    'php:S107'         => 'Too many parameters',
    'php:S116'         => 'Field name convention',
    'php:S100'         => 'Function name convention',
    'php:S1155'        => 'Use empty() not count()==0',
    'php:S6600'        => 'Unnecessary echo parens',
    'php:S2003'        => 'Use require_once',
    'php:S7735'        => 'Avoid negated conditions',
    'php:S1848'        => 'Objects not dropped immediately',
    'php:S1172'        => 'Unused parameter',
    'php:S3358'        => 'Nested ternaries',
    'php:S2583'        => 'Always-true/false conditions',
    'php:S905'         => 'No-op statements',
    'php:S2681'        => 'Multiline blocks need braces',
    'php:S2234'        => 'Args match params',
    'php:S4144'        => 'Identical method implementations',
    'php:S1117'        => 'Local var shadows field',
    'typescript:S7785' => 'Async IIFE → top-level await',
    'typescript:S7647' => 'Empty lifecycle methods',
    'typescript:S7764' => 'globalThis not window',
    'javascript:S7647' => 'Empty lifecycle methods (JS)',
    'shelldre:S1066'   => 'Merge nested if statements',
];

// ── POST handler (AJAX command runner) ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: text/plain; charset=utf-8');

    $key = trim($_POST['cmd'] ?? '');
    if (!isset($CMDS[$key])) {
        http_response_code(400);
        echo "Unknown command: " . htmlspecialchars($key);
        exit;
    }

    $def = $CMDS[$key];
    $cmd = $def['cmd'];

    // Substitute params — escapeshellarg() prevents command injection (SonarQube S2083)
    foreach ($def['params'] ?? [] as $p => $_label) {
        $val = escapeshellarg(trim($_POST[$p] ?? ''));
        $cmd = str_replace('{' . $p . '}', $val, $cmd);
    }

    // Inject env vars from session
    foreach ($def['env'] ?? [] as $varName) {
        $val = '';
        if ($varName === 'SONAR_TOKEN') {
            $posted = trim($_POST['sonar_token'] ?? '');
            if ($posted !== '') {
                $_SESSION['sonar_token'] = $posted;
            }
            $val = $_SESSION['sonar_token'] ?? '';
        } elseif ($varName === 'GITHUB_TOKEN') {
            $posted = trim($_POST['github_token'] ?? '');
            if ($posted !== '') {
                $_SESSION['github_token'] = $posted;
            }
            $val = $_SESSION['github_token'] ?? '';
        }
        if ($val !== '') {
            putenv("$varName=$val");
        }
    }

    if (!empty($def['bg'])) {
        pclose(popen('cmd /c ' . $cmd, 'r'));
        echo 'Started in background window.';
        if (isset($def['open'])) {
            echo "\nOpen: " . $def['open'];
        }
        exit;
    }

    // Force ANSI colour output in child processes.
    // proc_open pipes are not a TTY so tools suppress colour by default.
    // putenv() only updates the CRT env block on Windows, not the Win32 env block
    // that CreateProcess reads — so we must pass env explicitly as the 5th argument.
    // FORCE_COLOR=1  → Node/npm tools + Symfony Console ≥ 5.4 (Composer/Psalm/Rector/Fixer)
    // CLICOLOR_FORCE → many Unix-style CLIs
    // TERM           → general terminal-type hint
    $baseEnv = is_array($e = getenv()) ? $e : [];
    $childEnv = array_merge($baseEnv, [
        'FORCE_COLOR'    => '1',
        'CLICOLOR_FORCE' => '1',
        'TERM'           => 'xterm-256color',
        'COLORTERM'      => 'truecolor',
    ]);

    // Stream stdout to browser via proc_open.
    // stdin is explicitly closed (nul) so interactive tools can't block waiting for input.
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-cache, no-store');
    header('X-Accel-Buffering: no');
    ob_implicit_flush(true);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $descriptors = [
        0 => ['file', 'nul', 'r'],  // stdin: closed (Windows /dev/null)
        1 => ['pipe', 'w'],          // stdout
        2 => ['file', 'nul', 'w'],  // stderr discarded — merged into stdout via 2>&1
    ];

    $process = proc_open('cmd /c ' . $cmd . ' 2>&1', $descriptors, $pipes, __DIR__, $childEnv);
    if (!is_resource($process)) {
        echo 'ERROR: Could not start process.';
        exit;
    }

    $needsFilter  = ($def['filter'] ?? '') === 'snyk_summary';
    $fullOutput   = '';

    while (!feof($pipes[1])) {
        $chunk = fread($pipes[1], 4096);
        if ($chunk === false || $chunk === '') {
            continue;
        }
        if ($needsFilter) {
            $fullOutput .= $chunk;
        } else {
            echo $chunk;
            flush();
        }
    }

    fclose($pipes[1]);
    proc_close($process);

    if ($needsFilter) {
        $lines = explode("\n", $fullOutput);
        $lines = array_filter($lines, static fn(string $l): bool => str_contains($l, 'Total issues'));
        $lines = array_map(static fn(string $l): string => preg_replace('/[^\x20-\x7E]/', '', $l) ?? $l, $lines);
        echo implode("\n", $lines) ?: 'No "Total issues" line found in output.';
    }

    exit;
}

// ── Page state ────────────────────────────────────────────────────────────────
$menu    = $_GET['menu'] ?? 'main';
$isMain  = ($menu === 'main');
$menuDef = $isMain ? null : ($MENUS[$menu] ?? null);
$pageTitle = $menuDef ? $menuDef['title'] : 'Invoice System (Yii3-i)';

// Build slim JS command map (only what JS needs — no cmd strings)
$jsCommands = [];
foreach ($CMDS as $k => $def) {
    $jsCommands[$k] = [
        'params'            => array_keys($def['params'] ?? []),
        'paramLabels'       => $def['params'] ?? [],
        'paramPrefix'       => $def['paramPrefix'] ?? [],
        'paramSelect'       => $def['paramSelect'] ?? [],
        'paramSelectSuffix' => $def['paramSelectSuffix'] ?? [],
        'confirm'     => $def['confirm'] ?? null,
        'bg'          => !empty($def['bg']),
        'needsSonar'  => in_array('SONAR_TOKEN', $def['env'] ?? [], true),
        'needsGithub' => in_array('GITHUB_TOKEN', $def['env'] ?? [], true),
    ];
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Yii3-i Dev Tools<?= $isMain ? '' : ' — ' . htmlspecialchars($pageTitle) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
body{background:#0d1117;color:#e6edf3;min-height:100vh}
.navbar{background:#161b22!important;border-bottom:1px solid #30363d}
.card-cat{cursor:pointer;border:1px solid #30363d;background:#161b22;transition:border-color .15s,transform .1s;text-decoration:none;display:block}
.card-cat:hover{border-color:#58a6ff;transform:translateY(-2px);color:inherit}
.card-cat h6{color:#58a6ff;margin-bottom:.2rem}
.card-cat small{color:#8b949e}
.cli-menu-pop .popover-header{background:#161b22;color:#58a6ff;border-bottom:1px solid #30363d;font-size:.82em}
.cli-menu-pop .popover-body{background:#0d1117;color:#c9d1d9;font-size:.8em;line-height:1.6;padding:.45rem .6rem}
.cli-menu-pop{border:1px solid #30363d!important;max-width:240px}
.cli-menu-pop .popover-arrow::before{border-top-color:#30363d!important}
.cli-menu-pop .popover-arrow::after{border-top-color:#0d1117!important}
.btn-cmd{text-align:left;border-color:#30363d;color:#e6edf3;background:#161b22;width:100%;padding:.55rem 1rem}
.btn-cmd:hover{border-color:#58a6ff;color:#58a6ff;background:#161b22}
.btn-danger-cmd{text-align:left;border-color:#f85149;color:#f85149;background:#161b22;width:100%;padding:.55rem 1rem}
.btn-danger-cmd:hover{background:rgba(248,81,73,.07)}
#out-panel{position:fixed;bottom:0;left:0;right:0;background:#0d1117;border-top:2px solid #30363d;z-index:1050;display:none;max-height:55vh}
.out-hdr{padding:.4rem 1rem;border-bottom:1px solid #30363d;display:flex;justify-content:space-between;align-items:center;background:#161b22}
#out-pre{margin:0;padding:1rem;font-size:.82em;font-family:'Courier New',monospace;overflow-y:auto;max-height:calc(55vh - 38px);color:#e6edf3;background:#0d1117;white-space:pre-wrap;word-break:break-word}
#out-pre span[style*="background"]{padding:.1em .35em;border-radius:3px}
body.panel-open{padding-bottom:55vh}
.divider-label{font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:#8b949e;margin:1rem 0 .4rem;padding-top:.8rem;border-top:1px solid #30363d}
.rule-badge{cursor:pointer;font-size:.72em;font-family:monospace}
.inp{background:#0d1117!important;border-color:#30363d!important;color:#e6edf3!important}
.inp:focus{border-color:#58a6ff!important;box-shadow:none!important}
.token-bar{background:#161b22;border:1px solid #30363d;border-radius:6px;padding:.75rem 1rem;margin-bottom:1rem}
.snyk-notice{background:#161b22;border:1px solid #30363d;border-radius:6px;padding:.6rem 1rem;margin-bottom:1rem;font-size:.85em;color:#8b949e}
</style>
</head>
<body>

<nav class="navbar px-3 py-2">
  <a class="navbar-brand fw-bold text-light text-decoration-none" href="?menu=main">⚡ Yii3-i Dev Tools</a>
  <?php if (!$isMain): ?>
  <a href="?menu=main" class="btn btn-sm btn-outline-secondary">← Main Menu</a>
  <?php endif; ?>
</nav>

<div class="container-fluid p-3">
<?php if ($isMain): ?>

  <p class="text-secondary mb-3 small">Select a category</p>
  <div class="row g-2">
<?php
  $mainItems = [
    ['psalm',      'Psalm',        'Static analysis'],
    ['composer',   'Composer',     'PHP dependencies'],
    ['node',       'Node',         'npm packages'],
    ['typescript', 'TypeScript',   'TS build tools'],
    ['angular',    'Angular',      'Angular CLI'],
    ['testing',    'Testing',      'PHPUnit + Codeception'],
    ['snyk',       'Snyk',         'Security scanning'],
    ['fixer',      'PHP-CS-Fixer', 'Code style fixer'],
    ['phpcs',      'PHPCS',        'Code style checker'],
    ['rector',     'Rector',       'Automated refactoring'],
    ['sonar',      'SonarCloud',   'Cloud code quality'],
    ['yii',        'Yii',          'Console commands'],
    ['github',     'GitHub',       'GitHub CLI'],
    ['peppol',     'Peppol',       'e-invoicing code lists'],
    ['bench',      'Benchmarks',   'Performance benchmarks'],
    ['system',     'System',       'Versions + utilities'],
  ];
  $ICON_MAP = [
    'psalm'      => 'psalm',
    'composer'   => 'composer',
    'node'       => 'nodejs',
    'typescript' => 'typescript',
    'angular'    => 'angular',
    'testing'    => 'testing',
    'snyk'       => 'snyk',
    'fixer'      => 'phpcs-fixer',
    'phpcs'      => 'phpcs',
    'rector'     => 'rector',
    'sonar'      => 'sonarcloud',
    'yii'        => 'yii',
    'github'     => 'github',
    'peppol'     => 'peppol',
    'bench'      => 'benchmarks',
    'system'     => 'system',
  ];
  foreach ($mainItems as [$key, $label, $desc]):
    $iconSlug    = $ICON_MAP[$key] ?? $key;
    $iconRel     = 'public/img/cli/' . $iconSlug . '.svg';
    $hasIcon     = is_file(__DIR__ . '/' . $iconRel);
    // Brand logos that carry their own colours — don't invert to white
    $noInvert    = ['yii'];
    $iconFilter  = in_array($iconSlug, $noInvert, true)
                   ? 'opacity:.9'
                   : 'filter:brightness(0)invert(1);opacity:.8';
    $subLabels   = array_column($MENUS[$key]['items'] ?? [], 0);
    $menuTitle   = $MENUS[$key]['title'] ?? $label;
  ?>
  <div class="col-6 col-sm-4 col-md-3 col-lg-2">
    <a href="?menu=<?= $key ?>" class="card-cat p-3 h-100"
       data-menu-title="<?= htmlspecialchars($menuTitle, ENT_QUOTES) ?>"
       data-submenu-items="<?= htmlspecialchars((string) json_encode($subLabels, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">
      <?php if ($hasIcon): ?>
      <img src="/<?= $iconRel ?>" height="48" alt=""
           class="mb-2 d-block" style="width:auto;max-width:100%;<?= $iconFilter ?>">
      <?php endif; ?>
      <h6><?= htmlspecialchars($label) ?></h6>
      <small><?= htmlspecialchars($desc) ?></small>
    </a>
  </div>
<?php endforeach; ?>
  </div>

<?php elseif ($menu === 'snyk_resolved'):
  $sqliteOk  = sqliteAvailable();
?>
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0">Resolved Vulnerabilities Index</h5>
    <a href="?menu=snyk" class="btn btn-sm btn-outline-secondary">← Snyk</a>
  </div>
<?php if (!$sqliteOk): ?>
  <!-- ── SQLite setup guide ── -->
<?php
  $cliIni     = php_ini_loaded_file() ?: '';
  $cliVersion = PHP_VERSION;
  $cliDrivers = implode(', ', PDO::getAvailableDrivers()) ?: '(none)';
?>
  <!-- Version-conflict banner -->
  <div class="p-3 mb-3" style="background:#3d1f00;border:1px solid #f0883e;border-radius:6px">
    <h6 class="mb-2" style="color:#f0883e">⚠ CLI PHP ≠ WAMP Apache PHP — read this first</h6>
    <p class="small mb-1" style="color:#e6c48a">
      <code>m.bat</code> launches <code>php -S</code> using the <strong>CLI PHP</strong>
      (currently <strong>PHP <?= htmlspecialchars($cliVersion) ?></strong>).
      The WAMP system tray manages extensions for the <strong>Apache PHP</strong>,
      which may be a different version entirely.
    </p>
    <p class="small mb-0" style="color:#e6c48a">
      Enabling <code>php_pdo_sqlite</code> via the WAMP tray fixes Apache — it does
      <strong>not</strong> fix the CLI that runs this tool. Use Method 1 below instead.
    </p>
  </div>

  <div class="p-3 mb-3" style="background:#161b22;border:1px solid #f85149;border-radius:6px">
    <h6 class="text-danger mb-2">PHP SQLite driver not enabled in CLI PHP <?= htmlspecialchars($cliVersion) ?></h6>
    <p class="small text-secondary mb-3">
      Active PDO drivers in this PHP process:
      <code><?= htmlspecialchars($cliDrivers) ?></code>
    </p>

    <div class="row g-3">

      <!-- Method 1: edit CLI php.ini -->
      <div class="col-12">
        <div class="p-3" style="background:#0d1117;border:1px solid #3fb950;border-radius:4px">
          <h6 class="mb-2" style="color:#3fb950">
            Method 1 — Edit the CLI php.ini
            <span class="badge bg-success ms-2" style="font-size:.65em">Correct approach</span>
          </h6>
          <p class="small text-secondary mb-2">
            CLI php.ini loaded by this process:
          </p>
          <code class="d-block mb-3 p-2" style="background:#161b22;border-radius:4px;color:#7ee787;word-break:break-all">
            <?= $cliIni !== '' ? htmlspecialchars($cliIni) : 'Run <span style="color:#f0883e">php --ini</span> in a terminal to find it' ?>
          </code>
          <ol class="small text-secondary ps-3 mb-0">
            <li>Open the file path above in a text editor <em>(run as Administrator if needed)</em></li>
            <li>Search for <code>;extension=pdo_sqlite</code> — delete the leading <code>;</code></li>
            <li>Search for <code>;extension=sqlite3</code> — delete the leading <code>;</code></li>
            <li>Save, then close and reopen the terminal running <code>m.bat</code></li>
          </ol>
        </div>
      </div>

      <!-- Method 2: WAMP tray (Apache only — not this tool) -->
      <div class="col-12">
        <div class="p-3" style="background:#0d1117;border:1px solid #30363d;border-radius:4px;opacity:.75">
          <h6 class="text-secondary mb-2">
            Method 2 — WAMP System Tray
            <span class="badge bg-secondary ms-2" style="font-size:.65em">Apache only — won't fix m.bat</span>
          </h6>
          <p class="small text-secondary mb-0">
            Left-click WAMP icon → <strong>PHP → PHP Extensions</strong> →
            tick <code>php_pdo_sqlite</code> + <code>php_sqlite3</code> →
            <strong>Restart All Services</strong>.<br>
            This enables SQLite for Apache-served pages but has
            <strong>no effect</strong> on the CLI PHP used by <code>m.bat</code>.
            Only use this if you also need SQLite in browser-served PHP.
          </p>
        </div>
      </div>

      <!-- Verify -->
      <div class="col-12">
        <div class="p-3" style="background:#0d1117;border:1px solid #30363d;border-radius:4px">
          <h6 class="text-light mb-2">Verify the fix (run in a new terminal)</h6>
          <code style="color:#7ee787">php -r "print_r(PDO::getAvailableDrivers());"</code>
          <p class="small text-secondary mt-2 mb-0">
            <code>sqlite</code> must appear in the array.
            Then close and reopen <code>m.bat</code>, and click Re-check below.
          </p>
        </div>
      </div>

    </div>

    <div class="mt-3">
      <a href="?menu=snyk_resolved" class="btn btn-sm btn-outline-primary">↺ Re-check now</a>
    </div>
  </div>

<?php else:
  $db        = getVulnDb();
  $filter    = $_GET['filter'] ?? 'all';
  $baseUrl   = '?menu=snyk_resolved';
  $sql       = 'SELECT * FROM vuln_resolved';
  if ($filter === 'fp')  $sql .= ' WHERE false_pos  = 1';
  if ($filter === 'ai')  $sql .= ' WHERE ai_related = 1';
  $sql .= ' ORDER BY created_at DESC';
  $rows      = $db->query($sql)->fetchAll();
  $totalAll  = (int) $db->query('SELECT COUNT(*) FROM vuln_resolved')->fetchColumn();
  $totalFp   = (int) $db->query('SELECT COUNT(*) FROM vuln_resolved WHERE false_pos  = 1')->fetchColumn();
  $totalAi   = (int) $db->query('SELECT COUNT(*) FROM vuln_resolved WHERE ai_related = 1')->fetchColumn();

  $sevBadge  = static function(string $s): string {
      return match ($s) {
          'CRITICAL' => 'bg-danger',
          'HIGH'     => 'bg-danger bg-opacity-75',
          'MEDIUM'   => 'bg-warning text-dark',
          'LOW'      => 'bg-secondary',
          default    => 'bg-secondary',
      };
  };
?>
  <!-- Filter bar -->
  <div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="<?= $baseUrl ?>" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">
      All <span class="badge bg-light text-dark ms-1"><?= $totalAll ?></span>
    </a>
    <a href="<?= $baseUrl ?>&filter=fp" class="btn btn-sm <?= $filter === 'fp' ? 'btn-primary' : 'btn-outline-secondary' ?>">
      False Positives <span class="badge bg-light text-dark ms-1"><?= $totalFp ?></span>
    </a>
    <a href="<?= $baseUrl ?>&filter=ai" class="btn btn-sm <?= $filter === 'ai' ? 'btn-warning text-dark' : 'btn-outline-warning' ?>">
      AI-Related <span class="badge bg-light text-dark ms-1"><?= $totalAi ?></span>
    </a>
    <button class="btn btn-sm btn-outline-success ms-auto"
            onclick="document.getElementById('add-form').classList.toggle('d-none')">
      + Add Entry
    </button>
  </div>

  <!-- Vulnerability table -->
  <?php if ($rows === []): ?>
    <p class="text-secondary">No entries match this filter.</p>
  <?php else: ?>
  <div class="table-responsive">
  <table class="table table-sm table-hover align-middle" style="font-size:.82em">
    <thead style="background:#161b22">
      <tr>
        <th>Sev</th>
        <th>Snyk ID</th>
        <th>Title</th>
        <th>File</th>
        <th>Category / Vector</th>
        <th>Flags</th>
        <th>Date</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><span class="badge <?= $sevBadge($r['severity']) ?>"><?= htmlspecialchars($r['severity']) ?></span></td>
        <td style="font-family:monospace;max-width:11rem;word-break:break-all">
          <?php if ($r['advisory_url'] !== ''): ?>
            <a href="<?= htmlspecialchars($r['advisory_url']) ?>" target="_blank"
               style="color:#58a6ff;font-size:.75em"
               title="<?= htmlspecialchars($r['snyk_id']) ?>">
              <?= htmlspecialchars(substr($r['snyk_id'], 0, 20)) ?>…
            </a>
          <?php else: ?>
            <span class="text-secondary" style="font-size:.75em"
                  title="<?= htmlspecialchars($r['snyk_id']) ?>">
              <?= htmlspecialchars(substr($r['snyk_id'], 0, 20)) ?>…
            </span>
          <?php endif; ?>
        </td>
        <td title="<?= htmlspecialchars($r['reason']) ?>"><?= htmlspecialchars($r['title']) ?></td>
        <td style="max-width:12rem;word-break:break-all;color:#8b949e">
          <?= htmlspecialchars($r['file_path']) ?>
        </td>
        <td>
          <div><?= htmlspecialchars($r['category']) ?></div>
          <?php if ($r['threat_vec'] !== ''): ?>
            <small class="text-secondary"><?= htmlspecialchars($r['threat_vec']) ?></small>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($r['false_pos']): ?><span class="badge bg-warning text-dark me-1">FP</span><?php endif; ?>
          <?php if ($r['ai_related']): ?><span class="badge bg-info text-dark">AI</span><?php endif; ?>
        </td>
        <td style="white-space:nowrap;color:#8b949e"><?= htmlspecialchars($r['resolved_date']) ?></td>
        <td>
          <form method="post" action="?del_vuln=1" onsubmit="return confirm('Delete this entry?')">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button class="btn btn-sm btn-outline-danger py-0 px-1" style="font-size:.7em">✕</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>

  <!-- Add new entry form (hidden by default) -->
  <div id="add-form" class="d-none mt-4 p-3" style="background:#161b22;border:1px solid #30363d;border-radius:6px">
    <h6 class="mb-3 text-light">Add Resolved Vulnerability</h6>
    <form method="post" action="?add_vuln=1">
      <div class="row g-2">
        <div class="col-12 col-md-6">
          <label class="form-label small text-secondary">Snyk ID</label>
          <input type="text" name="snyk_id" class="form-control form-control-sm inp"
                 placeholder="SNYK-CODE-xxxxxxxx-…">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label small text-secondary">Title <span class="text-danger">*</span></label>
          <input type="text" name="title" required class="form-control form-control-sm inp"
                 placeholder="Short description of the finding">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small text-secondary">Severity</label>
          <select name="severity" class="form-select form-select-sm inp">
            <option value="CRITICAL">CRITICAL</option>
            <option value="HIGH">HIGH</option>
            <option value="MEDIUM" selected>MEDIUM</option>
            <option value="LOW">LOW</option>
            <option value="INFO">INFO</option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small text-secondary">Threat Vector</label>
          <input type="text" name="threat_vec" class="form-control form-control-sm inp"
                 placeholder="e.g. XSS, XXE, Prompt Injection">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label small text-secondary">Category</label>
          <input type="text" name="category" class="form-control form-control-sm inp"
                 placeholder="e.g. False Positive — XSS">
        </div>
        <div class="col-12">
          <label class="form-label small text-secondary">File Path</label>
          <input type="text" name="file_path" class="form-control form-control-sm inp"
                 placeholder="src/Invoice/…">
        </div>
        <div class="col-12">
          <label class="form-label small text-secondary">Reason / Resolution</label>
          <textarea name="reason" rows="2" class="form-control form-control-sm inp"
                    placeholder="Why this was marked resolved or as a false positive"></textarea>
        </div>
        <div class="col-12">
          <label class="form-label small text-secondary">Advisory URL (CWE / OWASP / Snyk)</label>
          <input type="url" name="advisory_url" class="form-control form-control-sm inp"
                 placeholder="https://cwe.mitre.org/data/definitions/79.html">
        </div>
        <div class="col-12 d-flex gap-4 mt-1">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="false_pos" id="fp_chk" value="1">
            <label class="form-check-label small text-secondary" for="fp_chk">False Positive</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="ai_related" id="ai_chk" value="1">
            <label class="form-check-label small text-secondary" for="ai_chk">AI-Related Threat</label>
          </div>
        </div>
        <div class="col-12 d-flex gap-2 mt-1">
          <button type="submit" class="btn btn-sm btn-success">Save</button>
          <button type="button" class="btn btn-sm btn-outline-secondary"
                  onclick="document.getElementById('add-form').classList.add('d-none')">Cancel</button>
        </div>
      </div>
    </form>
  </div>

<?php endif; // sqliteAvailable() else ?>
<?php elseif ($menuDef): ?>

  <h5 class="mb-3"><?= htmlspecialchars($pageTitle) ?></h5>

<?php /* ── SonarCloud token bar ── */ ?>
<?php if ($menu === 'sonar'): $hasToken = !empty($_SESSION['sonar_token']); ?>
  <div class="token-bar">
    <div class="d-flex align-items-center gap-3 flex-wrap mb-2">
      <span class="text-secondary small">SonarCloud Token</span>
      <span id="token-badge" class="badge <?= $hasToken ? 'bg-success' : 'bg-warning text-dark' ?>">
        <?= $hasToken ? 'Set ✓' : 'Not set' ?>
      </span>
      <button class="btn btn-sm btn-outline-secondary" onclick="setSonarToken()">
        <?= $hasToken ? 'Change' : 'Set Token' ?>
      </button>
      <?php if ($hasToken): ?>
      <button class="btn btn-sm btn-outline-danger" onclick="clearSonarToken()">Clear</button>
      <?php endif; ?>
    </div>
    <div class="small text-secondary mb-1">Quick-run by rule (click any badge):</div>
    <div class="d-flex flex-wrap gap-1">
<?php foreach ($SONAR_RULES as $ruleKey => $ruleDesc): ?>
      <span class="badge bg-secondary rule-badge"
            title="<?= htmlspecialchars($ruleDesc) ?>"
            onclick="runDirect('sonar_rule',{rule:'<?= htmlspecialchars($ruleKey, ENT_QUOTES) ?>'})">
        <?= htmlspecialchars($ruleKey) ?>
      </span>
<?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php /* ── Peppol GitHub token ── */ ?>
<?php if ($menu === 'peppol'): $hasGhToken = !empty($_SESSION['github_token']); ?>
  <div class="token-bar">
    <div class="d-flex align-items-center gap-3">
      <span class="text-secondary small">GitHub Token (optional — 60 req/hr unauthenticated)</span>
      <span class="badge <?= $hasGhToken ? 'bg-success' : 'bg-secondary' ?>"><?= $hasGhToken ? 'Set ✓' : 'Anonymous' ?></span>
      <button class="btn btn-sm btn-outline-secondary" onclick="setGithubToken()">Set Token</button>
    </div>
  </div>
<?php endif; ?>

<?php /* ── Snyk notice ── */ ?>
<?php if ($menu === 'snyk'): ?>
  <div class="snyk-notice">
    <strong class="text-light">FREE ACCOUNT REQUIRED</strong> — Sign up at snyk.io (free for open source)<br>
    Organisation: <code>rossaddison</code> — Run Setup steps [1–3] on first use, in order.
  </div>
<?php endif; ?>

  <div class="d-flex flex-column gap-2">
<?php
  $shownTruncDivider = false;
  $shownSetupDivider = false;
  foreach ($menuDef['items'] as [$label, $cmdKey]):
    // Navigation link — not a shell command
    if (str_starts_with($cmdKey, '__nav__:')): ?>
    <a href="?menu=<?= htmlspecialchars(substr($cmdKey, 8)) ?>"
       class="btn btn-outline-info btn-cmd">
      <?= htmlspecialchars($label) ?> →
    </a>
<?php   continue;
    endif;
    $def = $CMDS[$cmdKey];
    $isDestructive = !empty($def['confirm']);
    $isBg = !empty($def['bg']);
    $isSetup = str_starts_with($label, '[SETUP');

    if ($isDestructive && !$shownTruncDivider):
      $shownTruncDivider = true;
?>
    <div class="divider-label">⚠ Destructive — requires confirmation</div>
<?php
    elseif ($isSetup && !$shownSetupDivider):
      $shownSetupDivider = true;
?>
    <div class="divider-label">Setup (run once, in order)</div>
<?php
    elseif (!$isSetup && $shownSetupDivider && $menu === 'snyk' && !$shownTruncDivider):
      $shownSetupDivider = false;
?>
    <div class="divider-label">Scan</div>
<?php endif; ?>

    <button class="btn <?= $isDestructive ? 'btn-danger-cmd' : 'btn-cmd' ?>"
            onclick="handleCmd('<?= $cmdKey ?>')">
      <?= htmlspecialchars($label) ?>
      <?php if ($isBg): ?>
        <span class="badge bg-secondary float-end ms-2" style="font-size:.65em;font-weight:400">new window</span>
      <?php endif; ?>
      <?php if ($isDestructive): ?>
        <span class="badge bg-danger float-end ms-2" style="font-size:.65em;font-weight:400">DESTRUCTIVE</span>
      <?php endif; ?>
    </button>
<?php endforeach; ?>
  </div>

<?php else: ?>
  <div class="alert alert-danger mt-3">Unknown menu: <?= htmlspecialchars($menu) ?></div>
<?php endif; ?>
</div>

<!-- Output panel -->
<div id="out-panel">
  <div class="out-hdr">
    <span id="out-title" class="small fw-bold"></span>
    <div class="d-flex gap-2">
      <button id="copy-btn" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="copyOutput()">Copy</button>
      <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="closePanel()">✕ close</button>
    </div>
  </div>
  <pre id="out-pre">Ready.</pre>
</div>

<!-- Parameter modal -->
<div class="modal fade" id="paramModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="background:#161b22;border-color:#30363d">
      <div class="modal-header" style="border-color:#30363d">
        <h6 class="modal-title" id="paramModalTitle">Parameters</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="paramModalBody"></div>
      <div class="modal-footer" style="border-color:#30363d">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary btn-sm" onclick="submitParams()">Run</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/public/js/cli/menu-tooltips.js"></script>
<script>
const CMDS = <?= json_encode($jsCommands, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
let currentCmd = null;
let paramModal;

document.addEventListener('DOMContentLoaded', () => {
    paramModal = new bootstrap.Modal(document.getElementById('paramModal'));
    // Restore token badge colour from sessionStorage if page just loaded
    const st = sessionStorage.getItem('sonar_token');
    const badge = document.getElementById('token-badge');
    if (badge && st) { badge.className = 'badge bg-success'; badge.textContent = 'Set ✓'; }
});

function handleCmd(key) {
    const def = CMDS[key];
    if (!def) return;
    if (def.confirm && !confirm(def.confirm)) return;
    if (def.params.length > 0) {
        showParamModal(key, def);
    } else {
        runDirect(key, {});
    }
}

function showParamModal(key, def) {
    currentCmd = key;
    document.getElementById('paramModalTitle').textContent = key;
    const body = document.getElementById('paramModalBody');
    body.innerHTML = def.params.map(p => {
        const selOptions = def.paramSelect?.[p];
        const selSuffix  = def.paramSelectSuffix?.[p] ?? '';
        const prefix     = def.paramPrefix?.[p] ?? '';

        let prefixHtml = '';
        let selId      = '';
        if (selOptions) {
            selId = `p_${p}_sel`;
            const opts = selOptions
                .map(o => `<option value="${o}">${o}</option>`)
                .join('');
            prefixHtml = `
          <div class="d-flex align-items-center gap-2 mb-2">
            <select id="${selId}" class="form-select form-select-sm"
                    style="background:#0d1117;border-color:#30363d;color:#e6edf3;width:auto">
              ${opts}
            </select>
            <span class="font-monospace text-secondary">${selSuffix}<span style="color:#ffa657">####</span></span>
          </div>`;
        } else if (prefix) {
            prefixHtml = `<span class="d-block font-monospace small text-secondary mb-1">${prefix}<span style="color:#ffa657">####</span></span>`;
        }

        return `
        <div class="mb-3">
          <label class="form-label small text-secondary">${def.paramLabels[p]}</label>
          ${prefixHtml}
          <input type="text" class="form-control form-control-sm"
                 style="background:#0d1117;border-color:#30363d;color:#e6edf3"
                 id="p_${p}" data-pk="${p}"
                 data-prefix="${prefix}"
                 data-sel-id="${selId}"
                 data-sel-suffix="${selSuffix}"
                 placeholder="${selOptions || prefix ? 'e.g. 1192' : ''}"
                 onkeydown="if(event.key==='Enter')submitParams()">
        </div>`;
    }).join('');
    document.getElementById('paramModal').addEventListener('shown.bs.modal', () => {
        body.querySelector('input')?.focus();
    }, { once: true });
    paramModal.show();
}

function submitParams() {
    const params = {};
    document.querySelectorAll('#paramModalBody [data-pk]').forEach(el => {
        const selId = el.dataset.selId;
        let prefix;
        if (selId) {
            const sel = document.getElementById(selId);
            prefix = (sel ? sel.value : '') + (el.dataset.selSuffix ?? '');
        } else {
            prefix = el.dataset.prefix ?? '';
        }
        params[el.dataset.pk] = prefix + el.value;
    });
    paramModal.hide();
    runDirect(currentCmd, params);
}

async function runDirect(key, params) {
    const def = CMDS[key];
    const form = new FormData();
    form.append('cmd', key);
    Object.entries(params).forEach(([k, v]) => form.append(k, v));

    if (def.needsSonar) {
        const t = sessionStorage.getItem('sonar_token') || '';
        if (t) form.append('sonar_token', t);
    }
    if (def.needsGithub) {
        const t = sessionStorage.getItem('github_token') || '';
        if (t) form.append('github_token', t);
    }

    // Open panel immediately — streaming output will fill it as the command runs
    const panel = document.getElementById('out-panel');
    const pre   = document.getElementById('out-pre');
    const title = document.getElementById('out-title');
    pre.textContent = '';
    title.textContent = '⏳ ' + key;
    panel.style.display = 'block';
    document.body.classList.add('panel-open');

    try {
        const response = await fetch(location.pathname + location.search,
            { method: 'POST', body: form });

        if (!response.ok) {
            pre.textContent = 'HTTP error ' + response.status;
            title.textContent = '✗ ' + key;
            return;
        }

        const reader  = response.body.getReader();
        const decoder = new TextDecoder();
        title.textContent = '▶ ' + key;

        let fullText = '';
        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            fullText += decoder.decode(value, { stream: true });
            // Show raw text while streaming; strip CR-overwrites so progress
            // lines don't pile up visibly during the run
            pre.textContent = fullText.replace(/\r[^\n]/g, '');
            pre.scrollTop = pre.scrollHeight;
        }

        // Stream complete — render with ANSI colours
        if (fullText.trim()) {
            pre.innerHTML = ansiToHtml(fullText);
        } else {
            pre.textContent = '(no output)';
        }
        title.textContent = '✓ ' + key;
    } catch (err) {
        title.textContent = '✗ ' + key;
        pre.textContent += '\n[Error: ' + err.message + ']';
    }
}

function showPanel(title, content) {
    document.getElementById('out-title').textContent = title;
    const pre = document.getElementById('out-pre');
    // Escape HTML, then linkify URLs
    const esc = content
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    pre.innerHTML = esc.replace(/(https?:\/\/\S+)/g,
        '<a href="$1" target="_blank" style="color:#58a6ff">$1</a>');
    const panel = document.getElementById('out-panel');
    panel.style.display = 'block';
    document.body.classList.add('panel-open');
    pre.scrollTop = 0;
}

function closePanel() {
    document.getElementById('out-panel').style.display = 'none';
    document.body.classList.remove('panel-open');
}

function setSonarToken() {
    const t = prompt('Enter your SonarCloud token:');
    if (t !== null && t.trim() !== '') {
        sessionStorage.setItem('sonar_token', t.trim());
        const f = new FormData();
        f.append('sonar_token', t.trim());
        fetch('?set_token=1', { method: 'POST', body: f })
            .then(() => {
                const badge = document.getElementById('token-badge');
                if (badge) { badge.className = 'badge bg-success'; badge.textContent = 'Set ✓'; }
            });
    }
}

function clearSonarToken() {
    sessionStorage.removeItem('sonar_token');
    fetch('?clear_token=1', { method: 'POST' }).then(() => location.reload());
}

// ── ANSI colour codes → HTML ─────────────────────────────────────────────────
// Maps SGR parameter numbers to inline CSS. null = reset.
const ANSI_STYLES = {
    // Attributes
    '0':null, '1':'font-weight:bold', '3':'font-style:italic', '4':'text-decoration:underline',
    '22':null, '23':null, '24':null,
    // Standard foreground (30-37) + default (39) + bright (90-97)
    '30':'color:#666',       '31':'color:#f85149',  '32':'color:#3fb950',
    '33':'color:#d29922',    '34':'color:#58a6ff',  '35':'color:#bc8cff',
    '36':'color:#56d364',    '37':'color:#e6edf3',  '39':null,
    '90':'color:#8b949e',    '91':'color:#ff7b72',  '92':'color:#7ee787',
    '93':'color:#ffa657',    '94':'color:#79c0ff',  '95':'color:#d2a8ff',
    '96':'color:#39c5cf',    '97':'color:#f0f6fc',
    // Standard background (40-47) + default (49) + bright (100-107)
    '40':'background:#000',      '41':'background:#c0392b',  '42':'background:#27ae60',
    '43':'background:#b8860b',   '44':'background:#2980b9',  '45':'background:#8e44ad',
    '46':'background:#16a085',   '47':'background:#bdc3c7',  '49':null,
    '100':'background:#555',     '101':'background:#ff7b72', '102':'background:#7ee787',
    '103':'background:#ffa657',  '104':'background:#79c0ff', '105':'background:#d2a8ff',
    '106':'background:#39c5cf',  '107':'background:#f0f6fc',
};

function ansiToHtml(raw) {
    const esc = s => s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

    // OSC 8 hyperlinks: ESC ] 8 ; ; URL ST ... text ... ESC ] 8 ; ; ST
    // ST = BEL (\x07) or String Terminator (ESC \).
    // Replace with \x02URL\x02text\x03 markers — they contain no \x1b[ so
    // the SGR split below won't corrupt them. The URL passes through esc()
    // unchanged (& → &amp; is correct for HTML href attributes).
    const ST = '(?:\x07|\x1b\\\\)';
    raw = raw.replace(
        new RegExp('\x1b\\]8;;([^\x07\x1b]*?)' + ST + '([\\s\\S]*?)\x1b\\]8;;' + ST, 'g'),
        (_m, url, text) => url ? `\x02${url}\x02${text}\x03` : text
    );
    // Strip any remaining OSC sequences (window title, cursor colour, etc.)
    raw = raw.replace(new RegExp('\x1b\\][^\x07\x1b]*?' + ST, 'g'), '');

    // SGR (colour/style) — split on CSI ESC[
    const chunks = raw.split('\x1b[');
    let html = esc(chunks[0]);
    let depth = 0;

    for (let i = 1; i < chunks.length; i++) {
        const m = chunks[i].match(/^([0-9;]*)([A-Za-z])([\s\S]*)$/);
        if (!m) { html += esc(chunks[i]); continue; }
        const [, params, letter, rest] = m;

        if (letter === 'm') {
            const codes = params === '' ? ['0'] : params.split(';');
            for (const code of codes) {
                if (code === '0' || code === '') {
                    while (depth-- > 0) html += '</span>';
                    depth = 0;
                } else if (ANSI_STYLES[code] != null) {
                    html += `<span style="${ANSI_STYLES[code]}">`;
                    depth++;
                }
            }
        }
        // All non-m CSI sequences (cursor movement, erase, etc.) are silently dropped

        // CR followed by non-LF overwrites the current line — normalise to newline
        const text = rest.replace(/\r[^\n]/g, '\n');
        html += esc(text);
    }
    while (depth-- > 0) html += '</span>';

    // Restore hyperlink markers as clickable <a> tags.
    // URL was already HTML-escaped by esc() in the SGR pass above.
    html = html.replace(
        /\x02([^\x02\x03]*)\x02([\s\S]*?)\x03/g,
        (_m, url, linkHtml) =>
            `<a href="${url}" target="_blank" rel="noopener" style="color:#79c0ff;text-decoration:underline">${linkHtml}</a>`
    );

    return html;
}

function copyOutput() {
    const text = document.getElementById('out-pre').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.getElementById('copy-btn');
        btn.textContent = 'Copied!';
        btn.classList.replace('btn-outline-secondary', 'btn-outline-success');
        setTimeout(() => {
            btn.textContent = 'Copy';
            btn.classList.replace('btn-outline-success', 'btn-outline-secondary');
        }, 1800);
    });
}

function setGithubToken() {
    const t = prompt('Enter your GitHub token (blank = unauthenticated):');
    if (t !== null) {
        sessionStorage.setItem('github_token', t.trim());
        const f = new FormData();
        f.append('github_token', t.trim());
        fetch('?set_gh_token=1', { method: 'POST', body: f });
    }
}
</script>
</body>
</html>
