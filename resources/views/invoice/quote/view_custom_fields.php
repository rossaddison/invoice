<?php
    declare(strict_types=1);
    
    use Yiisoft\Html\Html;
?>

<?php if ($custom_fields): ?>
                    <div>
                        <div class="mb-3 form-group">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <?= $translator->translate('i.custom_fields'); ?>
                                </div>
                                <div>
                                    <?= Html::openTag('div', ['class' => 'row']); ?>
                                        <div class="form-group">
                                            <?php $i = 0; ?>
                                            <?php foreach ($custom_fields as $custom_field): ?>
                                                <?php if ($custom_field->getLocation() != 0) {
                                                    continue;
                                                } ?>
                                                <?php $i++; ?>
                                                <?php if ($i % 2 != 0): ?>
                                                    <?= $cvH->print_field_for_view($custom_field, $quoteCustomForm, $quote_custom_values, $custom_values); ?>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="form-group">
                                            <?php $i = 0; ?>
                                            <?php foreach ($custom_fields as $custom_field): ?>
                                                <?php if ($custom_field->getLocation() != 0) {
                                                    continue;
                                                } ?>
                                                <?php $i++; ?>
                                                <?php if ($i % 2 == 0): ?>
                                                    <?= $cvH->print_field_for_view($custom_field, $quoteCustomForm, $quote_custom_values, $custom_values); ?>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
<?php endif; ?>