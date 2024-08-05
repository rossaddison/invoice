<?php

declare(strict_types=1);

/**
 * @see id="modal-change-client" triggered by <a href="#modal-change-client"> inv\view
 * @var App\Invoice\Entity\Inv $inv
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $clients
 */

?>
<div id="modal-change-client" class="modal modal-lg" role="dialog" aria-labelledby="modal_change_client" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
        </div>
        <div class="modal-header">
            <h4 class="panel-title"><?= $translator->translate('i.change_client'); ?></h4>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="change_client_id"><?= $translator->translate('i.client'); ?></label>
                <select name="change_client_id" id="change_client_id" class="form-control">
                    <option value="0"><?= $translator->translate('i.none'); ?></option>
                        <?php
                            /**
                             * @var App\Invoice\Entity\Client $client
                             */
                            foreach ($clients as $client) { ?>
                            <option value="<?= $client->getClient_id(); ?>">
                                <?= $client->getClient_name() ?: '#'; ?>
                            </option>
                        <?php } ?>
                </select>
            </div>
            <input class="hidden" id="inv_id" value="<?= $inv->getId(); ?>">
        </div>
        <div class="modal-header">
            <div class="btn-group">
                <button class="client_change_confirm btn btn-success" id="client_change_confirm" type="button">
                    <i class="fa fa-check"></i> <?= $translator->translate('i.submit'); ?>
                </button>
            </div>
        </div>
    </form>
</div>
