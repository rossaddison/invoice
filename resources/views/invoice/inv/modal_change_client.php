<?php

declare(strict_types=1);

/**
 * Related logic: see id="modal-change-client" triggered by <a href="#modal-change-client"> inv\view.
 *
 * @var App\Invoice\Entity\Inv                 $inv
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array                                  $clients
 * @var string                                 $csrf
 */
?>

<div id="modal-change-client" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?php echo $translator->translate('change.client'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?php echo $csrf; ?>">
                    <div class="form-group">
                        <label for="change_client_id"><?php echo $translator->translate('client'); ?></label>
                        <select name="change_client_id" id="change_client_id" class="form-control">
                            <option value="0"><?php echo $translator->translate('none'); ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\Client $client
                                     */
                                    foreach ($clients as $client) { ?>
                                    <option value="<?php echo $client->getClient_id(); ?>">
                                        <?php echo $client->getClient_name() ?: '#'; ?>
                                    </option>
                                <?php } ?>
                        </select>
                    </div>
                    <input class="hidden" id="inv_id" value="<?php echo $inv->getId(); ?>">
                </form>    
            </div>
            <div class="modal-footer">
                <div class="btn-group">
                    <button class="client_change_confirm btn btn-success" id="client_change_confirm" type="button">
                        <i class="fa fa-check"></i> <?php echo $translator->translate('submit'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

