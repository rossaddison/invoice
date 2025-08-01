<?php
declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 * @var array $countries
 * @var array $sender_identifier_array
 * @var string $cldr
 * @var string $country
 */
?>
<div class='row'>
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('storecove'); ?>
            </div>
            <div class="panel-body">
                <div class='row'>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[storecove_country]" <?= $s->where('storecove_country'); ?>>
                                <?= Html::a($translator->translate('storecove.create.a.sender.legal.entity.country'), 'https://www.storecove.com/docs/#_create_a_sender', ['style' => 'text-decoration:none']); ?>
                            </label>
                            <?php $body['settings[storecove_country]'] = $s->getSetting('storecove_country'); ?>
                            <select name="settings[storecove_country]" id="settings[storecove_country]"
                                    class="form-control">
                                        <?php
                                        /**
                                         * @var string $cldr
                                         * @var string $country
                                         */
                                        foreach ($countries as $cldr => $country) { ?>
                                    <option value="<?= $cldr; ?>"
                                    <?php $s->check_select($body['settings[storecove_country]'], $cldr); ?>>
                                            <?= $cldr . str_repeat("&nbsp;", 2) . str_repeat("-", 10) . str_repeat("&nbsp;", 2) . $country ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="storecove_legal_entity_id">
                                <?= $translator->translate('storecove.legal.entity.id.for.json'); ?>
                            </label>
                            <?php $body['settings[storecove_legal_entity_id]'] = $s->getSetting('storecove_legal_entity_id'); ?>
                            <input type="text" name="settings[storecove_legal_entity_id]" id="storecove_legal_entity_id"
                                   class="form-control" 
                                   value="<?= $body['settings[storecove_legal_entity_id]']; ?>">
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[storecove_sender_identifier]" <?= $s->where('storecove_sender_identifier'); ?> >
                                <?= $translator->translate('storecove.sender.identifier'); ?>
                            </label>
                            <?php $body['settings[storecove_sender_identifier]'] = $s->getSetting('storecove_sender_identifier'); ?>
                            <select name="settings[storecove_sender_identifier]" id="settings[storecove_sender_identifier]" class="form-control">

                                <?php
                                /**
                                 * @var array $value
                                 * @var string $key
                                 * @var string $value['Region']
                                 * @var string $value['Country']
                                 * @var string $value['Legal']
                                 * @var string $value['Tax']
                                 */
                                foreach ($sender_identifier_array as $key => $value) {
                                    ?>

                                    <option value="<?= $key; ?>" <?php $s->check_select($body['settings[storecove_sender_identifier]'], $key) ?>>
                                        <?=
                                        ucfirst(
                                            $value['Region']
                                                . str_repeat("&nbsp;", 2)
                                                . str_repeat("-", 10)
                                                . str_repeat("&nbsp;", 2) .
                                                $value['Country']
                                                . str_repeat("&nbsp;", 2)
                                                . str_repeat("-", 10)
                                                . str_repeat("&nbsp;", 2) .
                                                (!empty($value['Legal']) ? $value['Legal'] : $translator->translate('storecove.not.available'))
                                                . str_repeat("&nbsp;", 2)
                                                . str_repeat("-", 10)
                                                . str_repeat("&nbsp;", 2) .
                                                (!empty($value['Tax']) ? $value['Tax'] : $translator->translate('storecove.not.available')),
                                        );
                                    ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <br>
                            <label for="storecove_sender_identifier_basis" <?= $s->where('storecove_sender_identifier_basis'); ?>>
                                <?= $translator->translate('storecove.sender.identifier.basis'); ?>
                            </label>
                            <?php $body['settings[storecove_sender_identifier_basis]'] = $s->getSetting('storecove_sender_identifier_basis'); ?>
                            <select name="settings[storecove_sender_identifier_basis]" class="form-control"
                                    id="storecove_sender_identifier_basis" data-minimum-results-for-search="Infinity">
                                <option value="Legal">
                                    <?= $translator->translate('storecove.legal'); ?>
                                </option>
                                <option value="Tax"
                                <?php
                                $s->check_select($body['settings[storecove_sender_identifier_basis]'], $translator->translate('storecove.tax'));
?>>
                                        <?= $translator->translate('storecove.tax'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>