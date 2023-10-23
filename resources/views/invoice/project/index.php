<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use App\Widget\OffsetPagination;

/**
 * @var \App\Invoice\Entity\Project $project
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */
 
 echo $alert;
?>
<div>
 <h5>Project</h5>
 <a class="btn btn-success" href="<?= $urlGenerator->generate('project/add'); ?>">
      <i class="fa fa-plus"></i> <?= $s->trans('new'); ?> </a></div>

<?php
$pagination = OffsetPagination::widget()
->paginator($paginator)
->urlGenerator(fn ($page) => $urlGenerator->generate('project/index', ['page' => $page]));
        
        

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
                
        <th><?= $s->trans('client'); ?></th>

        <th><?= $s->trans('options'); ?></th>
    </tr>
   </thead>
<tbody>

<?php foreach ($paginator->read() as $project) { ?>
     <tr>
                
      <td><?= Html::encode($project->getName()); ?></td>
                
        <td><?= Html::encode(null!== $project->getClient()->getClient_name() || null!== $project->getClient()->getClient_surname() ? $project->getClient()->getClient_name() ." ".$project->getClient()->getClient_surname() : $project->getClient()->getClient_surname()); ?></td>

        <td>
          <div class="options btn-group">
          <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="fa fa-cog"></i>
                <?= $s->trans('options'); ?>
          </a>
          <ul class="dropdown-menu">
              <li>
                  <a href="<?= $urlGenerator->generate('project/edit',['id'=>$project->getId()]); ?>" style="text-decoration:none"><i class="fa fa-edit fa-margin"></i>
                       <?= $s->trans('edit'); ?>
                  </a>
              </li>
              <li>
                  <a href="<?= $urlGenerator->generate('project/view',['id'=>$project->getId()]); ?>" style="text-decoration:none"><i class="fa fa-eye fa-margin"></i>
                       <?= $s->trans('view'); ?>
                  </a>
              </li>
             <li>
                  <a href="<?= $urlGenerator->generate('project/delete',['id'=>$project->getId()]); ?>" style="text-decoration:none" onclick="return confirm('<?= $s->trans('delete_record_warning'); ?>');">
                       <i class="fa fa-trash fa-margin"></i><?= $s->trans('delete'); ?>                                    
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
        sprintf($translator->translate('invoice.index.footer.showing').' projects', $pageSize, $paginator->getTotalItems()),
        ['class' => 'text-muted']
    );
    } else {
      echo Html::p($translator->translate('invoice.records.no'));
    }
?>
</div>
</div>
