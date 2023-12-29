<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use App\Widget\OffsetPagination;

/**
 * @var \App\Invoice\Entity\TaxRate $taxrate
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

 echo $alert;
?>
<div>
 <h5><?= $translator->translate('i.tax_rate');?></h5>
 <a class="btn btn-success" href="<?= $urlGenerator->generate('taxrate/add'); ?>">
      <i class="fa fa-plus"></i> <?= $translator->translate('i.new'); ?> </a></div>

<?php
    $pagination = OffsetPagination::widget()
    ->paginator($paginator)
    ->urlGenerator(fn ($page) => $urlGenerator->generate('taxrate/index', ['page' => $page]));
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
        <th><?= $translator->translate('i.tax_rate_name'); ?></th>
        <th><?= $translator->translate('i.tax_rate_percent'); ?></th>
        <th><?= $translator->translate('invoice.peppol.tax.rate.code'); ?></th>
        <th><?= $translator->translate('invoice.storecove.tax.rate.code'); ?></th>
        <th><?= $translator->translate('invoice.default'); ?></th>
        <th><?= $translator->translate('i.options'); ?></th>
    </tr>
   </thead>
<tbody>

<?php foreach ($paginator->read() as $taxrate) { ?>
     <tr>
                
      <td><?= Html::encode($taxrate->getTax_rate_name()); ?></td>
      <td><?= Html::encode($taxrate->getTax_rate_percent()); ?></td>
      <td><?= Html::encode($taxrate->getPeppol_tax_rate_code()); ?></td>
      <td><?= Html::encode(ucfirst(str_replace('_', ' ', $taxrate->getStorecove_tax_type()))); ?></td>
      <td><?= ($taxrate->getTax_rate_default()) ? '<span class="label active">' . $translator->translate('i.yes') . '</span>' : '<span class="label inactive">' . $translator->translate('i.no') . '</span>'; ?></td>          

        <td>
          <div class="options btn-group">
          <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="fa fa-cog"></i>
                <?= $translator->translate('i.options'); ?>
          </a>
          <ul class="dropdown-menu">
              <li>
                  <a href="<?= $urlGenerator->generate('taxrate/edit',['tax_rate_id'=>$taxrate->getTax_rate_id()]); ?>" style="text-decoration:none"><i class="fa fa-edit fa-margin"></i>
                       <?= $translator->translate('i.edit'); ?>
                  </a>
              </li>
              <li>
                  <a href="<?= $urlGenerator->generate('taxrate/view',['tax_rate_id'=>$taxrate->getTax_rate_id()]); ?>" style="text-decoration:none"><i class="fa fa-eye fa-margin"></i>
                       <?= $translator->translate('i.view'); ?>
                  </a>
              </li>
              <li>
                  <a href="<?= $urlGenerator->generate('taxrate/delete',['tax_rate_id'=>$taxrate->getTax_rate_id()]); ?>" style="text-decoration:none"><i class="fa fa-trash fa-margin"></i>
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
        sprintf($translator->translate('invoice.index.footer.showing').' taxrates', $pageSize, $paginator->getTotalItems()),
        ['class' => 'text-muted']
    );
    } else {
      echo Html::p($translator->translate('invoice.records.no'));
    }
?>
</div>

