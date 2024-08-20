<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @see id="inv-to-inv" triggered by <a href="#inv-to-inv" data-bs-toggle="modal"  style="text-decoration:none"> 
 * @see InvController view function
 * @var App\Invoice\Entity\Inv $inv 
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $clients
 * @var string $csrf
 */

?>
    
<div id="inv-to-inv" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title"><?php echo $translator->translate('i.copy_invoice'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
             </div>
             <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
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
                </form>    
            </div>
            <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $translator->translate('i.cancel'); ?></button>
                 <button type="button" class="inv_to_inv_confirm btn btn-success" id="inv_to_inv_confirm">
                    <i class="fa fa-check"></i> <?= $translator->translate('i.submit'); ?>
                 </button>
            </div>
        </div>
    </div>
</div>
