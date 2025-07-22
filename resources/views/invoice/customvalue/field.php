<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;

/*
 * @var App\Invoice\CustomField\CustomFieldForm $field_form
 * @var App\Invoice\Entity\CustomField $custom_field
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var array $custom_values
 * @var array $custom_values_types
 * @var string $csrf
 */
?>
<?php echo Html::openTag('form', ['method' => 'post']); ?>

    <?php echo Html::Tag('input', '', ['type' => 'hidden', 'name' => '_csrf', 'value' => $csrf]); ?>
    
    <?php echo Html::openTag('div', ['id' => 'headerbar']); ?>
        <?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
        <?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
        <?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
        <?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
        <?php echo Html::openTag('div', ['class' => 'card-header']); ?>

        <?php echo Html::openTag('h1', ['class' => 'headerbar-title']); ?>
            <?php echo $translator->translate('custom.values'); ?>
        <?php echo Html::closeTag('h1'); ?>

        <?php echo Html::openTag('div', ['class' => 'headerbar-item pull-right']); ?>
            <?php echo Html::openTag('div', ['class' => 'btn-group btn-group-sm']); ?>
                <?php echo Html::openTag('a', [
                    'class' => 'btn btn-default',
                    'href'  => $urlGenerator->generate('customfield/index')]); ?>
                        <?php echo Html::openTag('i', ['class' => 'fa fa-arrow-left']); ?>
                        <?php echo Html::closeTag('i'); ?><?php echo $translator->translate('back'); ?>    
                <?php echo Html::closeTag('a'); ?>
                <?php echo Html::openTag('a', [
                    'class' => 'btn btn-primary',
                    'href'  => $urlGenerator->generate('customvalue/new', ['id' => $custom_field->getId()])]); ?>
                        <?php echo Html::openTag('i', ['class' => 'fa fa-plus']); ?>
                        <?php echo Html::closeTag('i'); ?><?php echo $translator->translate('new'); ?>    
                <?php echo Html::closeTag('a'); ?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>

    

        <?php echo Html::openTag('div', ['id' => 'content']); ?>
            <?php echo Html::openTag('div', ['class' => 'row']); ?>
                <?php echo Html::openTag('div', ['class' => 'col-xs-12 col-md-6 col-md-offset-3']); ?>
                    <?php echo Html::openTag('div', ['class' => 'form-group']); ?>
                        <?php echo Field::text($field_form, 'label')
            ->label($translator->translate('field'))
            ->addInputAttributes([
                'class'    => 'form-control',
                'disabled' => 'disabled',
                'id'       => 'label'])
            ->value(Html::encode($field_form->getLabel()));
?>
                    <?php echo Html::closeTag('div'); ?>

                    <?php
$optionsDataType = [];
/**
 * @var string $type
 */
foreach ($custom_values_types as $type) {
    $alpha                  = str_replace('-', '_', strtolower($type));
    $optionsDataType[$type] = $translator->translate(''.$alpha.'');
}
?>
                    <?php echo Html::openTag('div', ['class' => 'form-group']); ?>    
                        <?php echo Field::select($field_form, 'type')
                            ->label($translator->translate('type'))
                            ->addInputAttributes([
                                'class'    => 'form-control',
                                'id'       => 'type',
                                'disabled' => 'disabled',
                            ])
                            ->optionsData($optionsDataType);
?>    
                    <?php echo Html::closeTag('div'); ?>    

                    <?php echo Html::openTag('div', ['class' => 'form-group']); ?>
                        <?php echo Html::openTag('table', ['class' => 'table table-bordered']); ?>
                            <?php echo Html::openTag('thead'); ?>
                            <?php echo Html::openTag('tr'); ?>
                                <?php echo Html::openTag('th'); ?><?php echo $translator->translate('id'); ?><?php echo Html::closeTag('th'); ?>
                                <?php echo Html::openTag('th'); ?><?php echo $translator->translate('label'); ?><?php echo Html::closeTag('th'); ?>
                                <?php echo Html::openTag('th'); ?><?php echo $translator->translate('options'); ?><?php echo Html::closeTag('th'); ?>
                            <?php echo Html::closeTag('tr'); ?> 
                            <?php echo Html::closeTag('thead'); ?>

                            <?php echo Html::openTag('tbody'); ?>
                            <?php
        /**
         * @var App\Invoice\Entity\CustomValue $custom_value
         */
        foreach ($custom_values as $custom_value) { ?>
                                <?php echo Html::openTag('tr'); ?>
                                    <?php echo Html::openTag('td'); ?><?php echo $custom_value->getId(); ?><?php echo Html::closeTag('td'); ?>
                                    <?php echo Html::openTag('td'); ?><?php echo Html::encode($custom_value->getvalue()); ?><?php echo Html::closeTag('td'); ?>
                                    <?php echo Html::openTag('td'); ?>
                                        <?php echo Html::openTag('div', ['class' => 'options btn-group']); ?>
                                            <?php echo Html::openTag('a', [
                                                'class'       => 'btn btn-default btn-sm dropdown-toggle',
                                                'data-toggle' => 'dropdown',
                                                'href'        => '#']); ?>
                                                <i class="fa fa-cog"></i> <?php echo $translator->translate('options'); ?>
                                            <?php echo Html::closeTag('a'); ?>
                                            <?php echo Html::openTag('ul', ['class' => 'dropdown-menu']); ?>
                                                <?php echo Html::openTag('li'); ?>
                                                    <?php echo Html::openTag(
                                                        'a',
                                                        [
                                                            'href'  => $urlGenerator->generate('customvalue/view', ['id' => $custom_value->getId()]),
                                                            'style' => 'text-decoration:none',
                                                            'class' => 'btn',
                                                        ],
                                                    ); ?>
                                                        <?php echo Html::openTag('p', ['style' => 'font-size:10px']); ?>            
                                                            <i class="fa fa-eye fa-margin"></i><?php echo $translator->translate('view'); ?>
                                                        <?php echo Html::closeTag('p'); ?>
                                                    <?php echo Html::closeTag('a'); ?>
                                                <?php echo Html::closeTag('li'); ?>
                                                <?php echo Html::openTag('li'); ?>
                                                    <?php echo Html::openTag(
                                                        'a',
                                                        [
                                                            'href'  => $urlGenerator->generate('customvalue/edit', ['id' => $custom_value->getId()]),
                                                            'style' => 'text-decoration:none',
                                                            'class' => 'btn',
                                                        ],
                                                    ); ?>
                                                        <?php echo Html::openTag('p', ['style' => 'font-size:10px']); ?>            
                                                            <i class="fa fa-edit fa-margin"></i><?php echo $translator->translate('edit'); ?>
                                                        <?php echo Html::closeTag('p'); ?>
                                                    <?php echo Html::closeTag('a'); ?>
                                                <?php echo Html::closeTag('li'); ?>
                                                <?php echo Html::openTag('li'); ?>
                                                    <?php echo Html::openTag(
                                                        'a',
                                                        [
                                                            'href'    => $urlGenerator->generate('customvalue/delete', ['id' => $custom_value->getId()]),
                                                            'style'   => 'text-decoration:none',
                                                            'class'   => 'btn',
                                                            'onclick' => 'return confirm('."'".$translator->translate('delete.record.warning')."')",
                                                        ],
                                                    ); ?>
                                                        <?php echo Html::openTag('p', ['style' => 'font-size:10px']); ?>            
                                                            <i class="fa fa-trash fa-margin"></i><?php echo $translator->translate('delete'); ?>
                                                        <?php echo Html::closeTag('p'); ?>
                                                    <?php echo Html::closeTag('a'); ?>
                                                    <?php echo Html::closeTag('form'); ?>
                                                <?php echo Html::closeTag('li'); ?>
                                            <?php echo Html::closeTag('ul'); ?>
                                        <?php echo Html::closeTag('div'); ?>
                                    <?php echo Html::closeTag('td'); ?>
                                <?php echo Html::closeTag('tr'); ?>
                            <?php } ?>
                            <?php echo Html::closeTag('tbody'); ?>

                        <?php echo Html::closeTag('table'); ?>
                    <?php echo Html::closeTag('div'); ?>

                <?php echo Html::closeTag('div'); ?>

            <?php echo Html::closeTag('div'); ?>

        <?php echo Html::closeTag('div'); ?>
    
    <?php echo Html::closeTag('div'); ?>                                                        
    
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>                                                        
<?php echo Html::closeTag('form'); ?>
