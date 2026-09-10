<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Renders the Business Details (MTD) /list response, filtered to
 * self-employment entries -- see HmrcController::selfEmploymentBusinesses()'s
 * own docblock for why this is no longer the self-employment-business-api
 * itself. That /list endpoint only returns businessId/typeOfBusiness/
 * tradingName/tradingType -- no address, accounting period, or
 * commencement date (those lived in the old, now-gone endpoint's response
 * and would need a separate per-businessId Business Details call this
 * page doesn't make).
 *
 * @var string $nino
 * @var int $statusCode
 * @var array<int, array<string, mixed>> $businesses
 * @var array<string, mixed> $raw
 */

echo H::openTag('div', ['class' => 'container mt-4']);

echo H::openTag('div', ['class' => 'card mb-3']);
echo H::openTag('div', [
    'class' => 'card-header d-flex justify-content-between align-items-center',
]);
echo H::tag('strong', 'Self-employed Businesses');
echo H::a('← Back', '/backend/hmrc', [
    'class' => 'btn btn-sm btn-outline-secondary',
]);
echo H::closeTag('div');

echo H::openTag('div', ['class' => 'card-body']);

echo H::openTag('p');
echo 'NINO: ' . H::tag('code', H::encode($nino));
echo ' &nbsp; HTTP ' . H::tag('span',
    (string) $statusCode,
    ['class' => $statusCode === 200 ? 'badge bg-success' : 'badge bg-danger']);
echo H::closeTag('p');

if ($statusCode !== 200) {
    $rawJson = (string) json_encode(
        $raw,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
    );
    echo H::tag('pre',
        H::encode($rawJson),
        ['class' => 'bg-light p-3 rounded small']);
} elseif ($businesses === []) {
    echo H::tag('p', 'No self-employment businesses found for this NINO.',
        ['class' => 'text-muted']);
} else {
    echo H::openTag('div', ['class' => 'table-responsive']);
    echo H::openTag('table', ['class' => 'table table-sm table-bordered']);
    echo H::openTag('thead', ['class' => 'table-light']);
    echo H::openTag('tr');
    foreach (['Business ID', 'Trading Name', 'Trading Type'] as $col) {
        echo H::tag('th', $col);
    }
    echo H::closeTag('tr');
    echo H::closeTag('thead');
    echo H::openTag('tbody');
    foreach ($businesses as $biz) {
        echo H::openTag('tr');
        $businessId = (string) ($biz['businessId'] ?? '—');
        echo H::tag('td', H::tag('code', H::encode($businessId)));
        echo H::tag('td', H::encode((string) ($biz['tradingName'] ?? '—')));
        echo H::tag('td', H::encode((string) ($biz['tradingType'] ?? '—')));
        echo H::closeTag('tr');
    }
    echo H::closeTag('tbody');
    echo H::closeTag('table');
    echo H::closeTag('div');
}

echo H::closeTag('div');
echo H::closeTag('div');
echo H::closeTag('div');
