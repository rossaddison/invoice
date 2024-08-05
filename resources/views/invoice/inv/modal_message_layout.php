<?php
declare(strict_types=1);

  use Yiisoft\Html\Html;
  use Yiisoft\Html\Tag\Button;
  use Yiisoft\Yii\Bootstrap5\Modal;
  
  /**
   * This is a layout which will hold the form passed to it
   * @see App\Widget\Bootstrap5ModalTranslatorMessageWithoutAction                                                                                                
   * @var Yiisoft\Translator\TranslatorInterface $translator
   * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
   * @var string $form
   * @var string $type 
   */
  
  echo  Modal::widget() 
    // display the show button with true at the bottom
    ->withToggle(false)
    ->withoutCloseButton()
    ->options([
        'id' => 'modal-message-'.$type,
        'aria-labelledby' => 'modal_message_'.$type,
        'aria-hidden' => 'true'
    ])
    ->bodyOptions([
        'class' => 'modal-body', 
        'style' => 'text-align:center;'
    ])  
    ->footerOptions([
        'class' => 'text-dark'
    ])    
    ->footer(Button::tag()
            ->addClass('btn btn-danger')
            ->content($translator->translate('i.close'))
            ->addAttributes(['data-dismiss' => 'modal'])
            ->render())            
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
    echo $form;
    echo Modal::end();    
    echo Html::br();