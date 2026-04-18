<?php
declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
* @var array $invoice_groups
* @var array $public_quote_templates
* @var array $pdf_quote_templates
* @var array $email_templates_quote
*/

$row = ['class' => 'row'];
$colMd6 = ['class' => 'col-xs-12 col-md-6'];
$colMd8 = ['class' => 'col-xs-12 col-md-8 col-md-offset-2'];
$panel = ['class' => 'panel panel-default'];
$panelHead = ['class' => 'panel-heading'];
$panelBody = ['class' => 'panel-body'];
$formGroup = ['class' => 'form-group'];
$formControl = ['class' => 'form-control form-control-lg',];
$helpBlock = ['class' => 'help-block'];
$noMargin = ['class' => 'form-control no-margin'];
$minSearch = ['data-minimum-results-for-search' => 'Infinity'];

echo H::tag('style', ' label { font-weight: bold; } ');
echo H::openTag('div', $row); //1
 echo H::openTag('div', $colMd8); //2
  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo $translator->translate('quote');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       $dqg = 'settings[default_quote_group]';
       echo H::openTag('label', [
        'for' => $dqg
       ]);
        echo $translator->translate(
         'default.quote.group'
        );
       echo H::closeTag('label');
       $body[$dqg] = $s->getSetting('default_quote_group');
       echo H::openTag('select', array_merge([
        'name' => $dqg,
        'id' => $dqg
       ], $formControl, $minSearch));
        echo  new Option()
         ->value('')
         ->content($translator->translate('none'));
        /**
        * @var App\Infrastructure\Persistence\Group\Group $invoice_group
        */
        foreach ($invoice_groups as $invoice_group) {
        echo  new Option()
         ->value($invoice_group->reqId())
         ->selected($body[$dqg] == $invoice_group->reqId())
         ->content(
          $invoice_group->getName() ?? ''
         );
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
      echo H::openTag('div', $formGroup); //7
       $dqn = 'settings[default_quote_notes]';
       echo H::openTag('label', [
        'for' => $dqn
       ]);

        echo $translator->translate('default.notes');
       echo H::closeTag('label');

       $body[$dqn] =
       $s->getSetting('default_quote_notes');


       echo H::openTag('textarea', array_merge([
        'name' => $dqn,
        'id' => $dqn,
        'rows' => '3'
       ], $formControl));

        echo $body[$dqn];
       echo H::closeTag('textarea');
      echo H::closeTag('div'); //7

     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       $qea = 'settings[quotes_expire_after]';
       echo H::openTag('label', [
        'for' => $qea
       ]);

        echo $translator->translate(
         'quotes.expire.after'
        );
       echo H::closeTag('label');

       $body[$qea] =
       $s->getSetting('quotes_expire_after');


       echo H::openTag('input', array_merge([
        'type' => 'number',
        'name' => $qea,
        'id' => $qea,
        'value' =>
        $body[$qea]
       ], $formControl));

      echo H::closeTag('div'); //7
      echo H::openTag('div', $formGroup); //7
       $gqn = 'settings[generate_quote_number_for_draft]';
       echo H::openTag('label', ['for' => $gqn]);
        echo $translator->translate(
         'generate.quote.number.for.draft'
        );
       echo H::closeTag('label');
       $body[$gqn] = $s->getSetting('generate_quote_number_for_draft');
       echo H::openTag('select', array_merge(['name' => $gqn, 'id' => $gqn],
        $formControl, $minSearch));
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected($body[$gqn] == '1')
         ->content(
          $translator->translate('yes')
         );

       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3

  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo $translator->translate('pdf.settings');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       $qsp = 'settings[mark_quotes_sent_pdf]';
       echo H::openTag('label', [
        'for' => $qsp
       ]);
        echo $translator->translate(
         'mark.quotes.sent.pdf'
        );
       echo H::closeTag('label');
       $body[$qsp] = $s->getSetting('mark_quotes_sent_pdf');
       echo H::openTag('select', array_merge([
        'name' => $qsp,
        'id' => $qsp], $formControl, $minSearch));
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected($body[$qsp] == '1')
         ->content(
          $translator->translate('yes')
         );

       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       $qpp = 'settings[quote_pre_password]';
       echo H::openTag('label', ['for' => $qpp]);
        echo $translator->translate('quote.pre.password');
       echo H::closeTag('label');
       $body[$qpp] = $s->getSetting('quote_pre_password');
       echo H::openTag('input', array_merge([
        'type' => 'text',
        'name' => $qpp,
        'id' => $qpp,
        'value' =>
        $body[$qpp]
       ], $formControl));
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo $translator->translate('quote.templates');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       $pqt = 'settings[pdf_quote_template]';
       echo H::openTag('label', ['for' => $pqt]);
        echo $translator->translate('default.pdf.template');
       echo H::closeTag('label');
       $body[$pqt] = $s->getSetting('pdf_quote_template');
       echo H::openTag('select', array_merge(['name' => $pqt, 'id' => $pqt],
        $formControl, $minSearch));
        echo  new Option()
         ->value('')
         ->content($translator->translate('none'));
       /**
        * @var string $quote_template
        */
        foreach ($pdf_quote_templates as $quote_template) {
         echo  new Option()
         ->value($quote_template)
         ->selected($body[$pqt] == $quote_template)
         ->content(ucfirst($quote_template));
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       $pubQt = 'settings[public_quote_template]';
       echo H::openTag('label', [
        'for' => $pubQt
       ]);

        echo $translator->translate(
         'default.public.template'
        );
       echo H::closeTag('label');

       $body[$pubQt] = $s->getSetting('public_quote_template');
       echo H::openTag('select', array_merge(['name' => $pubQt, 'id' => $pubQt],
        $formControl, $minSearch));
        echo  new Option()
         ->value('')
         ->content($translator->translate('none'));
       /**
        * @var string $quote_template
        */
        foreach ($public_quote_templates as $quote_template) {
         echo  new Option()
         ->value($quote_template)
         ->selected($body[$pubQt] == $quote_template)
         ->content(ucfirst($quote_template));
        }

       echo H::closeTag('select');
      echo H::closeTag('div'); //7

     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6

      echo H::openTag('div', $formGroup); //7
       $eqt = 'settings[email_quote_template]';
       echo H::openTag('label', ['for' => $eqt]);
        echo $translator->translate('default.email.template'
        );
       echo H::closeTag('label');
       $body[$eqt] = $s->getSetting('email_quote_template');
       echo H::openTag('select', array_merge([
        'name' => $eqt,
        'id' => $eqt
       ], $formControl, $minSearch));
        echo  new Option()
         ->value('')
         ->content($translator->translate('none'));
        /**
        * @var App\Invoice\Entity\EmailTemplate
        * $email_template
        */
        foreach ($email_templates_quote as $email_template) {
         echo  new Option()
         ->value($email_template->getEmailTemplateId())
         ->selected($body['settings[email_quote_template]'] ==
            $email_template->getEmailTemplateId())
         ->content(
          $email_template
          ->getEmailTemplateTitle()
          ?? ''
         );
        }

       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7

       echo H::openTag('label', [
        'for' => 'settings[pdf_quote_footer]'
       ]);

        echo $translator->translate(
         'pdf.quote.footer'
        );
       echo H::closeTag('label');
       $pqf = 'settings[pdf_quote_footer]';
       $body[$pqf] = $s->getSetting('pdf_quote_footer');
       echo H::openTag('textarea', array_merge(['name' => $pqf, 'id' => $pqf],
        $noMargin));
        echo $body[$pqf];
       echo H::closeTag('textarea');
       echo H::openTag('p', $helpBlock);
        echo $translator->translate('pdf.quote.footer.hint');
       echo H::closeTag('p');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
