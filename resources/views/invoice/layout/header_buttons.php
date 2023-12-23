<?php
    declare(strict_types=1);
    
    use Yiisoft\FormModel\Field;
    use Yiisoft\Html\Html;
?>
<?= Html::openTag('div', ['class' => 'headerbar-item pull-right']); ?>
    <?= Html::openTag('div', ['class' => 'headerbar-item pull-right']); ?>
    <?php $buttonsDataArray = [
        [
            $s->trans('back'), 
            'type' => 'reset', 
            'onclick' => 'window.history.back()',
            'class' => 'btn btn-danger',
            'id' => 'btn-cancel',
            'name' => 'btn_cancel',
            'value' => '1'
        ],
        [
            $s->trans('save'), 
            'type' => 'submit', 
            'class' => 'btn btn-success',
            'id' => 'btn-submit',
            'name' => 'btn_submit',
            'value' => '1'
        ],
    ]
    ?>
    <?=   
        Field::buttongroup()
        ->buttonsData($buttonsDataArray);
    ?>
<?= Html::closeTag('div'); ?>
