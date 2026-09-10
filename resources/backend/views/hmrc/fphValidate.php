<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Renders the Test Fraud Prevention Headers API's own /validate response
 * (see https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/txm-fph-validator-api/1.0/oas/resolved
 * for the real shape) -- previously returned to the browser as raw JSON;
 * HmrcController::fphValidate()'s own docblock covers the error-case fix
 * (a platform-level failure like RESOURCE_FORBIDDEN never reaches this
 * view at all, it's a flash message instead). This view only ever
 * renders a genuine 200, i.e. the validation actually ran -- $code here
 * is the outcome (VALID_HEADERS/INVALID_HEADERS/POTENTIALLY_INVALID_HEADERS),
 * not an HTTP or platform error code.
 *
 * @var string $alert
 * @var string $specVersion
 * @var string $code
 * @var string $message
 * @var list<array<string, mixed>> $errors
 * @var list<array<string, mixed>> $warnings
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var App\Invoice\Setting\SettingRepository $s
 */

$badgeClass = match ($code) {
    'VALID_HEADERS' => 'badge bg-success',
    'INVALID_HEADERS' => 'badge bg-danger',
    'POTENTIALLY_INVALID_HEADERS' => 'badge bg-warning text-dark',
    default => 'badge bg-secondary',
};
$badgeText = match ($code) {
    'VALID_HEADERS' => $translator->translate('mtd.fph.all.valid'),
    'INVALID_HEADERS' => $translator->translate('mtd.fph.some.invalid'),
    'POTENTIALLY_INVALID_HEADERS' =>
        $translator->translate('mtd.fph.some.advisories'),
    default => $translator->translate('mtd.fph.no.provided'),
};

echo $s->getSetting('disable_flash_messages') === '0' ? $alert : '';

echo H::openTag('div', ['class' => 'container mt-4']);

echo H::openTag('div', ['class' => 'card mb-3']);
echo H::openTag('div', [
    'class' => 'card-header d-flex justify-content-between align-items-center',
]);
echo H::tag('strong', 'Fraud Prevention Headers — Validation Result');
echo H::a('← Back', '/backend/hmrc', [
    'class' => 'btn btn-sm btn-outline-secondary',
]);
echo H::closeTag('div');

echo H::openTag('div', ['class' => 'card-body']);

echo H::openTag('p');
echo H::tag('span', $badgeText, ['class' => $badgeClass]);
if ($specVersion !== '') {
    echo ' &nbsp; ' . H::tag(
        'small',
        'Spec v' . H::encode($specVersion),
        ['class' => 'text-muted'],
    );
}
echo H::closeTag('p');

if ($message !== '') {
    echo H::tag('p', H::encode($message));
}

/**
 * @var Closure(string, list<array<string, mixed>>): void $renderIssueTable
 */
$renderIssueTable = static function (string $heading, array $issues): void {
    if ($issues === []) {
        return;
    }
    echo H::tag('h6', $heading);
    echo H::openTag('div', ['class' => 'table-responsive mb-3']);
    echo H::openTag('table', ['class' => 'table table-sm table-bordered']);
    echo H::openTag('thead', ['class' => 'table-light']);
    echo H::openTag('tr');
    foreach (['Code', 'Message', 'Headers'] as $col) {
        echo H::tag('th', $col);
    }
    echo H::closeTag('tr');
    echo H::closeTag('thead');
    echo H::openTag('tbody');
    foreach ($issues as $issue) {
        if (!is_array($issue)) {
            continue;
        }
        $issueCode = isset($issue['code']) ? (string) $issue['code'] : '—';
        $issueMessage = isset($issue['message']) ? (string) $issue['message'] : '—';
        /** @var list<string> $issueHeaders */
        $issueHeaders = (array) ($issue['headers'] ?? []);

        echo H::openTag('tr');
        echo H::tag('td', H::tag('code', H::encode($issueCode)));
        echo H::tag('td', H::encode($issueMessage));
        echo H::tag('td', H::encode(implode(', ', $issueHeaders) ?: '—'));
        echo H::closeTag('tr');
    }
    echo H::closeTag('tbody');
    echo H::closeTag('table');
    echo H::closeTag('div');
};

$renderIssueTable('Errors', $errors);
$renderIssueTable('Warnings', $warnings);

echo H::closeTag('div');
echo H::closeTag('div');
echo H::closeTag('div');
