<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\WebView $this
 */

// id="create-inv" triggered by <a href="#create-inv" class="btn btn-success" data-toggle="modal"  style="text-decoration:none"> on 
// views/inv/index.php

?>
<div id="create-inv" class="modal modal-lg" role="dialog" aria-labelledby="modal_create_inv" aria-hidden="true">
    <form class="modal-content">
      <div class="modal-body">  
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
        </div>        
        <div class="modal-header">
            <h5 class="col-12 modal-title text-center"><?php echo $translator->translate('i.create_invoice'); ?></h5>
            <br>
        </div>        
        <div>
            <label for="create_inv_client_id"><?= $translator->translate('i.client'); ?></label>
            <select name="create_inv_client_id" id="create_inv_client_id" class="form-control">
                <?php foreach ($clients as $client) { ?>
                    <option value="<?= $client->getClient_id(); ?>">
                        <?= Html::encode($client->getClient_name() . str_repeat(' ', 3)). $client->getClient_surname(); ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <div>
            <label for="inv_password"><?= $translator->translate('i.password'); ?></label>
            <input type="text" name="inv_password" id="inv_password" class="form-control"
                   value="<?php echo $s->get_setting('invoice_pre_password') ? '' : $s->get_setting('invoice_pre_password') ?>"
                   autocomplete="off">
        </div>
        <div>
            <label for="inv_group_id"><?= $translator->translate('i.invoice_group'); ?>: </label>
            <select name="inv_group_id" id="inv_group_id"
                    class="form-control">
                <?php foreach ($invoice_groups as $group) { ?>
                    <option value="<?php echo $group->getId(); ?>"
                        <?= $s->check_select($s->get_setting('default_invoice_group'), $group->getId()); ?>>
                        <?= Html::encode($group->getName()); ?>
                    </option>
                <?php } ?>
            </select>
        </div>       

        <div class="modal-header">
            <div class="btn-group">
                <button class="inv_create_confirm btn btn-success" id="inv_create_confirm" type="button">
                    <i class="fa fa-check"></i>
                    <?= $translator->translate('i.submit'); ?>
                </button>
            </div>
        </div>
      </div>    
    </form>
</div>

