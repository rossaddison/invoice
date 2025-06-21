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
    public static function buttons(CurrentRoute $currentRoute, sR $sR, Translator $translator, UrlGenerator $urlGenerator, string $origin): string
    {
        $defaultListLimit = $sR->getSetting('default_list_limit');
        $setting = $sR->withKey('default_list_limit');
        $setting_id = '';
        $buttons = '';
        // The user can click on the first button showing list limit and it will redirect to the actual setting for the list limit
        // under the general tab
        $adjustListLimitButton = A::tag()
        ->addAttributes([
            'data-bs-toggle' => 'tooltip',
            'title' => $translator->translate('default.list.limit'),
        ])
        ->addClass('btn btn-success me-1')
        ->content($defaultListLimit)
        ->href(
            $urlGenerator->generate('setting/tab_index', ['_language' => 'en'], ['active' => 'general']) . '#settings[default_list_limit]',
        )
        ->id('btn-submit-' . $defaultListLimit)
        ->render();
        if (null !== $setting) {
            $setting_id = $setting->getSetting_id();
            $limits_array = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 50, 75, 100, 150, 200, 250, 300];
            foreach ($limits_array as $value) {
                $buttons .= A::tag()
                ->addAttributes(['type' => 'submit'])
                ->addClass('btn btn-danger me-1')
                ->content((string)$value)
                ->href(
                    $urlGenerator->generate(
                        'setting/listlimit',
                        [
                            '_language' => $currentRoute->getArgument('_language'),
                            'setting_id' => $setting_id, 'limit' => $value, 'origin' => $origin,
                        ]
                    )
                )
                ->id('btn-submit-' . (string)$value)
                ->render();
            }
        }
        return $adjustListLimitButton . $buttons;
    }

    /**
     * @param UserInv $userinv
     * @param UrlGenerator $urlGenerator
     * @param string $origin
     * @param int $listLimit
     * @return string
     */
    public static function buttonsGuest(
        UserInv $userinv,
        UrlGenerator $urlGenerator,
        Translator $translator,
        string $origin,
        int $listLimit
    ): string {
        $buttons = '';
        $userinv_id = $userinv->getId();
        $limits_array = [$listLimit, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 50, 75, 100, 150, 200, 250, 300];
        foreach ($limits_array as $value) {
            $buttons .= A::tag()
            ->addAttributes(['type' => 'submit', 'data-bs-toggle' => 'tooltip', 'title' => $translator->translate('user.inv.refer.to')])
            ->addClass($value == $listLimit ? 'btn btn-success me-1' : 'btn btn-danger me-1')
            ->content((string)$value)
            ->href(
                $urlGenerator->generate(
                    'userinv/guestlimit',
                    [
                        'userinv_id' => $userinv_id, 'limit' => $value, 'origin' => $origin,
                    ]
                )
            )
            ->id('btn-submit-' . (string)$value)
            ->render();
        }
        return $buttons;
    }
}
