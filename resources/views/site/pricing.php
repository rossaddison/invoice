<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\H2;
use Yiisoft\Html\Tag\H4;
use Yiisoft\Html\Tag\P;

/*
 * @link  Acknowledgement to bootstrapbrain free templates wavelite for the bootstrap 5 code classes and structure
 * @see This wavelite template has been adjusted to accomodate
 * @see ..\invoice\src\ViewInjection\CommonViewInjection.php
 * @see ..\invoice\resources\messages\en\app.php
 * @var App\Invoice\Setting\SettingRepository $s
 * @var array $pricing
 */
?>

<?php echo Html::openTag('section', ['id' => 'Pricing', 'class' => 'py-5 py-xl-8']); ?>
    <?php echo Html::openTag('div', ['class' => 'container']); ?>
        <?php echo Html::openTag('div', ['class' => 'row row gy-5 gy-lg-0 align-items-center']); ?>
            <?php echo Html::openTag('div', ['class' => 'col-12 col-lg-4']); ?>
                <?php echo H2::tag()
    ->addClass('display-3 fw-bolder mb-4')
    ->content((string) $pricing['pricing'])
    ->render(); ?>
                <?php echo P::tag()
    ->addClass('fs-4 mb-4 mb-xl-5')
    ->content((string) $pricing['explore'])
    ->render(); ?>    
                <?php echo A::tag()
                    ->addClass('btn bsb-btn-2xl btn-primary rounded-pill')
                    ->href('#!')
                    ->content((string) $pricing['plans'])
                    ->render(); ?>
            <?php echo Html::closeTag('div'); ?>  
            <?php echo Html::openTag('div', ['class' => 'col-12 col-lg-8']); ?>
                <?php echo Html::openTag('div', ['class' => 'row justify-content-xl-end']); ?>
                    <?php echo Html::openTag('div', ['class' => 'col-12 col-xl-11']); ?>
                        <?php echo Html::openTag('div', ['class' => 'row gy-4 gy-md-0 gx-xxl-5']); ?>
                            <?php echo Html::openTag('div', ['class' => 'col-12 col-md-6']); ?>
                                <?php echo Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm']); ?>
                                    <?php echo Html::openTag('div', ['class' => 'card-body p-4 p-xxl-5']); ?>
                                        <?php echo H2::tag()
                ->addClass('h4 mb-2')
                ->content((string) $pricing['starter'])
                ->render(); ?>
                                        <?php echo H4::tag()
                ->addClass('display-3 fw-bold text-primary mb-0')
                ->content($s->format_currency(50))
                ->render(); ?>
                                        <?php echo P::tag()
                ->addClass('text-secondary mb-4')
                ->content((string) $pricing['currencyPerMonth'])
                ->render(); ?>
                                        <?php echo Html::openTag('ul', ['class' => 'list-group list-group-flush mb-4']); ?>
                                            <?php echo Html::openTag('li', ['class' => 'list-group-item']); ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
                                                  <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
                                                </svg>
                                                <span><strong>5</strong><?php echo str_repeat(' ', 1).(string) $pricing['basic']; ?></span>
                                            <?php echo Html::closeTag('li'); ?>    
                                            <?php echo Html::openTag('li', ['class' => 'list-group-item']); ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
                                                  <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
                                                </svg>
                                                <span><strong>100,000</strong><?php echo str_repeat(' ', 1).(string) $pricing['visits']; ?></span>
                                            <?php echo Html::closeTag('li'); ?>
                                        <?php echo Html::closeTag('ul'); ?>
                                        <?php echo A::tag()
                                                ->addClass('btn bsb-btn-2xl btn-accent rounded-pill')
                                                ->href('#!')
                                                ->content((string) $pricing['choosePlan'])
                                                ->render(); ?>                                
                                    <?php echo Html::closeTag('div'); ?>            
                                <?php echo Html::closeTag('div'); ?>
                            <?php echo Html::closeTag('div'); ?>
                            <?php echo Html::openTag('div', ['class' => 'col-12 col-md-6']); ?>                            
                                <?php echo Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-lg pt-md-4 pb-md-4']); ?>                            
                                    <?php echo Html::openTag('div', ['class' => 'card-body p-4 p-xxl-5']); ?>                            
                                        <?php echo H2::tag()
                                            ->addClass('h4 mb-2')
                                            ->content((string) $pricing['pro']); ?>
                                        <?php echo H4::tag()
                                            ->addClass('display-3 fw-bold text-primary mb-0')
                                            ->content($s->format_currency(50))
                                            ->render(); ?>
                                        <?php echo P::tag()
                                            ->addClass('text-secondary')
                                            ->content((string) $pricing['currencyPerMonth'])
                                            ->render(); ?>
                                        <?php echo Html::openTag('ul', ['class' => 'list-group list-group-flush mb-4']); ?>
                                            <?php echo Html::openTag('li', ['class' => 'list-group-item']); ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
                                                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
                                                </svg>
                                                <span><strong>20</strong><?php echo str_repeat(' ', 1).(string) $pricing['special']; ?></span>
                                            <?php echo Html::closeTag('li'); ?>    
                                            <?php echo Html::openTag('li', ['class' => 'list-group-item']); ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
                                                  <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
                                                </svg>
                                                <span><strong>400,000</strong><?php echo str_repeat(' ', 1).(string) $pricing['visits']; ?></span>
                                            <?php echo Html::closeTag('li'); ?>
                                        <?php echo Html::closeTag('ul'); ?>
                                        <?php echo A::tag()
                                                ->addClass('btn btn-accent rounded-pill')
                                                ->href('#!')
                                                ->content((string) $pricing['choosePlan'])
                                                ->render(); ?>        
                                    <?php echo Html::closeTag('div'); ?>            
                                <?php echo Html::closeTag('div'); ?>
                            <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::closeTag('div'); ?>                        
                    <?php echo Html::closeTag('div'); ?>
                <?php echo Html::closeTag('div'); ?>
            <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('section'); ?> 

