<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @var string $nino
 * @var int $statusCode
 * @var array<int, array<string, mixed>> $businesses
 * @var array<string, mixed> $raw
 */

echo H::openTag('div', ['class' => 'container mt-4']);

echo H::openTag('div', ['class' => 'card mb-3']);
echo H::openTag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
echo H::tag('strong', 'Self-employed Businesses');
echo H::a('← Back', '/backend/hmrc', ['class' => 'btn btn-sm btn-outline-secondary']);
echo H::closeTag('div');

echo H::openTag('div', ['class' => 'card-body']);

echo H::openTag('p');
echo 'NINO: ' . H::tag('code', H::encode($nino));
echo ' &nbsp; HTTP ' . H::tag('span',
    (string) $statusCode,
    ['class' => $statusCode === 200 ? 'badge bg-success' : 'badge bg-danger']);
echo H::closeTag('p');

if ($statusCode !== 200) {
    echo H::tag('pre',
        H::encode((string) json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
        ['class' => 'bg-light p-3 rounded small']);
} elseif ($businesses === []) {
    echo H::tag('p', 'No self-employment businesses found for this NINO.',
        ['class' => 'text-muted']);
} else {
    echo H::openTag('div', ['class' => 'table-responsive']);
    echo H::openTag('table', ['class' => 'table table-sm table-bordered']);
    echo H::openTag('thead', ['class' => 'table-light']);
    echo H::openTag('tr');
    foreach (['Business ID', 'Trading Name', 'Business Address', 'Accounting Period', 'Commencement Date'] as $col) {
        echo H::tag('th', $col);
    }
    echo H::closeTag('tr');
    echo H::closeTag('thead');
    echo H::openTag('tbody');
    foreach ($businesses as $biz) {
        /** @var array<string, mixed> $biz */
        $address = $biz['businessAddressDetails'] ?? [];
        /** @var array<string, mixed> $address */
        $addressStr = implode(', ', array_filter([
            (string) ($address['addressLine1'] ?? ''),
            (string) ($address['addressLine2'] ?? ''),
            (string) ($address['addressLine3'] ?? ''),
            (string) ($address['addressLine4'] ?? ''),
            (string) ($address['postCode'] ?? ''),
        ]));
        $period = $biz['accountingPeriod'] ?? [];
        /** @var array<string, mixed> $period */
        $periodStr = ($period['start'] ?? '') !== ''
            ? H::encode((string) $period['start']) . ' → ' . H::encode((string) ($period['end'] ?? ''))
            : '—';
        echo H::openTag('tr');
        echo H::tag('td', H::tag('code', H::encode((string) ($biz['selfEmploymentId'] ?? '—'))));
        echo H::tag('td', H::encode((string) ($biz['tradingName'] ?? '—')));
        echo H::tag('td', H::encode($addressStr !== '' ? $addressStr : '—'));
        echo H::tag('td', $periodStr, ['class' => 'text-nowrap']);
        echo H::tag('td', H::encode((string) ($biz['commencementDate'] ?? '—')));
        echo H::closeTag('tr');
    }
    echo H::closeTag('tbody');
    echo H::closeTag('table');
    echo H::closeTag('div');
}

echo H::closeTag('div');
echo H::closeTag('div');
echo H::closeTag('div');
