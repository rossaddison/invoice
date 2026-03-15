<?php

/**
 * HTML-to-PHP Conversion Reference
 * ==================================
 * This file is the canonical goto reference for converting HTML partials
 * into the PHP echo style used across this project.
 *
 * RULES (enforced by all partial_settings_*.php files):
 *  - declare(strict_types=1) always appears at the top.
 *  - No raw HTML. Everything is produced via Yiisoft\Html\Html (aliased H).
 *  - Indentation is ONE SPACE per nesting level. No tabs.
 *  - Every H::closeTag() call carries a numeric comment matching its openTag.
 *  - Reusable CSS-class arrays are declared as $variables at file top
 *    (see "CSS shortcut variables" section below) rather than repeated inline.
 *  - Option tags inside a <select> use `new Option()` fluent chain, NOT H::tag.
 *  - Checkbox inputs use H::hiddenInput + H::checkbox OR H::tag('input', '', [...])
 *    — both patterns exist; prefer H::tag for consistency with void elements.
 *  - User-supplied / persisted values placed inside attribute arrays must be
 *    wrapped with H::encode() to prevent XSS.
 *  - $body['settings[key]'] is always assigned from $s->getSetting('key')
 *    immediately before the widget that uses it.
 */

declare(strict_types=1);

// ─── Imports ────────────────────────────────────────────────────────────────
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Option;

// ─── PHPDoc / injected variables ─────────────────────────────────────────────
/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var array $someList  e.g. languages, countries, currency codes …
 */

// ─── CSS shortcut variables (declare once, reuse everywhere) ─────────────────
// Mirrors the convention established in partial_settings_peppol.php.
$row            = ['class' => 'row'];
$colMd6         = ['class' => 'col-xs-12 col-md-6'];
$colMd8Offset2  = ['class' => 'col-xs-12 col-md-8 col-md-offset-2'];
$panel          = ['class' => 'panel panel-default'];
$panelHead      = ['class' => 'panel-heading'];
$panelBody      = ['class' => 'panel-body'];
$formGroup      = ['class' => 'form-group'];
$formControl    = ['class' => 'form-control'];
$checkbox       = ['class' => 'checkbox'];
$inputGroup     = ['class' => 'input-group'];
$inputGroupText = ['class' => 'input-group-text'];
$inputSm        = ['class' => 'input-sm form-control'];
$helpBlock      = ['class' => 'help-block'];

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 1 — Panel / grid scaffold
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('div', $row); //1
 echo H::openTag('div', $colMd8Offset2); //2
  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo $translator->translate('section.heading.key');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('div', $row); //5
     // ── columns go here ──
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 2 — Yes / No select  (most common control)
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('div', $colMd6); //6
 echo H::openTag('div', $formGroup); //7
  echo H::openTag('label', ['for' => 'settings[some_flag]']);
   echo $translator->translate('some.flag');
  echo H::closeTag('label');
  $body['settings[some_flag]'] = $s->getSetting('some_flag');
  echo H::openTag('select', [
   'name'  => 'settings[some_flag]',
   'id'    => 'settings[some_flag]',
   'class' => 'form-control',
   'data-minimum-results-for-search' => 'Infinity'   // omit when search is useful
  ]);
   echo  new Option()
    ->value('0')
    ->content($translator->translate('no'));
   echo  new Option()
    ->value('1')
    ->selected($body['settings[some_flag]'] == '1')
    ->content($translator->translate('yes'));
  echo H::closeTag('select');
 echo H::closeTag('div'); //7
echo H::closeTag('div'); //6

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 3 — Select with dynamic option list (foreach)
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('div', $colMd6); //6
 echo H::openTag('div', $formGroup); //7
  echo H::openTag('label', ['for' => 'settings[some_code]']);
   echo $translator->translate('some.code');
  echo H::closeTag('label');
  $body['settings[some_code]'] = $s->getSetting('some_code');
  echo H::openTag('select', [
   'name'  => 'settings[some_code]',
   'id'    => 'settings[some_code]',
   'class' => 'input-sm form-control'
  ]);
   echo  new Option()
    ->value('0')
    ->content($translator->translate('none'));
   /**
   * @var string $key
   * @var string $val
   */
   foreach ($someList as $key => $val) {
    echo  new Option()
     ->value($key)
     ->selected($body['settings[some_code]'] == $key)
     ->content($val);
   }
  echo H::closeTag('select');
 echo H::closeTag('div'); //7
echo H::closeTag('div'); //6

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 4 — Text input
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('div', $colMd6); //6
 echo H::openTag('div', $formGroup); //7
  echo H::openTag('label', ['for' => 'settings[some_text]']);
   echo $translator->translate('some.text');
  echo H::closeTag('label');
  $body['settings[some_text]'] = $s->getSetting('some_text');
  echo H::openTag('input', [
   'type'  => 'text',
   'name'  => 'settings[some_text]',
   'id'    => 'settings[some_text]',
   'class' => 'form-control',
   'value' => H::encode($body['settings[some_text]'])  // encode persisted value
  ]);
 echo H::closeTag('div'); //7
echo H::closeTag('div'); //6

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 5 — Number input
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('div', $colMd6); //6
 echo H::openTag('div', $formGroup); //7
  echo H::openTag('label', ['for' => 'some_number']);
   echo $translator->translate('some.number');
  echo H::closeTag('label');
  $body['settings[some_number]'] = $s->getSetting('some_number');
  echo H::openTag('input', [
   'type'      => 'number',
   'name'      => 'settings[some_number]',
   'id'        => 'some_number',
   'class'     => 'form-control',
   'min'       => '1',
   'minlength' => '1',
   'required'  => true,
   'value'     => $body['settings[some_number]']
  ]);
 echo H::closeTag('div'); //7
echo H::closeTag('div'); //6

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 6 — Textarea
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('div', $colMd6); //6
 echo H::openTag('div', $formGroup); //7
  echo H::openTag('label', ['for' => 'settings[some_notes]']);
   echo $translator->translate('some.notes');
  echo H::closeTag('label');
  $body['settings[some_notes]'] = $s->getSetting('some_notes');
  echo H::openTag('textarea', array_merge([
   'name' => 'settings[some_notes]',
   'id'   => 'settings[some_notes]',
   'rows' => '4'
  ], $formControl));
   echo H::encode($body['settings[some_notes]']);
  echo H::closeTag('textarea');
 echo H::closeTag('div'); //7
echo H::closeTag('div'); //6

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 7 — Checkbox  (hidden + checkbox via H::tag void-element style)
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('div', $colMd6); //6
 echo H::openTag('div', $formGroup); //7
  echo H::openTag('div', $checkbox); //8
   $body['settings[some_flag]'] = $s->getSetting('some_flag');
   echo H::openTag('label');
    // Hidden keeps the 0-value when the box is unchecked
    echo H::tag('input', '', [
     'type'  => 'hidden',
     'name'  => 'settings[some_flag]',
     'value' => '0'
    ]);
    echo H::tag('input', '', [
     'type'    => 'checkbox',
     'name'    => 'settings[some_flag]',
     'value'   => '1',
     'checked' => ($body['settings[some_flag]'] == '1') ? 'checked' : null
    ]);
    echo chr(32) . $translator->translate('some.flag');
   echo H::closeTag('label');
  echo H::closeTag('div'); //8
 echo H::closeTag('div'); //7
echo H::closeTag('div'); //6

// Alternative: H::hiddenInput + H::checkbox (same semantic result)
// echo H::hiddenInput('settings[some_flag]', '0');
// echo H::checkbox('settings[some_flag]', '1', [
//  'checked' => ($body['settings[some_flag]'] == 1) ? 'checked' : null
// ]);

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 8 — Input group with regenerate button (e.g. cron key)
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('div', $colMd6); //6
 echo H::openTag('div', $formGroup); //7
  echo H::openTag('label', ['for' => 'settings[some_key]']);
   echo $translator->translate('some.key');
  echo H::closeTag('label');
  echo H::openTag('div', $inputGroup); //8
   echo H::openTag('input', [
    'type'  => 'text',
    'name'  => 'settings[some_key]',
    'id'    => 'settings[some_key]',
    'class' => 'some_key form-control',
    'value' => H::encode((string) ($body['settings[some_key]'] ?? $s->getSetting('some_key')))
   ]);
   echo H::openTag('div', $inputGroupText); //9
    // JS wires the click — see src\Invoice\Asset\rebuild-1.13\js\setting.js
    echo H::openTag('button', [
     'id'    => 'btn_generate_some_key',
     'type'  => 'button',
     'class' => 'btn_generate_some_key btn btn-primary btn-block'
    ]);
     echo H::openTag('i', ['class' => 'fa fa-recycle fa-margin']);
     echo H::closeTag('i');
    echo H::closeTag('button');
   echo H::closeTag('div'); //9
  echo H::closeTag('div'); //8
 echo H::closeTag('div'); //7
echo H::closeTag('div'); //6

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 9 — Anchor link  (H::a)
// ════════════════════════════════════════════════════════════════════════════
// Short form: H::a(text, url, attributes)
echo H::a(
 $translator->translate('some.link.label'),
 'https://example.com',
 [
  'style'          => 'text-decoration:none',
  'data-bs-toggle' => 'tooltip',
  'title'          => ''
 ]
);

// Open/close form (for multi-line link content):
echo H::openTag('a', ['href' => 'https://example.com']);
 echo $translator->translate('some.link.label');
echo H::closeTag('a');

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 10 — Arbitrary / void tags via H::tag
// ════════════════════════════════════════════════════════════════════════════
// Void (self-closing) elements — second arg is always ''.
echo H::tag('img', '', [
 'src'    => '/img/some-image.png',
 'width'  => '12',
 'height' => '12'
]);

// Non-void with inline content — supply text as second arg.
echo H::tag('h6', $translator->translate('some.heading'));

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 11 — Bootstrap Icon (bi-*) via Yiisoft\Html\Tag\I
// ════════════════════════════════════════════════════════════════════════════
echo  new I()->addClass('bi bi-info-circle')->render();
echo  new I()->addClass('bi bi-github')->render() . ' Github';

// FontAwesome variant (inline open/close):
echo H::openTag('i', ['class' => 'fa fa-recycle fa-margin']);
echo H::closeTag('i');

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 12 — Help-block paragraph beneath a control
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('p', $helpBlock);
 echo $translator->translate('example') . ': ';
 echo H::openTag('span', ['style' => 'font-family: Monaco, Lucida Console, monospace']);
  echo $s->format_currency(123456.78);
 echo H::closeTag('span');
echo H::closeTag('p');

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 13 — Paragraph blocks (further-reading / instructional text)
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('p');
 echo H::openTag('b');
  echo 'Further reading: ';
 echo H::closeTag('b');
 echo H::openTag('a', ['href' => 'https://example.com']);
  echo 'Reference title';
 echo H::closeTag('a');
echo H::closeTag('p');

echo H::openTag('p');
 echo H::openTag('b');
  echo '1. Step heading ';
 echo H::closeTag('b');
 echo H::openTag('pre');
  echo 'command or code here';
 echo H::closeTag('pre');
echo H::closeTag('p');
