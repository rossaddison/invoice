<?php
declare(strict_types=1);

/**
 * @var App\Invoice\Entity\TaxRate $taxRate
 * @var App\Invoice\Entity\Unit $unit
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @psalm-var array<array-key, array<array-key, string>|string> $taxRates
 * @psalm-var array<array-key, array<array-key, string>|string> $units
 * @var string $csrf
 */
?>

<div id="generate-products-modal" class="modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">🏭 <?= $translator->translate('generate')
                    . ' ' .$translator->translate('products'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="generate-products-form">
                    <input type="hidden" name="_csrf" value="<?= $csrf; ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tax_rate_id" class="form-label">📊
                            <?= $translator->translate('tax.rate'); ?></label>
                            <select class="form-select" id="tax_rate_id" name="tax_rate_id" required>
                                <option value="">-- <?= $translator->translate('tax.rate'); ?> --</option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\TaxRate $taxRate
                                     */
                                    foreach ($taxRates as $taxRate): ?>
                                    <option value="<?= $taxRate->getTaxRateId(); ?>">
                                        <?= $taxRate->getTaxRateName() ?? ''; ?> (<?= $taxRate->getTaxRatePercent() ?? ''; ?>%)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="unit_id" class="form-label">📏
                             <?= $translator->translate('unit'); ?></label>
                            <select class="form-select" id="unit_id" name="unit_id" required>
                                <option value="">--
                                <?= $translator->translate('unit'); ?>
                                 --</option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\Unit $unit
                                     */
                                    foreach ($units as $unit): ?>
                                    <option value="<?= $unit->getUnit_id(); ?>">
                                        <?= $unit->getUnit_name(); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ <?= $translator->translate('cancel'); ?></button>
                <button type="button" class="btn btn-success" id="process-generate-products">✅ <?= $translator->translate('generate') . $translator->translate('products'); ?></button>
            </div>
        </div>
    </div>
</div>