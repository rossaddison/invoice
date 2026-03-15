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
$formControl = ['class' => 'form-control'];
$helpBlock = ['class' => 'help-block'];
$noMargin = ['class' => 'form-control no-margin'];
$minSearch = ['data-minimum-results-for-search' => 'Infinity'];

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

       echo H::openTag('label', [
        'for' => 'settings[default_quote_group]'
       ]); 

        echo $translator->translate(
         'default.quote.group'
        );
       echo H::closeTag('label'); 

       $body['settings[default_quote_group]'] = 
       $s->getSetting('default_quote_group');


       echo H::openTag('select', array_merge([
        'name' => 'settings[default_quote_group]',
        'id' => 'settings[default_quote_group]'
       ], $formControl, $minSearch)); 


        echo  new Option()
         ->value('')
         ->content($translator->translate('none'));


        /**
        * @var App\Invoice\Entity\Group 
        * $invoice_group
        */
        foreach ($invoice_groups as $invoice_group) { 
        echo  new Option()
         ->value($invoice_group->getId())
         ->selected(
          $body['settings[default_quote_group]'] 
          == $invoice_group->getId()
         )
         ->content(
          $invoice_group->getName() ?? ''
         );
        } 

       echo H::closeTag('select'); 
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7

       echo H::openTag('label', [
        'for' => 'settings[default_quote_notes]'
       ]); 

        echo $translator->translate('default.notes');
       echo H::closeTag('label'); 

       $body['settings[default_quote_notes]'] = 
       $s->getSetting('default_quote_notes');


       echo H::openTag('textarea', array_merge([
        'name' => 'settings[default_quote_notes]',
        'id' => 'settings[default_quote_notes]',
        'rows' => '3'
       ], $formControl)); 

        echo $body['settings[default_quote_notes]'];
       echo H::closeTag('textarea'); 
      echo H::closeTag('div'); //7

     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7

       echo H::openTag('label', [
        'for' => 'settings[quotes_expire_after]'
       ]); 

        echo $translator->translate(
         'quotes.expire.after'
        );
       echo H::closeTag('label'); 

       $body['settings[quotes_expire_after]'] = 
       $s->getSetting('quotes_expire_after');


       echo H::openTag('input', array_merge([
        'type' => 'number',
        'name' => 'settings[quotes_expire_after]',
        'id' => 'settings[quotes_expire_after]',
        'value' => 
        $body['settings[quotes_expire_after]']
       ], $formControl)); 

      echo H::closeTag('div'); //7
      echo H::openTag('div', $formGroup); //7

       echo H::openTag('label', [
        'for' => 
        'settings[generate_quote_number_for_draft]'
       ]); 

        echo $translator->translate(
         'generate.quote.number.for.draft'
        );
       echo H::closeTag('label'); 

       $body['settings[generate_quote_number_for_draft]'] = 
       $s->getSetting(
        'generate_quote_number_for_draft'
       );


       echo H::openTag('select', array_merge([
        'name' => 
        'settings[generate_quote_number_for_draft]',
        'id' => 
        'settings[generate_quote_number_for_draft]'
       ], $formControl, $minSearch)); 


        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );


        echo  new Option()
         ->value('1')
         ->selected(
          $body[
          'settings['.
          'generate_quote_number_for_draft]'
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
        'for' => 'settings[mark_quotes_sent_pdf]'
       ]); 

        echo $translator->translate(
         'mark.quotes.sent.pdf'
        );
       echo H::closeTag('label'); 

       $body['settings[mark_quotes_sent_pdf]'] = 
       $s->getSetting('mark_quotes_sent_pdf');


       echo H::openTag('select', array_merge([
        'name' => 'settings[mark_quotes_sent_pdf]',
        'id' => 'settings[mark_quotes_sent_pdf]'
       ], $formControl, $minSearch)); 


        echo  new Option()
         ->value('0')
         ->content(
          $translator->translate('no')
         );


        echo  new Option()
         ->value('1')
         ->selected(
          $body['settings[mark_quotes_sent_pdf]'] 
          == '1'
         )
         ->content(
          $translator->translate('yes')
         );

       echo H::closeTag('select'); 
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7

       echo H::openTag('label', [
        'for' => 'settings[quote_pre_password]'
       ]); 

        echo $translator->translate(
         'quote.pre.password'
        );
       echo H::closeTag('label'); 

       $body['settings[quote_pre_password]'] = 
       $s->getSetting('quote_pre_password');


       echo H::openTag('input', array_merge([
        'type' => 'text',
        'name' => 'settings[quote_pre_password]',
        'id' => 'settings[quote_pre_password]',
        'value' => 
        $body['settings[quote_pre_password]']
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

       echo H::openTag('label', [
        'for' => 'settings[pdf_quote_template]'
       ]); 

        echo $translator->translate(
         'default.pdf.template'
        );
       echo H::closeTag('label'); 

       $body['settings[pdf_quote_template]'] = 
       $s->getSetting('pdf_quote_template');


       echo H::openTag('select', array_merge([
        'name' => 'settings[pdf_quote_template]',
        'id' => 'settings[pdf_quote_template]'
       ], $formControl, $minSearch)); 


        echo  new Option()
         ->value('')
         ->content($translator->translate('none'));


        /**
        * @var string $quote_template
        */
        foreach ($pdf_quote_templates 
         as $quote_template) { 
         echo  new Option()
         ->value($quote_template)
         ->selected(
         $body['settings[pdf_quote_template]'] 
         == $quote_template
        )
         ->content(ucfirst($quote_template));
        } 

       echo H::closeTag('select'); 
      echo H::closeTag('div'); //7

      echo H::openTag('div', $formGroup); //7

       echo H::openTag('label', [
        'for' => 'settings[public_quote_template]'
       ]); 

        echo $translator->translate(
         'default.public.template'
        );
       echo H::closeTag('label'); 

       $body['settings[public_quote_template]'] = 
       $s->getSetting('public_quote_template');


       echo H::openTag('select', array_merge([
        'name' => 'settings[public_quote_template]',
        'id' => 'settings[public_quote_template]'
       ], $formControl, $minSearch)); 


        echo  new Option()
         ->value('')
         ->content($translator->translate('none'));


        /**
        * @var string $quote_template
        */
        foreach ($public_quote_templates 
         as $quote_template) { 
         echo  new Option()
         ->value($quote_template)
         ->selected(
         $body[
         'settings[public_quote_template]'
        ] == $quote_template
        )
         ->content(ucfirst($quote_template));
        } 

       echo H::closeTag('select'); 
      echo H::closeTag('div'); //7

     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6

      echo H::openTag('div', $formGroup); //7

       echo H::openTag('label', [
        'for' => 'settings[email_quote_template]'
       ]); 

        echo $translator->translate(
         'default.email.template'
        );
       echo H::closeTag('label'); 

       $body['settings[email_quote_template]'] = 
       $s->getSetting('email_quote_template');


       echo H::openTag('select', array_merge([
        'name' => 'settings[email_quote_template]',
        'id' => 'settings[email_quote_template]'
       ], $formControl, $minSearch)); 


        echo  new Option()
         ->value('')
         ->content($translator->translate('none'));


        /**
        * @var App\Invoice\Entity\EmailTemplate 
        * $email_template
        */
        foreach ($email_templates_quote 
         as $email_template) { 
         echo  new Option()
         ->value(
         $email_template
         ->getEmail_template_id()
        )
         ->selected(
          $body[
          'settings[email_quote_template]'
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
        'for' => 'settings[pdf_quote_footer]'
       ]); 

        echo $translator->translate(
         'pdf.quote.footer'
        );
       echo H::closeTag('label'); 

       $body['settings[pdf_quote_footer]'] = 
       $s->getSetting('pdf_quote_footer');


       echo H::openTag('textarea', array_merge([
        'name' => 'settings[pdf_quote_footer]',
        'id' => 'settings[pdf_quote_footer]'
       ], $noMargin)); 

        echo $body['settings[pdf_quote_footer]'];
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
