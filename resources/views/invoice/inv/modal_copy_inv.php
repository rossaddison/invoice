<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/*
 * Related logic: see id="inv-to-inv" triggered by <a href="#inv-to-inv" data-bs-toggle="modal"  style="text-decoration:none">
 * Related logic: see InvController view function
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
               <h5 class="modal-title"><?php echo $translator->translate('copy.invoice'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
             </div>
             <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="user_id" id="user_id" value="<?php echo $inv->getUser_id(); ?>">
                    <div class="form-group">
                        <label for="create_inv_client_id"><?php echo $translator->translate('client'); ?></label>
                        <select name="create_inv_client_id" id="create_inv_client_id" class="form-control">
                            <option value="<?php echo $inv->getClient()?->getClient_id(); ?>"><?php echo $inv->getClient()?->getClient_name() ?? '#'; ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\Client $client
                                     */
                                    foreach ($clients as $client) { ?>
                                    <option value="<?php echo $client->getClient_id(); ?>">
                                        <?php echo Html::encode($client->getClient_name()); ?>
                                    </option>
                                <?php } ?>
                        </select>          
                    </div>
                </form>    
            </div>
            <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $translator->translate('cancel'); ?></button>
                 <!-- inv.js inv_to_inv_confirm, InvController function inv_to_inv_confirm -->
                 <button type="button" class="inv_to_inv_confirm btn btn-success" id="inv_to_inv_confirm">
                    <i class="fa fa-check"></i> <?php echo $translator->translate('submit'); ?>
                 </button>
            </div>
        </div>
    </div>
</div>
