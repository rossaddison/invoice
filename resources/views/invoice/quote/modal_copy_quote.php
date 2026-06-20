<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Related logic: see id="quote-to-quote" triggered by <a href="#quote-to-quote" data-bs-toggle="modal">
 * Related logic: see views/quote/view.php
 *
 * @var App\Infrastructure\Persistence\Quote\Quote $quote
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $clients
 * @var array $taxRates
 * @var string $csrf
 */

$currentClientId = $quote->reqClientId();

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
                    <input type="hidden" name="user_id" id="user_id" value="<?= $quote->reqUserId(); ?>">
                    <div class="mb-2">
                        <input type="text"
                               id="copy-quote-client-search"
                               class="form-control form-control-sm"
                               placeholder="<?= $translator->translate('search'); ?>…"
                               autocomplete="off">
                    </div>
                    <div id="copy-quote-client-list" style="max-height:280px;overflow-y:auto;">
                        <?php
                        /**
                         * @var App\Infrastructure\Persistence\Client\Client $client
                         */
                        foreach ($clients as $client):
                            $id        = $client->reqId();
                            $isCurrent = ($id === $currentClientId);
                        ?>
                        <div class="form-check">
                            <?php if ($isCurrent): ?>
                            <input type="hidden"
                                   name="copy_quote_client_ids[]"
                                   value="<?= $id ?>">
                            <?php endif; ?>
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="copy_quote_client_ids[]"
                                   value="<?= $id ?>"
                                   id="copy_quote_client_<?= $id ?>"
                                   <?= $isCurrent ? 'checked disabled' : '' ?>>
                            <label class="form-check-label" for="copy_quote_client_<?= $id ?>">
                                <?= Html::encode($client->getClientName()); ?>
                                <?php if ($isCurrent): ?>
                                <small class="text-muted">(<?= $translator->translate('current'); ?>)</small>
                                <?php endif; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?= $translator->translate('cancel'); ?>
                </button>
                <!-- quote.ts handleQuoteToQuoteConfirm → QuoteController quoteToQuoteConfirm -->
                <button type="button"
                        class="quote_to_quote_confirm btn btn-success"
                        id="quote_to_quote_confirm">
                    <i class="bi bi-check-lg"></i> <?= $translator->translate('submit'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
