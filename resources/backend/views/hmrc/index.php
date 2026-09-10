<?php

declare(strict_types=1);

use App\Auth\Client\HmrcApiCatalogue;
use App\Auth\Client\HmrcDeveloperHubLinks;
use App\Backend\Asset\HmrcApiSelectAsset;
use Yiisoft\Bootstrap5\ButtonVariant;
use Yiisoft\Bootstrap5\Dropdown;
use Yiisoft\Bootstrap5\DropdownItem;
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\I;

/**
 * @var string $vrn
 * @var string $nino
 * @var string $fphConnectionMethod
 * @var string $govVendorProductName
 * @var string $govVendorVersion
 * @var string $grantedScope
 * @var array<string, array{name: string, scopes: list<string>, needs: string, serviceName: string, version: string}> $availableApis
 * @var array<string, array{name: string, scopes: list<string>, needs: string, serviceName: string, version: string}> $fullCatalogue
 * @var bool $subscriptionsLoaded
 * @var string $hmrcAuthUrl
 * @var string $developerHubAppId
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

$assetManager->register(HmrcApiSelectAsset::class);

$vrnSet = $vrn !== '';
$ninoSet = $nino !== '';
$fphSet = $fphConnectionMethod !== '';
$loggedIn = $grantedScope !== '';
$developerHubAppIdSet = $developerHubAppId !== '';

// Every field below that has a real input on Settings -> Making Tax
// Digital gets a link straight to it (its href's #fragment matches that
// input's own id, e.g. 'settings[nino]' -- native browser anchor
// navigation scrolls straight to it, no JS needed; same mechanism
// WebControllerService::generateUrl()'s own docblock already documents
// for exactly this "deep link to a specific field" use). Rows with no
// corresponding input (Vendor Product/Version, Developer Hub
// Application ID, Granted Scopes) stay plain text.
$settingsMtdUrl = $urlGenerator->generate(
    'setting/tabIndex',
    [],
    ['active' => 'mtd'],
);
$settingsMtdFieldUrl = static fn (string $fieldId): string =>
    $urlGenerator->generate('setting/tabIndex', [], ['active' => 'mtd'], $fieldId);

echo H::openTag('div', ['class' => 'container mt-4']);
echo H::openTag('div', ['class' => 'row']);
echo H::openTag('div', ['class' => 'col-12 col-md-8 offset-md-2']);

// ── Status card ──────────────────────────────────────────────────────────────
echo H::openTag('div', ['class' => 'card mb-3']);
echo H::openTag('div', [
    'class' => 'card-header d-flex justify-content-between align-items-center',
]);
echo H::tag('strong', 'HMRC Making Tax Digital — Status');
echo H::a('← Settings', $settingsMtdUrl, [
    'class' => 'btn btn-sm btn-outline-secondary',
]);
echo H::closeTag('div');
echo H::openTag('div', ['class' => 'card-body']);
echo H::openTag('table', ['class' => 'table table-sm']);
echo H::openTag('tbody');

echo H::openTag('tr');
echo H::tag('td', H::a(
    'VAT Registration Number (VRN)',
    $settingsMtdFieldUrl('settings[vat_registration_number]'),
));
echo H::tag('td', $vrnSet
    ? H::tag('span', $vrn, ['class' => 'text-success'])
    : H::tag('span', 'Not set — configure in Settings → Making Tax Digital', ['class' => 'text-danger']));
echo H::closeTag('tr');

echo H::openTag('tr');
echo H::tag('td', H::a(
    $translator->translate('mtd.nino'),
    $settingsMtdFieldUrl('settings[nino]'),
));
echo H::tag('td', $ninoSet
    ? H::tag('span', $nino, ['class' => 'text-success'])
    : H::tag('span',
        'Not set — configure in Settings → Making Tax Digital.'
            . ' Every NINO-based API below (Self-employed Business,'
            . ' Business Details, etc.) needs this.',
        ['class' => 'text-danger']));
echo H::closeTag('tr');

echo H::openTag('tr');
echo H::tag('td', H::a(
    'FPH Connection Method',
    $settingsMtdFieldUrl('settings[fph_connection_method]'),
));
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

echo H::openTag('tr');
echo H::tag('td', $translator->translate('mtd.hmrc.developer.hub.application.id'));
echo H::tag('td', $developerHubAppIdSet
    ? H::tag('code',
        H::encode($developerHubAppId),
        ['class' => 'small text-success'])
    : H::tag('span',
        $translator->translate('mtd.hmrc.developer.hub.application.id.not.set'),
        ['class' => 'text-warning small']));
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
// 'vat-mtd', not bare 'vat' -- HMRC's txm-fph-validator-api spec's {api}
// path parameter uses each service's real "-mtd"-suffixed identifier;
// confirmed live 2026-09-09 (a bare 'vat' 404s with MATCHING_RESOURCE_NOT_FOUND).
echo H::a(
    'FPH Feedback (VAT)',
    '/backend/hmrc/fphFeedback/vat-mtd',
    ['class' => 'btn btn-sm btn-outline-secondary'],
);
echo H::a('VAT Obligations', '/backend/hmrc/vatObligations', ['class' => 'btn btn-sm btn-outline-info']);
echo H::closeTag('div');

echo H::closeTag('div');
echo H::closeTag('div');

// ── API catalogue / combo box ─────────────────────────────────────────────────
echo H::openTag('div', ['class' => 'card mb-3']);
echo H::openTag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
echo H::tag('strong', 'Available APIs');
echo H::openTag('div', ['class' => 'd-flex gap-2 align-items-center']);
if ($subscriptionsLoaded) {
    echo H::tag('span', '✅ Subscriptions loaded from HMRC', ['class' => 'badge bg-success']);
} elseif ($loggedIn) {
    echo H::tag('span', 'Derived from granted scopes', ['class' => 'badge bg-info text-dark']);
} elseif ($hmrcAuthUrl !== '') {
    echo H::tag('a', 'Log in with HMRC', [
        'href'  => $hmrcAuthUrl,
        'class' => 'btn btn-sm btn-dark',
        'id'    => 'btn-sandboxhmrc',
    ]);
} else {
    echo H::tag('span', 'Configure HMRC OAuth in Settings to log in', ['class' => 'badge bg-secondary']);
}
// Developer Hub account/application management links -- only meaningful
// once logged in via HMRC OAuth (matches this whole card's own gating);
// see HmrcDeveloperHubLinks's own docblock for why the application ID is
// a separate value from the OAuth client_id already used above.
if ($loggedIn) {
    // Every link here leaves this app for the real Developer Hub site --
    // opened in a new tab (rel=noopener so that tab can't reach back into
    // this one via window.opener) rather than navigating this one away.
    $developerHubLinkAttributes = [
        'target' => '_blank',
        'rel'    => 'noopener noreferrer',
    ];

    $developerHubItems = [];
    foreach (HmrcDeveloperHubLinks::accountLinks() as $link) {
        $developerHubItems[] = DropdownItem::link(
            $translator->translate($link['labelKey']),
            $link['url'],
            itemAttributes: $developerHubLinkAttributes,
        );
    }
    $applicationLinks = HmrcDeveloperHubLinks::applicationLinks($developerHubAppId);
    if ($applicationLinks !== []) {
        $developerHubItems[] = DropdownItem::divider();
        foreach ($applicationLinks as $link) {
            $developerHubItems[] = DropdownItem::link(
                $translator->translate($link['labelKey']),
                $link['url'],
                itemAttributes: $developerHubLinkAttributes,
            );
        }
    }
    // Deliberately NOT ButtonVariant::DARK: that's "Log in with HMRC"'s own
    // colour a few lines up, and this dropdown only ever renders once
    // already logged in -- sharing that colour made the two impossible to
    // tell apart at a glance (reported live 2026-09-10), undoing the one
    // visual signal this header used to give for login state. An outline
    // style plus an explicit icon keeps it clearly a links menu, not a
    // login call-to-action.
    echo Dropdown::widget()
        ->togglerContent(
            new I()->addClass('bi bi-box-arrow-up-right')
                . ' ' . $translator->translate('mtd.hmrc.developer.hub'),
        )
        ->togglerVariant(ButtonVariant::OUTLINE_SECONDARY)
        ->addTogglerAttribute('id', 'btn-developer-hub-links')
        ->items(...$developerHubItems);
}
echo H::closeTag('div');
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
        'id'    => 'api-context',
        'name'  => 'context',
        'class' => 'form-select',
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
        // No route means no page is wired up for this API yet (see
        // HmrcApiCatalogue::routeFor()'s own docblock for which ones
        // are) -- disabling the option outright rather than leaving it
        // selectable with a permanently-disabled Go button, which read
        // as broken (reported live 2026-09-10: picking Self Assessment,
        // logged in, Go never enabled -- correct given no route exists,
        // but nothing said so).
        $optionLabel = $entry['name'] . ' v' . $entry['version'] . $needsLabel;
        if ($route === null) {
            $optionLabel .= ' — not yet available';
        }
        echo H::tag('option',
            H::encode($optionLabel),
            [
                'value'       => $context,
                'data-route'  => $routeUrl,
                'data-needs'  => $entry['needs'],
                'disabled'    => $route === null,
            ]);
    }

    echo H::closeTag('select');
    echo H::tag('small',
        '"Not yet available" options don\'t have a page built for them yet.',
        ['class' => 'text-muted']);
    echo H::closeTag('div');

    echo H::tag('button', 'Go →', [
        'type'     => 'button',
        'id'       => 'btn-go',
        'class'    => 'btn btn-primary',
        'disabled' => true,
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

// ── Full catalogue (always visible) ──────────────────────────────────────────
echo H::openTag('div', ['class' => 'card mb-3']);
echo H::openTag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
echo H::tag('strong', 'Full API Catalogue');
echo H::tag('span',
    count($fullCatalogue) . ' APIs',
    ['class' => 'badge bg-secondary']);
echo H::closeTag('div');
echo H::openTag('div', ['class' => 'card-body p-0']);
echo H::openTag('div', ['class' => 'table-responsive']);
echo H::openTag('table', ['class' => 'table table-sm table-bordered mb-0 small']);
echo H::openTag('thead', ['class' => 'table-light']);
echo H::openTag('tr');
foreach (['API', 'Context Path', 'Version', 'Scopes', 'Needs', 'Route'] as $col) {
    echo H::tag('th', $col);
}
echo H::closeTag('tr');
echo H::closeTag('thead');
echo H::openTag('tbody');
foreach ($fullCatalogue as $context => $entry) {
    $route       = HmrcApiCatalogue::routeFor($context);
    $inGranted   = $availableApis !== [] && isset($availableApis[$context]);
    $rowClass    = $inGranted ? 'table-success' : '';
    echo H::openTag('tr', ['class' => $rowClass]);
    echo H::tag('td', H::encode($entry['name']));
    echo H::tag('td', H::tag('code', H::encode($context), ['class' => 'small']));
    echo H::tag('td', H::encode($entry['version']));
    echo H::tag('td', H::tag('code', H::encode(implode(' ', $entry['scopes'])), ['class' => 'small']));
    echo H::tag('td', H::encode(strtoupper($entry['needs'])));
    echo H::tag('td', $route !== null
        ? H::tag('span', '✅', ['data-bs-toggle' => 'tooltip', 'title' => $route])
        : H::tag('span', '—', ['class' => 'text-muted']));
    echo H::closeTag('tr');
}
echo H::closeTag('tbody');
echo H::closeTag('table');
echo H::closeTag('div');
echo H::tag('p',
    'Rows highlighted green are within your current granted token scope.',
    ['class' => 'text-muted small px-3 py-2 mb-0']);
echo H::closeTag('div');
echo H::closeTag('div');

echo H::closeTag('div');
echo H::closeTag('div');
echo H::closeTag('div');
