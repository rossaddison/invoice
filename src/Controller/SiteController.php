<?php

declare(strict_types=1);

namespace App\Controller;

use Yiisoft\Yii\View\ViewRenderer;

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
}
