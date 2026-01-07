<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var array<string, string> $errors
 * @var string $actionName
 * @var string $csrf
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

if ($errors) {
    /**
     * @var string $field
     * @var string $error
     */
    foreach ($errors as $field => $error) {
        echo Alert::widget()
             ->variant(AlertVariant::DANGER)
             ->body($field . ':' . $error, true)
             ->dismissable(true)
             ->render();
    }
}
?>
<div class="container mt-4">
    <div class="card border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><?= $translator->translate('edit'); ?> Peppol <?= $translator->translate('data'); ?></h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= $urlGenerator->generate($actionName, $actionArguments); ?>">
                <?= Html::hiddenInput('_csrf', $csrf); ?>
                
                <div class="mb-3">
                    <label for="peppol_po_itemid" class="form-label">
                        <?= $translator->translate('peppol'); ?> PO Item ID
                    </label>
                    <?= Html::textInput('peppol_po_itemid', Html::encode($body['peppol_po_itemid'] ?? ''), [
                        'id' => 'peppol_po_itemid',
                        'class' => 'form-control',
                        'maxlength' => 8,
                        'placeholder' => 'Max 8 characters'
                    ]); ?>
                    <small class="form-text text-muted">
                        Buyers Item Identification (cac:BuyersItemIdentification/cbc:ID)
                    </small>
                </div>

                <div class="mb-3">
                    <label for="peppol_po_lineid" class="form-label">
                        <?= $translator->translate('peppol'); ?> PO Line ID
                    </label>
                    <?= Html::textInput('peppol_po_lineid', Html::encode($body['peppol_po_lineid'] ?? ''), [
                        'id' => 'peppol_po_lineid',
                        'class' => 'form-control',
                        'maxlength' => 8,
                        'placeholder' => 'Max 8 characters'
                    ]); ?>
                    <small class="form-text text-muted">
                        Order Line Reference (cac:OrderLineReference/cbc:LineID)
                    </small>
                </div>

                <div class="border border-secondary rounded p-3 text-center">
                    <?= Html::submitButton(
                        $translator->translate('save'),
                        ['class' => 'btn btn-success btn-lg']
                    ); ?>
                </div>
            </form>
        </div>
    </div>
</div>