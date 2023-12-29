<?php
declare(strict_types=1);

namespace App\Widget;
    
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface as Translator;

final class Button
{
    public static function back(Translator $translator) : void {
        echo Html::openTag('div', ['class' => 'headerbar-item pull-right']);
        $buttonsDataArray = [
            [
                $translator->translate('i.back'), 
                'type' => 'reset', 
                'onclick' => 'window.history.back()',
                'class' => 'btn btn-danger',
                'id' => 'btn-cancel',
                'name' => 'btn_cancel',
                'value' => '1'
            ],
        ];
        echo Field::buttongroup()
            ->buttonsData($buttonsDataArray);
        echo Html::closeTag('div'); 
    }
    
    public static function back_save(Translator $translator) : void {
        echo Html::openTag('div', ['class' => 'headerbar-item pull-right']);
        $buttonsDataArray = [
            [
                $translator->translate('i.back'), 
                'type' => 'reset', 
                'onclick' => 'window.history.back()',
                'class' => 'btn btn-danger',
                'id' => 'btn-cancel',
                'name' => 'btn_cancel',
                'value' => '1'
            ],
            [
            $translator->translate('i.save'), 
            'type' => 'submit', 
            'class' => 'btn btn-success',
            'id' => 'btn-submit',
            'name' => 'btn_submit',
            'value' => '1'
        ],
        ];
        echo Field::buttongroup()
            ->buttonsData($buttonsDataArray);
        echo Html::closeTag('div'); 
    }    
}