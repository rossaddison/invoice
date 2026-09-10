<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Renders the Test Fraud Prevention Headers API's own
 * /validation-feedback response (see
 * https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/txm-fph-validator-api/1.0/oas/resolved
 * for the real shape) -- feedback on this application's own past
 * requests to the given service, not a single synthetic validation
 * like fphValidate.php's own view. Each entry covers one real request:
 * an overall code, per-header results, and any cross-header
 * validations (e.g. Gov-Vendor-Forwarded/Gov-Vendor-Public-IP/
 * Gov-Client-Public-IP agreeing with each other).
 *
 * @var string $api
 * @var list<array<string, mixed>> $requests
 */

$badgeFor = static function (string $code): array {
    return match ($code) {
        'VALID_HEADERS', 'VALID_HEADER' => ['badge bg-success', $code],
        'INVALID_HEADERS', 'INVALID_HEADER' => ['badge bg-danger', $code],
        'POTENTIALLY_INVALID_HEADERS', 'POTENTIALLY_INVALID_HEADER' =>
            ['badge bg-warning text-dark', $code],
        'MISSING_HEADER' => ['badge bg-danger', $code],
        'UNEXPECTED_HEADER', 'TEST_SCENARIO_HEADER' =>
            ['badge bg-info text-dark', $code],
        'NO_HEADERS' => ['badge bg-secondary', $code],
        default => ['badge bg-secondary', $code === '' ? '—' : $code],
    };
};

echo H::openTag('div', ['class' => 'container mt-4']);

echo H::openTag('div', ['class' => 'card mb-3']);
echo H::openTag('div', [
    'class' => 'card-header d-flex justify-content-between align-items-center',
]);
echo H::tag('strong', 'Fraud Prevention Headers — Feedback ('
    . H::encode($api) . ')');
echo H::a('← Back', '/backend/hmrc', [
    'class' => 'btn btn-sm btn-outline-secondary',
]);
echo H::closeTag('div');

echo H::openTag('div', ['class' => 'card-body']);

if ($requests === []) {
    echo H::tag('p',
        'No recent requests found for this API.',
        ['class' => 'text-muted']);
}

foreach ($requests as $req) {
    $path = isset($req['path']) ? (string) $req['path'] : '—';
    $method = isset($req['method']) ? (string) $req['method'] : '—';
    $timestamp = isset($req['requestTimestamp'])
        ? (string) $req['requestTimestamp']
        : '—';
    $code = isset($req['code']) ? (string) $req['code'] : '';
    [$badgeClass, $badgeText] = $badgeFor($code);

    echo H::openTag('div', ['class' => 'border rounded p-3 mb-3']);
    echo H::openTag('p', ['class' => 'mb-2']);
    echo H::tag('span', $badgeText, ['class' => $badgeClass]);
    echo ' &nbsp; ' . H::tag('code', H::encode($method) . ' ' . H::encode($path));
    echo H::closeTag('p');
    echo H::tag('p', H::tag('small', 'Requested: ' . H::encode($timestamp)), [
        'class' => 'text-muted',
    ]);

    /** @var list<array<string, mixed>> $headersList */
    $headersList = (array) ($req['headers'] ?? []);
    if ($headersList !== []) {
        echo H::openTag('div', ['class' => 'table-responsive mb-2']);
        echo H::openTag('table', ['class' => 'table table-sm table-bordered']);
        echo H::openTag('thead', ['class' => 'table-light']);
        echo H::openTag('tr');
        foreach (['Header', 'Value', 'Result', 'Errors', 'Warnings'] as $col) {
            echo H::tag('th', $col);
        }
        echo H::closeTag('tr');
        echo H::closeTag('thead');
        echo H::openTag('tbody');
        foreach ($headersList as $h) {
            $headerName = isset($h['header']) ? (string) $h['header'] : '—';
            $headerValue = isset($h['value']) ? (string) $h['value'] : '—';
            $headerCode = isset($h['code']) ? (string) $h['code'] : '';
            [$hBadgeClass, $hBadgeText] = $badgeFor($headerCode);
            /** @var list<string> $headerErrors */
            $headerErrors = (array) ($h['errors'] ?? []);
            /** @var list<string> $headerWarnings */
            $headerWarnings = (array) ($h['warnings'] ?? []);

            echo H::openTag('tr');
            echo H::tag('td', H::tag('code', H::encode($headerName)));
            echo H::tag('td', H::encode($headerValue));
            echo H::tag('td',
                H::tag('span', $hBadgeText, ['class' => $hBadgeClass]));
            echo H::tag('td', H::encode(implode('; ', $headerErrors) ?: '—'));
            echo H::tag('td', H::encode(implode('; ', $headerWarnings) ?: '—'));
            echo H::closeTag('tr');
        }
        echo H::closeTag('tbody');
        echo H::closeTag('table');
        echo H::closeTag('div');
    }

    /** @var list<array<string, mixed>> $crossValidation */
    $crossValidation = (array) ($req['crossValidation'] ?? []);
    if ($crossValidation !== []) {
        echo H::tag('p', H::tag('strong', 'Cross-validation'), ['class' => 'mb-1']);
        echo H::openTag('div', ['class' => 'table-responsive']);
        echo H::openTag('table', ['class' => 'table table-sm table-bordered']);
        echo H::openTag('thead', ['class' => 'table-light']);
        echo H::openTag('tr');
        foreach (['Headers', 'Result', 'Errors'] as $col) {
            echo H::tag('th', $col);
        }
        echo H::closeTag('tr');
        echo H::closeTag('thead');
        echo H::openTag('tbody');
        foreach ($crossValidation as $cv) {
            /** @var list<string> $cvHeaders */
            $cvHeaders = (array) ($cv['headers'] ?? []);
            $cvCode = isset($cv['code']) ? (string) $cv['code'] : '';
            [$cvBadgeClass, $cvBadgeText] = $badgeFor($cvCode);
            /** @var list<string> $cvErrors */
            $cvErrors = (array) ($cv['errors'] ?? []);

            echo H::openTag('tr');
            echo H::tag('td', H::encode(implode(', ', $cvHeaders) ?: '—'));
            echo H::tag('td',
                H::tag('span', $cvBadgeText, ['class' => $cvBadgeClass]));
            echo H::tag('td', H::encode(implode('; ', $cvErrors) ?: '—'));
            echo H::closeTag('tr');
        }
        echo H::closeTag('tbody');
        echo H::closeTag('table');
        echo H::closeTag('div');
    }

    echo H::closeTag('div');
}

echo H::closeTag('div');
echo H::closeTag('div');
echo H::closeTag('div');
