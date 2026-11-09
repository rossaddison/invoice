<?php

declare(strict_types=1);

use App\Invoice\HomeCareRunImport\ParsedRun;
use App\Invoice\HomeCareRunImport\RunHouse;
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var string $csrf
 * @var ParsedRun $run
 * @var string $text
 * @var array<array-key, string> $runs
 * @var array<array-key, string> $primaries
 * @var array<int, string> $products
 * @var string $alert
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

echo H::tag('h4', H::encode($run->title));
echo H::tag('p', H::encode(sprintf(
    $translator->translate('homecare.runimport.summary'),
    $run->houseCount(),
    count($run->streets())
)), ['class' => 'text-muted']);

if ([] !== $run->headerNotes) {
    echo H::openTag('div', ['class' => 'alert alert-info']);
     foreach ($run->headerNotes as $note) {
         echo H::tag('div', H::encode($note));
     }
    echo H::closeTag('div');
}
if ([] !== $run->warnings) {
    echo H::openTag('div', ['class' => 'alert alert-warning']);
     echo H::tag('strong', H::encode($translator->translate('homecare.schedule.warnings')));
     echo H::openTag('ul', ['class' => 'mb-0']);
      foreach ($run->warnings as $warning) {
          echo H::tag('li', H::encode($warning));
      }
     echo H::closeTag('ul');
    echo H::closeTag('div');
}

foreach ($run->segments as $segment) {
    echo H::openTag('div', ['class' => 'card mb-2']);
     echo H::openTag('div', ['class' => 'card-header d-flex justify-content-between']);
      echo H::tag('strong', H::encode($segment->street . ($segment->continues ? ' ' . $translator->translate('homecare.runimport.continues') : '')));
      echo H::tag('span', H::encode((string) $segment->postcode), ['class' => 'text-muted']);
     echo H::closeTag('div');
     echo H::openTag('div', ['class' => 'card-body py-2']);
      foreach ($segment->notes as $note) {
          echo H::tag('div', H::encode('🚐 ' . $note), ['class' => 'text-muted small'])->encode(false);
      }
      echo H::openTag('table', ['class' => 'table table-sm mb-0']);
       foreach ($segment->houses as $house) {
           $rowClass = $house->isInactive() ? 'table-danger' : ($house->has(RunHouse::FLAG_NEW) ? 'table-success' : '');
           echo H::openTag('tr', ['class' => $rowClass]);
            echo H::tag('td', H::encode($house->number), ['class' => 'fw-bold', 'style' => 'width:5em']);
            echo H::tag('td', H::encode(implode(', ', $house->flags)), ['style' => 'width:14em']);
            echo H::tag('td', H::encode($house->note));
           echo H::closeTag('tr');
       }
      echo H::closeTag('table');
     echo H::closeTag('div');
    echo H::closeTag('div');
}

// Save: the run, a client and dwelling for each house, and a draft invoice with the standard product.
echo H::openTag('div', ['class' => 'card mb-3']);
 echo H::tag('div', H::encode($translator->translate('homecare.runimport.save.title')), ['class' => 'card-header']);
 echo H::openTag('div', ['class' => 'card-body']);
  echo H::tag('p', $translator->translate('homecare.runimport.save.hint'), ['class' => 'text-muted']);
  echo new Form()->post($urlGenerator->generate('homecarerunimport/save'))->csrf($csrf)->open();
  echo H::hiddenInput('run_text', $text)->render();
  echo H::openTag('div', ['class' => 'row g-2']);
   echo H::openTag('div', ['class' => 'col-12 col-md-6']);
    echo H::tag('label', $translator->translate('homecare.current.run'), ['class' => 'form-label', 'for' => 'import-run']);
    echo H::select('run_id')->id('import-run')->optionsData($runs)->prompt('➕ ' . $translator->translate('homecare.runimport.new.run'))->addAttributes(['class' => 'form-select'])->render();
   echo H::closeTag('div');
   echo H::openTag('div', ['class' => 'col-12 col-md-6']);
    echo H::tag('label', $translator->translate('homecare.runimport.new.run.name'), ['class' => 'form-label', 'for' => 'import-run-name']);
    echo H::input('text', 'run_name', $run->title)->id('import-run-name')->addAttributes(['class' => 'form-control'])->render();
   echo H::closeTag('div');
   echo H::openTag('div', ['class' => 'col-12 col-md-4']);
    echo H::tag('label', $translator->translate('homecare.schedule.new.run.category'), ['class' => 'form-label', 'for' => 'import-primary']);
    echo H::select('category_primary_id')->id('import-primary')->optionsData($primaries)->addAttributes(['class' => 'form-select'])->render();
   echo H::closeTag('div');
   echo H::openTag('div', ['class' => 'col-12 col-md-5']);
    echo H::tag('label', $translator->translate('homecare.runimport.product'), ['class' => 'form-label', 'for' => 'import-product']);
    echo H::select('product_id')->id('import-product')->optionsData($products)->addAttributes(['class' => 'form-select'])->render();
   echo H::closeTag('div');
   echo H::openTag('div', ['class' => 'col-12 col-md-3']);
    echo H::tag('label', $translator->translate('homecare.runimport.price'), ['class' => 'form-label', 'for' => 'import-price']);
    echo H::input('number', 'price', '')->id('import-price')->addAttributes(['class' => 'form-control', 'step' => '0.01', 'min' => '0'])->render();
   echo H::closeTag('div');
  echo H::closeTag('div');
  echo H::submitButton($translator->translate('homecare.runimport.save'))->addAttributes(['class' => 'btn btn-primary mt-3'])->render();
  echo new Form()->close();
 echo H::closeTag('div');
echo H::closeTag('div');

echo new Form()->post($urlGenerator->generate('homecarerunimport/template'))->csrf($csrf)->open();
echo H::hiddenInput('run_text', $text)->render();
echo H::submitButton($translator->translate('homecare.runimport.sheet.download.run'))->addAttributes(['class' => 'btn btn-outline-primary me-2 mb-2'])->render();
echo new Form()->close();

echo new Form()->post($urlGenerator->generate('homecarerunimport/export'))->csrf($csrf)->open();
echo H::hiddenInput('run_text', $text)->render();
echo H::submitButton($translator->translate('homecare.runimport.export'))->addAttributes(['class' => 'btn btn-success me-2'])->render();
echo H::a($translator->translate('cancel'), $urlGenerator->generate('homecarerunimport/index'), ['class' => 'btn btn-outline-secondary'])->render();
echo new Form()->close();
