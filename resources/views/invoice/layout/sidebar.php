<?php

declare(strict_types=1);

/**
 * @var App\Invoice\Setting\SettingRepository  $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 */
?>

<div class="sidebar hidden-xs">
    <ul>
        <li>
            <a href="<?php echo $urlGenerator->generate('client/index'); ?>" title="<?php echo $translator->translate('clients'); ?>"
               class="tip" data-bs-placement="right">
                <i class="bi bi-people"></i>
            </a>
        </li>
        <li>
            <a href="<?php echo $urlGenerator->generate('quote/index'); ?>" title="<?php echo $translator->translate('quotes'); ?>"
               class="tip" data-bs-placement="right">
                <i class="fa fa-file"></i>
            </a>
        </li>
        <li>
            <a href="<?php echo $urlGenerator->generate('inv/index'); ?>" title="<?php echo $translator->translate('invoices'); ?>"
               class="tip" data-bs-placement="right">
                <i class="fa fa-file-text"></i>
            </a>
        </li>
        <li>
            <a href="<?php echo $urlGenerator->generate('payment/index'); ?>" title="<?php echo $translator->translate('payments'); ?>"
               class="tip" data-bs-placement="right">
                <i class="bi bi-coin"></i>
            </a>
        </li>
        <li>
            <a href="<?php echo $urlGenerator->generate('product/index'); ?>" title="<?php echo $translator->translate('products'); ?>"
               class="tip" data-bs-placement="right">
                <i class="fa fa-database"></i>
            </a>
        </li>
        <?php if (1 == $s->getSetting('projects_enabled')) { ?>
            <li>
                <a href="<?php echo $urlGenerator->generate('task/index'); ?>" title="<?php echo $translator->translate('tasks'); ?>"
                   class="tip" data-bs-placement="right">
                    <i class="fa fa-check-square-o"></i>
                </a>
            </li>
        <?php } ?>
        <li>
            <a href="<?php echo $urlGenerator->generate('setting/tab_index'); ?>" title="<?php echo $translator->translate('system.settings'); ?>"
               class="tip" data-bs-placement="right">
                <i class="fa fa-cogs"></i>
            </a>
        </li>        
    </ul>
</div>
