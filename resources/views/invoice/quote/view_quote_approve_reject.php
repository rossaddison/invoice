<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

use App\Widget\LabelSwitch;

/**
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 * @var array $quoteStatuses draft 1 ... sent 2 ... viewed 3 ... approved 4 ... rejected 5 ... cancelled 6
 * @var bool $invEdit
 * @var string $sales_order_number
 */
?>
<div class="quote-properties">
    <label for="status_id">
    	<?= $translator->translate('status'); ?>
    </label>
    <select name="status_id" id="status_id" disabled class="form-control form-control-lg">
        <?php
            /**
             * @var string $key
             * @var array $status
             * @var string $status['label']
             */
            foreach ($quoteStatuses as $key => $status) { ?>
            <option value="<?php echo $key; ?>" <?php if ($key === $body['status_id']) {
                $s->checkSelect(Html::encode($body['status_id'] ?? ''), $key);
            } ?>>
                <?= Html::encode($status['label']); ?>
            </option>
        <?php } ?>
    </select>
</div>
<div class="quote-properties">
    <label for="quote_password" hidden>
        <?= $translator->translate('quote.password'); ?>
</label>
<input type="text" id="quote_password" class="form-control form-control-lg" disabled value="<?= Html::encode($body['password'] ?? ''); ?>" hidden>
</div>

<?php
    // draft => show the url
    if ($quote->getStatusId() == 1)
    { ?>
    <div class="quote-properties">
        <label for="quote_guest_url" hidden><?php echo $translator->translate('guest.url'); ?></label>
        <div class="input-group" hidden>
            <input type="text" id="quote_guest_url" disabled class="form-control form-control-lg" value="<?= $quote->getUrlKey(); ?>">
            <span class="input-group-text to-clipboard cursor-pointer"
                  data-clipboard-target="#quote_guest_url">
                <i class="bi bi-clipboard"></i>
            </span>
        </div>
    </div>
<?php } ?>
<?php
    // sent 2 or viewed 3 or rejected 5 AND no sales order => approve before transferring to sales order
    // if there was a sales order associated with it, we would not be able to approve it since it has been approved already
    if (($quote->getStatusId() === 2 ||
         $quote->getStatusId() === 3 ||
         $quote->getStatusId() === 5)  &&
         !$invEdit && ($quote->getSoId() === '0' || empty($quote->getSoId())))
    { ?>
    <div>
        <br>
        <a href="<?= $urlGenerator->generate('quote/urlKey', ['url_key' => $quote->getUrlKey()]); ?>" class="btn btn-success">
            <?= $translator->translate('approve.this.quote') ; ?></i>
        </a>
    </div>
<?php } ?>
<?php
    // sent 2 or viewed 3 or approved 4 AND user not permission to edit AND no sales order => can be rejected by user
    // if there was a sales order associated with it we would not be able to reject it
    if (($quote->getStatusId() === 2 ||
         $quote->getStatusId() === 3 ||
         $quote->getStatusId() === 4)  &&
         !$invEdit && ($quote->getSoId() === '0' || empty($quote->getSoId())))
    { ?>
    <div>
        <br>
        <a href="<?= $urlGenerator->generate('quote/urlKey', ['url_key' => $quote->getUrlKey()]); ?>" class="btn btn-danger">
            <?= $translator->translate('reject.this.quote') ; ?></i>
        </a>
    </div>
<?php } ?>
<input type="text" id="dropzone_client_id" readonly  hidden class="form-control form-control-lg" value="<?= $quote->getClient()?->getClientId(); ?>">
<?php
    // the quote has already been approved because it has a sales order number associated with it => it can only be viewed
    if ($quote->getSoId())
    { ?>
    <div has-feedback">
        <label for="salesorder_to_url"><?= $translator->translate('salesorder'); ?></label>
        <div class="input-group">
    	    <?= Html::a($sales_order_number, $urlGenerator->generate('salesorder/view', ['id' => $quote->getSoId()]), ['class' => 'btn btn-success']); ?>
        </div>
	</div>
<?php } ?>
