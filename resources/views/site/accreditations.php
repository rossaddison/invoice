<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Img;

/*
 * @link  Acknowledgement to bootstrapbrain free templates wavelite for the bootstrap 5 code classes and structure
 */
?>
<?php echo Html::openTag('section', ['id' => 'Accreditations', 'class' => 'py-5 py-xl-8']); ?>
    <?php echo Html::openTag('div', ['class' => 'container mb-5 mb-md-6 mb-xl-10']); ?>
        <?php echo Html::openTag('div', ['class' => 'row justify-content-md-center']); ?> 
            <?php echo Html::openTag('div', ['class' => 'col-12 col-md-10 col-lg-9 col-xl-8 col-xxl-7 text-center']); ?>
                <?php echo Html::openTag('h2', ['class' => 'display-3 fw-bolder mb-4']); ?>
                    <?php echo ''; ?>
                <?php echo Html::closeTag('h2'); ?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>                    
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::openTag('div', ['class' => 'container overflow-hidden']); ?>
    <?php echo Html::openTag('div', ['class' => 'row gy-4 gy-lg-0 gx-xxl-5']); ?> 
        <?php echo Html::openTag('div', ['class' => 'col-12 col-md-6 col-lg-3']); ?>
            <?php echo Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm overflow-hidden']); ?>
                <?php echo Html::openTag('div', ['class' => 'card-body p-0']); ?>
                    <?php echo Html::openTag('figure', ['class' => 'm-0 p-0']); ?>
                        <?php echo Img::tag()
            ->addClass('img-fluid rounded')
            ->src('/img/accreditations/1.jpg')
            ->render();
?>
                    <?php echo Html::closeTag('figure'); ?>
                <?php echo Html::closeTag('div'); ?>                    
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'col-12 col-md-6 col-lg-3']); ?>
            <?php echo Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm overflow-hidden']); ?>
                <?php echo Html::openTag('div', ['class' => 'card-body p-0']); ?>
                    <?php echo Html::openTag('figure', ['class' => 'm-0 p-0']); ?>
                        <?php echo Img::tag()
                ->addClass('img-fluid rounded')
                ->src('/img/accreditations/2.jpg')
                ->render();
?>
                    <?php echo Html::closeTag('figure'); ?>        
                <?php echo Html::closeTag('div'); ?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'col-12 col-md-6 col-lg-3']); ?>
            <?php echo Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm overflow-hidden']); ?>
                <?php echo Html::openTag('div', ['class' => 'card-body p-0']); ?>
                    <?php echo Html::openTag('figure', ['class' => 'm-0 p-0']); ?>
                        <?php echo Img::tag()
                    ->addClass('img-fluid rounded')
                    ->src('/img/accreditations/3.jpg')
                    ->render();
?>
                    <?php echo Html::closeTag('figure'); ?>        
                <?php echo Html::closeTag('div'); ?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'col-12 col-md-6 col-lg-3']); ?>
            <?php echo Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm overflow-hidden']); ?>
                <?php echo Html::openTag('div', ['class' => 'card-body p-0']); ?>
                    <?php echo Html::openTag('figure', ['class' => 'm-0 p-0']); ?>
                        <?php echo Img::tag()
                    ->addClass('img-fluid rounded')
                    ->src('/img/accreditations/4.jpg')
                    ->render();
?>
                    <?php echo Html::closeTag('figure'); ?>        
                <?php echo Html::closeTag('div'); ?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('section'); ?>

        

