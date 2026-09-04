<?php

declare(strict_types=1);

use App\Invoice\System\PhpVersionCheckResult;
use Yiisoft\Html\Html as H;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var PhpVersionCheckResult $phpVersionCheckResult
 */

$row       = ['class' => 'row'];
$colMd8    = ['class' => 'col-12 col-md-8 offset-md-2'];
$panel     = ['class' => 'card'];
$panelHead = ['class' => 'card-header'];
$panelBody = ['class' => 'card-body'];

$r = $phpVersionCheckResult;

// Update instructions per environment. Kept as plain text blocks (never
// executed server-side) — see docs/CSP_INLINE_HANDLER_SWEEP_GAPS.md for why
// this app never runs shell commands triggered by an HTTP request.
$instructions = [
    'yii' => "REM Windows, running via 'php yii serve' (CLI php.exe, not WAMP's Apache module)\r\n"
        . "php -v\r\n"
        . "REM Download the matching x64 Non-Thread-Safe zip from https://windows.php.net/download/\r\n"
        . "REM Extract it, point your PATH at the new php.exe, then verify:\r\n"
        . 'php -v',
    'wamp' => "REM Windows, WAMP's Apache-bundled PHP module\r\n"
        . "REM 1. Download the matching x64 Thread-Safe zip from https://windows.php.net/download/\r\n"
        . "REM 2. Extract to wamp64\\bin\\php\\phpX.Y.Z (copy php.ini from the old version)\r\n"
        . "REM 3. Left-click WAMP tray icon -> PHP -> Version -> select the new version\r\n"
        . 'REM 4. Restart All Services',
    'alpine' => "apk update\n"
        . "apk policy php84\n"
        . "apk upgrade \$(apk info | grep '^php84')\n"
        . "php -v\n"
        . "rc-service apache2 restart   # mod_php\n"
        . 'rc-service php-fpm84 restart # php-fpm, if used instead',
    'linux' => "# Debian/Ubuntu (apt-based)\n"
        . "sudo apt update\n"
        . "apt list --upgradable 2>/dev/null | grep php\n"
        . "sudo apt install --only-upgrade php8.4 php8.4-common php8.4-fpm\n"
        . "php -v\n"
        . 'sudo systemctl restart apache2 php8.4-fpm',
];
$platformLabels = ['yii' => 'yii serve (Windows CLI)', 'wamp' => 'WAMP', 'alpine' => 'Alpine Linux', 'linux' => 'Linux (apt)'];

echo H::openTag('div', $row); //1
echo H::openTag('div', $colMd8); //2
echo H::openTag('div', $panel); //3
echo H::openTag('div', $panelHead); //4
echo H::encode($translator->translate('system.updates'));
echo H::closeTag('div'); //4
echo H::openTag('div', $panelBody); //4

echo H::openTag('dl', ['class' => 'row mb-4']); //5
echo H::tag('dt', H::encode($translator->translate('system.updates.current.version')), ['class' => 'col-sm-4']);
echo H::tag('dd', H::encode($r->currentVersion), ['class' => 'col-sm-8']);
echo H::tag('dt', H::encode($translator->translate('system.updates.latest.version')), ['class' => 'col-sm-4']);
echo H::tag('dd', H::encode($r->latestVersion ?? '—'), ['class' => 'col-sm-8']);
echo H::tag('dt', H::encode($translator->translate('system.updates.last.checked')), ['class' => 'col-sm-4']);
echo H::tag(
    'dd',
    H::encode($r->checkedAt ?? $translator->translate('system.updates.never.checked')),
    ['class' => 'col-sm-8']
);
echo H::closeTag('dl'); //5

if ($r->error !== null) { //5
    echo H::tag(
        'div',
        H::encode($translator->translate('system.updates.check.failed')) . ': ' . H::encode($r->error),
        ['class' => 'alert alert-danger']
    );
} elseif ($r->latestVersion === null) { //5
    echo H::tag(
        'div',
        H::encode($translator->translate('system.updates.never.checked')),
        ['class' => 'alert alert-secondary']
    );
} elseif ($r->isOutdated) { //5
    $alertClass = $r->isSecurityRelease ? 'alert-danger' : 'alert-warning';
    $label = $r->isSecurityRelease
        ? H::encode($translator->translate('system.updates.security.release')) . ' — '
        : '';
    echo H::tag(
        'div',
        $label . H::encode($translator->translate('system.updates.outdated')) . ': ' . H::encode($r->latestVersion),
        ['class' => 'alert ' . $alertClass]
    );
} else { //5
    echo H::tag(
        'div',
        H::encode($translator->translate('system.updates.up.to.date')),
        ['class' => 'alert alert-success']
    );
} //5

echo H::a(
    H::encode($translator->translate('system.updates.check.now')),
    $urlGenerator->generate('setting/checkPhpVersion'),
    ['class' => 'btn btn-primary mb-4']
);

echo H::tag('p', H::encode($translator->translate('system.updates.select.platform')), ['class' => 'mb-2']);

echo H::openTag('div', ['class' => 'btn-group mb-3', 'role' => 'group']); //5
foreach ($platformLabels as $platform => $label) { //6
    echo H::button(H::encode($label), [
        'type' => 'button',
        'class' => 'btn btn-outline-secondary',
        'data-action' => 'toggle-panel',
        'data-target' => '#system-update-instructions-' . $platform,
    ]);
} //6
echo H::closeTag('div'); //5

foreach ($instructions as $platform => $commands) { //5
    $preId = 'system-update-instructions-text-' . $platform;
    echo H::openTag('div', [
        'id' => 'system-update-instructions-' . $platform,
        'class' => 'card mb-2 d-none',
    ]); //6
    echo H::openTag('div', ['class' => 'card-body']); //7
    echo H::tag('pre', H::encode($commands), ['id' => $preId, 'class' => 'mb-2']);
    echo H::button(H::encode($translator->translate('copy')), [
      'type' => 'button',
      'class' => 'btn btn-sm btn-outline-primary',
      'data-action' => 'copy-to-clipboard',
      'data-copy-target' => '#' . $preId,
    ]);
    echo H::closeTag('div'); //7
    echo H::closeTag('div'); //6
} //5

echo H::closeTag('div'); //4
echo H::closeTag('div'); //3
echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
