<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use App\Widget\OffsetPagination;

/**
 * @var \App\Invoice\Entity\Setting $setting
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\Session\Flash\FlashInterface $flash
 * @var \Yiisoft\Translator\TranslatorInterface $translator 
 */

echo $alert;

?>

<div>
 <h5><?= $s->trans('settings'); ?></h5>
 <a class="btn btn-success" href="<?= $urlGenerator->generate('setting/add'); ?>">
      <i class="fa fa-plus"></i> <?= $s->trans('new'); ?> </a>
</div>

<?php
$pagination = OffsetPagination::widget()
->paginator($paginator)
->urlGenerator(fn ($page) => $urlGenerator->generate('setting/debug_index', ['page' => $page]));

?>

<?php
  if ($pagination->isRequired()) {
     echo $pagination;
  }
?>
                
<div class="table-responsive">
<table class="table table-hover table-striped">
   <thead>
    <tr>
        <th><?= $s->trans('options'); ?></th>
        <th><?= 'Id'; ?></th>        
        <th><?= 'Key'; ?></th>
        <th><?= $s->trans('value'); ?></th>      
        
    </tr>
   </thead>
<tbody>

<?php foreach ($paginator->read() as $setting) { ?>
     <tr>
      <td>
          <div class="options btn-group">
          <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="fa fa-cog"></i>
                <?= $s->trans('options'); ?>
          </a>
          <ul class="dropdown-menu">
              <li>
                  <a href="<?= $urlGenerator->generate('setting/edit',['setting_id'=>$setting->getSetting_id()]); ?>"><i class="fa fa-edit fa-margin"></i>
                       <?= $s->trans('edit'); ?>
                  </a>
              </li>
              <li>
                  <a href="<?= $urlGenerator->generate('setting/delete',['setting_id'=>$setting->getSetting_id()]); ?>" style="text-decoration:none" onclick="return confirm('<?= $s->trans('delete_record_warning'); ?>');">
                        <i class="fa fa-trash fa-margin"></i><?= $s->trans('delete'); ?>                                    
                  </a>
              </li>
          </ul>
          </div>
      </td>      
      <td><?= Html::encode($setting->getSetting_id()); ?></td>          
      <td><?= Html::encode($setting->getSetting_key()); ?></td>
      <td><?= Html::encode($setting->getSetting_value()); ?></td>      
     </tr>
<?php } ?>
</tbody>
</table>
<?php
    $pageSize = $paginator->getCurrentPageSize();
    if ($pageSize > 0) {
      echo Html::p(
        sprintf($translator->translate('invoice.index.footer.showing').' settings', $pageSize, $paginator->getTotalItems()),
        ['class' => 'text-muted']
    );
    } else {
      echo Html::p($translator->translate('invoice.records.no'));
    }
?>
</div>