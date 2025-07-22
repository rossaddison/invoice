<?php

declare(strict_types=1);

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

?>

<div class="sidebar hidden-xs">
    <ul>
        <li>
            <a href="<?= $urlGenerator->generate('inv/guest'); ?>" title="<?= $translator->translate('clients'); ?>"
               class="tip" data-placement="right">
                <i class="fa fa-users"></i>
            </a>
        </li>
        <li>
            <a href="<?= $urlGenerator->generate('inv/guest'); ?>" title="<?= $translator->translate('quotes'); ?>"
               class="tip" data-placement="right">
                <i class="fa fa-file"></i>
            </a>
        </li>
        <li>
            <a href="<?= $urlGenerator->generate('inv/guest'); ?>" title="<?= $translator->translate('invoices'); ?>"
               class="tip" data-placement="right">
                <i class="fa fa-file-text"></i>
            </a>
        </li>
        <li>
            <a href="<?= $urlGenerator->generate('inv/guest'); ?>" title="<?= $translator->translate('payments'); ?>"
               class="tip" data-placement="right">
                <i class="fa fa-money"></i>
            </a>
        </li>
    </ul>
</div>
