<?php

declare(strict_types=1);

namespace App\Controller;

use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class SiteController
{
    public function __construct(private ViewRenderer $viewRenderer)
    {
        $this->viewRenderer = $viewRenderer->withController($this);
    }

    public function index(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('index');
    }

    public function about(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('about');
    }

    public function accreditations(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('accreditations');
    }

    public function gallery(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('gallery');
    }

    public function team(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('team');
    }

    public function pricing(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('pricing');
    }

    public function privacypolicy(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('privacypolicy');
    }

    public function termsofservice(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('termsofservice');
    }

    public function testimonial(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('testimonial');
    }
    
    public function oauth2autherror(#[RouteArgument('message')] string $message): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('oauth2autherror', ['message' => $message]);
    }    

    public function oauth2callbackresultunauthorised(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('oauth2callbackresultunauthorised');
    }

    public function usercancelledoauth2(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('usercancelledoauth2');
    }

    public function adminmustmakeactive(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('adminmustmakeactive');
    }

    public function contact(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('contact');
    }

    public function forgotalert(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('forgotalert');
    }

    public function forgotemailfailed(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('forgotemailfailed');
    }

    public function forgotusernotfound(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('forgotusernotfound');
    }

    public function onetimepassworderror(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('onetimepassworderror');
    }

    public function onetimepasswordfailure(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('onetimepasswordfailure');
    }

    public function onetimepasswordsuccess(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('onetimepasswordsuccess');
    }

    public function resetpasswordfailed(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('resetpasswordfailed');
    }

    public function resetpasswordsuccess(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('resetpasswordsuccess');
    }

    public function signupfailed(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('signupfailed');
    }

    public function signupsuccess(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('signupsuccess');
    }
}
