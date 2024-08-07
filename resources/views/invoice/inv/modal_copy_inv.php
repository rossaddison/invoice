<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @see id="inv-to-inv" triggered by <a href="#inv-to-inv" data-toggle="modal"  style="text-decoration:none"> 
 * @see InvController view function
 * @var App\Invoice\Entity\Inv $inv 
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $clients
 */

?>
<div id="inv-to-inv" class="modal modal-lg" role="dialog" aria-labelledby="modal_inv_to_inv" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-body">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
            </div>       
            <div class="modal-header">
                <h5 class="col-12 modal-title text-center"><?php echo $translator->translate('i.copy_invoice'); ?></h5>
                <br>
            </div> 
            <input type="hidden" name="user_id" id="user_id" value="<?= $inv->getUser_id(); ?>">
            <div class="form-group">
                <label for="create_inv_client_id"><?= $translator->translate('i.client'); ?></label>
                <select name="create_inv_client_id" id="create_inv_client_id" class="form-control">
                    <option value="<?= $inv->getClient()?->getClient_id(); ?>"><?= $inv->getClient()?->getClient_name() ?? '#'; ?></option>
                        <?php
                            /**
                             * @var App\Invoice\Entity\Client $client
                             */
                            foreach ($clients as $client) { ?>
                            <option value="<?= $client->getClient_id(); ?>">
                                <?= Html::encode($client->getClient_name()); ?>
                            </option>
                        <?php } ?>
                </select>          
            </div>
        </div>
        <div class="modal-footer">
            <div class="btn-group">
                <button class="inv_to_inv_confirm btn btn-success" id="inv_to_inv_confirm" type="button">
                    <i class="fa fa-check"></i> <?= $translator->translate('i.submit'); ?>
                </button>
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i> <?= $translator->translate('i.cancel'); ?>
                </button>
            </div>
        </div>

    </form>

</div>
