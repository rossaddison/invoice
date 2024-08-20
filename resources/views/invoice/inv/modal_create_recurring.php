<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
* @var App\Invoice\Helpers\DateHelper $dateHelper
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
* @var array $recur_frequencies
* @var string $csrf
*/
    
?>

<div id="create-recurring-inv" class="modal" tabindex="-1">
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
                            <input name="recur_start_date" id="recur_start_date" class="form-control input-sm datepicker" disabled role="presentation" autocomplete="off">
                            <span class="input-group-text">
                                <i class="fa fa-calendar fa-fw"></i>
                            </span>
                        </div>
                    </div>
                    <div class="form-group has-feedback">
                        <label for="recur_end_date"><?= $translator->translate('i.end_date'); ?> (<?= $translator->translate('i.optional'); ?>)</label>
                        <div class="input-group">
                            <input name="recur_end_date" id="recur_end_date" class="form-control input-sm datepicker" role="presentation" autocomplete="off">
                            <span class="input-group-text">
                                <i class="fa fa-calendar fa-fw"></i>
                            </span>
                        </div>
                    </div>
                </form>    
            </div>
            <div class="modal-footer">
                <div class="btn-group">
                    <!-- inv.js => create_recurring_confirm => invrecurring/create_recurring_confirm -->
                    <button class="create_recurring_confirm btn btn-success" id="create_recurring_confirm" type="button">
                        <i class="fa fa-check"></i> <?= $translator->translate('i.submit'); ?>
                    </button>
                    <button class="btn btn-danger" type="button" data-bs-dismiss"modal">
                        <i class="fa fa-times"></i> <?= $translator->translate('i.cancel'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<div>     
<?php $js14 = "$(function () {".
        '$("#recur_end_date.form-control.input-sm.datepicker").datepicker({dateFormat:"'.$dateHelper->display().'"});'.
      '});';
      echo Html::script($js14)->type('module');
?>
</div>

