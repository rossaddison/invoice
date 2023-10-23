<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
?>
<div class="row">
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('invoice.invoice.storecove'); ?>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[storecove_country]" <?= $s->where('storecove_country'); ?>>
                                <?= Html::a($translator->translate('invoice.storecove.create.a.sender.legal.entity.country'), 'https://www.storecove.com/docs/#_create_a_sender', ['style' => 'text-decoration:none']); ?>
                            </label>
                            <?php $body['settings[storecove_country]'] = $s->get_setting('storecove_country'); ?>
                            <select name="settings[storecove_country]" id="settings[storecove_country]"
                                    class="form-control">
                                        <?php foreach ($countries as $cldr => $country) { ?>
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
                                <?= $translator->translate('invoice.storecove.legal.entity.id.for.json'); ?>
                            </label>
                            <?php $body['settings[storecove_legal_entity_id]'] = $s->get_setting('storecove_legal_entity_id'); ?>
                            <input type="text" name="settings[storecove_legal_entity_id]" id="storecove_legal_entity_id"
                                   class="form-control" required
                                   value="<?= $body['settings[storecove_legal_entity_id]']; ?>">
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[storecove_sender_identifier]" <?= $s->where('storecove_sender_identifier'); ?> >
                                <?= $translator->translate('invoice.storecove.sender.identifier'); ?>
                            </label>
                            <?php $body['settings[storecove_sender_identifier]'] = $s->get_setting('storecove_sender_identifier'); ?>
                            <select name="settings[storecove_sender_identifier]" id="settings[storecove_sender_identifier]" class="form-control">

                                <?php
                                /**
                                 * @var int $key
                                 * @var array $value
                                 */
                                foreach ($sender_identifier_array as $key => $value) {
                                    ?>

                                    <option value="<?= $key; ?>" <?php $s->check_select($body['settings[storecove_sender_identifier]'], $key) ?>>
                                        <?=
                                        ucfirst($value['Region']
                                                . str_repeat("&nbsp;", 2)
                                                . str_repeat("-", 10)
                                                . str_repeat("&nbsp;", 2) .
                                                $value['Country']
                                                . str_repeat("&nbsp;", 2)
                                                . str_repeat("-", 10)
                                                . str_repeat("&nbsp;", 2) .
                                                (!empty($value['Legal']) ? $value['Legal'] : $translator->translate('invoice.storecove.not.available'))
                                                . str_repeat("&nbsp;", 2)
                                                . str_repeat("-", 10)
                                                . str_repeat("&nbsp;", 2) .
                                                (!empty($value['Tax']) ? $value['Tax'] : $translator->translate('invoice.storecove.not.available'))
                                        );
                                        ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <br>
                            <label for="storecove_sender_identifier_basis" <?= $s->where('storecove_sender_identifier_basis'); ?>>
                                <?= $translator->translate('invoice.storecove.sender.identifier.basis'); ?>
                            </label>
                            <?php $body['settings[storecove_sender_identifier_basis]'] = $s->get_setting('storecove_sender_identifier_basis'); ?>
                            <select name="settings[storecove_sender_identifier_basis]" class="form-control"
                                    id="storecove_sender_identifier_basis" data-minimum-results-for-search="Infinity">
                                <option value="Legal">
                                    <?= $translator->translate('invoice.storecove.legal'); ?>
                                </option>
                                <option value="Tax"
                                <?php
                                $s->check_select($body['settings[storecove_sender_identifier_basis]'], $translator->translate('invoice.storecove.tax'));
                                ?>>
                                        <?= $translator->translate('invoice.storecove.tax'); ?>
                                </option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>