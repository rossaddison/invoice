<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\H6;
use Yiisoft\Html\Tag\Img;
use Yiisoft\Html\Tag\Option;
use Yiisoft\Html\Tag\Table;
use Yiisoft\Html\Tag\Tr;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\QRCode;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
*/

echo H::tag('style', ' label { font-weight: bold; } ');
echo H::openTag('div', ['class' => 'row']); //1
 echo H::openTag('div', [ //2
  'class' => 'col-xs-12 col-md-8 col-md-offset-2'
 ]);
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo  new H6()->content(
      new A()
     ->attributes(['style' => 'text-decoration:none'])
     ->href('https://php-qrcode.readthedocs.io/en/main/')
     ->content($translator->translate('qr.code'))
    );
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'row']); //4
    echo H::openTag('div', ['class' => 'panel-body']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       $qrVer = 'settings[qr_version]';
       echo H::openTag('label', ['for' => $qrVer]);
        echo $translator->translate('qr.version');
       echo H::closeTag('label');
       $body[$qrVer] = $s->getSetting('qr_version') ?: '40';
       echo H::input('text', $qrVer,
        $body[$qrVer] ?? (string) Version::AUTO, [
            'id' => $qrVer,
            'class' => 'form-control form-control-lg',
       ]);
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'form-group']); //7
       $qrEcc = 'settings[qr_ecc_level]';
       echo H::openTag('label', ['for' => $qrEcc]);
        echo $translator->translate('qr.ecc.level');
       echo H::closeTag('label');
       $body[$qrEcc] = $s->getSetting('qr_ecc_level');
       echo H::openTag('select', [
        'name' => $qrEcc,
        'id' => $qrEcc,
        'class' => 'form-control form-control-lg',
       ]);
        $ecc_levels = ['0' => 'L', '1' => 'M', '2' => 'Q', '3' => 'H'];
        /**
        * @var string $value
        * @var string $label
        */
        foreach ($ecc_levels as $value => $label) {
        echo  new Option()
         ->value($value)
         ->selected($value == ($body[$qrEcc] ?? '0'))
         ->content($label);
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'form-group']); //7
       $qrHw = 'settings[qr_height_and_width]';
       echo H::openTag('label', [
        'for' => $qrHw
       ]);
        echo $translator->translate('qr.height.and.width');
       echo H::closeTag('label');
       $body[$qrHw] = $s->getSetting('qr_height_and_width');
       $qr_size = isset($body[$qrHw]) && !empty($body[$qrHw]) ?
            (int) $body[$qrHw] : 60;
       echo H::input('text', $qrHw,
        (string) $qr_size, [
        'id' => $qrHw,
        'class' => 'form-control form-control-lg',
       ]);
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'panel-heading']); //7
       echo  new H6()
        ->attributes(['class' => 'badge text-bg-info'])
        ->content($translator->translate('qr.code.1'));
       echo H::openTag('pre');
        echo "Html::openTag('div', ['id' => 'qr_code']);";
        echo H::tag('br');
        echo "    QrCodeWidget::absoluteUrl(";
        echo "\$urlGenerator->generateAbsolute('inv/view', [";
        echo H::tag('br');
        echo "        'id' => \$inv_id,";
        echo H::tag('br');
        echo "        '_language' => \$_language";
        echo H::tag('br');
        echo "    ]), \$translator->translate('qr.code'), 150);";
        echo H::tag('br');
        echo "Html::closeTag('div');";
        echo H::tag('br');
       echo H::closeTag('pre');
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'row']); //7
       echo H::openTag('div', ['class' => 'panel-body']); //8
        $pixels = (isset($body[$qrHw]) && ($body[$qrHw])) ? (int) $body[$qrHw] : 60;
         printf(new Img()
         ->width($pixels)
         ->height($pixels)
         ->src('%s')
         ->alt($translator->translate('qr.code'))
         ->render(),
         (string)  new QRCode()->render(
         'https://invoice.myhost/invoice/inv/view/6'
        )
        );
        echo new Table()
         ->attributes([
          'class' => 'table table-info table-striped table-bordered'
         ])
         ->rows(
           new Tr()->headerStrings([
          $translator->translate('qr.code.1'),
          $translator->translate('qr.code.details'),
         ]),
         new Tr()->dataStrings([
         $translator->translate('qr.code.source'),
         $translator->translate('qr.code.source.path'),
        ]),
         new Tr()->dataStrings([
         '*.php',
         'php $company_logo_and_address',
        ]),
         new Tr()->dataStrings([
         'Path',
         '\resources\views\invoice\template\invoice\pdf',
        ]),
         new Tr()->dataStrings([
         'Controller/action',
         'inv/pdf -> pdfHelper/generate_inv_pdf',
        ]),
         new Tr()->dataStrings([
         'src\invoice\Helpers\pdfHelper',
         'generate_inv_pdf -> generate_inv_html',
        ]),
         new Tr()->dataStrings([
         $translator->translate('qr.code.type'),
         $translator->translate('qr.code.type.absolute.url'),
        ]),
         new Tr()->dataStrings([
         $translator->translate('qr.absolute.url'),
         'https://invoice.myhost/invoice/inv/view/6',
        ]),
         new Tr()->dataStrings([
         $translator->translate('qr.meaning'),
         $translator->translate('qr.meaning.benefit'),
        ]),
         new Tr()->dataStrings([
         $translator->translate('qr.code.widget.used'),
         '\src\Widget\QrCode.php',
        ]),
         new Tr()->dataStrings([
         $translator->translate('qr.code.level.1'),
         '(new QRCode)->render(' .
         '"http://invoice.myhost/invoice/inv/view/6")',
        ]),
         new Tr()->dataStrings([
         $translator->translate('qr.code.settings.effect'),
         $translator->translate(
         'qr.code.settings.effect.explanation'
        ),
        ]),
        )
         ->render();
       echo H::closeTag('div'); //8
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
