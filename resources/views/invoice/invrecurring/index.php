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
 <h1 class="headerbar-title"><?= $translator->translate('i.recurring_invoices'); ?></h1>
 <a class="btn btn-success" href="<?= $urlGenerator->generate('inv/index'); ?>">
      <i class="fa fa-arrow-left"></i> <?= $translator->translate('i.invoices'); ?> </a></div>

<?php
$pagination = OffsetPagination::widget()
->paginator($paginator)
->urlGenerator(fn ($page) => $urlGenerator->generate('invrecurring/index', ['page' => $page])); 
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
        <th><?= $translator->translate('i.status'); ?></th>
        <th><?= $translator->translate('i.base_invoice'); ?></th>
        <th><?= $translator->translate('i.client'); ?></th>
        <th><?= $translator->translate('i.start_date'); ?></th>
        <th><?= $translator->translate('i.end_date'); ?></th>
        <th><?= $translator->translate('i.every'); ?></th>
        <th><?= $translator->translate('i.next_date'); ?></th>
        <th><?= $translator->translate('i.options'); ?></th>
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
                            <?= $no_next ? $translator->translate('i.inactive') : $translator->translate('i.active') ?>
            </span>
      </td>      
      <td><a href="<?= $urlGenerator->generate('inv/view',['id'=>$invrecurring->getInv_id()]); ?>"  title="<?= $translator->translate('i.edit'); ?>" style="text-decoration:none"><?php echo($invrecurring->getInv()->getNumber() ? $invrecurring->getInv()->getNumber() : $invrecurring->getInv_id()); ?></a></td>   
      <td><?= Html::a($invrecurring->getInv()->getClient()->getClient_name(),$urlGenerator->generate('client/view',['id'=>$invrecurring->getInv()->getClient()->getClient_id()])); ?></td>         
      <td><?= Html::encode(($invrecurring->getStart())->format('Y-m-d') !== '-0001-11-30' 
                            ? ($invrecurring->getStart())->format($datehelper->style()) : ''); ?></td>
      <td><?= Html::encode(($invrecurring->getEnd())->format('Y-m-d') !== '-0001-11-30'
                            ? ($invrecurring->getEnd())->format($datehelper->style()) : ''); ?></td>
      <td><?= Html::encode($translator->translate($recur_frequencies[$invrecurring->getFrequency()])); ?></td>
      <!-- If the next_date has a date then the invoice is still recurring and therefore active. -->
      <td><?= Html::encode($no_next ? '' : (($invrecurring->getNext())->format('Y-m-d') !=='-0001-11-30'
                                            ? ($invrecurring->getNext())->format($datehelper->style()) : '')); ?></td>
      <td>
          <div class="options btn-group">
          <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="fa fa-cog"></i>
                <?= $translator->translate('i.options'); ?>
          </a>
          <ul class="dropdown-menu">
              <li>
                <?php if (!$no_next) { ?>  
                  <a href="<?= $urlGenerator->generate('invrecurring/stop',['id'=>$invrecurring->getId()]); ?>" style="text-decoration:none"                    
                  ><i class="fa fa-edit fa-margin"></i>
                       <?= $translator->translate('i.stop'); ?>
                  </a>
                <?php } ?>  
              </li>
              <li>
                  <a href="<?= $urlGenerator->generate('invrecurring/edit',['id'=>$invrecurring->getId()]); ?>" style="text-decoration:none">                       <i class="fa fa-trash fa-margin"></i>
                       <?= $translator->translate('i.edit'); ?>
                  </a>
              </li>
              <li>
                  <a href="<?= $urlGenerator->generate('invrecurring/delete',['id'=>$invrecurring->getId()]); ?>" style="text-decoration:none">                       <i class="fa fa-trash fa-margin"></i>
                       <?= $translator->translate('i.delete'); ?>
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
