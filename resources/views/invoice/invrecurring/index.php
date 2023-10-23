<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use App\Widget\OffsetPagination;

/**
 * @var \App\Invoice\Entity\InvRecurring $invrecurring
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */
 
 echo $alert;
?>
<div>
 <h1 class="headerbar-title"><?= $s->trans('recurring_invoices'); ?></h1>
 <a class="btn btn-success" href="<?= $urlGenerator->generate('inv/index'); ?>">
      <i class="fa fa-arrow-left"></i> <?= $s->trans('invoices'); ?> </a></div>

<?php
$pagination = OffsetPagination::widget()
->paginator($paginator)
->urlGenerator(fn ($page) => $urlGenerator->generate('invrecurring/index', ['page' => $page])); 
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
        <th><?= $s->trans('status'); ?></th>
        <th><?= $s->trans('base_invoice'); ?></th>
        <th><?= $s->trans('client'); ?></th>
        <th><?= $s->trans('start_date'); ?></th>
        <th><?= $s->trans('end_date'); ?></th>
        <th><?= $s->trans('every'); ?></th>
        <th><?= $s->trans('next_date'); ?></th>
        <th><?= $s->trans('options'); ?></th>
    </tr>
   </thead>
<tbody>

<?php foreach ($paginator->read() as $invrecurring) { ?>
     <?php 
        $no_next = null===$invrecurring->getNext() ? true : false;
     ?>
     <tr>
      <td>
            <span class="label
                            <?php if ($no_next) {
                            echo 'label-default';
                        } else {
                            echo 'label-success';
                        } ?>">
                            <?= $no_next ? $s->trans('inactive') : $s->trans('active') ?>
            </span>
      </td>      
      <td><a href="<?= $urlGenerator->generate('inv/view',['id'=>$invrecurring->getInv_id()]); ?>"  title="<?= $s->trans('edit'); ?>" style="text-decoration:none"><?php echo($invrecurring->getInv()->getNumber() ? $invrecurring->getInv()->getNumber() : $invrecurring->getInv_id()); ?></a></td>   
      <td><?= Html::a($invrecurring->getInv()->getClient()->getClient_name(),$urlGenerator->generate('client/view',['id'=>$invrecurring->getInv()->getClient()->getClient_id()])); ?></td>         
      <td><?= Html::encode(($invrecurring->getStart())->format($datehelper->style())); ?></td>
      <td><?= Html::encode(($invrecurring->getEnd())->format($datehelper->style())); ?></td>
      <td><?= Html::encode($s->trans($recur_frequencies[$invrecurring->getFrequency()])); ?></td>
      <!-- If the next_date has a date then the invoice is still recurring and therefore active. -->
      <td><?= Html::encode($no_next ? '' : ($invrecurring->getNext())->format($datehelper->style())); ?></td>
      <td>
          <div class="options btn-group">
          <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="fa fa-cog"></i>
                <?= $s->trans('options'); ?>
          </a>
          <ul class="dropdown-menu">
              <li>
                <?php if (!$no_next) { ?>  
                  <a href="<?= $urlGenerator->generate('invrecurring/stop',['id'=>$invrecurring->getId()]); ?>" style="text-decoration:none"                    
                  ><i class="fa fa-edit fa-margin"></i>
                       <?= $s->trans('stop'); ?>
                  </a>
                <?php } ?>  
              </li>
              <li>
                  <a href="<?= $urlGenerator->generate('invrecurring/delete',['id'=>$invrecurring->getId()]); ?>" style="text-decoration:none">                       <i class="fa fa-trash fa-margin"></i>
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
        sprintf($translator->translate('invoice.index.footer.showing').' invrecurrings', $pageSize, $paginator->getTotalItems()),
        ['class' => 'text-muted']
    );
    } else {
      echo Html::p($translator->translate('invoice.records.no'));
    }
?>
</div>
</div>
