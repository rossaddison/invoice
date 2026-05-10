<?php

declare(strict_types=1);

/**
 * Related logic: see InvController::trash() and Trait\Trash::restore()
 * Triggered by <a href="#restore-inv-{id}" data-bs-toggle="modal">
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $csrf
 * @var int $id
 */

?>

<div id="restore-inv-<?= $id ?>" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?= $translator->translate('delete.invoice.restore'); ?></h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <?= $translator->translate('delete.invoice.restore.warning'); ?>
                </div>
                <form action="<?= $urlGenerator->generate('inv/restore', ['id' => $id]) ?>"
                      method="POST">
                    <input type="hidden"
                           name="_csrf"
                           value="<?= $csrf ?>">
                    <div class="btn-group">
                        <button type="submit"
                                class="btn btn-success">
                            <i class="bi bi-arrow-counterclockwise"></i>
                <?= $translator->translate('delete.invoice.restore') ?>
                        </button>
                        <a href="#"
                           class="btn btn-default"
                           data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i>
                            <?= $translator->translate('delete.invoice.cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
