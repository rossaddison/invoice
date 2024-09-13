<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @see search resources/views/invoice/inv/index #create-recurring-multiple which triggers this modal and corresponds to the id on this div
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $recur_frequencies
 * @var string $csrf
 */
    
?>

<div id="create-recurring-multiple" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?= $translator->translate('i.create_recurring'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <div class="form-group">
                        <label for="recur_frequency"><?= $translator->translate('i.every'); ?></label>
                        <select name="recur_frequency" id="recur_frequency" class="form-control">
                            <?php
                                /**
                                 * @var string $key
                                 * @var string $lang
                                 */
                                foreach ($recur_frequencies as $key => $lang) { ?>
                                <option value="<?php echo $key; ?>">
                                    <?= $translator->translate($lang); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group has-feedback">
                        <label for="recur_start_date"><?= $translator->translate('i.start_date'); ?></label>
                        <div class="input-group">
                            <input name="recur_start_date" id="recur_start_date" class="form-control" type="date" role="presentation" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="recur_next_date" class="label-info"><?= $translator->translate('i.start'). '➕'. 
                                                                            $translator->translate('i.every') .'🟰'.
                                                                            $translator->translate('i.next'); ?></label>
                    </div>
                    <div class="form-group has-feedback">
                        <label for="recur_end_date"><?= $translator->translate('i.end_date'); ?> (<?= $translator->translate('i.optional'); ?>)</label>
                        <div class="input-group">
                            <input name="recur_end_date" id="recur_end_date" class="form-control" type="date" role="presentation" autocomplete="off">
                        </div>
                    </div>
                </form>    
            </div>
            <div class="modal-footer">
                <div class="btn-group">
                    <!-- inv.js => create_recurring_confirm_multiple => invrecurring/multiple -->
                    <button class="create_recurring_confirm_multiple btn btn-success" id="create_recurring_confirm_multiple" type="button">
                        <i class="fa fa-check"></i> <?= $translator->translate('i.submit'); ?>
                    </button>
                    <button class="btn btn-danger" type="button" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> <?= $translator->translate('i.cancel'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

