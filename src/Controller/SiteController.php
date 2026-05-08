<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class SiteController
{
    public function __construct(private WebViewRenderer $webViewRenderer)
    {
        $this->webViewRenderer = $webViewRenderer->withController($this);
    }

    public function index(): Response
    {
        return $this->webViewRenderer->render('index');
    }

    public function about(): Response
    {
        return $this->webViewRenderer->render('about');
    }

    public function accreditations(): Response
    {
        return $this->webViewRenderer->render('accreditations');
    }

    public function gallery(): Response
    {
        return $this->webViewRenderer->render('gallery');
    }

    public function team(): Response
    {
        return $this->webViewRenderer->render('team');
    }

    public function pricing(): Response
    {
        return $this->webViewRenderer->render('pricing');
    }

    public function privacypolicy(): Response
    {
        return $this->webViewRenderer->render('privacypolicy');
    }

    public function termsofservice(): Response
    {
        return $this->webViewRenderer->render('termsofservice');
    }

    public function testimonial(): Response
    {
        return $this->webViewRenderer->render('testimonial');
    }

    public function oauth2autherror(#[RouteArgument('message')] string $message): Response
    {
        return $this->webViewRenderer->render('oauth2autherror', ['message' => $message]);
    }

    public function oauth2callbackresultunauthorised(): Response
    {
        return $this->webViewRenderer->render('oauth2callbackresultunauthorised');
    }

    public function usercancelledoauth2(): Response
    {
        return $this->webViewRenderer->render('usercancelledoauth2');
    }

    public function adminmustmakeactive(): Response
    {
        return $this->webViewRenderer->render('adminmustmakeactive');
    }

    public function contact(): Response
    {
        return $this->webViewRenderer->render('contact');
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
