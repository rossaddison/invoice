<?php
    declare(strict_types=1);
    
    use Yiisoft\Html\Html;
    use Yiisoft\Html\Tag\A;
    use Yiisoft\Html\Tag\H2;
    use Yiisoft\Html\Tag\H4;
    use Yiisoft\Html\Tag\P;
    
   /**
    * @link  Acknowledgement to bootstrapbrain free templates wavelite for the bootstrap 5 code classes and structure 
    * @see This wavelite template has been adjusted to accomodate 
    * @see ..\invoice\src\ViewInjection\CommonViewInjection.php
    * @see ..\invoice\resources\messages\en\app.php
    */
?>

<?= Html::openTag('section', ['id' => 'Pricing', 'class' => 'py-5 py-xl-8']); ?>
    <?= Html::openTag('div', ['class' => 'container']); ?>
        <?= Html::openTag('div', ['class' => 'row row gy-5 gy-lg-0 align-items-center']); ?>
            <?= Html::openTag('div', ['class' => 'col-12 col-lg-4']); ?>
                <?= H2::tag()
                    ->addClass('display-3 fw-bolder mb-4')
                    ->content($pricing['pricing'])
                    ->render(); ?>
                <?= P::tag()
                        ->addClass('fs-4 mb-4 mb-xl-5')
                        ->content($pricing['explore'])
                        ->render(); ?>    
                <?= A::tag()
                        ->addClass('btn bsb-btn-2xl btn-primary rounded-pill')        
                        ->href('#!')
                        ->content($pricing['plans'])        
                        ->render(); ?>
            <?= Html::closeTag('div'); ?>  
            <?= Html::openTag('div', ['class' => 'col-12 col-lg-8']); ?>
                <?= Html::openTag('div', ['class' => 'row justify-content-xl-end']); ?>
                    <?= Html::openTag('div', ['class' => 'col-12 col-xl-11']); ?>
                        <?= Html::openTag('div', ['class' => 'row gy-4 gy-md-0 gx-xxl-5']); ?>
                            <?= Html::openTag('div', ['class' => 'col-12 col-md-6']); ?>
                                <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm']); ?>
                                    <?= Html::openTag('div', ['class' => 'card-body p-4 p-xxl-5']); ?>
                                        <?= H2::tag()
                                            ->addClass('h4 mb-2')
                                            ->content($pricing['starter'])
                                            ->render(); ?>
                                        <?= H4::tag()
                                            ->addClass('display-3 fw-bold text-primary mb-0')
                                            ->content($s->format_currency(50))
                                            ->render(); ?>
                                        <?= P::tag()
                                            ->addClass('text-secondary mb-4')
                                            ->content($pricing['currencyPerMonth'])
                                            ->render(); ?>
                                        <?= Html::openTag('ul', ['class' => 'list-group list-group-flush mb-4']); ?>
                                            <?= Html::openTag('li', ['class' => 'list-group-item']); ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
                                                  <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
                                                </svg>
                                                <span><strong>5</strong><?= str_repeat(' ', 1).$pricing['basic']; ?></span>
                                            <?= Html::closeTag('li'); ?>    
                                            <?= Html::openTag('li', ['class' => 'list-group-item']); ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
                                                  <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
                                                </svg>
                                                <span><strong>100,000</strong><?= str_repeat(' ', 1).$pricing['visits']; ?></span>
                                            <?= Html::closeTag('li'); ?>
                                        <?= Html::closeTag('ul'); ?>
                                        <?= A::tag()
                                            ->addClass('btn bsb-btn-2xl btn-accent rounded-pill')        
                                            ->href('#!')
                                            ->content($pricing['choosePlan'])        
                                            ->render(); ?>                                
                                    <?= Html::closeTag('div'); ?>            
                                <?= Html::closeTag('div'); ?>
                            <?= Html::closeTag('div'); ?>
                            <?= Html::openTag('div', ['class' => 'col-12 col-md-6']); ?>                            
                                <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-lg pt-md-4 pb-md-4']); ?>                            
                                    <?= Html::openTag('div', ['class' => 'card-body p-4 p-xxl-5']); ?>                            
                                        <?= H2::tag()
                                            ->addClass('h4 mb-2')
                                            ->content($pricing['pro']); ?>
                                        <?= H4::tag()
                                            ->addClass('display-3 fw-bold text-primary mb-0')
                                            ->content($s->format_currency(50))
                                            ->render(); ?>
                                        <?= P::tag()
                                            ->addClass('text-secondary')
                                            ->content($pricing['currencyPerMonth'])
                                            ->render(); ?>
                                        <?= Html::openTag('ul', ['class' => 'list-group list-group-flush mb-4']); ?>
                                            <?= Html::openTag('li', ['class' => 'list-group-item']); ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
                                                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
                                                </svg>
                                                <span><strong>20</strong><?= str_repeat(' ', 1).$pricing['special']; ?></span>
                                            <?= Html::closeTag('li'); ?>    
                                            <?= Html::openTag('li', ['class' => 'list-group-item']); ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
                                                  <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
                                                </svg>
                                                <span><strong>400,000</strong><?= str_repeat(' ', 1).$pricing['visits']; ?></span>
                                            <?= Html::closeTag('li'); ?>
                                        <?= Html::closeTag('ul'); ?>
                                        <?= A::tag()
                                            ->addClass('btn btn-accent rounded-pill')        
                                            ->href('#!')
                                            ->content($pricing['choosePlan'])        
                                            ->render(); ?>        
                                    <?= Html::closeTag('div'); ?>            
                                <?= Html::closeTag('div'); ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('div'); ?>                        
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('section'); ?> 

