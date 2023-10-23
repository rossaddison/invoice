<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\VarDumper\VarDumper;
/**
 * @var \App\Invoice\Entity\Generator $generators
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool $canEdit
 * @var string $id
 */

echo $alert;

?>
    <h1><?= Html::encode($translator->translate('invoice.generator')); ?></h1>
    <div>        
        <?php
        if ($canEdit) {
            $highlight = PHP_SAPI !== 'cli';
            VarDumper::dump($generated, 40, $highlight);
            echo $highlight ? '<br>' : PHP_EOL;          
        }
        ?>
    </div>
<?php
echo Html::closeTag('div');