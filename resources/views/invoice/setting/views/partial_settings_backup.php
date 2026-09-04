<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

$row       = ['class' => 'row'];
$colMd8    = ['class' => 'col-12 col-md-8 offset-md-2'];
$panel     = ['class' => 'card'];
$panelHead = ['class' => 'card-header'];
$panelBody = ['class' => 'card-body'];

echo H::openTag('div', $row); //1
echo H::openTag('div', $colMd8); //2
echo H::openTag('div', $panel); //3
echo H::openTag('div', $panelHead); //4
echo H::encode($translator->translate('backup.database'));
echo H::closeTag('div'); //4
echo H::openTag('div', $panelBody); //4

echo H::tag('p', H::encode($translator->translate('backup.database.description')), ['class' => 'mb-3']);

echo H::a(
    H::encode($translator->translate('backup.database.download')),
    $urlGenerator->generate('setting/downloadBackup'),
    ['class' => 'btn btn-primary'],
);

echo H::closeTag('div'); //4
echo H::closeTag('div'); //3
echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
