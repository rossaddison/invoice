<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\VarDumper\VarDumper;

/**
 * @var App\Invoice\Helpers\GenerateCodeFileHelper $generated
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var bool $canEdit
 * @var bool $highlight
 * @var string $alert
 * @var string $id
 */

echo $alert;

?>
<?= Html::tag('h1')
    ->content(Html::encode($translator->translate('generator')));
?>
<?= Html::openTag('div'); ?>    
    <?php
    if ($canEdit) {
        $highlight = PHP_SAPI !== 'cli';
        VarDumper::dump($generated, 40, $highlight);
        echo $highlight ? '<br>' : PHP_EOL;
    }
?>
<?= Html::closeTag('div'); ?>