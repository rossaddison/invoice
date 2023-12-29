<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use App\Widget\OffsetPagination;

/**
 * @var \App\Invoice\Entity\Task $task
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\Session\Flash\FlashInterface $flash 
 */

echo $alert;
?>

<div>
 <h5><?= $translator->translate('i.task');?></h5>
 <a class="btn btn-success" href="<?= $urlGenerator->generate('task/add'); ?>"><i class="fa fa-plus"></i>
   <?= 
        $translator->translate('i.new'); 
   ?>
 </a>
</div>
<br>
<div class="submenu-row">
 <div class="btn-group index-options">
 </div>
</div>

<?php
    $pagination = OffsetPagination::widget()
    ->paginator($paginator)
    ->urlGenerator(fn ($page) => $urlGenerator->generate('task/index', ['page' => $page]));   
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
        <th><?= $translator->translate('i.status'); ?></th>
        <th><?= $translator->translate('i.name'); ?></th>
        <th><?= $translator->translate('i.description'); ?></th>
        <th><?= $translator->translate('i.price'); ?></th>
        <th><?= $translator->translate('i.task_finish_date'); ?></th>
        <th><?= $translator->translate('i.project'); ?></th>
        <th><?= $translator->translate('i.tax_rate'); ?></th>
        <th><?= $translator->translate('i.options'); ?></th>
    </tr>
   </thead>
<tbody>

<?php foreach ($paginator->read() as $task) { ?>
     <tr>
        <td><?= Html::encode($task->getStatus() ? $translator->translate('i.active') : $translator->translate('i.inactive')); ?></td>
        <td><?= Html::encode($task->getName()); ?></td>
        <td><?= Html::encode($task->getDescription()); ?></td>
        <td class="amount"><?= Html::encode($s->format_currency(null!==$task->getPrice() ? $task->getPrice() : 0.00)); ?></td>
        <td><?= Html::encode($datehelper->date_from_mysql($task->getFinish_date())); ?></td>        
        <td><?= Html::encode(($prjct->count($task->getProject_id()) > 0 ? $prjct->repoProjectquery($task->getId())->getName() : '')); ?></td>
        <td><?php echo ($task->getTaxrate()->getTax_rate_name()) ? Html::encode($task->getTaxrate()->getTax_rate_name()) : $translator->translate('i.none'); ?></td>
        <td>
          <div class="options btn-group">
          <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="fa fa-cog"></i>
                <?= $translator->translate('i.options'); ?>
          </a>
          <ul class="dropdown-menu">
              <li>
                  <a href="<?= $urlGenerator->generate('task/edit',['id'=>$task->getId()]); ?>" style="text-decoration:none"><i class="fa fa-edit fa-margin"></i>
                       <?= $translator->translate('i.edit'); ?>
                  </a>
              </li>
              <li>
                  <a href="<?= $urlGenerator->generate('task/view',['id'=>$task->getId()]); ?>" style="text-decoration:none"><i class="fa fa-eye fa-margin"></i>
                       <?= $translator->translate('i.view'); ?>
                  </a>
              </li>
              <li>
                  <a href="<?= $urlGenerator->generate('task/delete',['id'=>$task->getId()]); ?>" style="text-decoration:none" onclick="return confirm('<?= $translator->translate('i.delete_record_warning'); ?>');">
                       <i class="fa fa-trash fa-margin"></i><?= $translator->translate('i.delete'); ?>                                    
                  </a>
              </li>
          </ul>
          </div>
         </td>
     </tr>
<?php } ?>
</tbody>
</table>
<?php
    $pageSize = $paginator->getCurrentPageSize();
    if ($pageSize > 0) {
      echo Html::p(
        sprintf($translator->translate('invoice.index.footer.showing').' tasks', $pageSize, $paginator->getTotalItems()),
        ['class' => 'text-muted']
    );
    } else {
      echo Html::p($translator->translate('invoice.records.no'));
    }
?>
</div>
</div>
