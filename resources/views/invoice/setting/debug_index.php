<?php

declare(strict_types=1);

use App\Widget\OffsetPagination;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;

/**
 * @var App\Widget\Button                      $button
 * @var App\Invoice\Entity\Setting             $setting
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string                                 $alert
 * @var OffsetPaginator                        $paginator
 */
echo $alert;

?>

<div>
 <h5><?php echo $translator->translate('settings'); ?></h5>
 <a class="btn btn-success" href="<?php echo $urlGenerator->generate('setting/add'); ?>">
      <i class="fa fa-plus"></i> <?php echo $translator->translate('new'); ?> </a>
</div>

<?php
$pagination = OffsetPagination::widget()
    ->paginator($paginator)
    ->urlGenerator(fn (int $page) => $urlGenerator->generate('setting/debug_index', ['page' => $page]));

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
        <th><?php echo $translator->translate('options'); ?></th>
        <th><?php echo 'Id'; ?></th>        
        <th><?php echo 'Key'; ?></th>
        <th><?php echo $translator->translate('value'); ?></th>      
        
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
                <?php echo $translator->translate('options'); ?>
          </a>
          <ul class="dropdown-menu">
              <li>
                  <a href="<?php echo $urlGenerator->generate('setting/edit', ['setting_id' => $setting->getSetting_id()]); ?>"><i class="fa fa-edit fa-margin"></i>
                       <?php echo $translator->translate('edit'); ?>
                  </a>
              </li>
              <li>
                  <a href="<?php echo $urlGenerator->generate('setting/delete', ['setting_id' => $setting->getSetting_id()]); ?>" style="text-decoration:none" onclick="return confirm('<?php echo $translator->translate('delete.record.warning'); ?>');">
                        <i class="fa fa-trash fa-margin"></i><?php echo $translator->translate('delete'); ?>                                    
                  </a>
              </li>
          </ul>
          </div>
      </td>      
      <td><?php echo Html::encode($setting->getSetting_id()); ?></td>          
      <td><?php echo Html::encode($setting->getSetting_key()); ?></td>
      <td><?php echo Html::encode($setting->getSetting_value()); ?></td>      
     </tr>
<?php } ?>
</tbody>
</table>
<?php
    $pageSize = $paginator->getCurrentPageSize();
if ($pageSize > 0) {
    echo Html::p(
        sprintf($translator->translate('index.footer.showing').' settings', $pageSize, $paginator->getTotalItems()),
        ['class' => 'text-muted'],
    );
} else {
    echo Html::p($translator->translate('records.no'));
}
?>
</div>