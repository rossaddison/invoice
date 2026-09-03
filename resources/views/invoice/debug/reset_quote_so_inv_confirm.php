<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Related logic: see App\Invoice\InvoiceController function
 * resetQuoteSalesOrderInvConfirm/resetQuoteSalesOrderInv, and
 * App\Invoice\Setting\QuoteSalesOrderInvResetService.
 *
 * @var string $alerts
 * @var list<string> $tables
 * @var string $csrf
 * @var TranslatorInterface $translator
 * @var UrlGeneratorInterface $urlGenerator
 */

$cardHeader = ['class' => 'card-header bg-danger text-white'];
$warningBox = ['class' => 'alert alert-danger', 'role' => 'alert'];
$tableList  = ['class' => 'font-monospace small text-muted mb-3'];
?>

<div class="container py-5">
    <div class="row d-flex justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
            <?= $alerts ?>
            <div class="card border border-danger shadow-2-strong rounded-3">
                <div <?= H::renderTagAttributes($cardHeader) ?>>
                    <h1 class="fw-normal h4 mb-0">
                        &#9888; <?= H::encode($translator->translate('debug.reset.tree.title')) ?>
                    </h1>
                </div>
                <div class="card-body p-4">
                    <div <?= H::renderTagAttributes($warningBox) ?>>
                        <?= H::encode($translator->translate('debug.reset.tree.warning')) ?>
                    </div>

                    <p class="mb-1"><strong>
                        <?= H::encode($translator->translate('debug.reset.tree.tables.heading')) ?>
                    </strong></p>
                    <p <?= H::renderTagAttributes($tableList) ?>>
                        <?= H::encode(implode(', ', $tables)) ?>
                    </p>

                    <?= new Form()
                        ->post($urlGenerator->generate('invoice/resetQuoteSalesOrderInv'))
                        ->csrf($csrf)
                        ->id('resetQuoteSalesOrderInvForm')
                        ->open() ?>

                    <div class="mb-3">
                        <label for="db_password" class="form-label">
                            <?= H::encode($translator->translate('debug.reset.tree.db.password')) ?>
                        </label>
                        <?= H::input('password', 'db_password', null, [
                            'id' => 'db_password',
                            'class' => 'form-control',
                            'autocomplete' => 'off',
                            'required' => true,
                        ]) ?>
                    </div>

                    <button type="submit" class="btn btn-danger w-100 bi bi-exclamation-triangle-fill">
                        <?= ' ' . H::encode($translator->translate('debug.reset.tree.confirm.button')) ?>
                    </button>

                    <?= new Form()->close() ?>
                </div>
            </div>
        </div>
    </div>
</div>
