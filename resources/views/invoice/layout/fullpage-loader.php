<?php

declare(strict_types=1);

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */

?>

<div id="fullpage-loader" style="display: none">
    <div class="loader-content">
        <i id="loader-icon" class="bi bi-gear-fill"></i>
        <div id="loader-error" style="display: none">
            <?= $translator->translate('loading.error'); ?><br/>
            <a href=""
               class="btn btn-primary btn-sm" target="_blank">
                <i class="bi bi-life-preserver"></i> <?= $translator->translate('loading.error.help'); ?>
            </a>
        </div>
    </div>
    <div class="text-right">
        <button type="button" class="fullpage-loader-close btn btn-link tip" aria-label="<?php echo $translator->translate('close'); ?>"
                title="<?= $translator->translate('close'); ?>" data-placement="left">
            <span aria-hidden="true"><i class="bi bi-x-lg"></i></span>
        </button>
    </div>
</div>
