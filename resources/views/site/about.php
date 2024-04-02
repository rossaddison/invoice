<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\P;
use Yiisoft\Html\Tag\Button;

/**
 * @link  Acknowledgement to bootstrapbrain free templates wavelite for the bootstrap 5 code classes and structure 
 * @see This wavelite template has been adjusted to accomodate 
 * @see ..\invoice\src\ViewInjection\CommonViewInjection.php
 * @see ..\invoice\resources\messages\en\app.php
 */
?>

<?= Html::openTag('section', ['id' => 'About', 'class' => 'py-5 py-xl-8']); ?>
    <?= Html::openTag('div', ['class' => 'container']); ?>
        <?= Html::openTag('div', ['class' => 'row gy-5 gy-lg-0 align-items-lg-center']); ?> 
            <?= Html::openTag('div', ['class' => 'col-12 col-lg-6']); ?>
                <?php if ($s->get_setting('debug_mode') == '1') { ?>  
                    <?= Html::openTag('i', ['class' => 'bi bi-info-circle', 'data-bs-toggle' => 'tooltip', 'title' => 'invoice/public/img/soletrader/about/about.png']); ?>   
                    <?= Html::closeTag('i'); ?>
                <?php } ?>
                <?= Html::tag('img', '', ['class' => 'img-fluid rounded', 'loading' => 'lazy', 'src' => '/img/soletrader/about/about.png', 'alt' => '/img/soletrader/about/about.png']); ?>    
            <?= Html::closeTag('div'); ?>
                  
            <?= Html::openTag('div', ['class' => 'col-12 col-lg-6']); ?>
                <?= Html::openTag('div', ['class' => 'row justify-content-xl-end']); ?>
                    <?= Html::openTag('div', ['class' => 'col-12 col-xl-11']); ?>
                        <?= Html::openTag('h3', ['class' => 'display-3 fw-bolder mb-4']);?>
                            <?=
// We diligently apply our skills to the best of our ability                    
                               $about['we']; ?>
                        <?= Html::closeTag('h3'); ?>
                        <?= P::tag()
                            ->addClass('fs-4 mb-5')
                            ->content($about['choose'])
                            ->render(); ?>    
                        <?= Html::openTag('div', ['class' => 'accordion accordion-flush', 'id' => 'accordionExample']); ?>
                            <?= Html::openTag('div', ['class' => 'accordion-item']); ?>   
                                <?= Html::openTag('h2', ['class' => 'accordion-header', 'id' => 'headingOne']); ?>
                                    <?= Button::tag()
                                        ->addAttributes(
                                                [
                                                    'class' => 'accordion-button', 
                                                    'type' => 'button', 
                                                    'data-bs-toggle' => 'collapse', 
                                                    'data-bs-target' => '#collapseOne', 
                                                    'aria-expanded' => 'true', 
                                                    'aria-controls' => 'collapseOne'
                                                 ])
// Competitive rates                                        
                                        ->content($about['competitive'])
                                        ->render(); ?>
                                <?= Html::closeTag('h2'); ?>
                                <?= Html::openTag('div', ['id' => 'collapseOne', 'class' => 'accordion-collapse collapse show', 'aria-labelledby' => 'headingOne', 'data-bs-parent' => '#accordionExample']); ?>    
                                    <?= Html::openTag('div', ['class' => 'accordion-body']); ?>
                                        <?= 
// Without sacrificing quality                                
                                            $about['quality']; ?>
                                    <?= Html::closeTag('div'); ?>
                                <?= Html::closeTag('div'); ?>
                            <?= Html::closeTag('div'); ?>
                            <?= Html::openTag('div', ['class' => 'accordion-item']); ?>
                                <?= Html::openTag('h2', ['class' => 'accordion-header', 'id' => 'headingTwo']); ?>
                                    <?= Button::tag()
                                        ->addAttributes(
                                        [
                                            'class' => 'accordion-button collapsed', 
                                            'type' => 'button', 
                                            'data-bs-toggle' => 'collapse', 
                                            'data-bs-target' => '#collapseTwo', 
                                            'aria-expanded' => 'false', 
                                            'aria-controls' => 'collapseTwo'
                                        ])
// Contemporary skills                                        
                                        ->content($about['contemporary'])
                                        ->render(); ?>
                                <?= Html::closeTag('h2'); ?>
                                <?= Html::openTag('div', ['id' => 'collapseTwo', 'class' => 'accordion-collapse collapse', 'aria-labelledby' => 'headingTwo', 'data-bs-parent' => '#accordionExample']); ?>    
                                    <?= Html::openTag('div', ['class' => 'accordion-body']); ?>
                                        <?= $about['trained']; ?>
                                    <?= Html::closeTag('div'); ?>
                                <?= Html::closeTag('div'); ?>
                            <?= Html::closeTag('div'); ?>
                            <?= Html::openTag('div', ['class' => 'accordion-item']); ?>
                                <?= Html::openTag('h2', ['class' => 'accordion-header', 'id' => 'headingThree']); ?>
                                    <?= Button::tag()
                                        ->addAttributes(
                                        [
                                            'class' => 'accordion-button collapsed', 
                                            'type' => 'button', 
                                            'data-bs-toggle' => 'collapse', 
                                            'data-bs-target' => '#collapseThree', 
                                            'aria-expanded' => 'false', 
                                            'aria-controls' => 'collapseThree'
                                        ])
// Willing Return Support                                        
                                        ->content($about['willing'])
                                        ->render(); ?>
                                <?= Html::closeTag('h2'); ?>
                                <?= Html::openTag('div', ['id' => 'collapseThree', 'class' => 'accordion-collapse collapse', 'aria-labelledby' => 'headingThree', 'data-bs-parent' => '#accordionExample']); ?>    
                                    <?= Html::openTag('div', ['class' => 'accordion-body']); ?>
                                        <?= $about['dissatisfaction']; ?>. <?= $about['simply']; ?>
                                    <?= Html::closeTag('div'); ?>
                                <?= Html::closeTag('div'); ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('div'); ?>                    
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>                                
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'container pt-5 pt-xl-8']); ?>
        <?= Html::openTag('div', ['class' => 'row gy-4']); ?>    
            <?= Html::openTag('div', ['class' => 'col-12 col-sm-6 col-xl-3']); ?>    
                <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm']); ?>
                    <?= Html::openTag('div', ['class' => 'card-body text-center p-4 p-xxl-5']); ?>
                        <?= Html::openTag('div', ['class' => 'btn btn-primary pe-none mb-2 text-primary border-0']); ?>                
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-person-add" viewBox="0 0 16 16">
                              <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0Zm-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                              <path d="M8.256 14a4.474 4.474 0 0 1-.229-1.004H3c.001-.246.154-.986.832-1.664C4.484 10.68 5.711 10 8 10c.26 0 .507.009.74.025.226-.341.496-.65.804-.918C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4s1 1 1 1h5.256Z" />
                            </svg>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('h3', ['class' => 'h1 mb-2']); ?>
                            <?= Html::encode('100+'); ?>           
                        <?= Html::closeTag('h3'); ?>
                        <?= P::tag()
                            ->addClass('fs-5 mb-0')
// Happy Customers                                
                            ->content($about['happy'])
                            ->render(); ?>    
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        
            <?= Html::openTag('div', ['class' => 'col-12 col-sm-6 col-xl-3']); ?>
                <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm']); ?>
                    <?= Html::openTag('div', ['class' => 'card-body text-center p-4 p-xxl-5']); ?>
                        <?= Html::openTag('div', ['class' => 'btn btn-primary pe-none mb-2 text-primary border-0']); ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-heart-pulse" viewBox="0 0 16 16">
                              <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053.918 3.995.78 5.323 1.508 7H.43c-2.128-5.697 4.165-8.83 7.394-5.857.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17c3.23-2.974 9.522.159 7.394 5.856h-1.078c.728-1.677.59-3.005.108-3.947C13.486.878 10.4.28 8.717 2.01L8 2.748ZM2.212 10h1.315C4.593 11.183 6.05 12.458 8 13.795c1.949-1.337 3.407-2.612 4.473-3.795h1.315c-1.265 1.566-3.14 3.25-5.788 5-2.648-1.75-4.523-3.434-5.788-5Z" />
                              <path d="M10.464 3.314a.5.5 0 0 0-.945.049L7.921 8.956 6.464 5.314a.5.5 0 0 0-.88-.091L3.732 8H.5a.5.5 0 0 0 0 1H4a.5.5 0 0 0 .416-.223l1.473-2.209 1.647 4.118a.5.5 0 0 0 .945-.049l1.598-5.593 1.457 3.642A.5.5 0 0 0 12 9h3.5a.5.5 0 0 0 0-1h-3.162l-1.874-4.686Z" />
                            </svg>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('h3', ['class' => 'h1 mb-2']); ?>
                                <?= Html::encode('100+'); ?>           
                        <?= Html::closeTag('h3'); ?>                    
                        <?= P::tag()
                                ->addClass('fs-5 mb-0 text-secondary')
// Issues Solved                                
                                ->content($about['solved'])
                                ->render(); ?>
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col-12 col-sm-6 col-xl-3']); ?>
                <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm']); ?>
                    <?= Html::openTag('div', ['class' => 'card-body text-center p-4 p-xxl-5']); ?>
                        <?= Html::openTag('div', ['class' => 'btn btn-primary pe-none mb-2 text-primary border-0']); ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-droplet" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M7.21.8C7.69.295 8 0 8 0c.109.363.234.708.371 1.038.812 1.946 2.073 3.35 3.197 4.6C12.878 7.096 14 8.345 14 10a6 6 0 0 1-12 0C2 6.668 5.58 2.517 7.21.8zm.413 1.021A31.25 31.25 0 0 0 5.794 3.99c-.726.95-1.436 2.008-1.96 3.07C3.304 8.133 3 9.138 3 10a5 5 0 0 0 10 0c0-1.201-.796-2.157-2.181-3.7l-.03-.032C9.75 5.11 8.5 3.72 7.623 1.82z" />
                                <path fill-rule="evenodd" d="M4.553 7.776c.82-1.641 1.717-2.753 2.093-3.13l.708.708c-.29.29-1.128 1.311-1.907 2.87l-.894-.448z" />
                             </svg>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('h3', ['class' => 'h1 mb-2']); ?>
                            <?= Html::encode('100+'); ?>           
                        <?= Html::closeTag('h3'); ?>
                        <?= P::tag()
                            ->addClass('fs-5 mb-0 text-secondary')
// Finished Projects                                
                            ->content($about['finished'])
                            ->render(); ?>
                    <?= Html::closeTag('div'); ?>                    
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>                       
            <?= Html::openTag('div', ['class' => 'col-12 col-sm-6 col-xl-3']); ?>
                <?= Html::openTag('div', ['class' => 'card border-0 border-bottom border-primary shadow-sm']); ?>
                    <?= Html::openTag('div', ['class' => 'card-body text-center p-4 p-xxl-5']); ?>
                        <?= Html::openTag('div', ['class' => 'btn btn-primary pe-none mb-2 text-primary border-0']); ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-cloud-moon" viewBox="0 0 16 16">
                                <path d="M7 8a3.5 3.5 0 0 1 3.5 3.555.5.5 0 0 0 .625.492A1.503 1.503 0 0 1 13 13.5a1.5 1.5 0 0 1-1.5 1.5H3a2 2 0 1 1 .1-3.998.5.5 0 0 0 .509-.375A3.502 3.502 0 0 1 7 8zm4.473 3a4.5 4.5 0 0 0-8.72-.99A3 3 0 0 0 3 16h8.5a2.5 2.5 0 0 0 0-5h-.027z" />
                                <path d="M11.286 1.778a.5.5 0 0 0-.565-.755 4.595 4.595 0 0 0-3.18 5.003 5.46 5.46 0 0 1 1.055.209A3.603 3.603 0 0 1 9.83 2.617a4.593 4.593 0 0 0 4.31 5.744 3.576 3.576 0 0 1-2.241.634c.162.317.295.652.394 1a4.59 4.59 0 0 0 3.624-2.04.5.5 0 0 0-.565-.755 3.593 3.593 0 0 1-4.065-5.422z" />
                            </svg>
                        <?= Html::closeTag('div'); ?>                   
                        <?= Html::openTag('h3', ['class' => 'h1 mb-2']); ?>
                            <?= Html::encode('100+'); ?>           
                        <?= Html::closeTag('h3'); ?>
                        <?= P::tag()
                            ->addClass('fs-5 mb-0 text-secondary')
// Return Customers                                 
                            ->content($about['return'])
                            ->render(); ?>
                     <?= Html::closeTag('div'); ?>                    
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>                            
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('footer'); ?>
        <?= Html::tag('br'); ?>
            <?= A::tag()
                ->href('https://bootstrapbrain.com/template/free-bootstrap-5-multipurpose-one-page-template-wave/#pricing')
// Acknowledgement to Bootstrap Brain                
                ->content('Acknowledgement to Bootstrap Brain')
                ->render(); ?> 
    <?= Html::closeTag('footer'); ?>
<?= Html::closeTag('section'); ?>


