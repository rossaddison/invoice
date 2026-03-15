<?php
declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
*/

echo H::openTag('div', ['class' => 'row']); //1
 echo H::openTag('div', [ //2
  'class' => 'col-xs-12 col-md-8 col-md-offset-2'
 ]); //2
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo $translator->translate('email');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[email_pdf_attachment]'
       ]);
        echo $translator->translate('email.pdf.attachment');
       echo H::closeTag('label');
       $body['settings[email_pdf_attachment]'] =
       $s->getSetting('email_pdf_attachment');
       echo H::openTag('select', [
        'name' => 'settings[email_pdf_attachment]',
        'id' => 'settings[email_pdf_attachment]',
        'class' => 'form-control',
        'data-minimum-results-for-search' => 'Infinity'
       ]);
        echo  new Option()
         ->value('0')
         ->selected($body['settings[email_pdf_attachment]'] === '0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body['settings[email_pdf_attachment]'] === '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo H::openTag('label', ['for' => 'email_send_method']);
     echo $translator->translate('email.send.method');
    echo H::closeTag('label'); //4
    echo H::openTag('select', [
     'name' => 'settings[email_send_method]',
     'id' => 'email_send_method',
     'class' => 'form-control'
    ]);
     echo  new Option()
      ->value('')
      ->content($translator->translate('none'));
     echo  new Option()
      ->value('symfony')
      ->selected($s->getSetting('email_send_method') === 'symfony')
      ->content('eSmtp: Symfony');
    echo H::closeTag('select');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('div', ['class' => 'form-group']); //8
        echo H::tag('h6', 'eSMTP Host: ' .
         (string) $s->config_params()['esmtp_host']);
       echo H::closeTag('div'); //8
       echo H::openTag('div', ['class' => 'form-group']); //8
        echo H::tag('h6', 'eSMTP Port: ' .
         (string) $s->config_params()['esmtp_port']);
       echo H::closeTag('div'); //8
       echo H::openTag('div', ['class' => 'form-group']); //8
        echo H::tag('h6', 'eSMTP Schema: ' . ucfirst(
         (string) $s->config_params()['esmtp_scheme']
        ));
       echo H::closeTag('div'); //8
       echo H::openTag('div', ['class' => 'form-group']); //8
        echo H::tag('h6', 'Use SendMail: ' .
         $s->config_params()['use_send_mail']);
       echo H::closeTag('div'); //8
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
