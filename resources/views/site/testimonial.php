<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\H4;
use Yiisoft\Html\Tag\H5;

/*
 * @link  Acknowledgement to bootstrapbrain free templates wavelite for the bootstrap 5 code classes and structure
 * Related logic: see This wavelite template has been adjusted to accomodate
 * Related logic: see ..\invoice\src\ViewInjection\CommonViewInjection.php
 * Related logic: see ..\invoice\resources\messages\en\app.php
 * @var array $testimonial
 */

?>

<?php echo Html::openTag('section', ['id' => 'Testimonial', 'class' => 'py-5 py-xl-8']); ?>
    <?php echo Html::openTag('div', ['class' => 'container mb-5 mb-md-6 mb-xl-10']); ?>
        <?php echo Html::openTag('div', ['class' => 'row justify-content-md-center']); ?> 
            <?php echo Html::openTag('div', ['class' => 'col-12 col-md-10 col-lg-9 col-xl-8 col-xxl-7 text-center']); ?>
                <?php echo Html::openTag('h2', ['class' => 'display-3 fw-bolder mb-4']); ?>
                    <?php echo (string) $testimonial['we']; ?>
                <?php echo Html::closeTag('h2'); ?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>                    
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::openTag('div', ['class' => 'container overflow-hidden']); ?>
        <?php echo Html::openTag('div', ['class' => 'row gy-4 gy-md-0 gx-xxl-5']); ?>
            <?php echo Html::openTag('div', ['class' => 'col-12 col-md-4']); ?>
                <?php echo Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm']); ?>
                    <?php echo Html::openTag('div', ['class' => 'card-body p-4 p-xxl-5']); ?>
                        <?php echo Html::openTag('figure', ['class' => 'm-0 p-0']); ?>
                            <?php echo Html::tag('img', '', [
                                'class'   => 'img-fluid rounded rounded-circle mb-4 border border-5',
                                'loading' => 'lazy',
                                'src'     => '/img/soletrader/testimonial/testimonial-img-1.jpg',
                                'alt'     => 'Mama Nana']); ?>    
                            <?php echo Html::openTag('figcaption'); ?>
                                <?php echo Html::openTag('div', [
                                    'class' => 'text-warning mb-3']); ?>
                                <?php echo Html::closeTag('div'); ?>
                                    <?php echo Html::openTag('blockquote', ['class' => 'mb-3']); ?>
                                        <?php echo Html::openTag('blockquote', ['class' => 'mb-4']); ?>
                                            <?php echo (string) $testimonial['worker1']; ?>
                                        <?php echo Html::closeTag('blockquote'); ?>
                                    <?php echo Html::openTag('blockquote'); ?>
                                <?php echo H4::tag()
                                ->addClass('mb-2')
                                ->content('')
                                ->render(); ?>
                                <?php echo H5::tag()
                                ->addClass('fs-6 text-secondary mb-0')
                                ->content('👷')
                                ->render(); ?>
                            <?php echo Html::closeTag('figcaption'); ?>
                        <?php echo Html::closeTag('figure'); ?>
                    <?php echo Html::closeTag('div'); ?>
                <?php echo Html::closeTag('div'); ?>    
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'col-12 col-md-4']); ?>
                <?php echo Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm']); ?>
                    <?php echo Html::openTag('div', ['class' => 'card-body p-4 p-xxl-5']); ?>
                        <?php echo Html::openTag('figure'); ?>
                            <?php echo Html::tag('img', '', [
                                'class'   => 'img-fluid rounded rounded-circle mb-4 border border-5',
                                'loading' => 'lazy',
                                'src'     => '/img/soletrader/testimonial/testimonial-img-2.jpg',
                                'alt'     => 'Papa Quana']); ?>    
                            <?php echo Html::openTag('figcaption'); ?>
                                <?php echo Html::openTag('blockquote', ['class' => 'mb-4']); ?>
                                    <?php echo (string) $testimonial['worker2']; ?>
                                <?php echo Html::closeTag('blockquote'); ?>
                                <?php echo H4::tag()
                                ->addClass('mb-2')
                                ->content('️')
                                ->render(); ?>
                                <?php echo H5::tag()
                                ->addClass('fs-6 text-secondary mb-0')
                                ->content('👷‍♀')
                                ->render(); ?>
                            <?php echo Html::closeTag('figcaption'); ?>
                        <?php echo Html::closeTag('figure'); ?>
                    <?php echo Html::closeTag('div'); ?>
                <?php echo Html::closeTag('div'); ?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'col-12 col-md-4']); ?>
                <?php echo Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm']); ?>
                    <?php echo Html::openTag('div', ['class' => 'card-body p-4 p-xxl-5']); ?>
                        <?php echo Html::openTag('figure'); ?>
                            <?php echo Html::tag('img', '', [
                                'class'   => 'img-fluid rounded rounded-circle mb-4 border border-5',
                                'loading' => 'lazy',
                                'src'     => '/img/soletrader/testimonial/testimonial-img-3.jpg',
                                'alt'     => 'Rara Sasa']); ?>    
                            <?php echo Html::openTag('figcaption'); ?>
                                <?php echo Html::openTag('blockquote', ['class' => 'mb-4']); ?>
                                    <?php echo (string) $testimonial['worker3']; ?>
                                <?php echo Html::closeTag('blockquote'); ?>
                                <?php echo H4::tag()
                                ->addClass('mb-2')
                                ->content('')
                                ->render(); ?>
                                <?php echo H5::tag()
                                ->addClass('fs-6 text-secondary mb-0')
                                ->content('👨‍🏭')
                                ->render(); ?>
                            <?php echo Html::closeTag('figcaption'); ?>
                        <?php echo Html::closeTag('figure'); ?>
                    <?php echo Html::closeTag('div'); ?>
                <?php echo Html::closeTag('div'); ?>    
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>    
    <?php echo Html::closeTag('div'); ?>                        
<?php echo Html::closeTag('section'); ?>


