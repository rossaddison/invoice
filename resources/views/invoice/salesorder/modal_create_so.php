<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\WebView $this
 */

// id="create-so" triggered by <a href="#create-so" class="btn btn-success" data-toggle="modal"  style="text-decoration:none"> on 
// views/salesorder/index.php

?>
<div id="create-so" class="modal modal-lg" role="dialog" aria-labelledby="modal_create_so" aria-hidden="true">
    <form class="modal-content">
      <div class="modal-body">  
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
        </div>        
        <div class="modal-header">
            <h5 class="col-12 modal-title text-center"><?= $translator->translate('invoice.salesorder.create.quote') ?></h5>
            <br>
        </div>        
        <div>
            <label for="create_so_client_id"><?= $s->trans('client'); ?></label>
            <select name="create_so_client_id" id="create_so_client_id" class="form-control">
                <?php foreach ($clients as $client) { ?>
                  <!-- Ensure that only clients with user accounts are selected -->
                  <?php if (in_array($client->getClient_id(), $ucR->getClients_with_user_accounts())) { ?> )
                    <option value="<?= $client->getClient_id(); ?>">
                        <?= Html::encode($client->getClient_name()); ?>
                    </option>
                  <?php } ?>  
                <?php } ?>
            </select>
        </div>
        <div>
            <label for="so_password"><?= $s->trans('password'); ?></label>
            <input type="text" name="so_password" id="so_password" class="form-control"
                   value="<?php echo $translator->translate('invoice.salesorder.pre.password') ? '' : $translator->translate('invoice.salesorder.pre.password') ?>"
                   autocomplete="off">
        </div>

        <div>
            <label for="so_group_id"><?= $translator->translate('invoice.salesorder.group'); ?>: </label>
            <select name="so_group_id" id="so_group_id"
                    class="form-control">
                <?php foreach ($invoice_groups as $group) { ?>
                    <option value="<?php echo $group->getId(); ?>"
                        <?= $s->check_select($s->get_setting('default_so_group'), $group->getId()); ?>>
                        <?= Html::encode($group->getName()); ?>
                    </option>
                <?php } ?>
            </select>
        </div>       

        <div class="modal-header">
            <div class="btn-group">
                <button class="so_create_confirm btn btn-success" id="so_create_confirm" type="button">
                    <i class="fa fa-check"></i>
                    <?= $s->trans('submit'); ?>
                </button>
            </div>
        </div>
      </div>    
    </form>
</div>

