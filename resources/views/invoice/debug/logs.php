<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @var array<int,string> $lines
 * @var string $logFile
 */

$levelStyle = static function (string $line): string {
    $l = strtolower($line);
    if (str_contains($l, '[emergency]') || str_contains($l, '[alert]') || str_contains($l, '[critical]') || str_contains($l, '[error]')) {
        return 'color:#ff6b6b;';
    }
    if (str_contains($l, '[warning]')) {
        return 'color:#ffa94d;';
    }
    if (str_contains($l, '[info]')) {
        return 'color:#74c0fc;';
    }
    return 'color:#adb5bd;';
};

$containerFluid  = ['class' => 'container-fluid mt-3'];
$flexHeading     = ['class' => 'd-flex align-items-baseline gap-3 mb-2'];
$headingSmall    = ['class' => 'text-muted', 'style' => 'font-size:.85rem;'];
$subtext         = ['class' => 'text-muted mb-2', 'style' => 'font-size:.85rem;'];
$logWrap         = ['style' => 'max-height:75vh;overflow-y:auto;background:#1a1a2e;border-radius:6px;padding:1rem;'];
$lineStyle       = 'font-family:monospace;font-size:.78rem;line-height:1.5;white-space:pre-wrap;word-break:break-all;border-bottom:1px solid #2a2a3e;padding:1px 0;';

echo H::openTag('div', $containerFluid); //0
echo H::openTag('div', $flexHeading); //1
echo H::tag('h4', 'Application Log', ['class' => 'mb-0']); //2
echo H::tag(
    'small',
    H::encode($logFile),
    $headingSmall
); //2
echo H::closeTag('div'); //1
echo H::tag(
    'p',
    'Last 100 lines &mdash; newest first &mdash; only visible when ' . H::tag('code', 'YII_DEBUG=true'),
    $subtext
); //1
if ($lines === []) {
    echo H::tag('p', 'Log file is empty or does not exist yet.', ['class' => 'text-success']); //1
} else {
    echo H::openTag('div', $logWrap); //1
    foreach ($lines as $line) {
        echo H::tag(
            'div',
            H::encode($line),
            ['style' => $lineStyle . $levelStyle($line)]
        ); //2
    }
    echo H::closeTag('div'); //1
}
echo H::closeTag('div'); //0
