<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
* @var array $countries
* @var array $sender_identifier_array
* @var string $cldr
* @var string $country
*/

$col = 'col-12 col-md-6';
$formGroup = ['class' => 'mb-3'];
$panel = ['class' => 'card mb-3'];
$panelHead = ['class' => 'card-header'];
$panelBody = ['class' => 'card-body'];

// The one setting that decides which provider actually sends — read once
// here so both the select's initial value and each provider card's
// initial shown/hidden class agree with each other and with
// PeppolSendServiceRouter::resolve()'s own '' -> 'storecove' fallback.
$currentProvider = $s->getSetting('peppol_access_point_provider') ?: 'storecove';

echo H::openTag('div', ['class' => 'row']); //1
echo H::openTag('div', ['class' => 'col-12 col-md-8 offset-md-2']); //2

/* Master card — same role as Online Payments' "Add payment provider"
 * picker, except this one is an exclusive choice (only one Access
 * Point ever sends), not a multi-enable list — so selecting a
 * provider here shows only that provider's card below, never more
 * than one at once. */
echo H::openTag('div', $panel); //3
echo H::openTag('div', $panelHead); //4
echo $translator->translate('peppol.access.point');
echo H::closeTag('div'); //4
echo H::openTag('div', $panelBody); //4
echo H::openTag('p', ['class' => 'text-muted small']);
echo $translator->translate('peppol.access.point.hint');
echo H::closeTag('p');
echo H::openTag('div', $formGroup); //5
echo H::openTag('label', [
 'for' => 'settings[peppol_access_point_provider]'
]);
echo $translator->translate('peppol.access.point.provider');
echo $s->infoIcon('peppol_access_point_provider');
echo H::closeTag('label');
$body['settings[peppol_access_point_provider]'] = $currentProvider;
echo H::openTag('select', [
 'name' => 'settings[peppol_access_point_provider]',
 'id' => 'settings[peppol_access_point_provider]',
 'class' => 'form-select',
]);
echo new Option()
 ->value('storecove')
 ->selected($currentProvider === 'storecove')
 ->content($translator->translate('storecove'));
echo new Option()
 ->value('oxalis')
 ->selected($currentProvider === 'oxalis')
 ->content($translator->translate('oxalis'));
echo H::closeTag('select');
echo H::closeTag('div'); //5
echo H::closeTag('div'); //4
echo H::closeTag('div'); //3

/* Storecove card — shown when the select above is 'storecove'.
 * peppol-access-point.ts toggles .active-provider/.hidden the same
 * way settings.ts already does for .gateway-settings, just without a
 * per-card enable checkbox since there's nothing to independently
 * enable — the select above is the only on/off switch there is. */
echo H::openTag('div', [
 'id' => 'peppol-access-point-settings-storecove',
 'class' => 'peppol-access-point-settings card mb-3 '
  . ($currentProvider === 'storecove' ? 'active-provider' : 'hidden'),
]); //3
echo H::openTag('div', $panelHead); //4
echo $translator->translate('storecove');
echo H::openTag('a', [
 'href' => 'https://app.storecove.com/users/sign_in',
 'target' => '_blank',
 'rel' => 'noopener noreferrer',
 'class' => 'small ms-2',
]);
echo $translator->translate('peppol.access.point.get.credentials');
echo H::closeTag('a');
echo H::closeTag('div'); //4
echo H::openTag('div', $panelBody); //4
echo H::openTag('div', ['class' => 'row']); //5

echo H::openTag('div', ['class' => $col]); //6
echo H::openTag('div', $formGroup); //7
echo H::openTag('label', [
 'for' => 'settings[storecove_api_key]'
]);
echo $translator->translate('storecove.api.key');
echo $s->infoIcon('storecove_api_key');
echo H::closeTag('label');
// Decrypted the same way partial_settings_online_payment.php
// decrypts a stored password-type gateway secret — failing safe
// to an empty field on CryptorException, rather than crashing
// this whole settings tab over one corrupted value.
try {
    $storecoveApiKeyValue = (string) (strlen($s->getSetting('storecove_api_key')) > 0
     ? $s->decode($s->getSetting('storecove_api_key'))
     : '');
} catch (\App\Invoice\Libraries\CryptorException) {
    $storecoveApiKeyValue = '';
}
echo H::openTag('div', ['class' => 'position-relative']);
echo H::openTag('input', [
 'type' => 'password',
 'name' => 'settings[storecove_api_key]',
 'id' => 'settings[storecove_api_key]',
 'class' => 'form-control pe-5',
 'value' => $storecoveApiKeyValue,
]);
echo H::openTag('button', [
 'type' => 'button',
 'class' => 'btn btn-link position-absolute top-50 end-0 translate-middle-y password-reveal-toggle',
 'data-target' => 'settings[storecove_api_key]',
 'aria-label' => 'Show password',
 'tabindex' => '-1',
]);
echo H::tag('i', '', ['class' => 'bi bi-eye']);
echo H::closeTag('button');
echo H::closeTag('div');
echo H::openTag('input', [
 'type' => 'hidden',
 'value' => '1',
 'name' => 'settings[storecove_api_key_field_is_password]',
]);
echo H::closeTag('div'); //7
echo H::closeTag('div'); //6

echo H::openTag('div', ['class' => $col]); //6
echo H::openTag('div', $formGroup); //7
echo H::openTag('label', [
 'for' => 'settings[storecove_country]'
]);
echo $translator->translate(
    'storecove.create.a.sender.legal.entity.country'
);
echo H::openTag('a', [
 'href' => 'https://www.storecove.com/docs/#_create_a_sender',
 'target' => '_blank',
 'rel' => 'noopener noreferrer',
 'class' => 'small ms-2',
]);
echo $translator->translate('online.payment.find.here');
echo H::closeTag('a');
echo $s->infoIcon('storecove_country');
echo H::closeTag('label');
$body['settings[storecove_country]'] =
$s->getSetting('storecove_country');
echo H::openTag('select', [
 'name' => 'settings[storecove_country]',
 'id' => 'settings[storecove_country]',
 'class' => 'form-select',
]);
/**
* @var string $cldr
* @var string $country
*/
foreach ($countries as $cldr => $country) {
    echo new Option()
     ->value($cldr)
     ->selected(
         $cldr ==
          $body['settings[storecove_country]']
     )
     ->encode(false)
     ->content(
         $cldr .
          str_repeat("&nbsp;", 2) .
          str_repeat("-", 10) .
          str_repeat("&nbsp;", 2) .
          $country
     );
}
echo H::closeTag('select');
echo H::closeTag('div'); //7
echo H::closeTag('div'); //6

echo H::openTag('div', ['class' => $col]); //6
echo H::openTag('div', $formGroup); //7
echo H::openTag('label', [
 'for' => 'storecove_legal_entity_id'
]);
echo $translator->translate(
    'storecove.legal.entity.id.for.json'
);
echo $s->infoIcon('storecove_legal_entity_id');
echo H::closeTag('label');
$body['settings[storecove_legal_entity_id]'] =
$s->getSetting('storecove_legal_entity_id');
echo H::openTag('input', [
 'type' => 'text',
 'name' => 'settings[storecove_legal_entity_id]',
 'id' => 'storecove_legal_entity_id',
 'class' => 'form-control',
 'value' =>
 $body['settings[storecove_legal_entity_id]']
]);
echo H::closeTag('div'); //7
echo H::closeTag('div'); //6

echo H::openTag('div', ['class' => $col]); //6
echo H::openTag('div', $formGroup); //7
echo H::openTag('label', [
 'for' => 'settings[storecove_sender_identifier]'
]);
echo $translator->translate(
    'storecove.sender.identifier'
);
echo $s->infoIcon('storecove_sender_identifier');
echo H::closeTag('label');
$body['settings[storecove_sender_identifier]'] =
$s->getSetting('storecove_sender_identifier');
echo H::openTag('select', [
 'name' =>
 'settings[storecove_sender_identifier]',
 'id' =>
 'settings[storecove_sender_identifier]',
 'class' => 'form-select',
]);
/**
* @var string $key
* @var array $value
*/
foreach ($sender_identifier_array as $key => $value) {
    /** @var string $region */
    $region = $value['Region'] ?? '';
    /** @var string $countryVal */
    $countryVal = $value['Country'] ?? '';
    /** @var string $legal */
    $legal = !empty($value['Legal']) ?
    $value['Legal'] :
    $translator->translate(
        'storecove.not.available'
    );
    /** @var string $tax */
    $tax = !empty($value['Tax']) ?
    $value['Tax'] :
    $translator->translate(
        'storecove.not.available'
    );
    $content = ucfirst(
        $region .
         str_repeat("&nbsp;", 2) .
         str_repeat("-", 10) .
         str_repeat("&nbsp;", 2) .
         $countryVal .
         str_repeat("&nbsp;", 2) .
         str_repeat("-", 10) .
         str_repeat("&nbsp;", 2) .
         $legal .
         str_repeat("&nbsp;", 2) .
         str_repeat("-", 10) .
         str_repeat("&nbsp;", 2) .
         $tax
    );
    echo new Option()
     ->value($key)
     ->selected(
         $key ==
          $body[
          'settings[storecove_sender' .
          '_identifier]'
         ]
     )
     ->encode(false)
     ->content($content);
}
echo H::closeTag('select');
echo H::openTag('br');
echo H::openTag('label', [
 'for' =>
 'storecove_sender_identifier_basis'
]);
echo $translator->translate(
    'storecove.sender.identifier.basis'
);
echo $s->infoIcon('storecove_sender_identifier_basis');
echo H::closeTag('label');
$body['settings[storecove_sender_identifier' .
 '_basis]'] = $s->getSetting(
     'storecove_sender_identifier_basis'
 );
echo H::openTag('select', [
 'name' =>
 'settings[storecove_sender_identifier' .
 '_basis]',
 'class' => 'form-select',
 'id' => 'storecove_sender_identifier_basis',
 'data-minimum-results-for-search' =>
 'Infinity'
]);
echo new Option()
 ->value('Legal')
 ->selected(
     'Legal' ==
          $body[
          'settings[storecove_sender' .
          '_identifier_basis]'
         ]
 )
 ->content(
     $translator->translate(
         'storecove.legal'
     )
 );
echo new Option()
 ->value('Tax')
 ->selected(
     'Tax' ==
          $body[
          'settings[storecove_sender' .
          '_identifier_basis]'
         ]
 )
 ->content(
     $translator->translate(
         'storecove.tax'
     )
 );
echo H::closeTag('select');
echo H::closeTag('div'); //7
echo H::closeTag('div'); //6

echo H::closeTag('div'); //5
echo H::closeTag('div'); //4
echo H::closeTag('div'); //3

/* Oxalis card — shown when the select above is 'oxalis'. Read-only:
 * Oxalis (PEPPOL_SENDER_ID/OXALIS_BASE_URL/etc.) is configured via
 * .env, per config/common/di/peppol.php, not a DB-backed Setting like
 * every field above — there is nothing here to actually save. */
echo H::openTag('div', [
 'id' => 'peppol-access-point-settings-oxalis',
 'class' => 'peppol-access-point-settings card mb-3 '
  . ($currentProvider === 'oxalis' ? 'active-provider' : 'hidden'),
]); //3
echo H::openTag('div', $panelHead); //4
echo $translator->translate('oxalis');
echo H::closeTag('div'); //4
echo H::openTag('div', $panelBody); //4
echo H::openTag('p', ['class' => 'text-muted small']);
echo $translator->translate('oxalis.env.only.note');
echo H::closeTag('p');
/** @var array<string, string> $oxalisEnv */
$oxalisEnv = [
 'OXALIS_BASE_URL' => $_ENV['OXALIS_BASE_URL'] ?? 'http://localhost:8181',
 'PEPPOL_SENDER_ID' => $_ENV['PEPPOL_SENDER_ID'] ?? '',
 'PEPPOL_SML_ZONE' => $_ENV['PEPPOL_SML_ZONE'] ?? 'edelivery.tech.ec.europa.eu',
 'PEPPOL_SMP_BASE_URL' => $_ENV['PEPPOL_SMP_BASE_URL'] ?? '',
];
echo H::openTag('table', ['class' => 'table table-sm mb-2']);
foreach ($oxalisEnv as $envKey => $envValue) {
    echo H::openTag('tr');
    echo H::tag('td', H::tag('code', $envKey));
    // H::tag() already encodes a plain string by default (only a
    // Stringable implementing NoEncodeStringableInterface, like the
    // H::tag('em', ...) below, is left alone) — encoding $envValue
    // here too would double-encode it.
    echo H::tag('td', $envValue !== ''
     ? $envValue
     : H::tag('em', $translator->translate('oxalis.env.not.set')));
    echo H::closeTag('tr');
}
echo H::closeTag('table');
echo H::a(
    $translator->translate('oxalis.setup.guide'),
    'https://github.com/rossaddison/invoice/blob/main/src/Invoice/Peppol/README.md',
    ['target' => '_blank', 'rel' => 'noopener noreferrer', 'class' => 'small']
);
echo H::closeTag('div'); //4
echo H::closeTag('div'); //3

echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
