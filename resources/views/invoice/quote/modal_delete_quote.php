<?php

declare(strict_types=1); 

/**
 * @see id="delete-quote" triggered by <a href="#delete-quote" data-bs-toggle="modal"  style="text-decoration:none"> on views/quote/view.php
 * @see App\Invoice\Quote\QuoteController function view  'modal_delete_quote'
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $csrf
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

?>

<div id="delete-quote" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?= $translator->translate('i.delete_quote'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger"><?= $translator->translate('i.delete_quote_warning'); ?></div>
                <form action="<?= $urlGenerator->generate($actionName, $actionArguments) ?>" method="POST">
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>"> 
                    <div class="btn-group">
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-trash-o fa-margin"></i> <?= $translator->translate('i.confirm_deletion') ?>
                        </button>
                        <a href="#" class="btn btn-default" data-bs-dismiss="modal">
                            <i class="fa fa-times"></i> <?= $translator->translate('i.cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>    
        </div>
    </div>
</div>

