<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Img;

/**
 * @link  Acknowledgement to bootstrapbrain free templates wavelite for the bootstrap 5 code classes and structure
 */
?>
<?= Html::openTag('section', ['id' => 'Accreditations', 'class' => 'py-5 py-xl-8']); ?>
    <?= Html::openTag('div', ['class' => 'container mb-5 mb-md-6 mb-xl-10']); ?>
        <?= Html::openTag('div', ['class' => 'row justify-content-md-center']); ?> 
            <?= Html::openTag('div', ['class' => 'col-12 col-md-10 col-lg-9 col-xl-8 col-xxl-7 text-center']); ?>
                <?= Html::openTag('h2', ['class' => 'display-3 fw-bolder mb-4']);?>
                    <?= ''; ?>
                <?= Html::closeTag('h2'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>                    
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::openTag('div', ['class' => 'container overflow-hidden']); ?>
    <?= Html::openTag('div', ['class' => 'row gy-4 gy-lg-0 gx-xxl-5']); ?> 
        <?= Html::openTag('div', ['class' => 'col-12 col-md-6 col-lg-3']); ?>
            <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm overflow-hidden']); ?>
                <?= Html::openTag('div', ['class' => 'card-body p-0']); ?>
                    <?= Html::openTag('figure', ['class' => 'm-0 p-0']); ?>
                        <?= Img::tag()
                            ->addClass('img-fluid rounded')
                            ->src('/img/accreditations/1.jpg')
                            ->render();
?>
                    <?= Html::closeTag('figure'); ?>
                <?= Html::closeTag('div'); ?>                    
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col-12 col-md-6 col-lg-3']); ?>
            <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm overflow-hidden']); ?>
                <?= Html::openTag('div', ['class' => 'card-body p-0']); ?>
                    <?= Html::openTag('figure', ['class' => 'm-0 p-0']); ?>
                        <?= Img::tag()
    ->addClass('img-fluid rounded')
    ->src('/img/accreditations/2.jpg')
    ->render();
?>
                    <?= Html::closeTag('figure'); ?>        
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col-12 col-md-6 col-lg-3']); ?>
            <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm overflow-hidden']); ?>
                <?= Html::openTag('div', ['class' => 'card-body p-0']); ?>
                    <?= Html::openTag('figure', ['class' => 'm-0 p-0']); ?>
                        <?= Img::tag()
    ->addClass('img-fluid rounded')
    ->src('/img/accreditations/3.jpg')
    ->render();
?>
                    <?= Html::closeTag('figure'); ?>        
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col-12 col-md-6 col-lg-3']); ?>
            <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm overflow-hidden']); ?>
                <?= Html::openTag('div', ['class' => 'card-body p-0']); ?>
                    <?= Html::openTag('figure', ['class' => 'm-0 p-0']); ?>
                        <?= Img::tag()
    ->addClass('img-fluid rounded')
    ->src('/img/accreditations/4.jpg')
    ->render();
?>
                    <?= Html::closeTag('figure'); ?>        
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('section'); ?>

        

