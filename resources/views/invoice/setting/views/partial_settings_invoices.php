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
       $body['settings[default_invoice_group]'] = 
       $s->getSetting('default_invoice_group');
       echo H::openTag('select', [
        'name' => 'settings[default_invoice_group]',
        'id' => 'settings[default_invoice_group]',
        'class' => 'form-control'
       ]);
        echo  new Option()
         ->value('')
         ->content($translator->translate('none')); 
        /**
        * @var App\Invoice\Entity\Group $invoice_group
        */
        foreach ($invoice_groups as $invoice_group) { 
        echo  new Option()
         ->value($invoice_group->getId())
         ->selected(
          $body['settings[default_invoice_group]']
          == $invoice_group->getId()
         )
         ->content($invoice_group->getName() 
          ?? '');
          } 
          echo H::closeTag('select');
          echo H::closeTag('div'); //11

          echo H::openTag('div', $formGroup); //11
          echo H::openTag('label', [
          'for' => 'settings[default_invoice_terms]'
         ]);
        echo $translator->translate('default.terms');
       echo H::closeTag('label');
       $body['settings[default_invoice_terms]'] = 
       $s->getSetting('default_invoice_terms');
       echo H::openTag('textarea', [
        'name' => 'settings[default_invoice_terms]',
        'id' => 'settings[default_invoice_terms]',
        'class' => 'form-control',
        'rows' => '4'
       ]); 
        echo $body['settings[default_invoice_terms]'];
       echo H::closeTag('textarea');
      echo H::closeTag('div'); //7

     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6

      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 
        'settings[invoice_default_payment_method]'
       ]);
        echo $translator->translate(
         'default.payment.method'
        );
       echo H::closeTag('label');
       $body['settings[invoice_default_payment_method]'] 
       = $s->getSetting(
        'invoice_default_payment_method'
       );
       echo H::openTag('select', [
        'name' => 
        'settings[invoice_default_payment_method]',
        'class' => 'form-control',
        'id' => 
        'settings[invoice_default_payment_method]'
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
          $body[
          'settings[invoice_default_'
          . 'payment_method]'
         ]
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

       $body['settings[invoices_due_after]'] = 
       $s->getSetting('invoices_due_after');

       echo H::openTag('input', [
        'type' => 'number',
        'name' => 'settings[invoices_due_after]',
        'id' => 'settings[invoices_due_after]',
        'class' => 'form-control',
        'value' => $body['settings[invoices_due_after]']
       ]);
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 
        'settings[generate_invoice_number_for_'
        . 'draft]'
       ]);
        echo $translator->translate(
         'generate.invoice.number.for.draft'
        );
       echo H::closeTag('label');

       $body[
        'settings[generate_invoice_number_for_draft]'
       ] = $s->getSetting(
       'generate_invoice_number_for_draft'
       );

       echo H::openTag('select', [
        'name' => 
        'settings[generate_invoice_number_for_'
        . 'draft]',
        'class' => 'form-control',
        'id' => 
        'settings[generate_invoice_number_for_'
        . 'draft]'
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body[
          'settings[generate_invoice_number_'
          . 'for_draft]'
         ] == '1'
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
       echo H::openTag('label', [
        'for' => 'settings[mark_invoices_sent_pdf]'
       ]);
        echo $translator->translate(
         'mark.invoices.sent.pdf'
        );
       echo H::closeTag('label');

       $body['settings[mark_invoices_sent_pdf]'] = 
       $s->getSetting('mark_invoices_sent_pdf');

       echo H::openTag('select', [
        'name' => 'settings[mark_invoices_sent_pdf]',
        'id' => 'settings[mark_invoices_sent_pdf]',
        'class' => 'form-control'
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body[
          'settings[mark_invoices_sent_pdf]'
         ] == '1'
        )
         ->content(
          $translator->translate('yes')
         );
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 'settings[invoice_pre_password]'
       ]);
        echo $translator->translate('pre.password');
       echo H::closeTag('label');

       $body['settings[invoice_pre_password]'] = 
       $s->getSetting('invoice_pre_password');

       echo H::openTag('input', [
        'type' => 'text',
        'name' => 'settings[invoice_pre_password]',
        'id' => 'settings[invoice_pre_password]',
        'class' => 'form-control',
        'value' => 
        $body['settings[invoice_pre_password]']
       ]);
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 'settings[include_zugferd]'
       ]);
        echo $translator->translate(
         'pdf.include.zugferd'
        );
       echo H::closeTag('label');

       $body['settings[include_zugferd]'] = 
       $s->getSetting('include_zugferd');

       echo H::openTag('select', [
        'name' => 'settings[include_zugferd]',
        'id' => 'settings[include_zugferd]',
        'class' => 'form-control'
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body['settings[include_zugferd]'] 
          == '1'
         )
         ->content(
          $translator->translate('yes')
         );
       echo H::closeTag('select');
       echo H::openTag('p', $helpBlock);
        echo $translator->translate(
         'pdf.include.zugferd.help'
        );
       echo H::closeTag('p');
      echo H::closeTag('div'); //7

     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 'settings[pdf_watermark]'
       ]);
        echo $translator->translate('pdf.watermark');
       echo H::closeTag('label');

       $body['settings[pdf_watermark]'] = 
       $s->getSetting('pdf_watermark');

       echo H::openTag('select', [
        'name' => 'settings[pdf_watermark]',
        'id' => 'settings[pdf_watermark]',
        'class' => 'form-control'
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body['settings[pdf_watermark]'] 
          == '1'
         )
         ->content(
          $translator->translate('yes')
         );
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 'settings[pdf_stream_inv]'
       ]);
        echo  new I()
         ->class('fa fa-brands fa-google');
        echo $translator->translate('stream');
       echo H::closeTag('label');

       $body['settings[pdf_stream_inv]'] = 
       $s->getSetting('pdf_stream_inv');

       echo H::openTag('select', [
        'name' => 'settings[pdf_stream_inv]',
        'id' => 'settings[pdf_stream_inv]',
        'class' => 'form-control'
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body['settings[pdf_stream_inv]'] 
          == '1'
         )
         ->content(
          $translator->translate('yes')
         );
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 'settings[pdf_archive_inv]'
       ]);
        echo  new I()->class('fa fa-folder');
        echo $translator->translate('archive');
       echo H::closeTag('label');

       $body['settings[pdf_archive_inv]'] = 
       $s->getSetting('pdf_archive_inv');

       echo H::openTag('select', [
        'name' => 'settings[pdf_archive_inv]',
        'id' => 'settings[pdf_archive_inv]',
        'class' => 'form-control'
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body['settings[pdf_archive_inv]'] 
          == '1'
         )
         ->content(
          $translator->translate('yes')
         );
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 'settings[pdf_html_inv]'
       ]);
        echo  new I()
         ->class('fa fa-solid fa-code');
       echo H::closeTag('label');

       $body['settings[pdf_html_inv]'] = 
       $s->getSetting('pdf_html_inv');

       echo H::openTag('select', [
        'name' => 'settings[pdf_html_inv]',
        'id' => 'settings[pdf_html_inv]',
        'class' => 'form-control'
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );
        echo  new Option()
         ->value('1')
         ->selected(
          $body['settings[pdf_html_inv]'] 
          == '1'
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
       echo H::openTag('label', [
        'for' => 'settings[pdf_invoice_template]'
       ]);
        echo $translator->translate(
         'default.pdf.template'
        );
       echo H::closeTag('label');

       $body['settings[pdf_invoice_template]'] = 
       $s->getSetting('pdf_invoice_template');

       echo H::openTag('select', [
        'name' => 'settings[pdf_invoice_template]',
        'id' => 'settings[pdf_invoice_template]',
        'class' => 'form-control'
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
         $body[
         'settings[pdf_invoice_'
         . 'template]'
        ] == $invoice_template
        )
         ->content(
          ucfirst($invoice_template)
         );
        } 

       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 
        'settings[pdf_invoice_template_paid]'
       ]);
        echo $translator->translate(
         'pdf.template.paid'
        );
       echo H::closeTag('label');

       $body['settings[pdf_invoice_template_paid]'] = 
       $s->getSetting('pdf_invoice_template_paid');

       echo H::openTag('select', [
        'name' => 
        'settings[pdf_invoice_template_paid]',
        'id' => 'settings[pdf_invoice_template_paid]',
        'class' => 'form-control'
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
         $body[
         'settings[pdf_invoice_'
         . 'template_paid]'
        ] == $invoice_template
        )
         ->content(
          ucfirst($invoice_template)
         );
        } 

       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 
        'settings[pdf_invoice_template_overdue]'
       ]);
        echo $translator->translate(
         'pdf.template.overdue'
        );
       echo H::closeTag('label');

       $body['settings[pdf_invoice_template_overdue]'] 
       = $s->getSetting(
        'pdf_invoice_template_overdue'
       );

       echo H::openTag('select', [
        'name' => 
        'settings[pdf_invoice_template_overdue]',
        'class' => 'form-control',
        'id' => 
        'settings[pdf_invoice_template_overdue]'
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
         $body[
         'settings[pdf_invoice_'
         . 'template_overdue]'
        ] == $invoice_template
        )
         ->content(
          ucfirst($invoice_template)
         );
        } 

       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 'settings[public_invoice_template]'
       ]);
        echo $translator->translate(
         'default.public.template'
        );
       echo H::closeTag('label');

       $body['settings[public_invoice_template]'] = 
       $s->getSetting('public_invoice_template');

       echo H::openTag('select', [
        'name' => 'settings[public_invoice_template]',
        'id' => 'settings[public_invoice_template]',
        'class' => 'form-control'
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
         $body[
         'settings[public_invoice_'
         . 'template]'
        ] == $invoice_template
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
       echo H::openTag('label', [
        'for' => 'settings[email_invoice_template]'
       ]);
        echo $translator->translate(
         'default.email.template'
        );
       echo H::closeTag('label');

       $body['settings[email_invoice_template]'] = 
       $s->getSetting('email_invoice_template');

       echo H::openTag('select', [
        'name' => 'settings[email_invoice_template]',
        'id' => 'settings[email_invoice_template]',
        'class' => 'form-control'
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
         ->GetEmail_template_id()
        )
         ->selected(
          $body[
          'settings[email_invoice_'
          . 'template]'
         ] == $email_template
         ->getEmail_template_id()
        )
         ->content(
          $email_template
          ->getEmail_template_title()
          ?? ''
         );
        } 

       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 
        'settings[email_invoice_template_paid]'
       ]);
        echo $translator->translate(
         'email.template.paid'
        );
       echo H::closeTag('label');

       $body['settings[email_invoice_template_paid]'] = 
       $s->getSetting('email_invoice_template_paid');

       echo H::openTag('select', [
        'name' => 
        'settings[email_invoice_template_paid]',
        'id' => 
        'settings[email_invoice_template_paid]',
        'class' => 'form-control'
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
         ->getEmail_template_id()
        )
         ->selected(
          $body[
          'settings[email_invoice_'
          . 'template_paid]'
         ] == $email_template
         ->getEmail_template_id()
        )
         ->content(
          $email_template
          ->getEmail_template_title()
          ?? ''
         );
        } 

       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 
        'settings[email_invoice_template_'
        . 'overdue]'
       ]);
        echo $translator->translate(
         'email.template.overdue'
        );
       echo H::closeTag('label');

       $body[
        'settings[email_invoice_template_overdue]'
       ] = $s->getSetting(
       'email_invoice_template_overdue'
       );

       echo H::openTag('select', [
        'name' => 
        'settings[email_invoice_template_'
        . 'overdue]',
        'class' => 'form-control',
        'id' => 
        'settings[email_invoice_template_overdue]'
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
         ->getEmail_template_id()
        )
         ->selected(
          $body[
          'settings[email_invoice_'
          . 'template_overdue]'
         ] == $email_template
         ->getEmail_template_id()
        )
         ->content(
          $email_template
          ->getEmail_template_title()
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
        'for' => 'settings[pdf_invoice_footer]'
       ]);
        echo $translator->translate(
         'pdf.invoice.footer'
        );
       echo H::closeTag('label');

       $body['settings[pdf_invoice_footer]'] = 
       $s->getSetting('pdf_invoice_footer');

       echo H::openTag('textarea', [
        'name' => 'settings[pdf_invoice_footer]',
        'id' => 'settings[pdf_invoice_footer]',
        'class' => 'form-control no-margin'
       ]); 
        echo $body['settings[pdf_invoice_footer]'];
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
       echo H::openTag('label', [
        'for' => 
        'settings[automatic_email_on_recur]'
       ]);
        echo $translator->translate(
         'automatic.email.on.recur'
        );
       echo H::closeTag('label');

       $body['settings[automatic_email_on_recur]'] = 
       $s->getSetting('automatic_email_on_recur');

       echo H::openTag('select', [
        'name' => 
        'settings[automatic_email_on_recur]',
        'id' => 'settings[automatic_email_on_recur]',
        'class' => 'form-control'
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         ); 
        echo  new Option()
         ->value('1')
         ->selected(
          $body[
          'settings[automatic_email_on_'
          . 'recur]'
         ] == '1'
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
       echo H::openTag('label', [
        'for' => 'settings[read_only_toggle]'
       ]);
        echo $translator->translate(
         'set.to.read.only'
        );
       echo H::closeTag('label');

       $body['settings[read_only_toggle]'] = 
       $s->getSetting('read_only_toggle');

       echo H::openTag('select', [
        'name' => 'settings[read_only_toggle]',
        'id' => 'settings[read_only_toggle]',
        'class' => 'form-control'
       ]);
        echo  new Option()
         ->value('2')
         ->selected(
          $body['settings[read_only_toggle]'] 
          == '2'
         )
         ->content(
          $translator->translate('sent')
         ); 
        echo  new Option()
         ->value('3')
         ->selected(
          $body['settings[read_only_toggle]'] 
          == '3'
         )
         ->content(
          $translator->translate('viewed')
         ); 
        echo  new Option()
         ->value('4')
         ->selected(
          $body['settings[read_only_toggle]'] 
          == '4'
         )
         ->content(
          $translator->translate('paid')
         ); 
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 'settings[mark_invoices_sent_copy]'
       ]);
        echo $translator->translate(
         'mark.invoices.sent.copy'
        );
       echo H::closeTag('label');

       $body['settings[mark_invoices_sent_copy]'] = 
       $s->getSetting('mark_invoices_sent_copy');

       echo H::openTag('select', [
        'name' => 'settings[mark_invoices_sent_copy]',
        'id' => 'settings[mark_invoices_sent_copy]',
        'class' => 'form-control'
       ]);
        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         ); 
        echo  new Option()
         ->value('1')
         ->selected(
          $body[
          'settings[mark_invoices_sent_'
          . 'copy]'
         ] == '1'
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
