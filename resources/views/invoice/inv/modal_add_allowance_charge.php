<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Modal;

/**
 * @see id="add-inv-allowance-charge" triggered by <a href="#add-inv-allowance-charge" data-bs-toggle="modal"  style="text-decoration:none"> on views/inv/view.php 
 * @see InvController/save_inv_allowance_charge
 * @see echo $modal_add_allowance_charge; at BOTTOM resources/views/invoice/inv/view.php
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $modal_add_allowance_charge_form
 * @var string $type     
 */

echo  Modal::widget() 
    // display the show button with true at the bottom of inv/view.php       
    ->withToggle(false)        
    ->options([
        'id' => 'add-inv-allowance-charge',
        'aria-labelledby' => 'modal_add_inv_allowance_charge',
        'aria-hidden' => 'true'
    ])
    ->bodyOptions([
        'class' => 'modal-body', 
        'style' => 'text-align:center;'
    ])  
    ->footerOptions([
        'class' => 'text-dark'
    ])
    /**
     * @link https://getbootstrap.com/docs/5.1/components/modal/#optional-sizes
     */        
    ->size(Modal::SIZE_LARGE)
    /**
     * @link https://getbootstrap.com/docs/5.1/components/modal/#static-backdrop
     */        
    ->staticBackdrop(true)
    /**
     * @link https://getbootstrap.com/docs/5.1/components/modal/#scrolling-long-content
     */        
    ->scrollable(true)
    /**
     * @link https://getbootstrap.com/docs/5.1/components/modal/#vertically-centered
     */        
    ->centered(true)
    /**
     * @link https://getbootstrap.com/docs/5.1/components/modal/#remove-animation
     */        
    ->fade(false)
    /**
     * @link https://getbootstrap.com/docs/5.1/components/modal/#fullscreen-modal
     */        
    ->fullscreen('modal-fullscreen-lg-down')
    ->begin();
    echo $modal_add_allowance_charge_form;
    echo Modal::end();
    echo Html::br(); 
   