<?php

declare(strict_types=1);

namespace App\Controller;

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

    public function team(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('team');
    }

    public function pricing(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('pricing');
    }

    public function testimonial(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('testimonial');
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
