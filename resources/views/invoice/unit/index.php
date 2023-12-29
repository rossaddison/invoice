<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use App\Widget\OffsetPagination;

/**
 * @var \App\Invoice\Entity\Unit $units
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Yii\View\Csrf $csrf
 */

echo $alert;

?>

<div id="headerbar">
    <h1 class="headerbar-title"><?= $translator->translate('i.units'); ?></h1>

    <div class="headerbar-item pull-right">
        <a class="btn btn-sm btn-primary" href="<?= $urlGenerator->generate('unit/add'); ?>">
            <i class="fa fa-plus"></i> <?= $translator->translate('i.new'); ?>
        </a>
    </div>

    <?php
      $pagination = OffsetPagination::widget()
      ->paginator($paginator)
      ->urlGenerator(fn ($page) => $urlGenerator->generate('family/index', ['page' => $page]));
    ?>
    <?php 
        if ($pagination->isRequired()) {
           echo $pagination;
        }
    ?>
</div>

<div id="content" class="table-content">

    <div class="table-responsive">
        <table class="table table-hover table-striped">

            <thead>
            <tr>
                <th><?= $translator->translate('i.unit_name'); ?></th>
                <th><?= $translator->translate('i.unit_name_plrl'); ?></th>
                <th><?= $translator->translate('i.options'); ?></th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($units as $unit) { ?>
                <tr>
                    <td><?= Html::encode($unit->getUnit_name()); ?></td>
                    <td><?= Html::encode($unit->getUnit_name_plrl()); ?></td>
                    <td>
                        <div class="options btn-group">
                            <a class="btn btn-default btn-sm dropdown-toggle"
                               data-toggle="dropdown" href="#">
                                <i class="fa fa-cog"></i> <?= $translator->translate('i.options'); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="<?= $urlGenerator->generate('unit/edit', ['id' => $unit->getUnit_id()]); ?>" style="text-decoration:none">
                                        <i class="fa fa-edit fa-margin"></i> <?= $translator->translate('i.edit'); ?>
                                    </a>
                                </li>
                                <?php if ($upR->repoUnitCount((string)$unit->getUnit_id()) === 0 ) { ?>
                                <li>
                                    <a href="<?= $urlGenerator->generate('unitpeppol/add', ['unit_id' => $unit->getUnit_id()]); ?>" style="text-decoration:none">
                                        <i class="fa fa-plus fa-margin"></i><?= $translator->translate('invoice.unit.peppol.add'); ?>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if ($upR->repoUnitCount((string)$unit->getUnit_id()) > 0 ) { ?>
                                <li>
                                    <a href="<?= $urlGenerator->generate('unitpeppol/edit', ['id' => $unit->getUnit_id()]); ?>" style="text-decoration:none">
                                        <i class="fa fa-edit fa-margin"></i> <?= $translator->translate('invoice.unit.peppol.edit'); ?>
                                    </a>
                                </li>
                                <?php } ?>
                                    <li>
                                    <form action="<?= $urlGenerator->generate('unit/delete', ['id' => $unit->getUnit_id()]); ?>" style="text-decoration:none"
                                          method="POST">
                                        <input type="hidden" name="_csrf" value="<?= $csrf; ?>">
                                        <button type="submit" class="dropdown-button"
                                                onclick="return confirm('<?= $translator->translate('i.delete_record_warning'); ?>');">
                                            <i class="fa fa-trash-o fa-margin"></i> <?= $translator->translate('i.delete'); ?>
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>

        </table>

    </div>

</div>
