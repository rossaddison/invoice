<?php

declare(strict_types=1); 

/**
 * @see InvController function view_modal_delete_inv
 * @see inv/view.php id="delete-inv" triggered by <a href="#delete-inv" data-toggle="modal"  style="text-decoration:none">
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $actionName
 * @var string $csrf
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments 
 */

?>

<div id="delete-inv" class="modal modal-lg" role="dialog" aria-labelledby="modal_delete_inv" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-close"></i></button>
            <h4 class="panel-title"><?= $translator->translate('i.delete_invoice'); ?></h4>
        </div>
        <div class="modal-body">
            <div class="alert alert-danger"><?= $translator->translate('i.delete_invoice_warning'); ?></div>
        </div>
        <div class="modal-footer">
            <form action="<?= $urlGenerator->generate($actionName, $actionArguments) ?>" method="POST">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <div class="btn-group">
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-trash-o fa-margin"></i> <?= $translator->translate('i.confirm_deletion') ?>
                    </button>
                    <a href="#" class="btn btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> <?= $translator->translate('i.cancel'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

