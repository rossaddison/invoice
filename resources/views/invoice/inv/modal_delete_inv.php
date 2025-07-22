<?php

declare(strict_types=1);

/**
 * @see InvController function view_modal_delete_inv
 * @see inv/view.php id="delete-inv" triggered by <a href="#delete-inv" data-bs-toggle="modal"  style="text-decoration:none">
 *
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string                                 $actionName
 * @var string                                 $csrf
 *
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */
?>

<div id="delete-inv" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?php echo $translator->translate('delete.invoice'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger"><?php echo $translator->translate('delete.invoice.warning'); ?></div>
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

