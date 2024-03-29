<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 */

?>
<div id="headerbar">
    <h1 class="headerbar-title">
        <a href="<?= $urlGenerator->generate('customfield/index',['_language'=>'en']); ?>">
              <?= $translator->translate('i.custom_fields'); ?>
        </a>
    </h1>    
    <div class="headerbar-item pull-right">
    <div class="btn-group btn-group-sm">      
            <a href="" class="btn btn-success ajax-loader" Id="btn_save_client_custom_fields"><i class="fa fa-check"></i><?= $translator->translate('i.save'); ?></a>
            <button type="button" onclick="window.history.back()" id="btn-cancel" name="btn_cancel" class="btn btn-danger" value="1">
                <i class="fa fa-arrow-left"></i> <?= $translator->translate('i.back'); ?>
            </button>
    </div>
</div>
</div>
<div>
<div col="row">
    <?php foreach ($custom_fields as $custom_field): ?>
    <div class="col-md-4">
          <?= $cvH->print_field_for_form($custom_field, $form, $translator, $client_custom_values, $custom_values); ?>
    </div>    
    <?php endforeach; ?>        
</div> 
</div>
</div>    
<br>
<br>
<br>
<br>