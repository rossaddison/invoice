<?php
declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Html\Html;

final class LabelSwitch
{
    public static function checkbox(
        string $checkbox_name,
        string $checkbox_value_zero_or_one,
        string $checkbox_label_text_zero,
        string $checkbox_label_text_one,
        string $checkbox_label_id,
        string $fontsize    
    ) : void
    {
       echo Html::openTag('div',['class'=>'form-check form-switch']);
       echo Html::checkbox($checkbox_name, $checkbox_value_zero_or_one,['class'=>'form-check-input','checked'=>'checked', 'disabled' => 'disabled', 'style'=>'font-size:'.$fontsize.'px;'])->render();     
       echo ($checkbox_value_zero_or_one === '0' 
                    ? Html::label($checkbox_label_text_zero, $checkbox_label_id)
                          ->attribute('class','form-check-label btn btn-outline-success disabled')
                          ->attribute('style','background-color:lightgreen;font-size:'.$fontsize.'px;')
                          ->render()
                    : Html::label($checkbox_label_text_one, $checkbox_label_id)
                          ->attribute('class','form-check-label btn btn-outline-primary disabled')
                          ->attribute('style','background-color:lightblue;font-size:'.$fontsize.'px;')
                          ->render());
       echo Html::closeTag('div');
    }
}
