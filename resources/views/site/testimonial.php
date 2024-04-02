<?php
    declare(strict_types=1);
    
    use Yiisoft\Html\Html;
    use Yiisoft\Html\Tag\H4;
    use Yiisoft\Html\Tag\H5;
    
   /**
    * @link  Acknowledgement to bootstrapbrain free templates wavelite for the bootstrap 5 code classes and structure 
    * @see This wavelite template has been adjusted to accomodate 
    * @see ..\invoice\src\ViewInjection\CommonViewInjection.php
    * @see ..\invoice\resources\messages\en\app.php
    */
?>

<?= Html::openTag('section', ['id' => 'Testimonial', 'class' => 'py-5 py-xl-8']); ?>
    <?= Html::openTag('div', ['class' => 'container mb-5 mb-md-6 mb-xl-10']); ?>
        <?= Html::openTag('div', ['class' => 'row justify-content-md-center']); ?> 
            <?= Html::openTag('div', ['class' => 'col-12 col-md-10 col-lg-9 col-xl-8 col-xxl-7 text-center']); ?>
                <?= Html::openTag('h2', ['class' => 'display-3 fw-bolder mb-4']);?>
                    <?= $testimonial['we']; ?>
                <?= Html::closeTag('h2'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>                    
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'container overflow-hidden']); ?>
        <?= Html::openTag('div', ['class' => 'row gy-4 gy-md-0 gx-xxl-5']); ?>
            <?= Html::openTag('div', ['class' => 'col-12 col-md-4']); ?>
                <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm']); ?>
                    <?= Html::openTag('div', ['class' => 'card-body p-4 p-xxl-5']); ?>
                        <?= Html::openTag('figure', ['class' => 'm-0 p-0']); ?>
                            <?= Html::tag('img', '', [
                                'class' => 'img-fluid rounded rounded-circle mb-4 border border-5', 
                                'loading' => 'lazy', 
                                'src' => '/img/soletrader/testimonial/testimonial-img-1.jpg', 
                                'alt' => 'Mama Nana']); ?>    
                            <?= Html::openTag('figcaption'); ?>
                                <?= Html::openTag('div', [
                                    'class' => 'text-warning mb-3']); ?>
                                <?= Html::closeTag('div'); ?>
                                    <?= Html::openTag('blockquote', ['class' => 'mb-3']); ?>
                                        <?= Html::openTag('blockquote', ['class' => 'mb-4']); ?>
                                            <?= $testimonial['worker1']; ?>
                                        <?= Html::closeTag('blockquote'); ?>
                                    <?= Html::openTag('blockquote'); ?>
                                <?= H4::tag()
                                    ->addClass('mb-2')
                                    ->content('')
                                    ->render(); ?>
                                <?= H5::tag()
                                    ->addClass('fs-6 text-secondary mb-0')
                                    ->content('Worker')
                                    ->render(); ?>
                            <?= Html::closeTag('figcaption'); ?>
                        <?= Html::closeTag('figure'); ?>
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>    
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col-12 col-md-4']); ?>
                <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm']); ?>
                    <?= Html::openTag('div', ['class' => 'card-body p-4 p-xxl-5']); ?>
                        <?= Html::openTag('figure'); ?>
                            <?= Html::tag('img', '', [
                                'class' => 'img-fluid rounded rounded-circle mb-4 border border-5', 
                                'loading' => 'lazy', 
                                'src' => '/img/soletrader/testimonial/testimonial-img-2.jpg', 
                                'alt' => 'Papa Quana']); ?>    
                            <?= Html::openTag('figcaption'); ?>
                                <?= Html::openTag('blockquote', ['class' => 'mb-4']); ?>This is our testimonial<?= Html::closeTag('blockquote'); ?>
                                <?= H4::tag()
                                    ->addClass('mb-2')
                                    ->content('')
                                    ->render(); ?>
                                <?= H5::tag()
                                    ->addClass('fs-6 text-secondary mb-0')
                                    ->content('Worker')
                                    ->render(); ?>
                            <?= Html::closeTag('figcaption'); ?>
                        <?= Html::closeTag('figure'); ?>
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col-12 col-md-4']); ?>
                <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm']); ?>
                    <?= Html::openTag('div', ['class' => 'card-body p-4 p-xxl-5']); ?>
                        <?= Html::openTag('figure'); ?>
                            <?= Html::tag('img', '', [
                                'class' => 'img-fluid rounded rounded-circle mb-4 border border-5', 
                                'loading' => 'lazy', 
                                'src' => '/img/soletrader/testimonial/testimonial-img-3.jpg', 
                                'alt' => 'Rara Sasa']); ?>    
                            <?= Html::openTag('figcaption'); ?>
                                <?= Html::openTag('blockquote', ['class' => 'mb-4']); ?>This is our testimonial<?= Html::closeTag('blockquote'); ?>
                                <?= H4::tag()
                                    ->addClass('mb-2')
                                    ->content('')
                                    ->render(); ?>
                                <?= H5::tag()
                                    ->addClass('fs-6 text-secondary mb-0')
                                    ->content('Worker')
                                    ->render(); ?>
                            <?= Html::closeTag('figcaption'); ?>
                        <?= Html::closeTag('figure'); ?>
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>    
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>    
    <?= Html::closeTag('div'); ?>                        
<?= Html::closeTag('section'); ?>


