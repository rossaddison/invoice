<?php

declare(strict_types=1);

use App\Widget\OffsetPagination;
use Yiisoft\Html\Html;
use Yiisoft\Data\Paginator\OffsetPaginator;

/**
 * @var App\Widget\Button $button
 * @var App\Invoice\Entity\Setting $setting
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $alert
 * @var OffsetPaginator $paginator
 */

echo $alert;

?>

<div>
 <h5><?= $translator->translate('settings'); ?></h5>
 <a class="btn btn-success" href="<?= $urlGenerator->generate('setting/add'); ?>">
      <i class="fa fa-plus"></i> <?= $translator->translate('new'); ?> </a>
</div>

<?php
$pagination = OffsetPagination::widget()
->paginator($paginator)
->urlGenerator(fn(int $page) => $urlGenerator->generate('setting/debug_index', ['page' => $page]));

?>

<?php
  if ($pagination->isPaginationRequired()) {
      echo $pagination;
  }
?>
                
<div class="table-responsive">
<table class="table table-hover table-striped">
   <thead>
    <tr>
        <th><?= $translator->translate('options'); ?></th>
        <th><?= 'Id'; ?></th>        
        <th><?= 'Key'; ?></th>
        <th><?= $translator->translate('value'); ?></th>      
        
    </tr>
   </thead>
<tbody>

<?php
     /**
      * @var App\Invoice\Entity\Setting $setting
      */
     foreach ($paginator->read() as $setting) { ?>
     <tr>
      <td>
          <div class="options btn-group">
          <a class="btn btn-default btn-sm dropdown-toggle" data-bs-toggle="dropdown" href="#">
                <i class="fa fa-cog"></i>
                <?= $translator->translate('options'); ?>
          </a>
          <ul class="dropdown-menu">
              <li>
                  <a href="<?= $urlGenerator->generate('setting/edit', ['setting_id' => $setting->getSetting_id()]); ?>"><i class="fa fa-edit fa-margin"></i>
                       <?= $translator->translate('edit'); ?>
                  </a>
              </li>
              <li>
                  <a href="<?= $urlGenerator->generate('setting/delete', ['setting_id' => $setting->getSetting_id()]); ?>" style="text-decoration:none" onclick="return confirm('<?= $translator->translate('delete.record.warning'); ?>');">
                        <i class="fa fa-trash fa-margin"></i><?= $translator->translate('delete'); ?>                                    
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
        sprintf($translator->translate('index.footer.showing') . ' settings', $pageSize, $paginator->getTotalItems()),
        ['class' => 'text-muted'],
    );
} else {
    echo Html::p($translator->translate('records.no'));
}
?>
</div>