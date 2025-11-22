<?php

declare(strict_types=1);

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

?>

<div class="sidebar hidden-xs">
    <ul>
        <li>
            <a href="<?= $urlGenerator->generate('client/index'); ?>" title="<?= $translator->translate('clients'); ?>"
               class="tip" data-bs-placement="right">
                <i class="bi bi-people"
                   aria-hidden="true"></i>
            </a>
        </li>
        <li>
            <a href="<?= $urlGenerator->generate('quote/index'); ?>" title="<?= $translator->translate('quotes'); ?>"
               class="tip" data-bs-placement="right">
                <i class="fa fa-file"
                   aria-hidden="true"></i>
            </a>
        </li>
        <li>
            <a href="<?= $urlGenerator->generate('inv/index'); ?>" title="<?= $translator->translate('invoices'); ?>"
               class="tip" data-bs-placement="right">
                <i class="fa fa-file-text"
                   aria-hidden="true"></i>
            </a>
        </li>
        <li>
            <a href="<?= $urlGenerator->generate('payment/index'); ?>"
               title="<?= $translator->translate('payments'); ?>"
               class="tip" data-bs-placement="right">
                <i class="bi bi-coin"
                   aria-hidden="true"></i>
            </a>
        </li>
        <li>
            <a href="<?= $urlGenerator->generate('product/index'); ?>"
               title="<?= $translator->translate('products'); ?>"
               class="tip" data-bs-placement="right">
                <i class="fa fa-database"
                   aria-hidden="true"></i>
            </a>
        </li>
        <?php if ($s->getSetting('projects_enabled') == 1) : ?>
            <li>
                <a href="<?= $urlGenerator->generate('task/index'); ?>"
                   title="<?= $translator->translate('tasks'); ?>"
                   class="tip" data-bs-placement="right">
                    <i class="fa fa-check-square-o"
                       aria-hidden="true"></i>
                </a>
            </li>
        <?php endif; ?>
        <li>
            <a href="<?= $urlGenerator->generate('setting/tab_index'); ?>"
               title="<?= $translator->translate('system.settings'); ?>"
               class="tip" data-bs-placement="right">
               <i class="fa fa-cogs"
                  aria-hidden="true"></i>
            </a>
        </li>
    </ul>
</div>
