<?php

declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Inv\InvRepository as iR;
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Span;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Translator\TranslatorInterface as Translator;

final class Button
{
    private CurrentRoute $currentRoute;
    private Translator $translator;
    private UrlGenerator $generator;
    
    public function __construct(CurrentRoute $currentRoute, Translator $translator, UrlGenerator $generator) {
        $this->currentRoute = $currentRoute;
        $this->translator = $translator;
        $this->generator = $generator;
    }
    
    public static function back() : string {
        $string = Html::openTag('div', ['class' => 'headerbar-item pull-right']);
        $buttonsDataArray = [
            [
                //$translator->translate('i.back'),
                '',
                'type' => 'reset', 
                'onclick' => 'window.history.back()',
                'class' => 'btn btn-danger bi bi-arrow-left',
                'id' => 'btn-cancel',
                'name' => 'btn_cancel',
                'value' => '1'
            ],
        ];
        $string.= Field::buttongroup()
            ->buttonsData($buttonsDataArray);
        return $string.= Html::closeTag('div'); 
    }
    
    public static function back_save() : string {
        $string = Html::openTag('div', ['class' => 'headerbar-item pull-right']);
        $buttonsDataArray = [
            [
                //$translator->translate('i.back'),
                '',
                'type' => 'reset', 
                'onclick' => 'window.history.back()',
                'class' => 'btn btn-danger bi bi-arrow-left',
                'id' => 'btn-cancel',
                'name' => 'btn_cancel',
                'value' => 'main'
            ],
            [
                //$translator->translate('i.save'),
                '',
                'type' => 'submit', 
                'class' => 'btn btn-success bi bi-save pull-right',
                'id' => 'btn-submit',
                'name' => 'btn_submit',
                'value' => '1'
        ],
        ];
        $string.= Field::buttongroup()
            ->buttonsData($buttonsDataArray);
        return $string.= Html::closeTag('div'); 
    }
    
    public static function save() : void {
        echo Html::openTag('div', ['class' => 'headerbar-item pull-right']);
        $buttonsDataArray = [
           [
                //$translator->translate('i.save'),
                '',
                'type' => 'submit', 
                'class' => 'btn btn-success bi bi-save pull-right',
                'id' => 'btn-submit',
                'name' => 'btn_submit',
                'value' => '1'
            ],
        ];
        echo Field::buttongroup()
            ->buttonsData($buttonsDataArray);
        echo Html::closeTag('div'); 
    }
    
    public static function activeLabel(Translator $translator) : string {
        return Span::tag()
                ->addClass('label active')
                ->content(Html::encode($translator->translate('i.yes')))
                ->render();
    }
    
    public static function inactiveLabel(Translator $translator) : string {
        return Span::tag()
                ->addClass('label inactive')
                ->content(Html::encode($translator->translate('i.no')))
                ->render();
    }
    
    public static function inactiveWithAddUserAccount(UrlGenerator $generator, Translator $translator) : string {
        return Span::tag()
                ->content(
                    Html::a('', $generator->generate('userinv/add'),
                        [
                            'class' => 'fa fa-plus',
                            'style' => 'text-decoration:none',
                            'tooltip' => 'data-bs-toggle',
                            'title' => $translator->translate('invoice.client.has.not.user.account') 
                        ]
                    )
                )
                ->render();
    } 
    
    public static function ascDesc(UrlGenerator $generator, string $field, string $class, string $translated, bool $guest = false) : string {
        return A::tag()
        ->addClass('btn btn-'.$class)
        ->content('⬆️')
        ->href($generator->generate('inv/'. ($guest ? 'guest' : 'index'), [], ['sort' => $field]))
        ->id('btn-'. $field. '-asc')
        ->render().' '.$translated.' '.A::tag()
        ->addClass('btn btn-'.$class)
        ->content('⬇')
        ->href($generator->generate('inv/'. ($guest ? 'guest' : 'index'), [], ['sort' => '-'.$field]))
        ->id('btn-'. $field. '-desc')
        ->render();
    }
    
    public static function statusMark(UrlGenerator $generator, iR $iR,
                                      int $status,
                                      string $translated, 
                                      bool $guest = false
                                      ) : string {
        return A::tag()
        ->addClass('btn btn-'.$iR->getSpecificStatusArrayClass($status))
        ->content($iR->getSpecificStatusArrayEmoji($status).' '.$iR->getSpecificStatusArrayLabel((string)$status))
        ->href($generator->generate('inv/'. ($guest ? 'guestmark' : 'indexmark'), ['status' => $status]))
        ->id('btn-'. $iR->getSpecificStatusArrayClass($status))
        ->render().' '.$translated.' ';
    }
}