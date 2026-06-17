<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class SiteAuthController
{
    public function __construct(private WebViewRenderer $webViewRenderer)
    {
        $this->webViewRenderer = $webViewRenderer->withControllerName('site');
    }

    public function forgotalert(): Response
    {
        return $this->webViewRenderer->render('forgotalert');
    }

    public function forgotemailfailed(): Response
    {
        return $this->webViewRenderer->render('forgotemailfailed');
    }

    public function forgotusernotfound(): Response
    {
        return $this->webViewRenderer->render('forgotusernotfound');
    }

    public function onetimepassworderror(): Response
    {
        return $this->webViewRenderer->render('onetimepassworderror');
    }

    public function onetimepasswordfailure(): Response
    {
        return $this->webViewRenderer->render('onetimepasswordfailure');
    }

    public function onetimepasswordsuccess(): Response
    {
        return $this->webViewRenderer->render('onetimepasswordsuccess');
    }

    public function resetpasswordfailed(): Response
    {
        return $this->webViewRenderer->render('resetpasswordfailed');
    }

    public function resetpasswordsuccess(): Response
    {
        return $this->webViewRenderer->render('resetpasswordsuccess');
    }

    public function signupfailed(): Response
    {
        return $this->webViewRenderer->render('signupfailed');
    }

    public function signupsuccess(): Response
    {
        return $this->webViewRenderer->render('signupsuccess');
    }
}
