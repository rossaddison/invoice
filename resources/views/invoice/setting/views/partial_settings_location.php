<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\H6;
use Yiisoft\Html\Tag\Option;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
*/

$formGroup = ['class' => 'mb-3'];

echo H::openTag('div', ['class' => 'row']); //1
echo H::openTag('div', ['class' => 'col-12 col-md-8 offset-md-2']); //2

echo H::openTag('div', ['class' => 'card mb-3']); //3 tester card
echo H::openTag('div', ['class' => 'card-header']); //4
echo new H6()->content($translator->translate('location.tester'));
echo H::closeTag('div'); //4
echo H::openTag('div', ['class' => 'card-body']); //4
echo H::tag('p', H::encode($translator->translate('location.tester.description')));
echo H::tag('button', H::encode($translator->translate('location.test.button')), [
    'type' => 'button', 'id' => 'btn-geolocation-test',
    'class' => 'btn btn-outline-primary',
]);
echo H::openTag('div', ['id' => 'geolocation-result', 'class' => 'mt-3']); //5
echo H::closeTag('div'); //5
echo H::closeTag('div'); //4
echo H::closeTag('div'); //3

echo H::openTag('div', ['class' => 'card']); //3 setting card
echo H::openTag('div', ['class' => 'card-header']); //4
echo new H6()->content($translator->translate('capture.gps.on.send'));
echo H::closeTag('div'); //4
echo H::openTag('div', ['class' => 'card-body']); //4
echo H::tag(
    'p',
    H::encode($translator->translate('capture.gps.on.send.description')),
    ['class' => 'text-muted']
);
echo H::openTag('div', $formGroup); //5
$cgos = 'settings[capture_gps_on_send]';
echo H::openTag('label', ['for' => $cgos]);
echo $translator->translate('capture.gps.on.send');
echo $s->infoIcon('capture_gps_on_send');
echo H::closeTag('label');
$body[$cgos] = $s->getSetting('capture_gps_on_send');
echo H::openTag('select', [
   'name' => $cgos, 'id' => $cgos, 'class' => 'form-select',
]);
echo new Option()->value('0')->content($translator->translate('no'));
echo new Option()->value('1')
    ->selected(($body[$cgos] ?? '0') == '1')
    ->content($translator->translate('yes'));
echo H::closeTag('select');
echo H::closeTag('div'); //5
echo H::closeTag('div'); //4
echo H::closeTag('div'); //3

echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
