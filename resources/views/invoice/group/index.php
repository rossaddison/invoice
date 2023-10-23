<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use App\Widget\OffsetPagination;

/**
 * @var \App\Invoice\Entity\Group $group
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

echo $alert;
?>
<div>
 <h5>Group</h5>
 <a class="btn btn-success" href="<?= $urlGenerator->generate('group/add'); ?>">
      <i class="fa fa-plus"></i> <?= $s->trans('new'); ?> </a></div>

<?php
$pagination = OffsetPagination::widget()
->paginator($paginator)
->urlGenerator(fn ($page) => $urlGenerator->generate('group/index', ['page' => $page]));
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
        <th><?= $s->trans('name'); ?></th>
        <th><?= $s->trans('identifier_format'); ?></th>
        <th><?= $s->trans('left_pad'); ?></th>
        <th><?= $s->trans('next_id'); ?></th>
        <th><?= $s->trans('options'); ?></th>
    </tr>
   </thead>
<tbody>

<?php foreach ($paginator->read() as $group) { ?>
     <tr>
                
      <td><?= Html::encode($group->getName()); ?></td>
      <td><?= Html::encode($group->getIdentifier_format()); ?></td>
      <td><?= Html::encode($group->getLeft_pad()); ?></td>
      <td><?= Html::encode($group->getNext_id()); ?></td>
                

        <td>
          <div class="options btn-group">
          <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="fa fa-cog"></i>
                <?= $s->trans('options'); ?>
          </a>
          <ul class="dropdown-menu">
              <li>
                  <a href="<?= $urlGenerator->generate('group/edit',['id'=>$group->getId()]); ?>" style="text-decoration:none">                       <i class="fa fa-edit fa-margin"></i>
                       <?= $s->trans('edit'); ?>
                  </a>
              </li>
              <li>
                  <a href="<?= $urlGenerator->generate('group/view',['id'=>$group->getId()]); ?>" style="text-decoration:none">                       <i class="fa fa-eye fa-margin"></i>
                       <?= $s->trans('view'); ?>
                  </a>
              </li>
              <li>
                  <a href="<?= $urlGenerator->generate('group/delete',['id'=>$group->getId()]); ?>" style="text-decoration:none">                       <i class="fa fa-trash fa-margin"></i>
                       <?= $s->trans('delete'); ?>
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
        sprintf($translator->translate('invoice.index.footer.showing').' groups', $pageSize, $paginator->getTotalItems()),
        ['class' => 'text-muted']
    );
    } else {
      echo Html::p($translator->translate('invoice.records.no'));
    }
?>
</div>
</div>
