<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\FormModel\Field;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 */
?>
<?= Html::openTag('form', ['method' => 'post']); ?>

    <?= Html::Tag('input','',['type' => 'hidden', 'name' => '_csrf', 'value' => $csrf]); ?>
    
    <?= Html::openTag('div',['id' => 'headerbar']); ?>
        <?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
        <?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
        <?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
        <?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
        <?= Html::openTag('div',['class'=>'card-header']); ?>

        <?= Html::openTag('h1',['class' => 'headerbar-title']); ?>
            <?= $s->trans('custom_values'); ?>
        <?= Html::closeTag('h1'); ?>

        <?= Html::openTag('div',['class' => 'headerbar-item pull-right']); ?>
            <?= Html::openTag('div',['class' => 'btn-group btn-group-sm']); ?>
                <?= Html::openTag('a',[
                        'class' => 'btn btn-default', 
                        'href' => $urlGenerator->generate('customfield/index')]); ?>
                        <?= Html::openTag('i', ['class' => 'fa fa-arrow-left']);?>
                        <?= Html::closeTag('i'); ?><?= $s->trans('back'); ?>    
                <?= Html::closeTag('a'); ?>
                <?= Html::openTag('a',[
                        'class' => 'btn btn-primary', 
                        'href' => $urlGenerator->generate('customvalue/new',['id'=>$custom_field->getId()])]); ?>
                        <?= Html::openTag('i', ['class' => 'fa fa-plus']);?>
                        <?= Html::closeTag('i'); ?><?= $s->trans('new'); ?>    
                <?= Html::closeTag('a'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>

    

        <?= Html::openTag('div',['id' => 'content']); ?>
            <?= Html::openTag('div',['class' => 'row']); ?>
                <?= Html::openTag('div',['class' => 'col-xs-12 col-md-6 col-md-offset-3']); ?>
                    <?= Html::openTag('div',['class' => 'form-group']); ?>
                        <?= Field::text($field_form, 'label')
                            ->label($s->trans('field'))    
                            ->addInputAttributes([
                                'class' => 'form-control',
                                'disabled' => 'disabled',
                                'id' => 'label'])
                            ->value(Html::encode($field_form->getLabel())); 
                        ?>
                    <?= Html::closeTag('div'); ?>

                    <?php
                        $optionsDataType = [];
                        foreach ($custom_values_types as $type) {
                            $alpha = str_replace('-', '_', strtolower($type));
                            $optionsDataType[$type] = $s->trans($alpha);
                        }
                    ?>
                    <?= Html::openTag('div',['class' => 'form-group']); ?>    
                        <?=
                            Field::select($field_form, 'type')
                            ->label($s->trans('type'),['control-label'])
                            ->addInputAttributes([
                                'class' => 'form-control',
                                'id' => 'type',
                                'disabled' => 'disabled'
                            ])    
                            ->optionsData($optionsDataType);
                        ?>    
                    <?= Html::closeTag('div'); ?>    

                    <?= Html::openTag('div', ['class' => 'form-group']); ?>
                        <?= Html::openTag('table', ['class' => 'table table-bordered']); ?>
                            <?= Html::openTag('thead'); ?>
                            <?= Html::openTag('tr'); ?>
                                <?= Html::openTag('th'); ?><?= $s->trans('id'); ?><?= Html::closeTag('th'); ?>
                                <?= Html::openTag('th'); ?><?= $s->trans('label'); ?><?= Html::closeTag('th'); ?>
                                <?= Html::openTag('th'); ?><?= $s->trans('options'); ?><?= Html::closeTag('th'); ?>
                            <?= Html::closeTag('tr'); ?> 
                            <?= Html::closeTag('thead'); ?>

                            <?= Html::openTag('tbody'); ?>
                            <?php foreach ($custom_values as $custom_value) { ?>
                                <?= Html::openTag('tr'); ?>
                                    <?= Html::openTag('td'); ?><?= $custom_value->getId(); ?><?= Html::closeTag('td'); ?>
                                    <?= Html::openTag('td'); ?><?= Html::encode($custom_value->getvalue()); ?><?= Html::closeTag('td'); ?>
                                    <?= Html::openTag('td'); ?>
                                        <?= Html::openTag('div', ['class' => 'options btn-group']); ?>
                                            <?= Html::openTag('a', [
                                                'class'=> 'btn btn-default btn-sm dropdown-toggle', 
                                                'data-toggle' => 'dropdown',
                                                'href' => '#']); ?>
                                                <i class="fa fa-cog"></i> <?= $s->trans('options'); ?>
                                            <?= Html::closeTag('a'); ?>
                                            <?= Html::openTag('ul', ['class' => 'dropdown-menu']); ?>
                                                <?= Html::openTag('li'); ?>
                                                    <?= Html::openTag('a', [
                                                                                'href' => $urlGenerator->generate('customvalue/edit',['id'=>$custom_value->getId()]),
                                                                                'style' => 'text-decoration:none',
                                                                                'class' => 'btn'
                                                                            ]
                                                                    ); ?>
                                                        <?= Html::openTag('p', ['style' =>'font-size:10px']); ?>            
                                                            <i class="fa fa-edit fa-margin"></i><?= $s->trans('edit'); ?>
                                                        <?= Html::closeTag('p'); ?>
                                                    <?= Html::closeTag('a'); ?>
                                                <?= Html::closeTag('li'); ?>
                                                <?= Html::openTag('li'); ?>
                                                    <?= Html::openTag('a', [
                                                                                'href' => $urlGenerator->generate('customvalue/delete',['id'=>$custom_value->getId()]),
                                                                                'style' => 'text-decoration:none',
                                                                                'class' => 'btn',
                                                                                'onclick' => 'return confirm('."'".$s->trans('delete_record_warning')."')"
                                                                            ]
                                                                    ); ?>
                                                        <?= Html::openTag('p', ['style' =>'font-size:10px']); ?>            
                                                            <i class="fa fa-trash fa-margin"></i><?= $s->trans('delete'); ?>
                                                        <?= Html::closeTag('p'); ?>
                                                    <?= Html::closeTag('a'); ?>
                                                    <?= Html::closeTag('form'); ?>
                                                <?= Html::closeTag('li'); ?>
                                            <?= Html::closeTag('ul'); ?>
                                        <?= Html::closeTag('div'); ?>
                                    <?= Html::closeTag('td'); ?>
                                <?= Html::closeTag('tr'); ?>
                            <?php } ?>
                            <?= Html::closeTag('tbody'); ?>

                        <?= Html::closeTag('table'); ?>
                    <?= Html::closeTag('div'); ?>

                <?= Html::closeTag('div'); ?>

            <?= Html::closeTag('div'); ?>

        <?= Html::closeTag('div'); ?>
    
    <?= Html::closeTag('div'); ?>                                                        
    
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>                                                        
<?= Html::closeTag('form'); ?>
