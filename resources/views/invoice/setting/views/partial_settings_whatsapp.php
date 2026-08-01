<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
* Related logic: see WhatsApp Business Cloud API "Get Started" guide at
* https://developers.facebook.com/documentation/business-messaging/whatsapp/get-started
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
* @var array $body
*/

echo H::tag('style', ' label { font-weight: bold; } ');

$row = ['class' => 'row'];
$colMd6 = ['class' => 'col-12 col-md-6'];
$colMd8 = ['class' => 'col-12 col-md-8 offset-md-2'];
$panel = ['class' => 'card'];
$panelHead = ['class' => 'card-header'];
$panelBody = ['class' => 'card-body'];
$formGroup = ['class' => 'mb-3'];
$checkbox = ['class' => 'form-check'];

echo H::openTag('div', $row); //1
 echo H::openTag('div', $colMd8); //2
  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo $translator->translate('whatsapp.business.cloud.api.general.purpose');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       $enable = 'settings[enable_whatsapp]';
       echo H::openTag('div', $checkbox); //8
        $body[$enable] = $s->getSetting('enable_whatsapp');
        echo H::openTag('input', [
         'type' => 'hidden',
         'name' => $enable,
         'value' => '0'
        ]);
        echo H::openTag('input', [
         'type' => 'checkbox',
         'class' => 'form-check-input',
         'id' => 'enable_whatsapp',
         'name' => $enable,
         'value' => '1',
         'checked' => ($body[$enable] == '1') ? 'checked' : null
        ]);
        echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'enable_whatsapp']);
         echo H::a(
          $translator->translate('whatsapp.business.cloud.api.enable'),
          'https://developers.facebook.com/documentation/business-messaging/whatsapp/get-started',
          ['class' => 'text-decoration-none']
         );
        echo H::closeTag('label');
       echo H::closeTag('div'); //8
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5

    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('p');
        echo H::openTag('b');
         echo 'Further reading: ';
        echo H::closeTag('b');
        echo H::openTag('a', [
         'href' => 'https://developers.facebook.com/documentation/business-messaging/whatsapp/get-started',
         'target' => '_blank'
        ]);
         echo 'WhatsApp Cloud API — Get Started';
        echo H::closeTag('a');
       echo H::closeTag('p');
       echo H::openTag('p');
        echo H::openTag('b');
         echo 'Further reading: ';
        echo H::closeTag('b');
        echo H::openTag('a', [
         'href' => 'https://developers.facebook.com/documentation/business-messaging/whatsapp/business-phone-numbers/phone-numbers',
         'target' => '_blank'
        ]);
         echo 'WhatsApp Business phone numbers';
        echo H::closeTag('a');
       echo H::closeTag('p');

       echo H::openTag('p');
        echo H::openTag('b');
         echo '1. Create a Meta Business App with the WhatsApp product added, at ';
        echo H::closeTag('b');
        echo H::openTag('a', [
         'href' => 'https://developers.facebook.com/apps',
         'target' => '_blank'
        ]);
         echo 'developers.facebook.com/apps';
        echo H::closeTag('a');
       echo H::closeTag('p');

       echo H::openTag('p');
        echo H::openTag('b');
         echo '2. In the app, go to ';
        echo H::closeTag('b');
        echo H::openTag('pre');
         echo 'WhatsApp > API Setup';
        echo H::closeTag('pre');
        echo 'and copy the ';
        echo H::openTag('b');
         echo 'Phone Number ID';
        echo H::closeTag('b');
        echo ' (this is Meta\'s internal ID for the sending number, not the visible phone number itself). Record it here:';
       echo H::closeTag('p');
       $phoneNumberId = 'settings[whatsapp_phone_number_id]';
       echo H::openTag('label', ['for' => $phoneNumberId]);
        echo $translator->translate('whatsapp.business.cloud.api.phone.number.id');
       echo H::closeTag('label');
       $body[$phoneNumberId] = $s->getSetting('whatsapp_phone_number_id');
       echo H::openTag('input', [
        'type' => 'text',
        'name' => $phoneNumberId,
        'id' => $phoneNumberId,
        'class' => 'form-select',
        'value' => H::encode($body[$phoneNumberId])
       ]);

       echo H::openTag('p');
        echo H::openTag('b');
         echo '3. ';
        echo H::closeTag('b');
        echo 'On the same page, record the ';
        echo H::openTag('b');
         echo 'WhatsApp Business Account ID';
        echo H::closeTag('b');
        echo ':';
       echo H::closeTag('p');
       $businessAccountId = 'settings[whatsapp_business_account_id]';
       echo H::openTag('label', ['for' => $businessAccountId]);
        echo $translator->translate('whatsapp.business.cloud.api.business.account.id');
       echo H::closeTag('label');
       $body[$businessAccountId] = $s->getSetting('whatsapp_business_account_id');
       echo H::openTag('input', [
        'type' => 'text',
        'name' => $businessAccountId,
        'id' => $businessAccountId,
        'class' => 'form-select',
        'value' => H::encode($body[$businessAccountId])
       ]);

       echo H::openTag('p');
        echo H::openTag('b');
         echo '4. ';
        echo H::closeTag('b');
        echo 'In Business Settings, create a ';
        echo H::openTag('b');
         echo 'System User';
        echo H::closeTag('b');
        echo ' and generate a permanent access token for it — the temporary token shown by default on the API Setup page expires and is only suitable for testing. Record the permanent token here:';
       echo H::closeTag('p');
       $accessToken = 'settings[whatsapp_access_token]';
       echo H::openTag('label', ['for' => $accessToken]);
        echo $translator->translate('whatsapp.business.cloud.api.access.token');
       echo H::closeTag('label');
       $body[$accessToken] = $s->getSetting('whatsapp_access_token');
       echo H::openTag('input', [
        'type' => 'password',
        'name' => $accessToken,
        'id' => $accessToken,
        'class' => 'form-select',
        'value' => H::encode(
         strlen($body[$accessToken]) > 0 ? $s->decode($body[$accessToken]) : ''
        )
       ]);
       echo H::openTag('input', [
        'type' => 'hidden',
        'value' => '1',
        'name' => 'settings[whatsapp_access_token_field_is_password]'
       ]);

       echo H::openTag('p');
        echo H::openTag('b');
         echo '5. ';
        echo H::closeTag('b');
        echo 'WhatsApp only allows free-form messages within a 24-hour window after the customer last messaged you. Outside that window — which covers every automated "your invoice is ready" notification — the message must use a pre-approved ';
        echo H::openTag('b');
         echo 'message template';
        echo H::closeTag('b');
        echo '. Create and get one approved under ';
        echo H::openTag('pre');
         echo 'WhatsApp > Message Templates';
        echo H::closeTag('pre');
        echo 'then record its name and language code below:';
       echo H::closeTag('p');
       $templateName = 'settings[whatsapp_template_name]';
       echo H::openTag('label', ['for' => $templateName]);
        echo $translator->translate('whatsapp.business.cloud.api.template.name');
       echo H::closeTag('label');
       $body[$templateName] = $s->getSetting('whatsapp_template_name');
       echo H::openTag('input', [
        'type' => 'text',
        'name' => $templateName,
        'id' => $templateName,
        'class' => 'form-select',
        'value' => H::encode($body[$templateName])
       ]);
       echo H::openTag('br');
       $templateLanguage = 'settings[whatsapp_template_language]';
       echo H::openTag('label', ['for' => $templateLanguage]);
        echo $translator->translate('whatsapp.business.cloud.api.template.language');
       echo H::closeTag('label');
       $body[$templateLanguage] = $s->getSetting('whatsapp_template_language') ?: 'en_GB';
       echo H::openTag('input', [
        'type' => 'text',
        'name' => $templateLanguage,
        'id' => $templateLanguage,
        'class' => 'form-select',
        'value' => H::encode($body[$templateLanguage])
       ]);

       echo H::openTag('p');
        echo H::openTag('b');
         echo '6. ';
        echo H::closeTag('b');
        echo 'While the app is in development mode, Meta only delivers messages to recipient numbers added and verified under ';
        echo H::openTag('pre');
         echo 'WhatsApp > API Setup > To';
        echo H::closeTag('pre');
        echo 'Add your own number there first, then record it below (in international format, e.g. +447700900123):';
       echo H::closeTag('p');
       $testRecipient = 'settings[whatsapp_test_recipient_number]';
       echo H::openTag('label', ['for' => $testRecipient]);
        echo $translator->translate('whatsapp.business.cloud.api.test.recipient.number');
       echo H::closeTag('label');
       $body[$testRecipient] = $s->getSetting('whatsapp_test_recipient_number');
       echo H::openTag('input', [
        'type' => 'text',
        'name' => $testRecipient,
        'id' => $testRecipient,
        'class' => 'form-select',
        'value' => H::encode($body[$testRecipient])
       ]);

       echo H::openTag('p');
        echo H::openTag('b');
         echo '7. ';
        echo H::closeTag('b');
        echo 'Save these settings, then send the configured template to the number above ';
        echo H::openTag('a', [
         'href' => $urlGenerator->generate('whatsapp/index')
        ]);
         echo 'by clicking here...';
        echo H::closeTag('a');
       echo H::closeTag('p');
       echo H::openTag('br');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5

    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('label');
       echo H::openTag('b');
        echo H::openTag('h4');
         echo 'Webhooks';
        echo H::closeTag('h4');
       echo H::closeTag('b');
      echo H::closeTag('label');
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('p');
        echo $translator->translate('whatsapp.business.cloud.api.webhook.current.status');
       echo H::closeTag('p');
       echo H::openTag('p');
        echo $translator->translate('whatsapp.business.cloud.api.webhook.future.use');
       echo H::closeTag('p');
       echo H::openTag('p');
        echo H::openTag('b');
         echo 'Further reading: ';
        echo H::closeTag('b');
        echo H::openTag('a', [
         'href' => 'https://developers.facebook.com/documentation/business-messaging/whatsapp/webhooks/get-started',
         'target' => '_blank'
        ]);
         echo 'WhatsApp Webhooks — Get Started';
        echo H::closeTag('a');
       echo H::closeTag('p');
       $verifyToken = 'settings[whatsapp_webhook_verify_token]';
       echo H::openTag('label', ['for' => $verifyToken]);
        echo $translator->translate('whatsapp.business.cloud.api.webhook.verify.token');
       echo H::closeTag('label');
       $body[$verifyToken] = $s->getSetting('whatsapp_webhook_verify_token');
       echo H::openTag('input', [
        'type' => 'password',
        'name' => $verifyToken,
        'id' => $verifyToken,
        'class' => 'form-select',
        'value' => H::encode($body[$verifyToken])
       ]);
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5

    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label');
        echo H::openTag('b');
         echo $translator->translate('whatsapp.business.cloud.api.webhook.url.this.site');
        echo H::closeTag('b');
       echo H::closeTag('label');
       echo H::openTag('p');
        echo H::openTag('pre');
         echo $urlGenerator->generateAbsolute('whatsapp/webhook');
        echo H::closeTag('pre');
       echo H::closeTag('p');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
