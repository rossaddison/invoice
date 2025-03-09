<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\H4;
use Yiisoft\Html\Tag\P;

/**
 * @link  Acknowledgement to bootstrapbrain free templates wavelite for the bootstrap 5 code classes and structure
 * @see This wavelite template has been adjusted to accomodate
 * @see ..\invoice\src\ViewInjection\CommonViewInjection.php
 * @see ..\invoice\resources\messages\en\app.php
 * @var array $team
 */
?>

<?= Html::openTag('section', ['id' => 'Team', 'class' => 'py-5 py-xl-8']); ?>
    <?= Html::openTag('div', ['class' => 'container mb-5 mb-md-6 mb-xl-10']); ?>
        <?= Html::openTag('div', ['class' => 'row justify-content-md-center']); ?> 
            <?= Html::openTag('div', ['class' => 'col-12 col-md-10 col-lg-9 col-xl-8 col-xxl-7 text-center']); ?>
                <?= Html::openTag('h2', ['class' => 'display-3 fw-bolder mb-4']);?>
                    <?=
                       (string)$team['we']; ?>
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
                        <?= Html::tag('img', '', [
                            'class' => 'img-fluid rounded',
                            'loading' => 'lazy',
                            'src' => '/img/soletrader/team/team-img-1.jpg',
                            'alt' => '/img/soletrader/team/team-img-1.jpg']); ?>    
                        <?= Html::openTag('figcaption', ['class' => 'm-0 p-4']); ?>     
                            <?= H4::tag()
                                ->addClass('mb-1')
                                ->content('Aye Bee')
                                ->render(); ?>
                            <?= P::tag()
                                ->addClass('text-secondary mb-0')
                                ->content((string)$team['coordinator'])
                                ->render(); ?>     
                        <?= Html::closeTag('figcaption'); ?>
                    <?= Html::closeTag('figure'); ?>
                <?= Html::closeTag('div'); ?>                    
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col-12 col-md-6 col-lg-3']); ?>
            <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm overflow-hidden']); ?>
                <?= Html::openTag('div', ['class' => 'card-body p-0']); ?>
                    <?= Html::openTag('figure', ['class' => 'm-0 p-0']); ?>
                        <?= Html::tag('img', '', ['class' => 'img-fluid rounded', 'loading' => 'lazy', 'src' => '/img/soletrader/team/team-img-1.jpg', 'alt' => '/img/soletrader/team/team-img-1.jpg']); ?>    
                        <?= Html::openTag('figcaption', ['class' => 'm-0 p-4']); ?>     
                            <?= H4::tag()
                                ->addClass('mb-1')
                                ->content('Cee Dee')
                                ->render(); ?>
                            <?= P::tag()
                                ->addClass('text-secondary mb-0')
                                ->content((string)$team['assistant'])
                                ->render(); ?>
                        <?= Html::closeTag('figcaption'); ?>
                    <?= Html::closeTag('figure'); ?>        
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col-12 col-md-6 col-lg-3']); ?>
            <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm overflow-hidden']); ?>
                <?= Html::openTag('div', ['class' => 'card-body p-0']); ?>
                    <?= Html::openTag('figure', ['class' => 'm-0 p-0']); ?>
                        <?= Html::tag('img', '', ['class' => 'img-fluid rounded', 'loading' => 'lazy', 'src' => '/img/soletrader/team/team-img-1.jpg', 'alt' => '/img/soletrader/team/team-img-1.jpg']); ?>    
                        <?= Html::openTag('figcaption', ['class' => 'm-0 p-4']); ?>     
                            <?= H4::tag()
                                ->addClass('mb-1')
                                ->content('Eee Eff')
                                ->render(); ?>
                            <?= P::tag()
                                ->addClass('text-secondary mb-0')
                                ->content((string)$team['assistant'])
                                ->render(); ?>
                        <?= Html::closeTag('figcaption'); ?>
                    <?= Html::closeTag('figure'); ?>        
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col-12 col-md-6 col-lg-3']); ?>
            <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm overflow-hidden']); ?>
                <?= Html::openTag('div', ['class' => 'card-body p-0']); ?>
                    <?= Html::openTag('figure', ['class' => 'm-0 p-0']); ?>
                        <?= Html::tag('img', '', ['class' => 'img-fluid rounded', 'loading' => 'lazy', 'src' => '/img/soletrader/team/team-img-1.jpg', 'alt' => '/img/soletrader/team/team-img-1.jpg']); ?>    
                        <?= Html::openTag('figcaption', ['class' => 'm-0 p-4']); ?>     
                            <?= H4::tag()
                                ->addClass('mb-1')
                                ->content('Jee Aich')
                                ->render(); ?>
                            <?= P::tag()
                                ->addClass('text-secondary mb-0')
                                ->content((string)$team['assistant'])
                                ->render(); ?>
                        <?= Html::closeTag('figcaption'); ?>
                    <?= Html::closeTag('figure'); ?>        
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('section'); ?>


