<?php
declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;
use Yiisoft\Html\Tag\I;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
* @var array $invoice_groups
* @var array $payment_methods
* @var array $pdf_invoice_templates
* @var array $public_invoice_templates
* @var array $public_pdf_templates
* @var array $email_templates_invoice
* @var array $roles
* @var array $places
* @var array $cantons
*/

$row = ['class' => 'row'];
$colMd6 = ['class' => 'col-xs-12 col-md-6'];
$colMd8 = ['class' => 'col-xs-12 col-md-8 col-md-offset-2'];
$panel = ['class' => 'panel panel-default'];
$panelHead = ['class' => 'panel-heading'];
$panelBody = ['class' => 'panel-body'];
$formGroup = ['class' => 'form-group'];
$helpBlock = ['class' => 'help-block'];

echo H::tag('style', ' label { font-weight: bold; } ');
echo H::openTag('div', $row); //1
 echo H::openTag('div', $colMd8); //2

  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo $translator->translate('invoices');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4

    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6

      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 'settings[default_invoice_group]'
       ]);
        echo $translator->translate(
         'default.invoice.group'
        );
       echo H::closeTag('label');
       $sdig = 'settings[default_invoice_group]';
       $body[$sdig] =
       $s->getSetting('default_invoice_group');
       echo H::openTag('select', [
        'name' => $sdig,
        'id' => $sdig,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('')
         ->content($translator->translate('none'));
        /**
        * @var App\Infrastructure\Persistence\Group\Group $invoice_group
        */
        foreach ($invoice_groups as $invoice_group) {
        echo  new Option()
         ->value($invoice_group->reqId())
         ->selected(
          $body[$sdig]
          == $invoice_group->reqId()
         )
         ->content($invoice_group->getName()
          ?? '');
          }
          echo H::closeTag('select');
          echo H::closeTag('div'); //11

          echo H::openTag('div', $formGroup); //11
          $sdit = 'settings[default_invoice_terms]';
          echo H::openTag('label', [
          'for' => $sdit
         ]);
        echo $translator->translate('default.terms');
       echo H::closeTag('label');
       $body[$sdit] =
       $s->getSetting('default_invoice_terms');
       echo H::openTag('textarea', [
        'name' => $sdit,
        'id' => $sdit,
        'class' => 'form-control form-control-lg',
        'rows' => '4'
       ]);
        echo $body[$sdit];
       echo H::closeTag('textarea');
      echo H::closeTag('div'); //7

     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6

      echo H::openTag('div', $formGroup); //7
       $sdpm = 'settings[invoice_default_payment_method]';
       echo H::openTag('label', [
        'for' =>
        $sdpm
       ]);
        echo $translator->translate(
         'default.payment.method'
        );
       echo H::closeTag('label');
       $body[$sdpm]
       = $s->getSetting(
        'invoice_default_payment_method'
       );
       echo H::openTag('select', [
        'name' =>
        $sdpm,
        'class' => 'form-control form-control-lg',
        'id' =>
        $sdpm
       ]);
        /**
        * @var App\Invoice\Entity\PaymentMethod
        *      $payment_method
        */
        foreach ($payment_methods as $payment_method) {
        echo  new Option()
         ->value($payment_method->getId())
         ->selected(
          $payment_method->getId() ==
          $body[$sdpm]
        )
         ->content(
          $payment_method->getName() ?? ''
         );
        }

       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 'settings[invoices_due_after]'
       ]);
        echo $translator->translate(
         'invoices.due.after'
        );
       echo H::closeTag('label');
       $sda = 'settings[invoices_due_after]';
       $body[$sda] =
       $s->getSetting('invoices_due_after');

       echo H::openTag('input', [
        'type' => 'number',
        'name' => $sda,
        'id' => $sda,
        'class' => 'form-control form-control-lg',
        'value' => $body[$sda]
       ]);
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       $snd = 'settings[generate_invoice_number_for_draft]';
       echo H::openTag('label', [
        'for' => $snd
       ]);
        echo $translator->translate(
         'generate.invoice.number.for.draft'
        );
       echo H::closeTag('label');

       $body[$snd] = $s->getSetting(
       'generate_invoice_number_for_draft'
       );

       echo H::openTag('select', [
        'name' => $snd,
        'class' => 'form-control form-control-lg',
        'id' => $snd
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body[$snd] == '1'
        )
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
       $smsp = 'settings[mark_invoices_sent_pdf]';
       echo H::openTag('label', [
        'for' => $smsp
       ]);
        echo $translator->translate(
         'mark.invoices.sent.pdf'
        );
       echo H::closeTag('label');
       $body[$smsp] = $s->getSetting('mark_invoices_sent_pdf');

       echo H::openTag('select', [
        'name' => $smsp,
        'id' => $smsp,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body[$smsp] == '1'
        )
         ->content(
          $translator->translate('yes')
         );
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       $sipp = 'settings[invoice_pre_password]';
       echo H::openTag('label', [
        'for' => $sipp
       ]);
        echo $translator->translate('pre.password');
       echo H::closeTag('label');

       $body[$sipp] =
       $s->getSetting('invoice_pre_password');

       echo H::openTag('input', [
        'type' => 'text',
        'name' => $sipp,
        'id' => $sipp,
        'class' => 'form-control form-control-lg',
        'value' =>
        $body[$sipp]
       ]);
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       $siz = 'settings[include_zugferd]';
       echo H::openTag('label', [
        'for' => $siz
       ]);
        echo $translator->translate(
         'pdf.include.zugferd'
        );
       echo H::closeTag('label');

       $body[$siz] =
       $s->getSetting('include_zugferd');

       echo H::openTag('select', [
        'name' => $siz,
        'id' => $siz,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body[$siz] == '1'
         )
         ->content(
          $translator->translate('yes')
         );
       echo H::openTag('p', $helpBlock);
        echo $translator->translate(
         'pdf.include.zugferd.help'
        );
       echo H::closeTag('p');
      echo H::closeTag('div'); //7

     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       $spw = 'settings[pdf_watermark]';
       echo H::openTag('label', [
        'for' => $spw
       ]);
        echo $translator->translate('pdf.watermark');
       echo H::closeTag('label');

       $body[$spw] =
       $s->getSetting('pdf_watermark');

       echo H::openTag('select', [
        'name' => $spw,
        'id' => $spw,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body[$spw] == '1'
         )
         ->content(
          $translator->translate('yes')
         );
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       $spsi = 'settings[pdf_stream_inv]';
       echo H::openTag('label', [
        'for' => $spsi
       ]);
        echo $translator->translate('stream');
       echo H::closeTag('label');

       $body[$spsi] =
       $s->getSetting('pdf_stream_inv');

       echo H::openTag('select', [
        'name' => $spsi,
        'id' => $spsi,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body[$spsi] == '1'
         )
         ->content(
          $translator->translate('yes')
         );
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       $spai = 'settings[pdf_archive_inv]';
       echo H::openTag('label', [
        'for' => $spai
       ]);
        echo  new I()->class('bi bi-folder');
        echo $translator->translate('archive');
       echo H::closeTag('label');

       $body[$spai] =
       $s->getSetting('pdf_archive_inv');

       echo H::openTag('select', [
        'name' => $spai,
        'id' => $spai,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body[$spai] == '1'
         )
         ->content(
          $translator->translate('yes')
         );
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       $sphi = 'settings[pdf_html_inv]';
       echo H::openTag('label', [
        'for' => $sphi
       ]);
        echo  'Preview Invoice Pdf as Webpage';
       echo H::closeTag('label');

       $body[$sphi] =
       $s->getSetting('pdf_html_inv');

       echo H::openTag('select', [
        'name' => $sphi,
        'id' => $sphi,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body[$sphi] == '1'
         )
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
    echo $translator->translate('templates');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4

    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6

      echo H::openTag('div', $formGroup); //7
       $spit = 'settings[pdf_invoice_template]';
       echo H::openTag('label', [
        'for' => $spit
       ]);
        echo $translator->translate(
         'default.pdf.template'
        );
       echo H::closeTag('label');

       $body[$spit] =
       $s->getSetting('pdf_invoice_template');

       echo H::openTag('select', [
        'name' => $spit,
        'id' => $spit,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('')
         ->content(
          $translator->translate('none')
         );

        /**
        * @var string $invoice_template
        */
        foreach ($pdf_invoice_templates
         as $invoice_template) {
         echo  new Option()
         ->value($invoice_template)
         ->selected(
          $body[$spit] == $invoice_template
        )
         ->content(
          ucfirst($invoice_template)
         );
        }

       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       $spitp = 'settings[pdf_invoice_template_paid]';
       echo H::openTag('label', [
        'for' => $spitp
       ]);
        echo $translator->translate(
         'pdf.template.paid'
        );
       echo H::closeTag('label');

       $body[$spitp] =
       $s->getSetting('pdf_invoice_template_paid');

       echo H::openTag('select', [
        'name' => $spitp,
        'id' => $spitp,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('')
         ->content(
          $translator->translate('none')
         );

        /**
        * @var string $invoice_template
        */
        foreach ($pdf_invoice_templates
         as $invoice_template) {
         echo  new Option()
         ->value($invoice_template)
         ->selected(
          $body[$spitp] == $invoice_template
        )
         ->content(
          ucfirst($invoice_template)
         );
        }

       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       $spito = 'settings[pdf_invoice_template_overdue]';
       echo H::openTag('label', [
        'for' => $spito
       ]);
        echo $translator->translate(
         'pdf.template.overdue'
        );
       echo H::closeTag('label');

       $body[$spito] = $s->getSetting(
        'pdf_invoice_template_overdue'
       );

       echo H::openTag('select', [
        'name' => $spito,
        'class' => 'form-control form-control-lg',
        'id' => $spito
       ]);
        echo  new Option()
         ->value('')
         ->content(
          $translator->translate('none')
         );

        /**
        * @var string $invoice_template
        */
        foreach ($pdf_invoice_templates
         as $invoice_template) {
         echo  new Option()
         ->value($invoice_template)
         ->selected(
          $body[$spito] == $invoice_template
        )
         ->content(
          ucfirst($invoice_template)
         );
        }

       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       $spubt = 'settings[public_invoice_template]';
       echo H::openTag('label', [
        'for' => $spubt
       ]);
        echo $translator->translate(
         'default.public.template'
        );
       echo H::closeTag('label');

       $body[$spubt] =
       $s->getSetting('public_invoice_template');

       echo H::openTag('select', [
        'name' => $spubt,
        'id' => $spubt,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('')
         ->content(
          $translator->translate('none')
         );

        /**
        * @var string $invoice_template
        */
        foreach ($public_invoice_templates
         as $invoice_template) {
         echo  new Option()
         ->value($invoice_template)
         ->selected(
          $body[$spubt] == $invoice_template
        )
         ->content(
          ucfirst($invoice_template)
         );
        }

       echo H::closeTag('select');
      echo H::closeTag('div'); //7

     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6

      echo H::openTag('div', $formGroup); //7
       $seit = 'settings[email_invoice_template]';
       echo H::openTag('label', [
        'for' => $seit
       ]);
        echo $translator->translate(
         'default.email.template'
        );
       echo H::closeTag('label');

       $body[$seit] =
       $s->getSetting('email_invoice_template');

       echo H::openTag('select', [
        'name' => $seit,
        'id' => $seit,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('')
         ->content(
          $translator->translate('none')
         );

        /**
        * @var App\Invoice\Entity\EmailTemplate
        *      $email_template
        */
        foreach ($email_templates_invoice
         as $email_template) {
         echo  new Option()
         ->value(
         $email_template
         ->GetEmailTemplateId()
        )
         ->selected(
          $body[$seit] == $email_template
          ->getEmailTemplateId()
        )
         ->content(
          $email_template
          ->getEmailTemplateTitle()
          ?? ''
         );
        }

       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       $seitp = 'settings[email_invoice_template_paid]';
       echo H::openTag('label', [
        'for' => $seitp
       ]);
        echo $translator->translate(
         'email.template.paid'
        );
       echo H::closeTag('label');

       $body[$seitp] =
       $s->getSetting('email_invoice_template_paid');

       echo H::openTag('select', [
        'name' => $seitp,
        'id' => $seitp,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('')
         ->content(
          $translator->translate('none')
         );

        /**
        * @var App\Invoice\Entity\EmailTemplate
        *      $email_template
        */
        foreach ($email_templates_invoice
         as $email_template) {
         echo  new Option()
         ->value(
         $email_template
         ->getEmailTemplateId()
        )
         ->selected(
          $body[$seitp] == $email_template
          ->getEmailTemplateId()
        )
         ->content(
          $email_template
          ->getEmailTemplateTitle()
          ?? ''
         );
        }

       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       $seito = 'settings[email_invoice_template_overdue]';
       echo H::openTag('label', [
        'for' => $seito
       ]);
        echo $translator->translate(
         'email.template.overdue'
        );
       echo H::closeTag('label');

       $body[$seito] = $s->getSetting(
       'email_invoice_template_overdue'
       );

       echo H::openTag('select', [
        'name' => $seito,
        'class' => 'form-control form-control-lg',
        'id' => $seito
       ]);
        echo  new Option()
         ->value('')
         ->content(
          $translator->translate('none')
         );

        /**
        * @var App\Invoice\Entity\EmailTemplate
        *      $email_template
        */
        foreach ($email_templates_invoice
         as $email_template) {
         echo  new Option()
         ->value(
         $email_template
         ->getEmailTemplateId()
        )
         ->selected(
          $body[$seito] == $email_template
          ->getEmailTemplateId()
        )
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
       $spif = 'settings[pdf_invoice_footer]';
       echo H::openTag('label', [
        'for' => $spif
       ]);
        echo $translator->translate(
         'pdf.invoice.footer'
        );
       echo H::closeTag('label');

       $body[$spif] =
       $s->getSetting('pdf_invoice_footer');

       echo H::openTag('textarea', [
        'name' => $spif,
        'id' => $spif,
        'class' => 'form-control no-margin'
       ]);
        echo $body[$spif];
       echo H::closeTag('textarea');
       echo H::openTag('p', $helpBlock);
        echo $translator->translate(
         'pdf.invoice.footer.hint'
        );
       echo H::closeTag('p');
      echo H::closeTag('div'); //7

     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5

   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3

  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo $translator->translate('email.settings');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4

    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6

      echo H::openTag('div', $formGroup); //7
       $saer = 'settings[automatic_email_on_recur]';
       echo H::openTag('label', [
        'for' => $saer
       ]);
        echo $translator->translate(
         'automatic.email.on.recur'
        );
       echo H::closeTag('label');

       $body[$saer] =
       $s->getSetting('automatic_email_on_recur');

       echo H::openTag('select', [
        'name' => $saer,
        'id' => $saer,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body[$saer] == '1'
        )
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
    echo $translator->translate('other.settings');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4

    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6

      echo H::openTag('div', $formGroup); //7
       $srot = 'settings[read_only_toggle]';
       echo H::openTag('label', [
        'for' => $srot
       ]);
        echo $translator->translate(
         'set.to.read.only'
        );
       echo H::closeTag('label');

       $body[$srot] =
       $s->getSetting('read_only_toggle');

       echo H::openTag('select', [
        'name' => $srot,
        'id' => $srot,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('2')
         ->selected(
          $body[$srot] == '2'
         )
         ->content(
          $translator->translate('sent')
         );
        echo  new Option()
         ->value('3')
         ->selected(
          $body[$srot] == '3'
         )
         ->content(
          $translator->translate('viewed')
         );
        echo  new Option()
         ->value('4')
         ->selected(
          $body[$srot] == '4'
         )
         ->content(
          $translator->translate('paid')
         );
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       $smisc = 'settings[mark_invoices_sent_copy]';
       echo H::openTag('label', [
        'for' => $smisc
       ]);
        echo $translator->translate(
         'mark.invoices.sent.copy'
        );
       echo H::closeTag('label');

       $body[$smisc] =
       $s->getSetting('mark_invoices_sent_copy');

       echo H::openTag('select', [
        'name' => $smisc,
        'id' => $smisc,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body[$smisc] == '1'
        )
         ->content(
          $translator->translate('yes')
         );
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5

   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
