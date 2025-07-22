<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Related logic: see id="quote-to-quote" triggered by <a href="#quote-to-quote" data-bs-toggle="modal"  style="text-decoration:none">
 * Related logic: see views/quote/view.php
 *
 * @var App\Invoice\Entity\Quote $quote
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $clients
 * @var array $taxRates
 * @var string $csrf
 */
?>

<div id="quote-to-quote" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?= $translator->translate('copy.quote'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="user_id" id="user_id" value="<?= $quote->getUser_id(); ?>">
                    <div class="form-group">
                        <label for="create_quote_client_id"><?= $translator->translate('client'); ?></label>
                        <select name="create_quote_client_id" id="create_quote_client_id" class="form-control">
                            <option value="<?= $quote->getClient()?->getClient_id(); ?>"><?= $quote->getClient()?->getClient_name() ?? '#'; ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\Client $client
                                     */
                                    foreach ($clients as $client) { ?>
                                    <option value="<?= $client->getClient_id(); ?>">
                                        <?= Html::encode($client->getClient_name()); ?>
                                    </option>
                                <?php } ?>
                        </select>          
                    </div>
                </form>    
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $translator->translate('cancel'); ?></button>
                <button type="button" class="quote_to_quote_confirm btn btn-success" id="quote_to_quote_confirm">
                    <i class="fa fa-check"></i> <?= $translator->translate('submit'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

