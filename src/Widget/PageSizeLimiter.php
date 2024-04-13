<?php
declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Entity\UserInv;
use App\Invoice\Setting\SettingRepository as sR;
use Yiisoft\Html\Tag\A;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Translator\TranslatorInterface as Translator;

final class PageSizeLimiter
{
    public static function Buttons(CurrentRoute $currentRoute, sR $sR, UrlGenerator $urlGenerator, string $origin) : string {
        $defaultListLimit = $sR->get_setting('default_list_limit');
        $setting = $sR->withKey('default_list_limit');
        $setting_id = '';
        $buttons = '';
        if (null!==$setting) {
            $setting_id = $setting->getSetting_id();
            $limits_array = [(int)$defaultListLimit, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 50, 75, 100, 150, 200, 250, 300];
            foreach ($limits_array as $value) {
                $buttons .= A::tag()
                ->addAttributes(['type' => 'submit'])
                ->addClass($value == $defaultListLimit ? 'btn btn-success me-1' : 'btn btn-danger me-1')
                ->content((string)$value)
                ->href($urlGenerator->generate('setting/listlimit',
                    [
                        '_language' => $currentRoute->getArgument('_language'), 
                        'setting_id' => $setting_id, 'limit' => $value, 'origin' => $origin
                    ])                    
                )
                ->id('btn-submit-'.(string)$value)
                ->render();
            }
        }    
        return $buttons;
    } 
    
    /**
     * 
     * @param UserInv $userinv
     * @param UrlGenerator $urlGenerator
     * @param string $origin
     * @param int $listLimit
     * @return string
     */
    public static function buttonsGuest(
            UserInv $userinv,
            UrlGenerator $urlGenerator, Translator $translator, string $origin, int $listLimit) : string 
    {
        $buttons = '';
        $userinv_id = $userinv->getId();
        $limits_array = [$listLimit, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 50, 75, 100, 150, 200, 250, 300];
        foreach ($limits_array as $value) {
            $buttons .= A::tag()
            ->addAttributes(['type' => 'submit', 'data-bs-toggle' => 'tooltip', 'title' => $translator->translate('invoice.user.inv.refer.to')])
            ->addClass($value == $listLimit ? 'btn btn-success me-1' : 'btn btn-danger me-1')
            ->content((string)$value)
            ->href($urlGenerator->generate('userinv/guestlimit',
                [
                    'userinv_id' => $userinv_id, 'limit' => $value, 'origin' => $origin
                ])                    
            )
            ->id('btn-submit-'.(string)$value)
            ->render();
        }
        return $buttons;
    }   
}