<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use App\Widget\OffsetPagination;

/**
 * @var \App\Invoice\Entity\Family $familys
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Yii\View\Csrf $csrf
 */

echo $alert;
?>


<div id="headerbar">
    <h1 class="headerbar-title"><?= $translator->translate('i.families'); ?></h1>

    <div class="headerbar-item pull-right">
        <a class="btn btn-sm btn-primary" href="<?= $urlGenerator->generate('family/add'); ?>">
            <i class="fa fa-plus"></i> <?= $translator->translate('i.new'); ?>
        </a>
    </div>
    
    <?php
      $pagination = OffsetPagination::widget()
      ->paginator($paginator)
      ->urlGenerator(fn ($page) => $urlGenerator->generate('family/index', ['page' => $page]));
    ?>
    <?php 
      if ($pagination->isPaginationRequired()) {
         echo $pagination;
      }
    ?> 
</div>

<div id="content" class="table-content">

    <div class="table-responsive">
        <table class="table table-hover table-striped">

            <thead>
            <tr>
                <th><?= $translator->translate('i.family_name'); ?></th>
                <th><?= $translator->translate('i.options'); ?></th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($familys as $family) { ?>
                <tr>
                    <td><?= Html::encode($family->getFamily_name()); ?></td>
                    <td>
                        <div class="options btn-group">
                            <a class="btn btn-default btn-sm dropdown-toggle"
                               data-toggle="dropdown" href="#">
                                <i class="fa fa-cog"></i> <?= $translator->translate('i.options'); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="<?= $urlGenerator->generate('family/edit', ['id' => $family->getFamily_id()]); ?>" style="text-decoration:none">
                                        <i class="fa fa-edit fa-margin"></i> <?= $translator->translate('i.edit'); ?>
                                    </a>
                                </li>
                                <li>
                                    <form action="<?= $urlGenerator->generate('family/delete',['id' => $family->getFamily_id()]); ?>"
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
