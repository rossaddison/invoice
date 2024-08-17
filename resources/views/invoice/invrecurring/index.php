<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use App\Widget\OffsetPagination;

/**
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $recur_frequencies
 * @var string $alert
 */
 
 echo $alert;
?>
<div>
 <h1 class="headerbar-title"><?= $translator->translate('i.recurring_invoices'); ?></h1>
 <a class="btn btn-success" href="<?= $urlGenerator->generate('inv/index'); ?>">
      <i class="fa fa-arrow-left"></i> <?= $translator->translate('i.invoices'); ?> </a>
</div>

<?php
$pagination = OffsetPagination::widget()
->paginator($paginator)
->urlGenerator(fn (string $page) => $urlGenerator->generate('invrecurring/index', ['page' => $page])); 
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

<?php
     /**
      * @var App\Invoice\Entity\InvRecurring $invRecurring
      */
     foreach ($paginator->read() as $invRecurring) { ?>
     <?php 
        $no_next = null===$invRecurring->getNext() ? true : false;
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
      <td><a href="<?= $urlGenerator->generate('inv/view',['id'=>$invRecurring->getInv_id()]); ?>"  title="<?= $translator->translate('i.edit'); ?>" style="text-decoration:none"><?php echo(strlen($invRecurring->getInv()?->getNumber() ?? '') > 0 ? $invRecurring->getInv()?->getNumber() : $invRecurring->getInv_id()); ?></a></td>   
      <td><?= Html::a($invRecurring->getInv()?->getClient()?->getClient_name() ?? '#',$urlGenerator->generate('client/view',['id'=>$invRecurring->getInv()?->getClient()?->getClient_id()])); ?></td>         
      <td><?= Html::encode(!is_string($recurringStart = $invRecurring->getStart()) ? $recurringStart->format($dateHelper->style()) : ''); ?></td>
      <td><?= Html::encode(!is_string($recurringEnd = $invRecurring->getEnd()) ? $recurringEnd->format($dateHelper->style()) : ''); ?></td>
      <td><?= Html::encode($translator->translate((string)$recur_frequencies[$invRecurring->getFrequency()])); ?></td>
      <!-- If the next_date has a date then the invoice is still recurring and therefore active. -->
      <td><?= Html::encode($no_next ? '' : ((!is_string($recurringNext = $invRecurring->getNext())) ? $recurringNext?->format($dateHelper->style()) : '')); ?></td>
      <td>
          <div class="options btn-group">
          <a class="btn btn-default btn-sm dropdown-toggle" data-bs-toggle="dropdown" href="#">
                <i class="fa fa-cog"></i>
                <?= $translator->translate('i.options'); ?>
          </a>
          <ul class="dropdown-menu">
              <li>
                <?php if (!$no_next) { ?>  
                  <a href="<?= $urlGenerator->generate('invrecurring/stop', ['id' => $invRecurring->getId()]); ?>" style="text-decoration:none"                    
                  ><i class="fa fa-edit fa-margin"></i>
                       <?= $translator->translate('i.stop'); ?>
                  </a>
                <?php } ?>  
              </li>
              <li>
                  <a href="<?= $urlGenerator->generate('invrecurring/edit', ['id' => $invRecurring->getId()]); ?>" style="text-decoration:none"><i class="fa fa-pencil fa-margin"></i>
                       <?= $translator->translate('i.edit'); ?>
                  </a>
              </li>
              <li>
                  <a href="<?= $urlGenerator->generate('invrecurring/view', ['id' => $invRecurring->getId()]); ?>" style="text-decoration:none"><i class="fa fa-eye fa-margin"></i>
                       <?= $translator->translate('i.view'); ?>
                  </a>
              </li>
              <li>
                  <a href="<?= $urlGenerator->generate('invrecurring/delete', ['id' => $invRecurring->getId()]); ?>" style="text-decoration:none"><i class="fa fa-trash fa-margin"></i>
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
