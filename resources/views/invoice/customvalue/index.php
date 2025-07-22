<?php

declare(strict_types=1);
use Yiisoft\Html\Html;

/*
 * @var App\Invoice\Entity\CustomField|null $custom_field
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var array $custom_values
 * @var array $custom_values_types
 * @var string $csrf
 * @var string $custom_field_id
 * @var string $title
 */
?>

<form method="post">

    <input type="hidden" name="_csrf" value="<?php echo $csrf; ?>">

    <div id="headerbar">
        <h1 class="headerbar-title"><?php echo $translator->translate('custom.values'); ?></h1>

        <div class="headerbar-item pull-right">
            <div class="btn-group btn-group-sm">
                <a class="btn btn-default" href="<?php echo $urlGenerator->generate('customfield/index'); ?>">
                    <i class="fa fa-arrow-left"></i> <?php echo $translator->translate('back'); ?>
                </a>
                <a class="btn btn-primary" href="<?php echo $urlGenerator->generate('customvalue/new', ['id' => $custom_field_id]); ?>">
                    <i class="fa fa-plus"></i> <?php echo $translator->translate('new'); ?>
                </a>
            </div>
        </div>
    </div>

    <div id="content">
        <?php if (null !== $custom_field) { ?>
        <?php echo Html::openTag('div', ['class' => 'row']); ?>
            <div class="col-xs-12 col-md-6 col-md-offset-3">

                <div class="form-group">
                    <label for="label"><?php echo $translator->translate('field'); ?>: </label>
                    <input type="text" name="label" id="label" class="form-control"
                           value="<?php echo Html::encode(strlen($customFieldLabel = ($custom_field->getLabel() ?? '')) > 0
                                                        ? $customFieldLabel
                                                        : ''); ?>" disabled="disabled">
                </div>

                <div class="form-group">
                    <label for="types"><?php echo $translator->translate('type'); ?>: </label>
                    <select name="types" id="types" class="form-control"
                            disabled="disabled">
                        <?php
                            /**
                             * @var string $type
                             */
                            foreach ($custom_values_types as $type) { ?>
                            <?php echo $alpha = str_replace('-', '_', strtolower($type)); ?>
                            <option value="<?php echo $type; ?>" <?php $s->check_select($custom_field->getType(), $type); ?>>
                                <?php echo $translator->translate($alpha.''); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th><?php echo $translator->translate('id'); ?></th>
                            <th><?php echo $translator->translate('label'); ?></th>
                            <th><?php echo $translator->translate('options'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            /**
                             * @var App\Invoice\Entity\CustomValue $custom_value
                             */
                            foreach ($custom_values as $custom_value) { ?>
                            <tr>
                                <td><?php echo $custom_value->getId(); ?></td>
                                <td><?php echo Html::encode($custom_value->getValue()); ?></td>
                                <td>
                                    <div class="options btn-group">
                                        <a class="btn btn-default btn-sm dropdown-toggle" data-bs-toggle="dropdown"
                                           href="#">
                                            <i class="fa fa-cog"></i> <?php echo $translator->translate('options'); ?>
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a href="<?php echo $urlGenerator->generate('customvalue/edit', ['id' => $custom_value->getId()]); ?>" style="text-decoration:none">
                                                    <i class="fa fa-edit fa-margin"></i> <?php echo $translator->translate('edit'); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="<?php echo $urlGenerator->generate('customvalue/delete', ['id' => $custom_value->getId()]); ?>" style="text-decoration:none" onclick="return confirm('<?php echo $translator->translate('delete.record.warning'); ?>');">
                                                    <i class="fa fa-trash fa-margin"></i><?php echo $translator->translate('delete'); ?>                                    
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>

                    </table>
                </div>

            </div>
        </div>
        <?php } ?>

    </div>

</form>
