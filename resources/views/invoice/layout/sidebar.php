<?php
    declare(strict_types=1); 
?>

<div class="sidebar hidden-xs">
    <ul>
        <li>
            <a href="<?= $urlGenerator->generate('client/index'); ?>" title="<?= $s->trans('clients'); ?>"
               class="tip" data-placement="right">
                <i class="fa fa-users"></i>
            </a>
        </li>
        <li>
            <a href="<?= $urlGenerator->generate('quote/index'); ?>" title="<?= $s->trans('quotes'); ?>"
               class="tip" data-placement="right">
                <i class="fa fa-file"></i>
            </a>
        </li>
        <li>
            <a href="<?= $urlGenerator->generate('inv/index'); ?>" title="<?= $s->trans('invoices'); ?>"
               class="tip" data-placement="right">
                <i class="fa fa-file-text"></i>
            </a>
        </li>
        <li>
            <a href="<?= $urlGenerator->generate('payment/index'); ?>" title="<?= $s->trans('payments'); ?>"
               class="tip" data-placement="right">
                <i class="fa fa-money"></i>
            </a>
        </li>
        <li>
            <a href="<?= $urlGenerator->generate('product/index'); ?>" title="<?= $s->trans('products'); ?>"
               class="tip" data-placement="right">
                <i class="fa fa-database"></i>
            </a>
        </li>
        <?php if ($s->get_setting('projects_enabled') == 1) : ?>
            <li>
                <a href="<?= $urlGenerator->generate('task/index'); ?>" title="<?= $s->trans('tasks'); ?>"
                   class="tip" data-placement="right">
                    <i class="fa fa-check-square-o"></i>
                </a>
            </li>
        <?php endif; ?>
        <li>
            <a href="<?= $urlGenerator->generate('setting/tab_index'); ?>" title="<?= $s->trans('system_settings'); ?>"
               class="tip" data-placement="right">
                <i class="fa fa-cogs"></i>
            </a>
        </li>
    </ul>
</div>
