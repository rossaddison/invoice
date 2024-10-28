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
            <a href="<?= $urlGenerator->generate('client/index'); ?>" title="<?= $translator->translate('i.clients'); ?>"
               class="tip" data-bs-placement="right">
                <i class="bi bi-people"></i>
            </a>
        </li>
        <li>
            <a href="<?= $urlGenerator->generate('quote/index'); ?>" title="<?= $translator->translate('i.quotes'); ?>"
               class="tip" data-bs-placement="right">
                <i class="fa fa-file"></i>
            </a>
        </li>
        <li>
            <a href="<?= $urlGenerator->generate('inv/index'); ?>" title="<?= $translator->translate('i.invoices'); ?>"
               class="tip" data-bs-placement="right">
                <i class="fa fa-file-text"></i>
            </a>
        </li>
        <li>
            <a href="<?= $urlGenerator->generate('payment/index'); ?>" title="<?= $translator->translate('i.payments'); ?>"
               class="tip" data-bs-placement="right">
                <i class="bi bi-coin"></i>
            </a>
        </li>
        <li>
            <a href="<?= $urlGenerator->generate('product/index'); ?>" title="<?= $translator->translate('i.products'); ?>"
               class="tip" data-bs-placement="right">
                <i class="fa fa-database"></i>
            </a>
        </li>
        <?php if ($s->getSetting('projects_enabled') == 1) : ?>
            <li>
                <a href="<?= $urlGenerator->generate('task/index'); ?>" title="<?= $translator->translate('i.tasks'); ?>"
                   class="tip" data-bs-placement="right">
                    <i class="fa fa-check-square-o"></i>
                </a>
            </li>
        <?php endif; ?>
        <li>
            <a href="<?= $urlGenerator->generate('setting/tab_index'); ?>" title="<?= $translator->translate('i.system_settings'); ?>"
               class="tip" data-bs-placement="right">
                <i class="fa fa-cogs"></i>
            </a>
        </li>        
    </ul>
</div>
