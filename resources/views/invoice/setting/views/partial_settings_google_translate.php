<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
* @var array $locales
*/

$row = ['class' => 'row'];
$colMd6 = ['class' => 'col-xs-12 col-md-6'];
$colMd8 = ['class' => 'col-xs-12 col-md-8 col-md-offset-2'];
$panel = ['class' => 'panel panel-default'];
$panelHead = ['class' => 'panel-heading'];
$panelBody = ['class' => 'panel-body'];
$formGroup = ['class' => 'form-group'];
$kJsonFilename = 'settings[google_translate_json_filename]';
$kLocale = 'settings[google_translate_locale]';
$biLink = 'bi bi-link';
$inputSmFc = 'input-sm form-control';
$curlPemUrl = 'https://curl.haxx.se/ca/cacert.pem';
$googleConsoleUrl = 'https://console.cloud.google.com/projectselector2/';
$googleConsolePath = 'iam-admin/serviceaccounts?supportedpurview=project';
$adjThe = ' Adjust the ';

echo H::openTag('div', $row); //1
 echo H::openTag('div', $colMd8); //2
  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo 'Google Translate';
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kJsonFilename
       ]);
        echo H::openTag('i', ['class' => 'bi bi-info-circle']);
        echo H::closeTag('i');
        echo ' ';
        echo 'Google Translate Json Filename ';
        echo '(eg. my_json_filename.json)';
       echo H::closeTag('label');
       $body[$kJsonFilename] =
       $s->getSetting('google_translate_json_filename');
       echo H::openTag('input', [
        'type' => 'text',
        'class' => $inputSmFc,
        'name' => $kJsonFilename,
        'id' => $kJsonFilename,
        'value' => $body[$kJsonFilename]
       ]);
      echo H::closeTag('div'); //7
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kLocale
       ]);
        echo H::openTag('i', ['class' => 'bi bi-info-circle']);
        echo H::closeTag('i');
        echo ' ';
        echo 'Google Translate Locale';
       echo H::closeTag('label');
       $body[$kLocale] =
       $s->getSetting('google_translate_locale');
       echo H::openTag('select', [
        'name' => $kLocale,
        'id' => $kLocale,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('')
         ->content($translator->translate('none'));
        /**
        * @var string $key
        * @var string $value
        */
        foreach ($locales as $value) {
        echo  new Option()
         ->value($value)
         ->selected(
          $body[$kLocale] == $value
         )
         ->content($value);
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('p');
     echo H::openTag('b');
      echo 'Objective:';
     echo H::closeTag('b');
     echo ' Translate the template file at ';
     echo '\www\invoice\resources\messages\en\app.php into e.g. ';
     echo H::openTag('a', [
      'href' =>
      'https://github.com/rossaddison/invoice/commit/' .
      '28188010c7965092f92484871712bf8347f0f5ed',
      'target' => '_blank'
     ]);
      echo 'zu_ZA\app.php';
     echo H::closeTag('a');
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('b');
      echo 'Step 1:';
     echo H::closeTag('b');
     echo ' Run the Generator ... Translate \'app\' to translate ';
     echo 'the above file from English into the language of your ';
     echo 'choice into';
     echo H::openTag('pre');
      echo H::openTag('h6');
       echo '...\\resources\\views\\invoice\\generator\\';
       echo 'output_overwrite';
      echo H::closeTag('h6');
     echo H::closeTag('pre');
     echo '.';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('b');
      echo 'Step 2:';
     echo H::closeTag('b');
     echo $adjThe;
     echo H::openTag('code');
      echo '\\resources\\views\\layout';
     echo H::closeTag('code');
     echo ' files.';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('b');
      echo 'Step 3:';
     echo H::closeTag('b');
     echo $adjThe;
     echo H::openTag('code');
      echo 'SettingsRepository locale_language_array()';
     echo H::closeTag('code');
     echo ' to include your language. e.g. \'pt-BR\' and also the ';
     echo H::openTag('code');
      echo 'locales';
     echo H::closeTag('code');
     echo ' function.';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('b');
      echo 'Step 4:';
     echo H::closeTag('b');
     echo $adjThe;
     echo H::openTag('code');
      echo 'config\\web\\params.php';
     echo H::closeTag('code');
     echo ' locales array to include your language. e.g. \'pt-BR\'';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('b');
      echo 'Step 5:';
     echo H::closeTag('b');
     echo ' Copy the contents from outputoverwrite folder into ';
     echo 'your resources/messages/{locale}';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('b');
      echo 'Step 6:';
     echo H::closeTag('b');
     echo ' Adjust the src/ViewInjection/LayoutViewInjection.php';
    echo H::closeTag('p');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('p');
     echo H::openTag('i', ['class' => $biLink]);
     echo H::closeTag('i');
     echo ' ';
     echo H::openTag('a', [
      'href' => $curlPemUrl,
      'target' => '_blank'
     ]);
      echo $curlPemUrl;
     echo H::closeTag('a');
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('i', ['class' => $biLink]);
     echo H::closeTag('i');
     echo H::openTag('a', [
      'href' =>
      $googleConsoleUrl .
      $googleConsolePath,
      'target' => '_blank'
     ]);
      echo $googleConsoleUrl;
      echo $googleConsolePath;
     echo H::closeTag('a');
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('i', ['class' => $biLink]);
     echo H::closeTag('i');
     echo php_ini_loaded_file();
    echo H::closeTag('p');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('p', ['class' => 'demoTitle']);
     echo '&nbsp; &nbsp;';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo 'GeneratorController includes a function ';
     echo H::openTag('em');
      echo 'google_translate_lang';
     echo H::closeTag('em');
     echo '. This function takes the English ';
     echo H::openTag('em');
      echo 'app_lang';
     echo H::closeTag('em');
     echo ' array located in ';
     echo H::openTag('em');
      echo 'src/Invoice/Language/English';
     echo H::closeTag('em');
     echo ' and translates it into the chosen locale ';
     echo '(Settings...View...Google Translate) outputting it to ';
     echo H::openTag('em');
      echo 'resources/views/generator/output_overwrite';
     echo H::closeTag('em');
     echo '.';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('strong');
      echo 'Step 1:';
     echo H::closeTag('strong');
     echo ' ';
     echo H::openTag('br');
     echo 'Download ';
     echo H::openTag('code');
      echo $curlPemUrl;
     echo H::closeTag('code');
     echo ' into active ';
     echo H::openTag('code');
      echo 'c:\\wamp64\\bin\\php\\php8.1.12';
     echo H::closeTag('code');
     echo ' folder';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('strong');
      echo 'Step 2:';
     echo H::closeTag('strong');
     echo ' ';
     echo H::openTag('br');
     echo 'Select your project that you created under ';
     echo H::openTag('code');
      echo $googleConsoleUrl;
      echo $googleConsolePath;
     echo H::closeTag('code');
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('strong');
      echo 'Step 3:';
     echo H::closeTag('strong');
     echo ' ';
     echo H::openTag('br');
     echo 'Click on Actions icon and select Manage Keys.';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('strong');
      echo 'Step 4:';
     echo H::closeTag('strong');
     echo ' ';
     echo H::openTag('br');
     echo 'Add Key.';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('strong');
      echo 'Step 5:';
     echo H::closeTag('strong');
     echo ' ';
     echo H::openTag('br');
     echo 'Choose the Json File option and Download the file to ';
     echo H::openTag('code');
      echo 'src/Invoice/Google_translate_unique_folder';
     echo H::closeTag('code');
     echo '.';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('strong');
      echo 'Step 6:';
     echo H::closeTag('strong');
     echo ' ';
     echo H::openTag('br');
     echo 'You will have to enable the Cloud Translation API ';
     echo 'and provide your billing details. You will be charged ';
     echo '0 currency.';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('strong');
      echo 'Step 7:';
     echo H::closeTag('strong');
     echo ' ';
     echo H::openTag('br');
     echo 'Adjust the php.ini [apache_module] by means of the ';
     echo 'wampserver icon or by clicking on the symlink in the ';
     echo 'directory.';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('strong');
      echo 'Step 8:';
     echo H::closeTag('strong');
     echo ' ';
     echo H::openTag('br');
     echo 'The symlink file points to ';
     echo H::openTag('code');
      echo 'C:\\wamp64\\bin\\php\\php8.3.16\\phpForApache.ini';
     echo H::closeTag('code');
     echo ' Adjust this manually at line 1947 [curl] with eg. ';
     echo H::openTag('code');
      echo '"c:/wamp64/bin/php/php8.3.16/cacert.pem"';
     echo H::closeTag('code');
     echo ' Note the forward slashes.';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('strong');
      echo 'Step 9:';
     echo H::closeTag('strong');
     echo ' ';
     echo H::openTag('br');
     echo 'Reboot your server.';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('strong');
      echo 'Step 10:';
     echo H::closeTag('strong');
     echo ' ';
     echo H::openTag('br');
     echo 'Copy the contents from outputoverwrite folder into ';
     echo 'your resources/messages/{locale}';
    echo H::closeTag('p');
    echo H::openTag('p');
     echo H::openTag('strong');
      echo 'Step 11:';
     echo H::closeTag('strong');
     echo ' ';
     echo H::openTag('br');
     echo 'Adjust the ';
     echo H::openTag('code');
      echo 'src/ViewInjection/LayoutViewInjection';
     echo H::closeTag('code');
    echo H::closeTag('p');
    echo H::openTag('p');
     echo '&nbsp;';
    echo H::closeTag('p');
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
