<?php

declare(strict_types=1);

/**
 * Related logic: see id="delete-quote" triggered by <a href="#delete-quote" data-bs-toggle="modal"  style="text-decoration:none"> on views/quote/view.php
 * Related logic: see App\Invoice\Quote\QuoteController function view  'modal_delete_quote'.
 *
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string                                 $csrf
 * @var string                                 $actionName
 *
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */
?>

<div id="delete-quote" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?php echo $translator->translate('delete.quote'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger"><?php echo $translator->translate('delete.quote.warning'); ?></div>
                <form action="<?php echo $urlGenerator->generate($actionName, $actionArguments); ?>" method="POST">
                    <input type="hidden" name="_csrf" value="<?php echo $csrf; ?>"> 
                    <div class="btn-group">
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-trash-o fa-margin"></i> <?php echo $translator->translate('confirm.deletion'); ?>
                        </button>
                        <a href="#" class="btn btn-default" data-bs-dismiss="modal">
                            <i class="fa fa-times"></i> <?php echo $translator->translate('cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>    
        </div>
    </div>
</div>

