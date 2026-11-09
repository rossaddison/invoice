<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var string $csrf
 * @var string $alert
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

echo H::openTag('div', ['class' => 'card']);
 echo H::tag('div', H::encode($translator->translate('homecare.runimport.title')), ['class' => 'card-header']);
 echo H::openTag('div', ['class' => 'card-body']);
  echo H::tag('p', $translator->translate('homecare.runimport.hint'), ['class' => 'text-muted']);
  echo new Form()->post($urlGenerator->generate('homecarerunimport/preview'))->csrf($csrf)->open();
  echo H::textarea('run_text')->addAttributes(['class' => 'form-control mb-2', 'rows' => '20', 'spellcheck' => 'false', 'aria-label' => $translator->translate('homecare.runimport.title')])->render();
  echo H::submitButton($translator->translate('homecare.runimport.read'))->addAttributes(['class' => 'btn btn-primary'])->render();
  echo new Form()->close();
 echo H::closeTag('div');
echo H::closeTag('div');

// The import sheet: download the blank CSV, fill it in, upload it.
echo H::openTag('div', ['class' => 'card mt-3']);
 echo H::tag('div', H::encode($translator->translate('homecare.runimport.sheet.title')), ['class' => 'card-header']);
 echo H::openTag('div', ['class' => 'card-body']);
  echo H::tag('p', $translator->translate('homecare.runimport.sheet.hint'), ['class' => 'text-muted']);
  echo new Form()->post($urlGenerator->generate('homecarerunimport/template'))->csrf($csrf)->open();
  echo H::submitButton($translator->translate('homecare.runimport.sheet.download'))->addAttributes(['class' => 'btn btn-outline-primary mb-3'])->render();
  echo new Form()->close();
  echo new Form()->post($urlGenerator->generate('homecarerunimport/upload'))->csrf($csrf)->enctypeMultipartFormData()->open();
  echo H::label($translator->translate('homecare.runimport.sheet.file'), 'import-sheet')->addAttributes(['class' => 'form-label'])->render();
  echo H::input('file', 'sheet')->id('import-sheet')->addAttributes(['accept' => '.csv,text/csv', 'class' => 'form-control mb-2'])->render();
  echo H::submitButton($translator->translate('homecare.runimport.sheet.upload'))->addAttributes(['class' => 'btn btn-primary'])->render();
  echo new Form()->close();
 echo H::closeTag('div');
echo H::closeTag('div');
