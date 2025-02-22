<?php
    declare(strict_types=1);
    
    use Yiisoft\Html\Tag\I;
    
    /**
     * @var App\Invoice\Setting\SettingRepository $s 
     * @var Yiisoft\Translator\TranslatorInterface $translator
     * @var array $body
     */
?>
<div class = 'row'>
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= I::tag()->addClass('bi bi-bootstrap')->render(); ?>
            </div>
            <div class="panel-body">
                <div class = 'row'>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <div class="checkbox">
                                <?php $body['settings[bootstrap5_offcanvas_enable]'] = $s->getSetting('bootstrap5_offcanvas_enable');?>
                                <label <?= $s->where('bootstrap5_offcanvas_enable'); ?>">
                                    <input type="hidden" name="settings[bootstrap5_offcanvas_enable]" value="0">
                                    <input type="checkbox" name="settings[bootstrap5_offcanvas_enable]" value="1"
                                        <?php $s->check_select($body['settings[bootstrap5_offcanvas_enable]'], 1, '==', true) ?>>
                                    <?= $translator->translate('invoice.invoice.bootstrap5.offcanvas.enable'); ?>
                                </label>
                            </div>
                             <div class="col-xs-12 col-md-6">
                                <div class="form-group">
                                    <label for="settings[bootstrap5_offcanvas_placement]" <?= $s->where('bootstrap5_offcanvas_placement'); ?> >
                                        <?= $translator->translate('invoice.invoice.bootstrap5.offcanvas.placement'); ?>
                                    </label>
                                    <?php $body['settings[bootstrap5_offcanvas_placement]'] = $s->getSetting('bootstrap5_offcanvas_placement'); ?>
                                    <select name="settings[bootstrap5_offcanvas_placement]" id="settings[bootstrap5_offcanvas_placement]" class="form-control">
                                        <option value="0"><?= $translator->translate('i.none'); ?></option>
                                        <?php
                                            $placements = ['top', 'bottom', 'start', 'end'];
                                            /**
                                             * @var string $placement
                                             */
                                            foreach ($placements as $placement) { ?>
                                            <option value="<?= $placement; ?>" <?php $s->check_select($body['settings[bootstrap5_offcanvas_placement]'], $placement) ?>>
                                                <?= ucfirst($placement); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>     
                        </div>
                    </div>
                </div>    
            </div>    
        </div>
    </div>
</div>
