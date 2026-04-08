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
use Yiisoft\Html\Tag\Input;
use Yiisoft\Html\Tag\Option;
use Yiisoft\Html\Tag\Input\Checkbox;
use Yiisoft\Html\Tag\TextArea;

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
$formControl    = ['class' => 'form-control form-control-lg'];
$checkbox       = ['class' => 'checkbox'];
$inputGroup     = ['class' => 'input-group'];
$inputGroupText = ['class' => 'input-group-text'];
$inputSm        = ['class' => 'input-sm form-control form-control-lg'];
$helpBlock      = ['class' => 'help-block'];

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 1 — Panel / grid scaffold
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('div', $row); //0
 echo H::openTag('div', $colMd8Offset2); //1
  echo H::openTag('div', $panel); //2
   echo H::openTag('div', $panelHead); //3
    echo $translator->translate('section.heading.key');
   echo H::closeTag('div'); //3
   echo H::openTag('div', $panelBody); //3
    echo H::openTag('div', $row); //4
     // ── columns go here ──
    echo H::closeTag('div'); //4
   echo H::closeTag('div'); //3
  echo H::closeTag('div'); //2
 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 2 — Yes / No select  (most common control)
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('div', $colMd6); //0
 echo H::openTag('div', $formGroup); //1
  echo H::openTag('label', ['for' => 'settings[some_flag]']); //2
   echo $translator->translate('some.flag'); //3
  echo H::closeTag('label'); //2
  $body['settings[some_flag]'] = $s->getSetting('some_flag');
  echo H::openTag('select', [
   'name'  => 'settings[some_flag]',
   'id'    => 'settings[some_flag]',
   'class' => 'form-control form-control-lg',
   'data-minimum-results-for-search' => 'Infinity'   // omit when search is useful
  ]); //2
   echo  new Option() 
         ->value('0')
         ->content($translator->translate('no')); //3
   echo  new Option() 
         ->value('1')
         ->selected($body['settings[some_flag]'] == '1')
         ->content($translator->translate('yes')); //3
  echo H::closeTag('select'); //2
 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 3 — Select with dynamic option list (foreach)
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('div', $colMd6); //0
 echo H::openTag('div', $formGroup); //1
  echo H::openTag('label', ['for' => 'settings[some_code]']); //2
   echo $translator->translate('some.code');
  echo H::closeTag('label'); //2
  $body['settings[some_code]'] = $s->getSetting('some_code');
  echo H::openTag('select', [
   'name'  => 'settings[some_code]',
   'id'    => 'settings[some_code]',
   'class' => 'input-sm form-control'
  ]); //2
   echo  new Option()
    ->value('0')
    ->content($translator->translate('none')); //3
   /**
   * @var string $key
   * @var string $val
   */
   foreach ($someList as $key => $val) {
    echo  new Option()
     ->value($key)
     ->selected($body['settings[some_code]'] == $key)
     ->content($val);
   } //3
  echo H::closeTag('select'); //2
 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 4 — Text input
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('div', $colMd6); //0
 echo H::openTag('div', $formGroup); //1
  echo H::openTag('label', ['for' => 'settings[some_text]']); //2
   echo $translator->translate('some.text');
  echo H::closeTag('label'); //2
   $body['settings[some_text]'] = $s->getSetting('some_text');
  echo new Input()
       ->type('text')
       ->class('form-control form-control-lg')
       ->id('settings[some_text]')
       ->name('settings[some_text]')
       ->value(H::encode($body['settings[some_text]']));
 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 5 — Number input
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('div', $colMd6); //0
 echo H::openTag('div', $formGroup); //1
  echo H::openTag('label', ['for' => 'some_number']); //2
   echo $translator->translate('some.number'); //3
  echo H::closeTag('label'); //2
   $body['settings[some_number]'] = $s->getSetting('some_number');
  echo new Input()
       ->type('number')
       ->class('form-control form-control-lg')
       ->id('settings[some_text]')
       ->name('settings[some_text]')
       ->addAttributes([
          'min' => '1',
          'minLength' => '1',
       ])
       ->required(true)   
       ->value(H::encode($body['settings[some_text]'])); //2 
 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 6 — Textarea
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('div', $colMd6); //0
 echo H::openTag('div', $formGroup); //1
  echo H::openTag('label', ['for' => 'settings[some_notes]']); //2
   echo $translator->translate('some.notes');
  echo H::closeTag('label'); //2
   $body['settings[some_notes]'] = $s->getSetting('some_notes');
  echo new TextArea()
       ->name('settings[some_notes]')
       ->id('settings[some_notes]')
       ->rows('4')
       ->value($body['settings[some_notes]']); //2
 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0

// ════════════════════════════════════════════════════════════════════════════
// PATTERN 7 — Checkbox
// ════════════════════════════════════════════════════════════════════════════
echo H::openTag('div', $colMd6); //0
 echo H::openTag('div', $formGroup); //1
  echo H::openTag('div', $checkbox); //2
   echo new Checkbox()
         ->name($name)
         ->id($id)
         ->label($label)
         ->checked(($body['settings[some_flag]'] == '1') ?: false); //3   
   echo H::closeTag('div'); //2
 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0
