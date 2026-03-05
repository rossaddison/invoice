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

echo H::openTag('div', ['class' => 'row']); //1
 echo H::openTag('div', [ //2
  'class' => 'col-xs-12 col-md-8 col-md-offset-2'
 ]);
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo H6::tag()->content(
     A::tag()
     ->attributes(['style' => 'text-decoration:none'])
     ->href('https://php-qrcode.readthedocs.io/en/main/')
     ->content($translator->translate('qr.code'))
    );
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'row']); //4
    echo H::openTag('div', ['class' => 'panel-body']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', ['for' => 'settings[qr_version]']);
        echo $translator->translate('qr.version');
       echo H::closeTag('label');
       $body['settings[qr_version]'] =
       $s->getSetting('qr_version') ?: '40';
       echo H::input('text', 'settings[qr_version]', 
        $body['settings[qr_version]'] ?? (string) Version::AUTO, [
        'id' => 'settings[qr_version]',
        'class' => 'form-control'
       ]);
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', ['for' => 'settings[qr_ecc_level]']);
        echo $translator->translate('qr.ecc.level');
       echo H::closeTag('label');
       $body['settings[qr_ecc_level]'] =
       $s->getSetting('qr_ecc_level');
       echo H::openTag('select', [
        'name' => 'settings[qr_ecc_level]',
        'id' => 'settings[qr_ecc_level]',
        'class' => 'form-control'
       ]);
        $ecc_levels = ['0' => 'L', '1' => 'M', '2' => 'Q', '3' => 'H'];
        /**
        * @var string $value
        * @var string $label
        */
        foreach ($ecc_levels as $value => $label) {
        echo Option::tag()
         ->value($value)
         ->selected($value == ($body['settings[qr_ecc_level]'] ?? '0'))
         ->content(H::encode($label));
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[qr_height_and_width]'
       ]);
        echo $translator->translate('qr.height.and.width');
       echo H::closeTag('label');
       $body['settings[qr_height_and_width]'] =
       $s->getSetting('qr_height_and_width');
       $qr_size = isset($body['settings[qr_height_and_width]'])
       && !empty($body['settings[qr_height_and_width]'])
       ? (int) $body['settings[qr_height_and_width]'] : 60;
       echo H::input('text', 'settings[qr_height_and_width]',
        (string) $qr_size, [
        'id' => 'settings[qr_height_and_width]',
        'class' => 'form-control'
       ]);
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'panel-heading']); //7
       echo H6::tag()
        ->attributes(['class' => 'label label-info'])
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
        $pixels = (isset($body['settings[qr_height_and_width]'])
         && ($body['settings[qr_height_and_width]']))
         ? (int) $body['settings[qr_height_and_width]'] : 60;
         printf(Img::tag()
         ->width($pixels)
         ->height($pixels)
         ->src('%s')
         ->alt($translator->translate('qr.code'))
         ->render(),
         (string) (new QRCode())->render(
         'https://invoice.myhost/invoice/inv/view/6'
        )
        );
        echo Table::tag()
         ->attributes([
          'class' => 'table table-info table-striped table-bordered'
         ])
         ->rows(
          Tr::tag()->headerStrings([
          $translator->translate('qr.code.1'),
          $translator->translate('qr.code.details'),
         ]),
        Tr::tag()->dataStrings([
         $translator->translate('qr.code.source'),
         $translator->translate('qr.code.source.path'),
        ]),
        Tr::tag()->dataStrings([
         '*.php',
         'php $company_logo_and_address',
        ]),
        Tr::tag()->dataStrings([
         'Path',
         '\resources\views\invoice\template\invoice\pdf',
        ]),
        Tr::tag()->dataStrings([
         'Controller/action',
         'inv/pdf -> pdfHelper/generate_inv_pdf',
        ]),
        Tr::tag()->dataStrings([
         'src\invoice\Helpers\pdfHelper',
         'generate_inv_pdf -> generate_inv_html',
        ]),
        Tr::tag()->dataStrings([
         $translator->translate('qr.code.type'),
         $translator->translate('qr.code.type.absolute.url'),
        ]),
        Tr::tag()->dataStrings([
         $translator->translate('qr.absolute.url'),
         'https://invoice.myhost/invoice/inv/view/6',
        ]),
        Tr::tag()->dataStrings([
         $translator->translate('qr.meaning'),
         $translator->translate('qr.meaning.benefit'),
        ]),
        Tr::tag()->dataStrings([
         $translator->translate('qr.code.widget.used'),
         '\src\Widget\QrCode.php',
        ]),
        Tr::tag()->dataStrings([
         $translator->translate('qr.code.level.1'),
         '(new QRCode)->render(' .
         '"http://invoice.myhost/invoice/inv/view/6")',
        ]),
        Tr::tag()->dataStrings([
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
