<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Yii\Bootstrap5\Modal;
use Yiisoft\Yii\Bootstrap5\Utility\Responsive;
use Yiisoft\Yii\Bootstrap5\ModalDialogFullScreenSize;

    /**
     * @see The usage of the refactored Modal has been put on hold
     * @see App\Widget\Bootstrap5ModalInv $this->layoutParameters['form']
     * @var Yiisoft\Translator\TranslatorInterface $translator
     * @var string $form
     * @var string $type 
     */
     
    echo Modal::widget()     
    ->bodyAttributes(['style' => 'text-align:center;'])        
    ->body($form)
    ->fullscreen(ModalDialogFullScreenSize::FULLSCREEN_SM_DOWN)             
    ->id('modal-add-'.$type)
    ->responsive(Responsive::LG)         
    ->scrollable()           
    ->triggerButton()              
    ->footerAttributes(['class' => 'text-dark'])    
    ->footer(Button::tag()->addClass('btn btn-danger')->attribute('data-bs-dismiss', 'modal')->content($translator->translate('i.close')))
    ->title('Modal title')
    ->verticalCentered()
    ->render(); 
    
    /**
     * The inert attribute has to be used to avoid 'aria-hidden' => true related errors
     * The simplified modal_layout is not using 'aria-hidden'
     */
    $inert = '$(function () {'.
            "const modal = document.getElementById('modal-add-inv');".
            "modal.removeAttribute('inert');".
            "modal.setAttribute('inert', '');".        
    '});';

    echo Html::script($inert)->type('module'); 