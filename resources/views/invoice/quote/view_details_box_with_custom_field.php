<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Quote\QuoteForm $quoteForm
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $customFields
 * @var array $customValues
 * @var array $quoteCustomValues
 * @var string $vat
 */

?>

<div class="col-xs-12 col-md-6">
    <div class="quote-properties">
        <label for="quote_number">
        	<?= $translator->translate('quote'); ?> #
        </label>
            <input type="text" id="quote_number" class="form-control" readonly
                <?php if (null !== ($quote->getNumber())) : ?> value="<?= $quote->getNumber(); ?>"
                <?php else : ?> placeholder="<?= $translator->translate('not.set'); ?>"
                <?php endif; ?>>
    </div>
<div class="quote-properties has-feedback">
    <label for="quote_date_created">
        <?= $vat == '0' ? $translator->translate('date.issued') : $translator->translate('quote.date'); ?>
    </label>
    <div class="input-group">
        <input name="quote_date_created" id="quote_date_created" disabled
               class="form-control"
               value="<?= Html::encode($quote->getDate_created()->format('Y-m-d')); ?>"/>
        <span class="input-group-text">
            <i class="fa fa-calendar fa-fw"></i>
        </span>
    </div>
</div>
<div class="quote-properties has-feedback">
    <label for="quote_date_expires">
        <?= $translator->translate('expires'); ?>
    </label>
    <div class="input-group">
        <input name="quote_date_expires" id="quote_date_expires" readonly
               class="form-control"
               value="<?= Html::encode($quote->getDate_expires()->format('Y-m-d')); ?>">
        <span class="input-group-text">
            <i class="fa fa-calendar fa-fw"></i>
        </span>
    </div>
</div>
<div>
    <?php
        /**
         * @var App\Invoice\Entity\CustomField $customField
         */
        foreach ($customFields as $customField): ?>
        <?php if ($customField->getLocation() !== 1) {
            continue;
        } ?>
        <?php  $cvH->print_field_for_view($customField, $quoteForm, $quoteCustomValues, $customValues); ?>                                   
    <?php endforeach; ?>
</div>    
</div>
