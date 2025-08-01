<?php

declare(strict_types=1);

/**
 * Related logic: see InvController function view_modal_delete_inv
 * Related logic: see inv/view.php id="delete-inv" triggered by <a href="#delete-inv" data-bs-toggle="modal"  style="text-decoration:none">
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $actionName
 * @var string $csrf
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

?>

<div id="delete-inv" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?= $translator->translate('delete.invoice'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger"><?= $translator->translate('delete.invoice.warning'); ?></div>
                <form action="<?= $urlGenerator->generate($actionName, $actionArguments) ?>" method="POST">
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>"> 
                    <div class="btn-group">
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-trash-o fa-margin"></i> <?= $translator->translate('confirm.deletion') ?>
                        </button>
                        <a href="#" class="btn btn-default" data-bs-dismiss="modal">
                            <i class="fa fa-times"></i> <?= $translator->translate('cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>    
        </div>
    </div>
</div>

