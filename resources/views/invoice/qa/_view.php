<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Qa\QaForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button  
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $qa         
 */

echo Html::openTag('h1');
 echo Html::encode($title);
echo Html::closeTag('h1');
echo Html::openTag('div', ['class'=>'container-fluid py-3']); 
 echo Html::openTag('div',
        ['class'=>'row justify-content-center']); //1
  echo Html::openTag('div',['class'=>'col-12 col-lg-10 col-xl-10']); //2
   echo Html::openTag('div',
           ['class'=>'card border border-dark shadow-2-strong rounded-3']); //3
    echo Html::openTag('div',['class'=>'card-header']); //4
     echo Html::openTag('h1',['class'=>'fw-normal h3 text-center']); //5
      echo $title;
     echo Html::closeTag('h1'); //5
     echo  new Form() //5
               ->post($urlGenerator->generate($actionName, $actionArguments))
               ->enctypeMultipartFormData()
               ->csrf($csrf)
               ->id('QaForm')
               ->open();
     echo $button::backSave();
      echo Html::openTag('div', ['class' => 'container']); //6
       echo Html::openTag('div', ['class' => 'row']); //7
        echo Html::openTag('div', ['class' => 'col card mb-3']); //8
         echo Html::openTag('div',['class' => 'card-header']); //9
          echo Html::openTag('h5');
           echo Html::encode($title);
          echo Html::closeTag('h5');
         echo Html::closeTag('div'); //9
         echo Html::openTag('div'); //9
          echo Field::text($form,'question')
               ->label($translator->translate('faq.question'))
               ->addInputAttributes([
                   'class' => 'form-control'
               ])
               ->value(Html::encode($form->getQuestion()))
               ->disabled()   
               ->placeholder($translator->translate('question'));
         echo Html::closeTag('div'); //9
         echo Html::openTag('div'); //9
          echo Field::text($form,'answer')
               ->label($translator->translate('faq.answer'))
               ->addInputAttributes([
                   'class' => 'form-control'
               ])
               ->value(Html::encode($form->getAnswer()))
               ->disabled()   
               ->placeholder($translator->translate('faq.answer'));
         echo Html::closeTag('div'); //9
         echo Html::openTag('div'); //9
          echo Field::text($form,'sort_order')
               ->label($translator->translate('faq.sort.order'))
               ->addInputAttributes([
                   'class' => 'form-control'
               ])
               ->value(Html::encode($form->getSortOrder()))
               ->disabled()   
               ->placeholder($translator->translate('faq.sort.order'));
         echo Html::closeTag('div'); //9
         echo Html::openTag('div'); //9
          echo Field::text($form,'active')
               ->label($translator->translate('faq.active'))
               ->addInputAttributes([
                   'class' => 'form-control'
               ])
               ->value(Html::encode($form->getActive()))
               ->disabled()  
               ->placeholder($translator->translate('active'));
         echo Html::closeTag('div');  //9
         echo Html::closeTag('form');//9 
        echo Html::closeTag('div'); //8
       echo Html::closeTag('div'); //7
      echo Html::closeTag('div'); //6
     echo Html::closeTag('div'); //5
    echo Html::closeTag('div'); //4
   echo Html::closeTag('div'); //3
  echo Html::closeTag('div'); //2
echo Html::closeTag('div');  //1 
?>
