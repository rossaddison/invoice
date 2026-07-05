<?php

declare(strict_types=1);

use App\Auth\Client\HmrcApiCatalogue;
use Yiisoft\Html\Html as H;

/**
 * @var string $vrn
 * @var string $fphConnectionMethod
 * @var string $govVendorProductName
 * @var string $govVendorVersion
 * @var string $grantedScope
 * @var array<string, array{name: string, scopes: list<string>, needs: string, serviceName: string, version: string}> $availableApis
 * @var bool $subscriptionsLoaded
 */

$vrnSet = $vrn !== '';
$fphSet = $fphConnectionMethod !== '';
$loggedIn = $grantedScope !== '';

echo H::openTag('div', ['class' => 'container mt-4']);
echo H::openTag('div', ['class' => 'row']);
echo H::openTag('div', ['class' => 'col-12 col-md-8 offset-md-2']);

// ── Status card ──────────────────────────────────────────────────────────────
echo H::openTag('div', ['class' => 'card mb-3']);
echo H::openTag('div', ['class' => 'card-header']);
echo H::tag('strong', 'HMRC Making Tax Digital — Status');
echo H::closeTag('div');
echo H::openTag('div', ['class' => 'card-body']);
echo H::openTag('table', ['class' => 'table table-sm']);
echo H::openTag('tbody');

echo H::openTag('tr');
echo H::tag('td', 'VAT Registration Number (VRN)');
echo H::tag('td', $vrnSet
    ? H::tag('span', $vrn, ['class' => 'text-success'])
    : H::tag('span', 'Not set — configure in Settings → Making Tax Digital', ['class' => 'text-danger']));
echo H::closeTag('tr');

echo H::openTag('tr');
echo H::tag('td', 'FPH Connection Method');
echo H::tag('td', $fphSet
    ? H::tag('span', $fphConnectionMethod, ['class' => 'text-success'])
    : H::tag('span', 'Not set — run Generate in Settings → Making Tax Digital', ['class' => 'text-warning']));
echo H::closeTag('tr');

echo H::openTag('tr');
echo H::tag('td', 'Vendor Product');
echo H::tag('td', $govVendorProductName !== ''
    ? $govVendorProductName
    : H::tag('span', 'Not set', ['class' => 'text-muted']));
echo H::closeTag('tr');

echo H::openTag('tr');
echo H::tag('td', 'Vendor Version');
echo H::tag('td', $govVendorVersion !== ''
    ? $govVendorVersion
    : H::tag('span', 'Not set', ['class' => 'text-muted']));
echo H::closeTag('tr');

if ($loggedIn) {
    echo H::openTag('tr');
    echo H::tag('td', 'Granted Scopes');
    echo H::tag('td', H::tag('code', H::encode($grantedScope), ['class' => 'small text-success']));
    echo H::closeTag('tr');
}

echo H::closeTag('tbody');
echo H::closeTag('table');

echo H::openTag('div', ['class' => 'd-flex gap-2 flex-wrap']);
echo H::a('Test FPH Headers', '/backend/hmrc/fphValidate', ['class' => 'btn btn-sm btn-outline-primary']);
echo H::a('FPH Feedback (VAT)', '/backend/hmrc/fphFeedback/vat', ['class' => 'btn btn-sm btn-outline-secondary']);
echo H::a('VAT Obligations', '/backend/hmrc/vatObligations', ['class' => 'btn btn-sm btn-outline-info']);
echo H::closeTag('div');

echo H::closeTag('div');
echo H::closeTag('div');

// ── API catalogue / combo box ─────────────────────────────────────────────────
echo H::openTag('div', ['class' => 'card mb-3']);
echo H::openTag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
echo H::tag('strong', 'Available APIs');
if ($subscriptionsLoaded) {
    echo H::tag('span', '✅ Subscriptions loaded from HMRC', ['class' => 'badge bg-success']);
} elseif ($loggedIn) {
    echo H::tag('span', 'Derived from granted scopes', ['class' => 'badge bg-info text-dark']);
} else {
    echo H::tag('span', 'Log in with HMRC to see your subscriptions', ['class' => 'badge bg-secondary']);
}
echo H::closeTag('div');

echo H::openTag('div', ['class' => 'card-body']);

if ($availableApis === []) {
    echo H::tag('p',
        'No APIs detected. Log in via HMRC OAuth or ensure your client_id is set in .env.',
        ['class' => 'text-muted mb-0']);
} else {
    echo H::openTag('form', ['method' => 'GET', 'action' => '', 'id' => 'api-select-form']);

    echo H::openTag('div', ['class' => 'mb-3']);
    echo H::tag('label', 'Select API to exercise:', ['for' => 'api-context', 'class' => 'form-label fw-semibold']);

    echo H::openTag('select', [
        'id'       => 'api-context',
        'name'     => 'context',
        'class'    => 'form-select',
        'onchange' => 'document.getElementById("api-select-form").action = this.options[this.selectedIndex].dataset.route || "#"; '
            . 'document.getElementById("btn-go").disabled = !this.options[this.selectedIndex].dataset.route;',
    ]);
    echo H::tag('option', '— choose an API —', ['value' => '']);

    foreach ($availableApis as $context => $entry) {
        $route     = HmrcApiCatalogue::routeFor($context);
        $routeUrl  = $route !== null ? '/' . str_replace('/', '/', $route) : '';
        $needsLabel = match ($entry['needs']) {
            HmrcApiCatalogue::NEEDS_NINO => ' [NINO]',
            HmrcApiCatalogue::NEEDS_VRN  => ' [VRN]',
            HmrcApiCatalogue::NEEDS_EORI => ' [EORI]',
            default                      => '',
        };
        echo H::tag('option',
            H::encode($entry['name'] . ' v' . $entry['version'] . $needsLabel),
            [
                'value'       => $context,
                'data-route'  => $routeUrl,
                'data-needs'  => $entry['needs'],
            ]);
    }

    echo H::closeTag('select');
    echo H::tag('small',
        'Routes marked [NINO] require logging in via HMRC OAuth first.',
        ['class' => 'text-muted']);
    echo H::closeTag('div');

    echo H::tag('button', 'Go →', [
        'type'     => 'button',
        'id'       => 'btn-go',
        'class'    => 'btn btn-primary',
        'disabled' => true,
        'onclick'  => 'var f=document.getElementById("api-select-form"); if(f.action && f.action !== location.href) location.href=f.action;',
    ]);

    echo H::closeTag('form');

    // Scope reference table
    echo H::tag('hr', '');
    echo H::tag('p', 'Scope reference for your registered application:', ['class' => 'fw-semibold mb-1']);
    echo H::openTag('div', ['class' => 'table-responsive']);
    echo H::openTag('table', ['class' => 'table table-sm table-bordered small']);
    echo H::openTag('thead', ['class' => 'table-light']);
    echo H::openTag('tr');
    foreach (['API', 'Version', 'Scopes', 'Needs'] as $col) {
        echo H::tag('th', $col);
    }
    echo H::closeTag('tr');
    echo H::closeTag('thead');
    echo H::openTag('tbody');
    foreach ($availableApis as $entry) {
        echo H::openTag('tr');
        echo H::tag('td', H::encode($entry['name']));
        echo H::tag('td', H::encode($entry['version']));
        echo H::tag('td', H::tag('code', H::encode(implode(' ', $entry['scopes'])), ['class' => 'small']));
        echo H::tag('td', H::encode(strtoupper($entry['needs'])));
        echo H::closeTag('tr');
    }
    echo H::closeTag('tbody');
    echo H::closeTag('table');
    echo H::closeTag('div');
}

echo H::closeTag('div');
echo H::closeTag('div');

echo H::closeTag('div');
echo H::closeTag('div');
echo H::closeTag('div');
