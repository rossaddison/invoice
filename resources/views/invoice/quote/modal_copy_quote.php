<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/*
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
               <h5 class="modal-title"><?php echo $translator->translate('copy.quote'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="user_id" id="user_id" value="<?php echo $quote->getUser_id(); ?>">
                    <div class="form-group">
                        <label for="create_quote_client_id"><?php echo $translator->translate('client'); ?></label>
                        <select name="create_quote_client_id" id="create_quote_client_id" class="form-control">
                            <option value="<?php echo $quote->getClient()?->getClient_id(); ?>"><?php echo $quote->getClient()?->getClient_name() ?? '#'; ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\Client $client
                                     */
                                    foreach ($clients as $client) { ?>
                                    <option value="<?php echo $client->getClient_id(); ?>">
                                        <?php echo Html::encode($client->getClient_name()); ?>
                                    </option>
                                <?php } ?>
                        </select>          
                    </div>
                </form>    
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $translator->translate('cancel'); ?></button>
                <button type="button" class="quote_to_quote_confirm btn btn-success" id="quote_to_quote_confirm">
                    <i class="fa fa-check"></i> <?php echo $translator->translate('submit'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

